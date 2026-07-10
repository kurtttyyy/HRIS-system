<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesTabSession;
use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\Education;
use App\Models\Resignation;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RegisterLoginController extends Controller
{
    use ResolvesTabSession;

    private const TAB_AUTH_MAP_ADMIN = 'tab_auth_users_admin';
    private const TAB_AUTH_MAP_EMPLOYEE = 'tab_auth_users_employee';

    public function register_store(Request $request){
        $attrs = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:255',
            'confirmation_password' => 'required|string|max:255',
        ]);

        if($attrs['password'] != $attrs['confirmation_password']){
            return redirect()->back()
                ->withErrors([
                    'confirmation_password' => 'Password confirmation does not match.',
                ])
                ->withInput($request->except(['password', 'confirmation_password']));
        }

        $hire = 'hired';
        $normalizedEmail = strtolower(trim((string) $attrs['email']));

        $matchingApplicant = Applicant::query()
            ->with('position')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->latest('id')
            ->first();

        if (!$matchingApplicant) {
            return redirect()->back()
                ->withErrors([
                    'email' => 'We could not find an application linked to this email address.',
                ])
                ->withInput($request->except(['password', 'confirmation_password']));
        }

        if (strtolower(trim((string) ($matchingApplicant->application_status ?? ''))) !== $hire) {
            return redirect()->back()
                ->withErrors([
                    'email' => 'Your account is not eligible for registration yet. You can create an account once your application status is marked as Hired by HR.',
                ])
                ->withInput($request->except(['password', 'confirmation_password']));
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->first();

        $rehireUser = $this->resolveRehireUser($matchingApplicant, $existingUser);
        $position = $matchingApplicant->position;
        $userDepartment = trim((string) ($position->department ?? ''));
        $userPosition = trim((string) ($position->title ?? $matchingApplicant->work_position ?? ''));

        if ($existingUser && !$rehireUser) {
            return redirect()->back()
                ->withErrors([
                    'email' => 'An account with this email already exists. Please log in instead.',
                ])
                ->withInput($request->except(['password', 'confirmation_password']));
        }

        if ($rehireUser) {
            $rehireUser->update([
                'first_name' => $attrs['first_name'],
                'last_name' => $attrs['last_name'],
                'middle_name' => $attrs['middle_name'],
                'role' => 'Employee',
                'status' => 'Approved',
                'account_status' => 'Active',
                'email' => $attrs['email'],
                'job_role' => $userPosition !== '' ? $userPosition : 'Employee',
                'position' => $userPosition !== '' ? $userPosition : 'Employee',
                'department' => $userDepartment !== '' ? $userDepartment : 'Unassigned',
                'password' => Hash::make($attrs['password']),
            ]);

            $user = $rehireUser->fresh();
        } else {
            $user = User::create([
                'first_name' => $attrs['first_name'],
                'last_name' => $attrs['last_name'],
                'middle_name' => $attrs['middle_name'],
                'role' => 'Employee',
                'status' => 'Approved',
                'account_status' => 'Active',
                'email' => $attrs['email'],
                'job_role' => $userPosition !== '' ? $userPosition : 'Employee',
                'position' => $userPosition !== '' ? $userPosition : 'Employee',
                'department' => $userDepartment !== '' ? $userDepartment : 'Unassigned',
                'password' => Hash::make($attrs['password']),
            ]);
        }

        Applicant::whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                    ->whereRaw('LOWER(application_status) = ?', [$hire])
                    ->update([
                        'user_id' => $user->id,
                    ]);

        $matchingApplicant->user_id = $user->id;
        $this->syncEducationRecordFromApplicant($user, $matchingApplicant->loadMissing('degrees'));

        return redirect()->route('login_display');
    }

    public function login_store(Request $request)
    {
        $attrs = $request->validate([
            'email'    => 'required|string|max:255',
            'password' => 'required|string',
            'tab_session' => 'nullable|string|max:120',
        ]);

        $loginIdentifier = trim((string) $attrs['email']);
        $normalizedLoginIdentifier = strtolower($loginIdentifier);

        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedLoginIdentifier])
            ->first();

        if (!$user) {
            $user = User::query()
                ->whereHas('employee', function ($query) use ($loginIdentifier) {
                    $query->whereRaw('LOWER(TRIM(employee_id)) = ?', [strtolower($loginIdentifier)]);
                })
                ->first();
        }

        if (!$user) {
            return back()
                ->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ])
                ->withInput();
        }

        $user = User::query()
            ->with('employee')
            ->whereKey($user->id)
            ->first();

        if ($user && strtolower(trim((string) ($user->account_status ?? ''))) === 'inactive') {
            return back()
                ->withErrors([
                    'email' => 'Your account is inactive. Please contact HR.',
                ])
                ->withInput();
        }

        if ($user && Hash::check($attrs['password'], (string) $user->password)) {
            $statusIsApproved = strtolower(trim((string) ($user->status ?? ''))) === 'approved';
            $isEmployee = strtolower(trim((string) ($user->role ?? ''))) === 'employee';
            $isHiredEmployee = $isEmployee && $this->userHasHiredApplicant($user);

            if ($isEmployee && !$isHiredEmployee) {
                return back()
                    ->withErrors([
                        'email' => 'Your employee account is not linked to a hired application yet.',
                    ])
                    ->withInput();
            }

            if (!$isEmployee && !$statusIsApproved) {
                return back()
                    ->withErrors([
                        'email' => 'Your account is not approved yet.',
                    ])
                    ->withInput();
            }

            if ($isHiredEmployee && !$statusIsApproved) {
                $user->forceFill(['status' => 'Approved'])->save();
            }

            Auth::login($user);
            $user = Auth::user();
            $tabSession = $this->resolveTabSessionKey($request);

            if ($tabSession !== '' && $user) {
                $tabScopeKey = $this->tabScopeMapKeyForRole((string) ($user->role ?? ''));
                $scopedTabAuthUsers = $tabScopeKey !== null
                    ? $request->session()->get($tabScopeKey, [])
                    : [];

                if ($tabScopeKey !== null) {
                    $scopedTabAuthUsers[$tabSession] = (int) $user->id;
                    $request->session()->put($tabScopeKey, $scopedTabAuthUsers);
                }

                // Clean legacy mixed-role map to prevent cross-account collisions.
                $legacyTabAuthUsers = $request->session()->get('tab_auth_users', []);
                if (is_array($legacyTabAuthUsers)) {
                    unset($legacyTabAuthUsers[$tabSession]);
                    $request->session()->put('tab_auth_users', $legacyTabAuthUsers);
                }
            }

            $this->recordAuthActivity($request, $user, 'Login', 'logged in to the system');

            Auth::logout();

            return match ($user->role) {
                'Employee' => redirect()
                    ->route('employee.employeeHome', $tabSession !== '' ? ['tab_session' => $tabSession] : [])
                    ->with('show_employee_welcome', true),
                'Admin'    => redirect()->route('admin.adminHome', $tabSession !== '' ? ['tab_session' => $tabSession] : []),
                default    => redirect()->route('login_display')->with('error', 'Unauthorized role'),
            };
        }

        return back()
            ->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])
            ->withInput();
    }

    private function userHasHiredApplicant(User $user): bool
    {
        $normalizedEmail = strtolower(trim((string) ($user->email ?? '')));

        return Applicant::query()
            ->whereRaw("LOWER(TRIM(COALESCE(application_status, ''))) = ?", ['hired'])
            ->where(function ($query) use ($user, $normalizedEmail) {
                $query->where('user_id', (int) $user->id);

                if ($normalizedEmail !== '') {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail]);
                }
            })
            ->exists();
    }

    public function forgot_password()
    {
        return view('auth-forgot-password');
    }

    public function send_password_reset_link(Request $request)
    {
        $attrs = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $status = Password::sendResetLink([
                'email' => $attrs['email'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Password reset email could not be sent.', [
                'email' => $attrs['email'],
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'email' => 'We could not send the reset link right now. Please check the mail settings or contact HR.',
                ])
                ->withInput();
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()
                ->withErrors(['email' => __($status)])
                ->withInput();
    }

    public function reset_password(Request $request, string $token)
    {
        return view('auth-reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update_password(Request $request)
    {
        $attrs = $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $attrs,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login_display')->with('status', __($status))
            : back()
                ->withErrors(['email' => __($status)])
                ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        $authUser = Auth::user();
        $tabSession = $this->resolveTabSessionKey($request);
        $tabSessionScope = $this->resolveTabSessionScope($request);
        $scopeMapKey = $this->tabScopeMapKeyForScope($tabSessionScope);
        $tabAuthUsers = $request->session()->get('tab_auth_users', []);
        $tabAuthUsersAdmin = $request->session()->get(self::TAB_AUTH_MAP_ADMIN, []);
        $tabAuthUsersEmployee = $request->session()->get(self::TAB_AUTH_MAP_EMPLOYEE, []);

        $removeByTabKey = static function (array $map, string $tabKey): array {
            unset($map[$tabKey]);
            return $map;
        };

        $removeByUserId = static function (array $map, int $userId): array {
            if ($userId <= 0) {
                return $map;
            }
            return collect($map)
                ->reject(fn ($mappedUserId) => (int) $mappedUserId === $userId)
                ->all();
        };

        if ($tabSession !== '') {
            // Remove only current tab from its own scope map.
            if ($scopeMapKey === self::TAB_AUTH_MAP_ADMIN) {
                $tabAuthUsersAdmin = $removeByTabKey($tabAuthUsersAdmin, $tabSession);
            } elseif ($scopeMapKey === self::TAB_AUTH_MAP_EMPLOYEE) {
                $tabAuthUsersEmployee = $removeByTabKey($tabAuthUsersEmployee, $tabSession);
            }

            // Legacy map cleanup: only remove if entry belongs to current user.
            $legacyMappedUserId = (int) ($tabAuthUsers[$tabSession] ?? 0);
            if ($legacyMappedUserId > 0 && $legacyMappedUserId === (int) ($authUser?->id ?? 0)) {
                $tabAuthUsers = $removeByTabKey($tabAuthUsers, $tabSession);
            }
        } else {
            $authUserId = (int) ($authUser?->id ?? 0);
            $tabAuthUsers = $removeByUserId($tabAuthUsers, $authUserId);
            $tabAuthUsersAdmin = $removeByUserId($tabAuthUsersAdmin, $authUserId);
            $tabAuthUsersEmployee = $removeByUserId($tabAuthUsersEmployee, $authUserId);
        }

        $request->session()->put('tab_auth_users', $tabAuthUsers);
        $request->session()->put(self::TAB_AUTH_MAP_ADMIN, $tabAuthUsersAdmin);
        $request->session()->put(self::TAB_AUTH_MAP_EMPLOYEE, $tabAuthUsersEmployee);

        if ($authUser) {
            $this->recordAuthActivity($request, $authUser, 'Logout', 'logged out of the system');
        }

        Auth::logout();
        $request->session()->regenerateToken();

        return redirect()->route('login_display', $tabSession !== '' ? ['tab_session' => $tabSession] : []);
    }

    private function syncEducationRecordFromApplicant(User $user, Applicant $applicant): void
    {
        if (!Schema::hasTable('education')) {
            return;
        }

        $degrees = collect($applicant->degrees ?? []);
        $firstDegree = fn (string $level) => $degrees
            ->first(fn ($row) => strtolower(trim((string) ($row->degree_level ?? ''))) === $level);

        $elementary = $firstDegree('elementary');
        $secondary = $firstDegree('secondary');
        $vocationalTrade = $firstDegree('vocational_trade');
        $bachelor = $firstDegree('bachelor');
        $master = $firstDegree('master');
        $doctorate = $firstDegree('doctorate');

        $payload = [
            'bachelor' => trim((string) ($applicant->bachelor_degree ?: ($bachelor->degree_name ?? ''))),
            'master' => trim((string) ($applicant->master_degree ?: ($master->degree_name ?? ''))),
            'doctorate' => trim((string) ($applicant->doctoral_degree ?: ($doctorate->degree_name ?? ''))),
        ];

        $optionalColumns = [
            'elementary_school_name' => $elementary->school_name ?? null,
            'elementary_year_finished' => $elementary->year_finished ?? null,
            'secondary_school_name' => $secondary->school_name ?? null,
            'secondary_year_finished' => $secondary->year_finished ?? null,
            'vocational_trade_school_name' => $vocationalTrade->school_name ?? null,
            'vocational_trade_year_finished' => $vocationalTrade->year_finished ?? null,
        ];

        foreach ($optionalColumns as $column => $value) {
            if (Schema::hasColumn('education', $column)) {
                $payload[$column] = filled($value) ? trim((string) $value) : null;
            }
        }

        $lookup = Education::query();
        if (Schema::hasColumn('education', 'applicant_id')) {
            $lookup->where('applicant_id', (int) $applicant->id);
        } else {
            $lookup->where('user_id', (int) $user->id);
        }

        $education = $lookup->first()
            ?: Education::query()->where('user_id', (int) $user->id)->first()
            ?: new Education();

        if (Schema::hasColumn('education', 'applicant_id')) {
            $education->applicant_id = (int) $applicant->id;
        }

        $education->fill([
            ...$payload,
            'user_id' => (int) $user->id,
            'bachelor' => $payload['bachelor'] ?? '',
            'master' => $payload['master'] ?? '',
            'doctorate' => $payload['doctorate'] ?? '',
        ])->save();
    }

    private function resolveRehireUser(?Applicant $matchingApplicant, ?User $existingUser): ?User
    {
        if (!$matchingApplicant) {
            return null;
        }

        if ($existingUser && $this->userHasApprovedResignation((int) $existingUser->id)) {
            return $existingUser;
        }

        $linkedUserId = (int) ($matchingApplicant->user_id ?? 0);
        if ($linkedUserId <= 0) {
            return null;
        }

        $linkedUser = User::query()->find($linkedUserId);
        if ($linkedUser && $this->userHasApprovedResignation((int) $linkedUser->id)) {
            return $linkedUser;
        }

        return null;
    }

    private function recordAuthActivity(Request $request, User $user, string $event, string $description): void
    {
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        $name = trim(implode(' ', array_filter([
            trim((string) ($user->first_name ?? '')),
            trim((string) ($user->middle_name ?? '')),
            trim((string) ($user->last_name ?? '')),
        ])));

        $role = trim((string) ($user->role ?? ''));
        $departmentHeadStatus = strtolower(trim((string) ($user->department_head ?? '')));

        ActivityLog::query()->create([
            'user_id' => $user->id,
            'user_name' => $name !== '' ? $name : (string) ($user->email ?? 'Unknown user'),
            'user_email' => $user->email,
            'user_role' => strtolower($role) === 'employee' && $departmentHeadStatus === 'approved' ? 'Department Head' : ($role !== '' ? $role : 'User'),
            'method' => $event,
            'route_name' => (string) optional($request->route())->getName(),
            'path' => '/'.ltrim($request->path(), '/'),
            'action' => $event,
            'description' => ($name !== '' ? $name : (string) ($user->email ?? 'Unknown user')).' '.$description.'.',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    private function userHasApprovedResignation(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return Resignation::query()
            ->where('user_id', $userId)
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN (?, ?)", ['approved', 'completed'])
            ->exists();
    }

    private function tabScopeMapKeyForRole(string $role): ?string
    {
        $normalizedRole = strtolower(trim($role));

        return match ($normalizedRole) {
            'admin', 'administrator' => self::TAB_AUTH_MAP_ADMIN,
            'employee' => self::TAB_AUTH_MAP_EMPLOYEE,
            default => null,
        };
    }

    private function tabScopeMapKeyForScope(string $scope): ?string
    {
        $normalizedScope = strtolower(trim($scope));

        return match ($normalizedScope) {
            'admin' => self::TAB_AUTH_MAP_ADMIN,
            'employee' => self::TAB_AUTH_MAP_EMPLOYEE,
            default => null,
        };
    }
}
