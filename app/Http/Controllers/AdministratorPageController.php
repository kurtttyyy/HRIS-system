<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\Conversation;
use App\Models\Employee;
use App\Models\GuestLog;
use App\Models\Interviewer;
use App\Models\LoadsRecord;
use App\Models\LoadsUpload;
use App\Models\OpenPosition;
use App\Models\PayslipRecord;
use App\Models\PayslipUpload;
use App\Models\LeaveApplication;
use App\Models\Resignation;
use App\Models\User;
use App\Support\ActivityChangeLogger;
use App\Support\EmployeeAccountStatusManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AdministratorPageController extends Controller
{
    private ?array $hiddenOfficialHolidayDatesCache = null;
    private ?array $calendarHolidayConfigCache = null;
    private array $holidayDateCheckCache = [];

    public function display_home(Request $request){
        $accept = User::with([
            'employee',
            'applicant',
            'applicant.documents' => function ($query) {
                $query->select([
                    'id',
                    'applicant_id',
                    'filename',
                    'filepath',
                    'type',
                    'mime_type',
                    'created_at',
                ])->orderByDesc('created_at');
            },
            'applicant.position:id,department',
        ])->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
                        ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
                        ->latest()
                        ->paginate(5, ['*'], 'recent_page')
                        ->withQueryString();
        
        // Get department overview (prefer users.department as source of truth)
        $resolveDepartmentName = function (User $user): string {
            $userDepartment = trim((string) ($user->department ?? ''));
            if ($userDepartment !== '') {
                return $userDepartment;
            }

            $employeeDepartment = trim((string) (optional($user->employee)->department ?? ''));
            if ($employeeDepartment !== '') {
                return $employeeDepartment;
            }

            $applicantDepartment = trim((string) (optional(optional($user->applicant)->position)->department ?? ''));
            return $applicantDepartment !== '' ? $applicantDepartment : 'Unassigned';
        };

        $departments = User::with(['employee', 'applicant.position:id,department'])
                        ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
                        ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
                        ->get()
                        ->groupBy(function ($user) use ($resolveDepartmentName) {
                            return $resolveDepartmentName($user);
                        })
                        ->map(function ($group) use ($resolveDepartmentName) {
                            return [
                                'name' => $resolveDepartmentName($group->first()),
                                'count' => $group->count()
                            ];
                        })
                        ->values();

        $totalEmployeeCount = User::query()
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->count();

        $today = now();
        $currentMonthStart = (clone $today)->startOfMonth();
        $currentRangeEnd = (clone $today)->endOfDay();

        $previousMonthReference = (clone $today)->subMonthNoOverflow();
        $previousMonthStart = (clone $previousMonthReference)->startOfMonth();
        $sameDayLastMonth = min(
            (int) $today->day,
            (int) $previousMonthReference->daysInMonth
        );
        $previousRangeEnd = (clone $previousMonthStart)
            ->addDays($sameDayLastMonth - 1)
            ->endOfDay();

        // "Applied" employees are based on account creation date.
        $employeesThisMonth = User::query()
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereBetween('created_at', [$currentMonthStart, $currentRangeEnd])
            ->count();

        $employeesLastMonth = User::query()
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereBetween('created_at', [$previousMonthStart, $previousRangeEnd])
            ->count();

        if ($employeesLastMonth > 0) {
            $monthlyEmployeePercentChange = (($employeesThisMonth - $employeesLastMonth) / $employeesLastMonth) * 100;
        } elseif ($employeesThisMonth > 0) {
            $monthlyEmployeePercentChange = 100;
        } else {
            $monthlyEmployeePercentChange = 0;
        }
        $monthlyEmployeePercentChange = round($monthlyEmployeePercentChange, 1);

        $todayDate = now()->toDateString();
        $approvedLeaveToday = LeaveApplication::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($application) use ($todayDate) {
                $startDate = $application->filing_date
                    ? Carbon::parse($application->filing_date)->startOfDay()
                    : Carbon::parse($application->created_at)->startOfDay();
                $days = (float) ($application->number_of_working_days ?? 0);
                if ($days <= 0) {
                    $days = max(
                        (float) ($application->days_with_pay ?? 0),
                        (float) ($application->applied_total ?? 0)
                    );
                }

                $rangeDays = max((int) ceil($days), 1);
                $endDate = $startDate->copy()->addDays($rangeDays - 1);

                return $todayDate >= $startDate->toDateString() && $todayDate <= $endDate->toDateString();
            })
            ->unique(function ($application) {
                $userId = $application->user_id ?? null;
                if (!is_null($userId)) {
                    return 'user:'.$userId;
                }

                return 'name:'.strtolower(trim((string) ($application->employee_name ?? '')));
            })
            ->values();

        $onLeaveTodayCount = (int) $approvedLeaveToday->count();
        $pendingLeaveRequestCount = (int) LeaveApplication::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw("TRIM(status) = ''")
                    ->orWhereRaw("LOWER(TRIM(status)) = ?", ['pending']);
            })
            ->count();
        $pendingLeaveRequestsForHome = LeaveApplication::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw("TRIM(status) = ''")
                    ->orWhereRaw("LOWER(TRIM(status)) = ?", ['pending']);
            })
            ->orderByDesc('created_at')
            ->take(3)
            ->get();
        $pendingResignationsForHome = Resignation::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $openPositionsCount = OpenPosition::query()->count();
        $openPositionApplicationsCount = Applicant::query()->count();
        $pendingEmployeesForNotifications = collect();

        [$adminNotificationItems, $adminNotificationStats] = $this->buildAdminNotifications(
            $pendingEmployeesForNotifications,
            $pendingLeaveRequestsForHome,
            $openPositionApplicationsCount,
            $pendingResignationsForHome
        );
        
        return view('Admin.adminHome', compact(
            'accept',
            'departments',
            'totalEmployeeCount',
            'monthlyEmployeePercentChange',
            'onLeaveTodayCount',
            'pendingLeaveRequestCount',
            'pendingLeaveRequestsForHome',
            'openPositionsCount',
            'openPositionApplicationsCount',
            'adminNotificationItems',
            'adminNotificationStats'
        ));
    }

    public function display_notifications()
    {
        $employee = collect();

        $departments = User::with(['employee', 'applicant.position:id,department'])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get()
            ->groupBy(function ($user) {
                $userDepartment = trim((string) ($user->department ?? ''));
                if ($userDepartment !== '') {
                    return $userDepartment;
                }

                $employeeDepartment = trim((string) (optional($user->employee)->department ?? ''));
                if ($employeeDepartment !== '') {
                    return $employeeDepartment;
                }

                $applicantDepartment = trim((string) (optional(optional($user->applicant)->position)->department ?? ''));
                return $applicantDepartment !== '' ? $applicantDepartment : 'Unassigned';
            })
            ->map(function ($group, $departmentName) {
                return [
                    'name' => $departmentName,
                    'count' => $group->count(),
                ];
            })
            ->values();

        $pendingLeaveRequestsForHome = LeaveApplication::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw("TRIM(status) = ''")
                    ->orWhereRaw("LOWER(TRIM(status)) = ?", ['pending']);
            })
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $pendingResignations = Resignation::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $openPositionApplicationsCount = Applicant::query()->count();

        [$adminNotificationItems, $adminNotificationStats] = $this->buildAdminNotifications(
            $employee,
            $pendingLeaveRequestsForHome,
            $openPositionApplicationsCount,
            $pendingResignations
        );

        return view('Admin.adminNotifications', compact(
            'adminNotificationItems',
            'adminNotificationStats',
            'employee',
            'departments',
            'openPositionApplicationsCount'
        ));
    }

    public function notification_summary()
    {
        $employee = collect();

        $departments = User::with(['employee', 'applicant.position:id,department'])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get()
            ->groupBy(function ($user) {
                $userDepartment = trim((string) ($user->department ?? ''));
                if ($userDepartment !== '') {
                    return $userDepartment;
                }

                $employeeDepartment = trim((string) (optional($user->employee)->department ?? ''));
                if ($employeeDepartment !== '') {
                    return $employeeDepartment;
                }

                $applicantDepartment = trim((string) (optional(optional($user->applicant)->position)->department ?? ''));
                return $applicantDepartment !== '' ? $applicantDepartment : 'Unassigned';
            })
            ->map(function ($group, $departmentName) {
                return [
                    'name' => $departmentName,
                    'count' => $group->count(),
                ];
            })
            ->values();

        $pendingLeaveRequests = LeaveApplication::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw("TRIM(status) = ''")
                    ->orWhereRaw("LOWER(TRIM(status)) = ?", ['pending']);
            })
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $pendingResignations = Resignation::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $openPositionApplicationsCount = Applicant::query()->count();

        [$adminNotificationItems, $adminNotificationStats] = $this->buildAdminNotifications(
            $employee,
            $pendingLeaveRequests,
            $openPositionApplicationsCount,
            $pendingResignations
        );

        return response()->json([
            'total' => (int) ($adminNotificationStats['total'] ?? 0),
            'stats' => $adminNotificationStats,
            'items' => $adminNotificationItems->map(function ($item) {
                $itemDate = $item['date'] ?? null;
                $dateHuman = $itemDate
                    ? Carbon::parse($itemDate)->diffForHumans(now(), ['parts' => 2])
                    : 'Live';

                return [
                    'id' => $item['id'] ?? null,
                    'category' => $item['category'] ?? 'Update',
                    'title' => $item['title'] ?? 'Notification',
                    'message' => $item['message'] ?? '',
                    'href' => $item['href'] ?? '#',
                    'badge' => $item['badge'] ?? 'Notice',
                    'tone' => $item['tone'] ?? 'slate',
                    'date' => optional($itemDate)?->toIso8601String(),
                    'date_human' => $dateHuman,
                ];
            })->values(),
        ]);
    }

    public function display_communication()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display');
        }

        if (!in_array(strtolower(trim((string) ($user->role ?? ''))), ['admin', 'administrator'], true)) {
            return redirect()->route('employee.employeeCommunication')
                ->with('warning', 'You are signed in as an employee account. Please log in as an admin account to access admin communication.');
        }

        $employees = User::query()
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->whereKeyNot((int) $user->id)
            ->orderBy('first_name')
            ->get();

        if (!Schema::hasTable('conversations') || !Schema::hasTable('conversation_messages')) {
            return view('Admin.adminCommunication', [
                'employees' => $employees,
                'conversations' => collect(),
                'conversationSummaries' => collect(),
                'selectedConversation' => null,
                'selectedParticipant' => null,
            ])->with('warning', 'Communication tables are not ready yet. Please run the latest migration.');
        }

        $resetChat = request()->boolean('reset_chat');
        $selectedParticipantId = $resetChat ? 0 : (int) request()->query('user', 0);
        $selectedConversationId = $resetChat ? 0 : (int) request()->query('conversation', 0);
        if ($selectedParticipantId === (int) $user->id) {
            $selectedParticipantId = 0;
        }

        $conversations = Conversation::query()
            ->forUser((int) $user->id)
            ->with([
                'userOne',
                'userTwo',
                'latestMessage.sender',
            ])
            ->withCount([
                'messages as unread_count' => function ($query) use ($user) {
                    $query->whereNull('read_at')
                        ->where('sender_user_id', '!=', (int) $user->id);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $selectedConversation = null;
        if ($selectedConversationId > 0) {
            $selectedConversation = $conversations->firstWhere('id', $selectedConversationId);
        }

        $selectedParticipant = null;
        if ($selectedConversation) {
            $selectedParticipant = $selectedConversation->otherParticipantFor((int) $user->id);
        } elseif ($selectedParticipantId > 0) {
            $selectedParticipant = $employees->firstWhere('id', $selectedParticipantId);
            if ($selectedParticipant) {
                $selectedConversation = $conversations->first(function (Conversation $conversation) use ($selectedParticipant, $user) {
                    $otherParticipant = $conversation->otherParticipantFor((int) $user->id);
                    return (int) ($otherParticipant?->id ?? 0) === (int) $selectedParticipant->id;
                });
            }
        }

        if ($selectedConversation) {
            $selectedConversation->load([
                'messages' => function ($query) {
                    $query->with(['sender', 'attachments'])->orderBy('created_at');
                },
                'userOne',
                'userTwo',
            ]);

            $selectedConversation->messages()
                ->whereNull('read_at')
                ->where('sender_user_id', '!=', (int) $user->id)
                ->update(['read_at' => now()]);

            $selectedParticipant = $selectedParticipant ?: $selectedConversation->otherParticipantFor((int) $user->id);

            $activeConversationIndex = $conversations->search(fn (Conversation $conversation) => (int) $conversation->id === (int) $selectedConversation->id);
            if ($activeConversationIndex !== false) {
                $conversations[$activeConversationIndex]->unread_count = 0;
            }
        }

        $conversationSummaries = $conversations->map(function (Conversation $conversation) use ($user) {
            $participant = $conversation->otherParticipantFor((int) $user->id);
            $latestMessage = $conversation->latestMessage;

            return [
                'id' => (int) $conversation->id,
                'participant' => $participant,
                'latest_message' => trim((string) ($latestMessage?->body ?? '')),
                'latest_at' => $conversation->last_message_at ?? $latestMessage?->created_at ?? $conversation->updated_at,
                'unread_count' => (int) ($conversation->unread_count ?? 0),
            ];
        })->filter(fn ($item) => $item['participant'])->values();

        $unreadCountsByParticipant = $conversationSummaries
            ->filter(fn ($item) => ($item['participant']->id ?? null) !== null)
            ->mapWithKeys(fn ($item) => [
                (int) $item['participant']->id => (int) ($item['unread_count'] ?? 0),
            ]);

        $employees = $employees->map(function ($employee) use ($unreadCountsByParticipant) {
            $employee->unread_message_count = (int) $unreadCountsByParticipant->get((int) $employee->id, 0);
            $employee->has_unread_messages = $employee->unread_message_count > 0;
            return $employee;
        });

        return view('Admin.adminCommunication', compact(
            'employees',
            'conversations',
            'conversationSummaries',
            'selectedConversation',
            'selectedParticipant'
        ));
    }

    private function buildAdminNotifications($pendingEmployees, $pendingLeaveRequests, int $openPositionApplicationsCount, $pendingResignations = null): array
    {
        $pendingEmployees = collect($pendingEmployees ?? []);
        $pendingLeaveRequests = collect($pendingLeaveRequests ?? []);
        $pendingResignations = collect($pendingResignations ?? []);
        $appTimezone = config('app.timezone');
        $permanentStatusNotifications = User::query()
            ->with(['employee', 'applicant.position:id,job_type'])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get()
            ->map(function (User $user) use ($appTimezone) {
                $regularizationDate = $this->resolveEmployeeRegularizationDate($user);
                if (!$regularizationDate) {
                    return null;
                }

                $today = now()->setTimezone($appTimezone)->startOfDay();
                $notificationDate = $regularizationDate->copy()->subWeek()->startOfDay();

                $regularizationDay = $regularizationDate->copy()->startOfDay();
                if ($today->lt($notificationDate)) {
                    return null;
                }

                $fullName = trim(implode(' ', array_filter([
                    $user->first_name ?? null,
                    $user->middle_name ?? null,
                    $user->last_name ?? null,
                ])));

                $isOverdue = $today->gt($regularizationDay);

                return [
                    'category' => 'Workforce',
                    'title' => $isOverdue
                        ? 'Employee regularization is overdue'
                        : 'Employee regularization due in one week',
                    'message' => $isOverdue
                        ? (($fullName !== '' ? $fullName : 'An employee').' should have become permanent on '.$regularizationDate->format('F j, Y').'.')
                        : (($fullName !== '' ? $fullName : 'An employee').' will become permanent on '.$regularizationDate->format('F j, Y').'.'),
                    'date' => $regularizationDay,
                    'href' => route('admin.adminEmployee'),
                    'badge' => $isOverdue ? 'Overdue' : 'Upcoming',
                    'tone' => $isOverdue ? 'rose' : 'sky',
                ];
            })
            ->filter()
            ->sortByDesc(fn ($item) => optional($item['date'] ?? null)->timestamp ?? 0)
            ->take(6)
            ->values();

        $latestHiringActivityAt = Applicant::query()
            ->select(['created_at', 'updated_at'])
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        $latestHiringDate = null;
        if ($latestHiringActivityAt) {
            $latestHiringDate = collect([
                $latestHiringActivityAt->updated_at,
                $latestHiringActivityAt->created_at,
            ])->filter()->map(function ($date) use ($appTimezone) {
                return Carbon::parse($date)->setTimezone($appTimezone);
            })->sortByDesc(fn (Carbon $date) => $date->timestamp)->first();
        }

        $approvalNotifications = collect();

        $leaveNotifications = $pendingLeaveRequests
            ->take(6)
            ->map(function ($application) {
                $employeeName = trim((string) ($application->employee_name ?? 'Employee'));
                $leaveType = trim((string) ($application->leave_type ?? 'Leave request'));
                $filingDateRaw = trim((string) ($application->filing_date ?? ''));
                $filingDateHasTime = $filingDateRaw !== '' && preg_match('/\d{1,2}:\d{2}/', $filingDateRaw) === 1;
                $createdAt = $application->created_at ? Carbon::parse($application->created_at)->setTimezone(config('app.timezone')) : null;
                $updatedAt = $application->updated_at ? Carbon::parse($application->updated_at)->setTimezone(config('app.timezone')) : null;
                $latestRecordedAt = collect([$createdAt, $updatedAt])
                    ->filter()
                    ->sortByDesc(fn (Carbon $date) => $date->timestamp)
                    ->first();

                // filing_date is often date-only; prefer precise timestamps from created/updated audit fields.
                $filedAt = $filingDateHasTime
                    ? Carbon::parse($filingDateRaw)->setTimezone(config('app.timezone'))
                    : ($latestRecordedAt ?: now());

                return [
                    'category' => 'Leave',
                    'title' => 'Leave request awaiting action',
                    'message' => $employeeName.' submitted '.$leaveType.'.',
                    'date' => $filedAt,
                    'href' => route('admin.adminLeaveManagement'),
                    'badge' => 'Pending',
                    'tone' => 'amber',
                ];
            });

        $hiringNotifications = collect();
        if ($openPositionApplicationsCount > 0) {
            $hiringNotifications->push([
                'category' => 'Hiring',
                'title' => 'Active hiring pipeline',
                'message' => number_format($openPositionApplicationsCount).' applicant'.($openPositionApplicationsCount === 1 ? '' : 's').' are attached to open roles.',
                'date' => $latestHiringDate,
                'href' => route('admin.adminApplicant'),
                'badge' => 'Pipeline',
                'tone' => 'sky',
            ]);
        }

        $requestNotifications = $pendingResignations
            ->take(6)
            ->map(function ($resignation) {
                $employeeName = trim((string) ($resignation->employee_name ?? 'Employee'));
                $filedAt = $resignation->submitted_at
                    ? Carbon::parse($resignation->submitted_at)
                    : Carbon::parse($resignation->created_at);

                return [
                    'category' => 'Requests',
                    'title' => 'Resignation request needs review',
                    'message' => $employeeName.' submitted a resignation request.',
                    'date' => $filedAt,
                    'href' => route('admin.adminResignations'),
                    'badge' => 'Pending',
                    'tone' => 'rose',
                ];
            });

        $notificationItems = collect()
            ->concat($approvalNotifications)
            ->concat($leaveNotifications)
            ->concat($hiringNotifications)
            ->concat($requestNotifications)
            ->concat($permanentStatusNotifications)
            ->sortByDesc(function ($item) {
                return optional($item['date'] ?? null)->timestamp ?? 0;
            })
            ->values()
            ->map(function ($item) {
                $item['id'] = md5(
                    ($item['category'] ?? 'update')
                    .'|'.($item['title'] ?? '')
                    .'|'.($item['message'] ?? '')
                    .'|'.optional($item['date'] ?? null)->format('Y-m-d H:i:s')
                );

                return $item;
            });

        $notificationStats = [
            'total' => $notificationItems->count(),
            'approvals' => $approvalNotifications->count(),
            'leave' => $leaveNotifications->count(),
            'hiring' => $hiringNotifications->count(),
            'requests' => $requestNotifications->count(),
            'workforce' => $permanentStatusNotifications->count(),
        ];

        return [$notificationItems, $notificationStats];
    }

    private function resolveEmployeeRegularizationDate(User $user): ?Carbon
    {
        $employee = $user->employee;
        if (!$employee) {
            return null;
        }

        $classification = Str::lower(trim((string) ($employee->classification ?? '')));
        if ($classification !== '' && (str_contains($classification, 'permanent') || str_contains($classification, 'regular'))) {
            return null;
        }

        $rawJoinDate = $employee->employement_date ?? optional($user->applicant)->date_hired;
        if (empty($rawJoinDate)) {
            return null;
        }

        $jobTypeRaw = $employee->job_type ?: optional(optional($user->applicant)->position)->job_type;
        $jobType = Str::lower(trim((string) $jobTypeRaw));
        $isNonTeaching = in_array($jobType, ['non-teaching', 'non teaching', 'nt', 'nonteaching'], true);

        try {
            $joinDate = Carbon::parse($rawJoinDate)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }

        return $isNonTeaching
            ? $joinDate->copy()->addMonths(6)
            : $joinDate->copy()->addYears(3);
    }

    public function display_employee(Request $request){
        app(EmployeeAccountStatusManager::class)->syncAllEmployeeStatuses();

        $employee = User::with([
            'applicant',
            'applicant.degrees' => function ($query) {
                $query->select([
                    'id',
                    'applicant_id',
                    'degree_level',
                    'degree_name',
                    'school_name',
                    'year_finished',
                    'sort_order',
                ])->orderBy('degree_level')->orderBy('sort_order');
            },
            'applicant.position:id,title,department,employment,job_type',
            'employee',
            'education',
            'government',
            'salary',
            'license',
            'resignations' => function ($query) {
                $query
                    ->select([
                        'id',
                        'user_id',
                        'submitted_at',
                        'effective_date',
                        'status',
                        'admin_note',
                        'processed_at',
                        'created_at',
                    ])
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id');
            },
            'leaveApplications' => function ($query) {
                $query
                    ->select([
                        'id',
                        'user_id',
                        'leave_type',
                        'number_of_working_days',
                        'inclusive_dates',
                        'beginning_vacation',
                        'beginning_sick',
                        'earned_vacation',
                        'earned_sick',
                        'applied_total',
                        'ending_vacation',
                        'ending_sick',
                        'days_with_pay',
                        'status',
                        'filing_date',
                        'created_at',
                    ])
                    ->orderByDesc('filing_date')
                    ->orderByDesc('id');
            },
            'positionHistories' => function ($query) {
                $query
                    ->select([
                        'id',
                        'user_id',
                        'old_position',
                        'new_position',
                        'old_classification',
                        'new_classification',
                        'old_department',
                        'new_department',
                        'old_salary',
                        'new_salary',
                        'changed_by',
                        'changed_at',
                        'note',
                        'created_at',
                    ])
                    ->orderByDesc('changed_at')
                    ->orderByDesc('id');
            },
            ])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get();

        $applicantIds = $employee
            ->pluck('applicant.id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $uploadedDocumentTypesByApplicant = ApplicantDocument::query()
            ->select(['applicant_id', 'type', 'filename'])
            ->whereIn('applicant_id', $applicantIds)
            ->where('type', 'not like', '__REQUIRED__::%')
            ->where('type', '!=', '__NOTICE__')
            ->where('type', '!=', '__FOLDER__')
            ->get()
            ->groupBy('applicant_id')
            ->map(function ($documents) {
                return $documents
                    ->map(function ($doc) {
                        return $this->normalizeDocumentLabel((string) ($doc->type ?: $doc->filename));
                    })
                    ->filter()
                    ->unique()
                    ->values();
            });

        $employee->each(function (User $row) {
            $row->setAttribute('leave_summary', $this->buildAdminEmployeeLeaveSummary($row, now()->format('Y-m')));
        });

        $employee->each(function (User $row) use ($uploadedDocumentTypesByApplicant) {
            $requiredConfig = $this->getRequiredDocumentConfigForApplicant((int) ($row->applicant?->id ?? 0));
            $requiredDocuments = collect($requiredConfig['required_documents'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values();

            $uploadedDocumentTypesNormalized = $uploadedDocumentTypesByApplicant
                ->get((int) ($row->applicant?->id ?? 0), collect());

            $missingRequiredDocuments = $requiredDocuments
                ->filter(function ($required) use ($uploadedDocumentTypesNormalized) {
                    return !$uploadedDocumentTypesNormalized->contains(
                        $this->normalizeDocumentLabel((string) $required)
                    );
                })
                ->values();

            $row->setAttribute('missing_required_documents', $missingRequiredDocuments->all());
            $row->setAttribute('missing_required_documents_count', (int) $missingRequiredDocuments->count());
        });

        $this->attachSubjectLoadsToEmployees($employee);

        $employeeDirectory = $employee->values();
        $employeeSearch = trim((string) $request->query('search', ''));
        $employeeDepartment = trim((string) $request->query('department', 'All'));
        $employeeStatus = trim((string) $request->query('status', 'All'));
        $employeePerPage = (int) $request->query('per_page', 10);
        if (!in_array($employeePerPage, [5, 10, 15, 25], true)) {
            $employeePerPage = 10;
        }

        $resolveEmployeeDepartment = static function ($emp): string {
            return trim((string) (data_get($emp, 'applicant.position.department') ?: data_get($emp, 'employee.department') ?: ($emp->department ?? '')));
        };

        $isMissingEmployeeValue = static function ($value): bool {
            if (is_null($value)) {
                return true;
            }

            $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
            return $normalized === '' || in_array($normalized, [
                '-',
                'n/a',
                'na',
                'unspecified',
                'not set',
                'school n/a',
                'year n/a',
                'school n/a, year n/a',
            ], true);
        };

        $hasMissingAddressParts = static function ($emp) use ($isMissingEmployeeValue): bool {
            $rawAddress = trim((string) (data_get($emp, 'employee.address') ?: data_get($emp, 'applicant.address') ?: ($emp->address ?? '')));
            $parts = $rawAddress === '' ? [] : collect(preg_split('/\s*,\s*/', $rawAddress))->map(fn ($item) => trim((string) $item))->values()->all();

            return collect([
                $parts[0] ?? null,
                $parts[1] ?? null,
                $parts[2] ?? null,
            ])->contains(fn ($value) => $isMissingEmployeeValue($value));
        };

        $hasMissingEmployeeInfo = static function ($emp) use ($isMissingEmployeeValue, $hasMissingAddressParts): bool {
            return collect([
                data_get($emp, 'employee.account_number'),
                data_get($emp, 'employee.sex') ?: data_get($emp, 'employee.gender'),
                data_get($emp, 'employee.civil_status'),
                data_get($emp, 'employee.contact_number') ?: data_get($emp, 'applicant.phone'),
                data_get($emp, 'employee.birthday'),
                data_get($emp, 'license.license'),
                data_get($emp, 'license.registration_number'),
                data_get($emp, 'government.SSS'),
                data_get($emp, 'government.TIN'),
                data_get($emp, 'government.PhilHealth'),
                data_get($emp, 'government.MID'),
                data_get($emp, 'government.RTN'),
                data_get($emp, 'salary.salary'),
            ])->contains(fn ($value) => $isMissingEmployeeValue($value))
                || $hasMissingAddressParts($emp)
                || (int) data_get($emp, 'missing_required_documents_count', 0) > 0;
        };

        $filteredEmployees = $employeeDirectory->filter(function ($emp) use ($employeeSearch, $employeeDepartment, $employeeStatus, $resolveEmployeeDepartment, $hasMissingEmployeeInfo) {
            $name = trim(($emp->last_name ?? '').', '.trim(($emp->first_name ?? '').' '.($emp->middle_name ?? '')), ', ');
            if ($employeeSearch !== '' && !str_contains(strtolower($name), strtolower($employeeSearch))) {
                return false;
            }

            if ($employeeDepartment !== '' && strcasecmp($employeeDepartment, 'All') !== 0 && strcasecmp($resolveEmployeeDepartment($emp), $employeeDepartment) !== 0) {
                return false;
            }

            if ($employeeStatus !== '' && strcasecmp($employeeStatus, 'All') !== 0) {
                if (strcasecmp($employeeStatus, 'Missing Info') === 0) {
                    return $hasMissingEmployeeInfo($emp);
                }

                return strcasecmp(trim((string) ($emp->account_status ?? 'Active')), $employeeStatus) === 0;
            }

            return true;
        })->values();

        $employeeLastPage = max((int) ceil($filteredEmployees->count() / $employeePerPage), 1);
        $employeePage = min(max((int) $request->query('page', 1), 1), $employeeLastPage);
        $employeePaginator = new LengthAwarePaginator(
            $filteredEmployees->forPage($employeePage, $employeePerPage)->values(),
            $filteredEmployees->count(),
            $employeePerPage,
            $employeePage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $employee = $employeePaginator->getCollection();
        $employeeFilters = [
            'search' => $employeeSearch,
            'department' => $employeeDepartment !== '' ? $employeeDepartment : 'All',
            'status' => $employeeStatus !== '' ? $employeeStatus : 'All',
            'per_page' => $employeePerPage,
        ];

        return view('Admin.adminEmployee', compact('employee', 'employeeDirectory', 'employeePaginator', 'employeeFilters'));
    }

    private function isSundayDate(?string $fromDate): bool
    {
        if (!$fromDate) {
            return false;
        }

        try {
            $date = Carbon::parse($fromDate)->startOfDay();
        } catch (\Throwable $e) {
            return false;
        }

        return $date->isSunday();
    }

    private function getAttendanceEmployeeLookupMaps(): array
    {
        return Cache::remember('admin_attendance_employee_lookup_maps', now()->addMinutes(10), function () {
            $jobTypeMap = [];
            $departmentMap = [];
            $displayNameMap = [];

            Employee::query()
                ->with([
                    'user:id,first_name,middle_name,last_name,department',
                    'user.applicant.position:id,department',
                ])
                ->select(['employee_id', 'job_type', 'department', 'user_id'])
                ->whereNotNull('employee_id')
                ->orderBy('employee_id')
                ->chunk(300, function ($employees) use (&$jobTypeMap, &$departmentMap, &$displayNameMap) {
                    foreach ($employees as $employee) {
                        $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                        if ($employeeId === '') {
                            continue;
                        }

                        $employeeDepartment = trim((string) ($employee->department ?? ''));
                        $userDepartment = trim((string) ($employee->user?->department ?? ''));
                        $applicantDepartment = trim((string) (optional(optional($employee->user?->applicant)->position)->department ?? ''));

                        $jobTypeMap[$employeeId] = $this->normalizeJobType($employee->job_type);
                        $departmentMap[$employeeId] = $employeeDepartment !== ''
                            ? $employeeDepartment
                            : ($userDepartment !== '' ? $userDepartment : ($applicantDepartment !== '' ? $applicantDepartment : null));
                        $displayNameMap[$employeeId] = $this->formatEmployeeDisplayName(
                            $employee->user?->first_name,
                            $employee->user?->middle_name,
                            $employee->user?->last_name
                        );
                    }
                });

            return [
                'job_type' => $jobTypeMap,
                'department' => $departmentMap,
                'display_name' => $displayNameMap,
            ];
        });
    }

    private function formatAttendanceDateValue($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->toDateString();
            }

            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isHolidayDate(?string $fromDate): bool
    {
        if (!$fromDate) {
            return false;
        }

        if (array_key_exists($fromDate, $this->holidayDateCheckCache)) {
            return $this->holidayDateCheckCache[$fromDate];
        }

        try {
            $date = Carbon::parse($fromDate)->startOfDay();
        } catch (\Throwable $e) {
            $this->holidayDateCheckCache[$fromDate] = false;
            return false;
        }

        $dateString = $date->toDateString();

        if ($this->isCustomHolidayDate($dateString)) {
            $this->holidayDateCheckCache[$fromDate] = true;
            return true;
        }

        if (!$this->isHiddenOfficialHolidayDate($dateString) && $this->isUsPublicHoliday($date)) {
            $this->holidayDateCheckCache[$fromDate] = true;
            return true;
        }

        if ($this->isChineseNewYearDate($date)) {
            $this->holidayDateCheckCache[$fromDate] = true;
            return true;
        }

        $this->holidayDateCheckCache[$fromDate] = false;
        return false;
    }

    private function isHiddenOfficialHolidayDate(string $date): bool
    {
        $hiddenDates = $this->getHiddenOfficialHolidayDates();
        return in_array($date, $hiddenDates, true);
    }

    private function isCustomHolidayDate(string $date): bool
    {
        $config = $this->getCalendarHolidayConfig();
        $customHolidays = $config['custom_holidays'] ?? [];
        if (array_key_exists($date, $customHolidays) && !empty($customHolidays[$date])) {
            return true;
        }

        $monthDay = substr($date, 5);
        $recurringHolidays = $config['recurring_holidays'] ?? [];
        return array_key_exists($monthDay, $recurringHolidays) && !empty($recurringHolidays[$monthDay]);
    }

    private function getCalendarHolidayConfig(): array
    {
        if (!is_null($this->calendarHolidayConfigCache)) {
            return $this->calendarHolidayConfigCache;
        }

        $default = [
            'hidden_official_holidays' => [],
            'custom_holidays' => [],
            'recurring_holidays' => [],
        ];

        try {
            if (!Storage::disk('local')->exists('calendar_holiday_config.json')) {
                $this->calendarHolidayConfigCache = $default;
                return $this->calendarHolidayConfigCache;
            }

            $raw = Storage::disk('local')->get('calendar_holiday_config.json');
            $payload = json_decode($raw, true);
            if (!is_array($payload)) {
                $this->calendarHolidayConfigCache = $default;
                return $this->calendarHolidayConfigCache;
            }

            $customHolidays = collect($payload['custom_holidays'] ?? [])
                ->filter(fn ($names, $date) => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && is_array($names))
                ->map(fn ($names) => array_values(array_filter(array_map(fn ($name) => is_string($name) ? trim($name) : '', $names), fn ($name) => $name !== '')))
                ->filter(fn ($names) => !empty($names))
                ->all();

            $recurringHolidays = collect($payload['recurring_holidays'] ?? [])
                ->filter(fn ($names, $monthDay) => is_string($monthDay) && preg_match('/^\d{2}-\d{2}$/', $monthDay) && is_array($names))
                ->map(fn ($names) => array_values(array_filter(array_map(fn ($name) => is_string($name) ? trim($name) : '', $names), fn ($name) => $name !== '')))
                ->filter(fn ($names) => !empty($names))
                ->all();

            $hiddenOfficialHolidays = collect($payload['hidden_official_holidays'] ?? [])
                ->filter(fn ($names, $date) => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && is_array($names) && !empty($names))
                ->all();

            $this->calendarHolidayConfigCache = [
                'hidden_official_holidays' => $hiddenOfficialHolidays,
                'custom_holidays' => $customHolidays,
                'recurring_holidays' => $recurringHolidays,
            ];

            return $this->calendarHolidayConfigCache;
        } catch (\Throwable $e) {
            $this->calendarHolidayConfigCache = $default;
            return $this->calendarHolidayConfigCache;
        }
    }

    private function getHiddenOfficialHolidayDates(): array
    {
        if (!is_null($this->hiddenOfficialHolidayDatesCache)) {
            return $this->hiddenOfficialHolidayDatesCache;
        }

        $config = $this->getCalendarHolidayConfig();
        $fromConfig = collect($config['hidden_official_holidays'] ?? [])
            ->keys()
            ->filter(fn ($date) => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->unique()
            ->values()
            ->all();

        if (!empty($fromConfig)) {
            $this->hiddenOfficialHolidayDatesCache = $fromConfig;
            return $this->hiddenOfficialHolidayDatesCache;
        }

        try {
            if (!Storage::disk('local')->exists('calendar_hidden_holidays.json')) {
                $this->hiddenOfficialHolidayDatesCache = [];
                return $this->hiddenOfficialHolidayDatesCache;
            }

            $raw = Storage::disk('local')->get('calendar_hidden_holidays.json');
            $payload = json_decode($raw, true);
            $dates = is_array($payload['dates'] ?? null) ? $payload['dates'] : [];
            $normalized = collect($dates)
                ->filter(fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                ->unique()
                ->values()
                ->all();

            $this->hiddenOfficialHolidayDatesCache = $normalized;
            return $this->hiddenOfficialHolidayDatesCache;
        } catch (\Throwable $e) {
            $this->hiddenOfficialHolidayDatesCache = [];
            return $this->hiddenOfficialHolidayDatesCache;
        }
    }

    private function isUsPublicHoliday(Carbon $date): bool
    {
        try {
            $response = Http::timeout(6)
                ->acceptJson()
                ->get("https://date.nager.at/api/v3/PublicHolidays/{$date->year}/US");

            if (!$response->ok()) {
                return false;
            }

            $holidays = $response->json();
            if (!is_array($holidays)) {
                return false;
            }

            $targetDate = $date->toDateString();
            foreach ($holidays as $holiday) {
                if (($holiday['date'] ?? null) === $targetDate) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    private function isChineseNewYearDate(Carbon $date): bool
    {
        $chineseNewYearByYear = [
            2024 => '2024-02-10',
            2025 => '2025-01-29',
            2026 => '2026-02-17',
            2027 => '2027-02-06',
            2028 => '2028-01-26',
            2029 => '2029-02-13',
            2030 => '2030-02-03',
            2031 => '2031-01-23',
            2032 => '2032-02-11',
            2033 => '2033-01-31',
            2034 => '2034-02-19',
            2035 => '2035-02-08',
        ];

        $target = $chineseNewYearByYear[$date->year] ?? null;
        return $target === $date->toDateString();
    }

    private function buildHolidayPresentEmployees(?string $fromDate, ?string $selectedJobType = null, $employeeJobTypeMap = null)
    {
        $attendanceDate = null;
        if ($fromDate) {
            try {
                $attendanceDate = Carbon::parse($fromDate)->startOfDay();
            } catch (\Throwable $e) {
                $attendanceDate = null;
            }
        }

        // Use the Admin Employee master list as source of truth.
        $employees = User::query()
            ->with('employee')
            ->where('role', 'Employee')
            ->whereHas('employee', function ($query) {
                $query->whereNotNull('employee_id')
                    ->where('employee_id', '!=', '');
            })
            ->orderBy('id')
            ->get();

        if ($selectedJobType && $employeeJobTypeMap) {
            $employees = $employees
                ->filter(function ($user) use ($employeeJobTypeMap, $selectedJobType) {
                    $employeeId = $this->normalizeEmployeeId($user->employee?->employee_id);
                    $employeeJobType = $this->normalizeJobType($employeeJobTypeMap->get($employeeId));
                    return $employeeJobType === $selectedJobType;
                })
                ->values();
        }

        return $employees
            ->map(function ($user) use ($attendanceDate, $employeeJobTypeMap) {
                $employeeProfile = $user->employee;
                $name = $this->formatEmployeeDisplayName(
                    $user->first_name,
                    $user->middle_name,
                    $user->last_name
                );
                $employeeId = $this->normalizeEmployeeId($employeeProfile?->employee_id);
                $jobType = $this->normalizeJobType($employeeJobTypeMap?->get($employeeId));

                return (object) [
                    'employee_id' => (string) ($employeeProfile?->employee_id ?? ''),
                    'employee_name' => $name,
                    'job_type' => $jobType,
                    'main_gate' => 'Holiday - No Class',
                    'attendance_date' => $attendanceDate,
                    'morning_in' => null,
                    'morning_out' => null,
                    'afternoon_in' => null,
                    'afternoon_out' => null,
                    'late_minutes' => 0,
                    'computed_late_minutes' => 0,
                    'missing_time_logs' => [],
                    'is_absent' => false,
                    'is_tardy_by_rule' => false,
                    'is_holiday_present' => true,
                ];
            })
            ->values();
    }

    private function isPresentByTimeWindow($row): bool
    {
        return $this->isTimeWithinRange($row->morning_in, '03:00:00', '08:15:00')
            && $this->isTimeWithinRange($row->morning_out, '11:55:00', '12:45:00')
            && $this->isTimeWithinRange($row->afternoon_in, '12:45:00', '13:15:00')
            && $this->isTimeWithinRange($row->afternoon_out, '17:00:00', '20:00:00');
    }

    private function isTimeWithinRange(?string $time, string $start, string $end): bool
    {
        if (!$time) {
            return false;
        }

        try {
            $timeValue = Carbon::createFromFormat('H:i:s', $time)->format('H:i:s');
            return $timeValue >= $start && $timeValue <= $end;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function calculateLateMinutesFromInTimes($row): int
    {
        $late = 0;

        if ($row->morning_in) {
            try {
                $morningActual = Carbon::createFromFormat('H:i:s', $row->morning_in);
                $morningExpected = Carbon::createFromFormat('H:i:s', '08:00:00');
                $morningGraceEnd = Carbon::createFromFormat('H:i:s', '08:15:00');
                if ($morningActual->greaterThan($morningGraceEnd)) {
                    $late += $morningExpected->diffInMinutes($morningActual);
                }
            } catch (\Throwable $e) {
            }
        }

        if ($row->afternoon_in) {
            try {
                $afternoonActual = Carbon::createFromFormat('H:i:s', $row->afternoon_in);
                $afternoonExpected = Carbon::createFromFormat('H:i:s', '13:00:00');
                $afternoonGraceEnd = Carbon::createFromFormat('H:i:s', '13:15:00');
                if ($afternoonActual->greaterThan($afternoonGraceEnd)) {
                    $late += $afternoonExpected->diffInMinutes($afternoonActual);
                }
            } catch (\Throwable $e) {
            }
        }

        return $late;
    }

    private function buildMissingEmployeeAbsences($records, ?string $fromDate, ?string $selectedJobType = null, $employeeJobTypeMap = null, $employeeDepartmentMap = null)
    {
        if ($fromDate) {
            try {
                $normalizedDate = Carbon::parse($fromDate)->toDateString();
                if ($this->isSundayDate($normalizedDate) || $this->isHolidayDate($normalizedDate)) {
                    return collect();
                }
            } catch (\Throwable $e) {
            }
        }

        $recordedEmployeeIds = $records
            ->pluck('employee_id')
            ->map(fn ($id) => $this->normalizeEmployeeId($id))
            ->filter()
            ->values()
            ->all();

        $employees = Employee::query()
            ->with('user:id,first_name,middle_name,last_name,role,status')
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
            ->orderBy('employee_id')
            ->get();

        if ($selectedJobType && $employeeJobTypeMap) {
            $employees = $employees
                ->filter(function ($employee) use ($employeeJobTypeMap, $selectedJobType) {
                    $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                    $employeeJobType = $this->normalizeJobType($employeeJobTypeMap->get($employeeId));
                    return $employeeJobType === $selectedJobType;
                })
                ->values();
        }

        $attendanceDate = null;
        if ($fromDate) {
            try {
                $attendanceDate = Carbon::parse($fromDate)->startOfDay();
            } catch (\Throwable $e) {
                $attendanceDate = null;
            }
        }

        return $employees
            ->reject(function ($employee) use ($recordedEmployeeIds) {
                $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                return in_array($employeeId, $recordedEmployeeIds, true);
            })
            ->map(function ($employee) use ($attendanceDate, $employeeJobTypeMap, $employeeDepartmentMap) {
                $user = $employee->user;
                $name = $this->formatEmployeeDisplayName(
                    $user?->first_name,
                    $user?->middle_name,
                    $user?->last_name
                );
                $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                $jobType = $this->normalizeJobType($employeeJobTypeMap?->get($employeeId));

                return (object) [
                    'employee_id' => (string) $employee->employee_id,
                    'employee_name' => $name,
                    'department' => $employeeDepartmentMap?->get($employeeId),
                    'job_type' => $jobType,
                    'main_gate' => null,
                    'attendance_date' => $attendanceDate,
                    'morning_in' => null,
                    'morning_out' => null,
                    'afternoon_in' => null,
                    'afternoon_out' => null,
                    'late_minutes' => 0,
                    'computed_late_minutes' => 0,
                    'missing_time_logs' => ['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'],
                    'is_absent' => true,
                    'is_tardy_by_rule' => false,
                ];
            })
            ->values();
    }

    private function buildMissingEmployeeAbsencesForRange($records, string $startDate, string $endDate, ?string $selectedJobType = null, $employeeJobTypeMap = null, $employeeDepartmentMap = null)
    {
        $recordedEmployeeDateKeys = collect($records)
            ->filter(function ($row) {
                return !empty($row->employee_id) && !empty($row->attendance_date);
            })
            ->map(function ($row) {
                $employeeId = $this->normalizeEmployeeId($row->employee_id);
                if ($employeeId === '') {
                    return null;
                }

                try {
                    $date = Carbon::parse($row->attendance_date)->toDateString();
                } catch (\Throwable $e) {
                    $date = null;
                }

                if (!$date) {
                    return null;
                }

                return $employeeId.'|'.$date;
            })
            ->filter()
            ->flip();

        $employees = Employee::query()
            ->with('user:id,first_name,middle_name,last_name,role,status')
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
            ->whereHas('user', function ($query) {
                $query->where('role', 'Employee')
                    ->where('status', 'Approved');
            })
            ->orderBy('employee_id')
            ->get();

        if ($selectedJobType && $employeeJobTypeMap) {
            $employees = $employees
                ->filter(function ($employee) use ($employeeJobTypeMap, $selectedJobType) {
                    $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                    $employeeJobType = $this->normalizeJobType($employeeJobTypeMap->get($employeeId));
                    return $employeeJobType === $selectedJobType;
                })
                ->values();
        }

        $absences = collect();
        $current = Carbon::parse($startDate)->startOfDay();
        $last = Carbon::parse($endDate)->startOfDay();

        while ($current->lte($last)) {
            $date = $current->toDateString();

            if ($this->isSundayDate($date) || $this->isHolidayDate($date)) {
                $current->addDay();
                continue;
            }

            foreach ($employees as $employee) {
                $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                if ($employeeId === '') {
                    continue;
                }

                $employeeDateKey = $employeeId.'|'.$date;
                if ($recordedEmployeeDateKeys->has($employeeDateKey)) {
                    continue;
                }

                $user = $employee->user;
                $name = $this->formatEmployeeDisplayName(
                    $user?->first_name,
                    $user?->middle_name,
                    $user?->last_name
                );
                 $jobType = $this->normalizeJobType($employeeJobTypeMap?->get($employeeId));

                $absences->push((object) [
                    'employee_id' => (string) $employee->employee_id,
                    'employee_name' => $name,
                    'department' => $employeeDepartmentMap?->get($employeeId),
                    'job_type' => $jobType,
                    'main_gate' => null,
                    'attendance_date' => Carbon::parse($date)->startOfDay(),
                    'morning_in' => null,
                    'morning_out' => null,
                    'afternoon_in' => null,
                    'afternoon_out' => null,
                    'late_minutes' => 0,
                    'computed_late_minutes' => 0,
                    'missing_time_logs' => ['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'],
                    'is_absent' => true,
                    'is_tardy_by_rule' => false,
                ]);
            }

            $current->addDay();
        }

        return $absences
            ->sortBy(function ($row) {
                $date = $this->formatAttendanceDateValue($row->attendance_date ?? null) ?? '';
                return $date.'|'.$this->normalizeEmployeeId($row->employee_id);
            })
            ->values();
    }

    private function expandRecordsForDateRange(
        $records,
        string $startDate,
        string $endDate,
        ?string $selectedJobType = null,
        $employeeJobTypeMap = null,
        $employeeDepartmentMap = null
    ) {
        $existingByEmployeeDate = collect($records)
            ->filter(function ($row) {
                return !empty($row->employee_id) && !empty($row->attendance_date);
            })
            ->sortByDesc('id')
            ->reduce(function ($carry, $row) {
                $employeeId = $this->normalizeEmployeeId($row->employee_id);
                if ($employeeId === '') {
                    return $carry;
                }

                $date = $this->formatAttendanceDateValue($row->attendance_date ?? null);

                if (!$date) {
                    return $carry;
                }

                $key = $employeeId.'|'.$date;
                if (!$carry->has($key)) {
                    $carry->put($key, $row);
                }

                return $carry;
            }, collect());

        $employees = Employee::query()
            ->with('user:id,first_name,middle_name,last_name,role,status')
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
            ->orderBy('employee_id')
            ->get();

        if ($selectedJobType && $employeeJobTypeMap) {
            $employees = $employees
                ->filter(function ($employee) use ($employeeJobTypeMap, $selectedJobType) {
                    $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                    $employeeJobType = $this->normalizeJobType($employeeJobTypeMap->get($employeeId));
                    return $employeeJobType === $selectedJobType;
                })
                ->values();
        }

        $expanded = collect();
        $current = Carbon::parse($startDate)->startOfDay();
        $last = Carbon::parse($endDate)->startOfDay();

        while ($current->lte($last)) {
            $date = $current->toDateString();

            foreach ($employees as $employee) {
                $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                if ($employeeId === '') {
                    continue;
                }

                $key = $employeeId.'|'.$date;
                $existing = $existingByEmployeeDate->get($key);

                if ($existing) {
                    $expanded->push($existing);
                    continue;
                }

                $user = $employee->user;
                $name = $this->formatEmployeeDisplayName(
                    $user?->first_name,
                    $user?->middle_name,
                    $user?->last_name
                );

                $expanded->push((object) [
                    'employee_id' => (string) $employee->employee_id,
                    'employee_name' => $name,
                    'department' => $employeeDepartmentMap?->get($employeeId),
                    'job_type' => $this->normalizeJobType($employeeJobTypeMap?->get($employeeId)),
                    'main_gate' => null,
                    'attendance_date' => Carbon::parse($date)->startOfDay(),
                    'morning_in' => null,
                    'morning_out' => null,
                    'afternoon_in' => null,
                    'afternoon_out' => null,
                    'late_minutes' => 0,
                    'computed_late_minutes' => 0,
                    'missing_time_logs' => ['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'],
                    'is_absent' => true,
                    'is_tardy_by_rule' => false,
                    'is_holiday_present' => false,
                ]);
            }

            $current->addDay();
        }

        return $expanded
            ->sortBy(function ($row) {
                $date = $this->formatAttendanceDateValue($row->attendance_date ?? null) ?? '';
                return $date.'|'.$this->normalizeEmployeeId($row->employee_id);
            })
            ->values();
    }

    private function appendHolidayPresentRowsForRange(
        $records,
        string $startDate,
        string $endDate,
        ?string $selectedJobType = null,
        $employeeJobTypeMap = null
    ) {
        $datesWithAnyRecord = collect($records)
            ->map(function ($row) {
                try {
                    return $row->attendance_date ? Carbon::parse($row->attendance_date)->toDateString() : null;
                } catch (\Throwable $e) {
                    return null;
                }
            })
            ->filter()
            ->unique()
            ->flip();

        $existingKeys = collect($records)
            ->filter(function ($row) {
                return !empty($row->employee_id) && !empty($row->attendance_date);
            })
            ->map(function ($row) {
                $employeeId = $this->normalizeEmployeeId($row->employee_id);
                if ($employeeId === '') {
                    return null;
                }

                try {
                    $date = Carbon::parse($row->attendance_date)->toDateString();
                } catch (\Throwable $e) {
                    return null;
                }

                return $date ? ($employeeId.'|'.$date) : null;
            })
            ->filter()
            ->flip();

        $current = Carbon::parse($startDate)->startOfDay();
        $last = Carbon::parse($endDate)->startOfDay();
        $holidayRows = collect();

        while ($current->lte($last)) {
            $date = $current->toDateString();
            if (!$this->isSundayDate($date) && $this->isHolidayDate($date)) {
                // If the date already has any attendance records, do not auto-fill missing
                // employees as present for that holiday date.
                if ($datesWithAnyRecord->has($date)) {
                    $current->addDay();
                    continue;
                }

                $dailyHolidayRows = $this->buildHolidayPresentEmployees($date, $selectedJobType, $employeeJobTypeMap)
                    ->filter(function ($row) use ($existingKeys) {
                        $employeeId = $this->normalizeEmployeeId($row->employee_id);
                        if ($employeeId === '' || empty($row->attendance_date)) {
                            return false;
                        }

                        try {
                            $date = Carbon::parse($row->attendance_date)->toDateString();
                        } catch (\Throwable $e) {
                            return false;
                        }

                        $key = $employeeId.'|'.$date;
                        if ($existingKeys->has($key)) {
                            return false;
                        }

                        $existingKeys->put($key, true);
                        return true;
                    })
                    ->values();

                $holidayRows = $holidayRows->concat($dailyHolidayRows);
                $datesWithAnyRecord->put($date, true);
            }

            $current->addDay();
        }

        if ($holidayRows->isEmpty()) {
            return collect($records);
        }

        return collect($records)
            ->concat($holidayRows)
            ->sortBy(function ($row) {
                $date = '';
                try {
                    $date = $row->attendance_date ? Carbon::parse($row->attendance_date)->toDateString() : '';
                } catch (\Throwable $e) {
                    $date = '';
                }

                return $date.'|'.$this->normalizeEmployeeId($row->employee_id);
            })
            ->values();
    }

    private function filterAttendanceRowsByEmployeeName($rows, string $searchName)
    {
        $needle = strtolower(trim($searchName));
        if ($needle === '') {
            return collect($rows)->values();
        }

        return collect($rows)
            ->filter(function ($row) use ($needle) {
                $name = strtolower(trim((string) ($row->employee_name ?? '')));
                return $name !== '' && str_contains($name, $needle);
            })
            ->values();
    }

    private function formatEmployeeDisplayName($firstName, $middleName, $lastName): ?string
    {
        $first = trim((string) ($firstName ?? ''));
        $middle = trim((string) ($middleName ?? ''));
        $last = trim((string) ($lastName ?? ''));

        $firstMiddle = trim(implode(' ', array_filter([$first, $middle], fn ($part) => $part !== '')));

        if ($last !== '' && $firstMiddle !== '') {
            return "{$last}, {$firstMiddle}";
        }

        if ($last !== '') {
            return $last;
        }

        if ($firstMiddle !== '') {
            return $firstMiddle;
        }

        return null;
    }

    private function normalizeLooseDisplayName($name): ?string
    {
        $value = trim((string) ($name ?? ''));
        if ($value === '') {
            return null;
        }

        // Preserve any existing delimiter style when source parts are unavailable.
        return preg_replace('/\s+/', ' ', $value);
    }

    private function normalizeJobType($value): ?string // Normalizes various user inputs for job type into consistent values used in the system. Returns null for empty or unrecognized inputs.
    {
        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['teaching', 't'], true)) {
            return 'Teaching';
        }

        if (in_array($normalized, ['non-teaching', 'non teaching', 'nonteaching', 'nt'], true)) {
            return 'Non-Teaching';
        }

        return ucwords($normalized);
    }

    private function normalizeEmployeeId($value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        // Excel often exports numeric IDs as "123.0"; map these back to the base ID.
        if (preg_match('/^(\d+)\.0+$/', $normalized, $matches)) {
            return $matches[1];
        }

        return $normalized;
    }

    private function normalizeFilterDate(?string $fromDate): ?string
    {
        if (!$fromDate) {
            return null;
        }

        try {
            return Carbon::parse($fromDate)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function display_leave(Request $request){
        $selectedMonth = trim((string) $request->query('month', now()->format('Y-m')));
        try {
            $monthCursor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthCursor = now()->startOfMonth();
            $selectedMonth = $monthCursor->format('Y-m');
        }

        $monthApplications = LeaveApplication::query()
            ->where(function ($query) use ($monthCursor) {
                $query
                    ->where(function ($filingDateQuery) use ($monthCursor) {
                        $filingDateQuery
                            ->whereNotNull('filing_date')
                            ->whereYear('filing_date', $monthCursor->year)
                            ->whereMonth('filing_date', $monthCursor->month);
                    })
                    ->orWhere(function ($createdAtQuery) use ($monthCursor) {
                        $createdAtQuery
                            ->whereNull('filing_date')
                            ->whereYear('created_at', $monthCursor->year)
                            ->whereMonth('created_at', $monthCursor->month);
                    });
            })
            ->orderByDesc('created_at')
            ->get();

        $approvedMonthApplications = $monthApplications
            ->filter(function ($application) {
                return strcasecmp((string) ($application->status ?? ''), 'Approved') === 0;
            })
            ->values();

        $monthRecords = $approvedMonthApplications
            ->map(function ($application) {
                $baseDate = $application->filing_date
                    ? Carbon::parse($application->filing_date)->startOfDay()
                    : Carbon::parse($application->created_at)->startOfDay();
                $days = (float) ($application->number_of_working_days ?? 0);
                if ($days <= 0) {
                    $days = max(
                        (float) ($application->days_with_pay ?? 0),
                        (float) ($application->applied_total ?? 0)
                    );
                }
                $rangeDays = max((int) ceil($days), 1);

                return [
                    'employee_name' => $application->employee_name ?? '-',
                    'leave_type' => $application->leave_type ?: 'Leave',
                    'start_date_carbon' => $baseDate->copy(),
                    'end_date_carbon' => $baseDate->copy()->addDays($rangeDays - 1),
                    'days' => $days,
                    'reason' => $application->inclusive_dates ?: '-',
                ];
            })
            ->values();

        $totalLeaveUsedDays = (int) $monthRecords->sum('days');
        $sickLeaveUsedDays = (int) $monthRecords
            ->filter(fn ($record) => strcasecmp((string) $record['leave_type'], 'Sick Leave') === 0)
            ->sum('days');

        $leaveTypeCounts = $monthRecords
            ->groupBy(fn ($record) => (string) ($record['leave_type'] ?? 'Leave'))
            ->map(fn ($records) => (int) $records->sum('days'));

        $buildRequestTypeBreakdown = function ($applications) {
            return collect($applications)
                ->groupBy(fn ($application) => (string) ($application->leave_type ?: 'Leave'))
                ->map(function ($applications, $leaveType) {
                    $days = $applications->sum(function ($application) {
                        $workingDays = (float) ($application->number_of_working_days ?? 0);

                        return $workingDays > 0
                            ? $workingDays
                            : max(
                                (float) ($application->days_with_pay ?? 0),
                                (float) ($application->applied_total ?? 0)
                            );
                    });

                    return [
                        'type' => (string) $leaveType,
                        'count' => $applications->count(),
                        'days' => round((float) $days, 1),
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->all();
        };

        $rejectedMonthApplications = $monthApplications
            ->filter(fn ($application) => strcasecmp(trim((string) ($application->status ?? '')), 'Rejected') === 0)
            ->values();
        $approvedSickApplications = $approvedMonthApplications
            ->filter(fn ($application) => str_contains(strtolower((string) ($application->leave_type ?? '')), 'sick'))
            ->values();
        $leaveSummaryBreakdowns = [
            'leave_used' => $buildRequestTypeBreakdown($approvedMonthApplications),
            'sick_used' => $buildRequestTypeBreakdown($approvedSickApplications),
            'approved' => $buildRequestTypeBreakdown($approvedMonthApplications),
            'rejected' => $buildRequestTypeBreakdown($rejectedMonthApplications),
        ];

        $allPendingLeaveRequests = $monthApplications
            ->filter(function ($application) {
                $status = trim((string) ($application->status ?? ''));
                return $status === '' || strcasecmp($status, 'Pending') === 0;
            })
            ->sortByDesc('created_at')
            ->values();

        $pendingRequestCount = $allPendingLeaveRequests->count();
        $rejectedRequestCount = $rejectedMonthApplications->count();
        $pendingLeaveDays = (float) $allPendingLeaveRequests->sum(function ($row) {
            return (float) ($row->number_of_working_days ?? 0);
        });
        $pendingLeaveRequests = $allPendingLeaveRequests->take(5)->values();
        $recentMonthRecords = $monthRecords->values();
        $leaveSnapshotToken = $this->buildLeaveManagementSnapshotToken($monthApplications);

        return view('Admin.adminLeaveManagement', compact(
            'selectedMonth',
            'totalLeaveUsedDays',
            'sickLeaveUsedDays',
            'monthRecords',
            'leaveTypeCounts',
            'pendingLeaveRequests',
            'pendingLeaveDays',
            'pendingRequestCount',
            'rejectedRequestCount',
            'leaveSummaryBreakdowns',
            'recentMonthRecords',
            'leaveSnapshotToken'
        ));
    }

    public function leave_management_snapshot(Request $request)
    {
        $selectedMonth = trim((string) $request->query('month', now()->format('Y-m')));
        try {
            $monthCursor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthCursor = now()->startOfMonth();
            $selectedMonth = $monthCursor->format('Y-m');
        }

        $monthApplications = LeaveApplication::query()
            ->where(function ($query) use ($monthCursor) {
                $query
                    ->where(function ($filingDateQuery) use ($monthCursor) {
                        $filingDateQuery
                            ->whereNotNull('filing_date')
                            ->whereYear('filing_date', $monthCursor->year)
                            ->whereMonth('filing_date', $monthCursor->month);
                    })
                    ->orWhere(function ($createdAtQuery) use ($monthCursor) {
                        $createdAtQuery
                            ->whereNull('filing_date')
                            ->whereYear('created_at', $monthCursor->year)
                            ->whereMonth('created_at', $monthCursor->month);
                    });
            })
            ->orderByDesc('created_at')
            ->get(['id', 'status', 'updated_at']);

        $pendingCount = $monthApplications->filter(function ($application) {
            $status = trim((string) ($application->status ?? ''));

            return $status === '' || strcasecmp($status, 'Pending') === 0;
        })->count();

        return response()->json([
            'month' => $selectedMonth,
            'token' => $this->buildLeaveManagementSnapshotToken($monthApplications),
            'total' => $monthApplications->count(),
            'pending' => $pendingCount,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function buildLeaveManagementSnapshotToken($applications): string
    {
        $snapshot = collect($applications)
            ->map(fn ($application) => [
                'id' => (int) $application->id,
                'status' => strtolower(trim((string) ($application->status ?? 'pending'))),
                'updated_at' => optional($application->updated_at)?->format('Y-m-d H:i:s.u'),
            ])
            ->values()
            ->all();

        return hash('sha256', json_encode($snapshot));
    }

    public function display_payslip(){
        $payslipFiles = PayslipUpload::query()
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $uploadedCount = (int) PayslipUpload::query()->count();
        $scannedCount = (int) PayslipUpload::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN (?, ?)", ['scanned', 'processed'])
            ->count();
        $latestUpload = PayslipUpload::query()
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->first();

        return view('Admin.adminPayslip', compact('payslipFiles', 'uploadedCount', 'scannedCount', 'latestUpload'));
    }

    public function display_payslip_view(Request $request){
        $uploadId = (int) $request->query('upload_id', 0);
        $recordId = (int) $request->query('record_id', 0);

        $baseRecordsQuery = PayslipRecord::query()
            ->when($uploadId > 0, fn ($query) => $query->where('payslip_upload_id', $uploadId))
            ->whereNotNull('employee_id')
            ->whereRaw("TRIM(COALESCE(employee_id, '')) <> ''");

        // Get latest row per normalized employee_id directly in SQL for better performance.
        $latestRecordIdsQuery = (clone $baseRecordsQuery)
            ->selectRaw('MAX(id) as id')
            ->groupBy(DB::raw('LOWER(TRIM(employee_id))'));

        $records = PayslipRecord::query()
            ->with('upload:id,original_name,uploaded_at')
            ->whereIn('id', $latestRecordIdsQuery)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->paginate(60)
            ->withQueryString();
        $selectedRecord = null;

        if ($recordId > 0) {
            $selectedRecord = PayslipRecord::query()
                ->with('upload:id,original_name,uploaded_at')
                ->when($uploadId > 0, fn ($query) => $query->where('payslip_upload_id', $uploadId))
                ->where('id', $recordId)
                ->first();

            if (!$selectedRecord && $records instanceof \Illuminate\Contracts\Pagination\Paginator) {
                $selectedRecord = collect($records->items())->firstWhere('id', $recordId);
            }
        }



        return view('Admin.adminPaySlipView', compact('records', 'selectedRecord', 'uploadId'));
    }

    public function display_resignations(Request $request){
        $selectedStatus = trim((string) $request->query('status', 'All'));
        $search = trim((string) $request->query('search', ''));
        $excludeCancelled = function ($query) {
            return $query->whereRaw("LOWER(TRIM(COALESCE(status, ''))) <> ?", ['cancelled']);
        };

        $resignationsQuery = Resignation::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email',
                'processor:id,first_name,last_name',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
        $excludeCancelled($resignationsQuery);

        if ($selectedStatus !== '' && strcasecmp($selectedStatus, 'All') !== 0) {
            $resignationsQuery->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", [strtolower($selectedStatus)]);
        }

        if ($search !== '') {
            $needle = strtolower($search);
            $resignationsQuery->where(function ($query) use ($needle) {
                $query
                    ->orWhereRaw("LOWER(COALESCE(employee_name, '')) LIKE ?", ['%'.$needle.'%'])
                    ->orWhereRaw("LOWER(COALESCE(employee_id, '')) LIKE ?", ['%'.$needle.'%'])
                    ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%'.$needle.'%'])
                    ->orWhereRaw("LOWER(COALESCE(position, '')) LIKE ?", ['%'.$needle.'%']);
            });
        }

        $resignations = $resignationsQuery->get();

        $pendingResignations = Resignation::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email',
                'processor:id,first_name,last_name',
            ])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $employees = User::query()
            ->with('employee:user_id,employee_id,department,position')
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->orderBy('first_name')
            ->get();

        $statusCounts = [
            'Pending' => (int) $excludeCancelled(Resignation::query())->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])->count(),
            'Approved' => (int) $excludeCancelled(Resignation::query())->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])->count(),
            'Completed' => (int) $excludeCancelled(Resignation::query())->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['completed'])->count(),
            'Rejected' => (int) $excludeCancelled(Resignation::query())->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['rejected'])->count(),
            'Cancelled' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['cancelled'])->count(),
        ];

        return view('Admin.adminResignations', compact(
            'resignations',
            'pendingResignations',
            'employees',
            'statusCounts',
            'selectedStatus',
            'search'
        ));
    }

    public function resignations_snapshot(Request $request)
    {
        $selectedStatus = trim((string) $request->query('status', 'All'));
        $search = trim((string) $request->query('search', ''));

        $recordsQuery = Resignation::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) <> ?", ['cancelled'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($selectedStatus !== '' && strcasecmp($selectedStatus, 'All') !== 0) {
            $recordsQuery->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", [strtolower($selectedStatus)]);
        }

        if ($search !== '') {
            $needle = strtolower($search);
            $recordsQuery->where(function ($query) use ($needle) {
                $query
                    ->orWhereRaw("LOWER(COALESCE(employee_name, '')) LIKE ?", ['%'.$needle.'%'])
                    ->orWhereRaw("LOWER(COALESCE(employee_id, '')) LIKE ?", ['%'.$needle.'%'])
                    ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%'.$needle.'%'])
                    ->orWhereRaw("LOWER(COALESCE(position, '')) LIKE ?", ['%'.$needle.'%']);
            });
        }

        $records = $recordsQuery
            ->get(['id', 'status', 'admin_note', 'attachment_path', 'attachment_name', 'submitted_at', 'effective_date', 'updated_at'])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'status' => (string) ($row->status ?? ''),
                'admin_note' => (string) ($row->admin_note ?? ''),
                'attachment_path' => (string) ($row->attachment_path ?? ''),
                'attachment_name' => (string) ($row->attachment_name ?? ''),
                'submitted_at' => optional($row->submitted_at)->toDateTimeString(),
                'effective_date' => optional($row->effective_date)->toDateString(),
                'updated_at' => optional($row->updated_at)->toDateTimeString(),
            ])
            ->values();

        $pendingRecords = Resignation::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get(['id', 'status', 'attachment_path', 'attachment_name', 'submitted_at', 'effective_date', 'updated_at'])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'status' => (string) ($row->status ?? ''),
                'attachment_path' => (string) ($row->attachment_path ?? ''),
                'attachment_name' => (string) ($row->attachment_name ?? ''),
                'submitted_at' => optional($row->submitted_at)->toDateTimeString(),
                'effective_date' => optional($row->effective_date)->toDateString(),
                'updated_at' => optional($row->updated_at)->toDateTimeString(),
            ])
            ->values();

        $statusCounts = [
            'Pending' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) <> ?", ['cancelled'])->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])->count(),
            'Approved' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) <> ?", ['cancelled'])->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])->count(),
            'Completed' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) <> ?", ['cancelled'])->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['completed'])->count(),
            'Rejected' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) <> ?", ['cancelled'])->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['rejected'])->count(),
            'Cancelled' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['cancelled'])->count(),
        ];

        $payload = [
            'records' => $records,
            'pending' => $pendingRecords,
            'statusCounts' => $statusCounts,
        ];

        return response()->json([
            'signature' => md5(json_encode($payload)),
            'recordCount' => $records->count(),
            'pendingCount' => $pendingRecords->count(),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function display_reports(Request $request){
        $selectedMonth = trim((string) $request->query('month', now()->format('Y-m')));
        try {
            $monthCursor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthCursor = now()->startOfMonth();
            $selectedMonth = $monthCursor->format('Y-m');
        }

        $monthStart = $monthCursor->copy()->startOfMonth();
        $monthEnd = $monthCursor->copy()->endOfMonth();

        $approvedEmployees = User::query()
            ->with('employee')
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get();

        $totalEmployees = $approvedEmployees->count();
        $departmentCounts = $approvedEmployees
            ->groupBy(function ($user) {
                $department = trim((string) ($user->department ?? ''));
                if ($department === '') {
                    $department = trim((string) ($user->employee?->department ?? ''));
                }

                return $department !== '' ? $department : 'Unassigned';
            })
            ->map(fn ($rows) => $rows->count())
            ->sortDesc()
            ->take(8);

        $resolveReportJobType = function ($value): string {
            $normalized = strtolower(trim((string) $value));
            if ($normalized === '') {
                return 'Unassigned';
            }

            if (in_array($normalized, ['teaching', 't', 't/ft', 't/pt', 'full-time', 'part-time', 'full time', 'part time'], true)) {
                return 'Teaching';
            }

            if (in_array($normalized, ['non-teaching', 'non teaching', 'nonteaching', 'nt'], true)) {
                return 'Non-Teaching';
            }

            return $this->normalizeJobType($normalized) ?? ucwords($normalized);
        };

        $jobTypeCounts = $approvedEmployees
            ->groupBy(function ($user) use ($resolveReportJobType) {
                $jobType = trim((string) ($user->employee?->job_type ?? ''));
                if ($jobType === '') {
                    $jobType = trim((string) ($user->employee?->classification ?? ''));
                }

                return $jobType !== '' ? $resolveReportJobType($jobType) : 'Unassigned';
            })
            ->map(fn ($rows) => $rows->count())
            ->sortDesc();

        $genderCounts = [
            'male' => $approvedEmployees
                ->filter(fn ($user) => strcasecmp(trim((string) ($user->employee?->sex ?? '')), 'Male') === 0)
                ->count(),
            'female' => $approvedEmployees
                ->filter(fn ($user) => strcasecmp(trim((string) ($user->employee?->sex ?? '')), 'Female') === 0)
                ->count(),
        ];
        $headUserIds = $approvedEmployees
            ->filter(fn ($user) => strcasecmp(trim((string) ($user->department_head ?? '')), 'Approved') === 0)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $coordinatorUserIds = $approvedEmployees
            ->filter(function ($user) {
                $position = strtolower(trim((string) ($user->employee?->position ?? '')));
                $classification = strtolower(trim((string) ($user->employee?->classification ?? '')));

                return str_contains($position, 'coordinator') || str_contains($classification, 'coordinator');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $headOrCoordinatorUserIds = $headUserIds
            ->concat($coordinatorUserIds)
            ->unique()
            ->values();
        $roleGroupCounts = [
            'heads' => $headUserIds->count(),
            'coordinators' => $coordinatorUserIds->count(),
            'staff' => max($totalEmployees - $headOrCoordinatorUserIds->count(), 0),
            'teaching' => (int) ($jobTypeCounts->get('Teaching', 0) ?? 0),
            'non_teaching' => (int) ($jobTypeCounts->get('Non-Teaching', 0) ?? 0),
        ];

        $joinYearCounts = $approvedEmployees
            ->map(function ($user) {
                $joinDate = $user->employee?->employement_date;
                if (empty($joinDate)) {
                    return null;
                }

                try {
                    return Carbon::parse($joinDate)->format('Y');
                } catch (\Throwable $e) {
                    return null;
                }
            })
            ->filter()
            ->countBy()
            ->sortKeys();
        $joinYearRange = collect(range(now()->year - 11, now()->year));
        $joinYearCounts = $joinYearRange
            ->mapWithKeys(fn ($year) => [(string) $year => (int) ($joinYearCounts->get((string) $year, 0))]);

        $attendanceTotal = 0;
        $attendanceAbsent = 0;
        $attendancePresent = 0;
        $attendanceTardy = 0;
        $attendanceRate = 0;

        $leaveApplications = LeaveApplication::query()
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('filing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhere(function ($createdQuery) use ($monthStart, $monthEnd) {
                        $createdQuery->whereNull('filing_date')
                            ->whereBetween('created_at', [$monthStart, $monthEnd]);
                    });
            })
            ->get();
        $leaveStatusCounts = $leaveApplications
            ->groupBy(fn ($row) => trim((string) ($row->status ?? 'Pending')) ?: 'Pending')
            ->map(fn ($rows) => $rows->count())
            ->sortDesc();
        $leaveTypeDays = $leaveApplications
            ->groupBy(fn ($row) => trim((string) ($row->leave_type ?? 'Leave')) ?: 'Leave')
            ->map(fn ($rows) => round((float) $rows->sum('number_of_working_days'), 1))
            ->sortDesc()
            ->take(6);

        $resignationStatusCounts = Resignation::query()
            ->get()
            ->groupBy(fn ($row) => trim((string) ($row->status ?? 'Pending')) ?: 'Pending')
            ->map(fn ($rows) => $rows->count())
            ->sortDesc();

        $documentCount = ApplicantDocument::query()->count();
        $monthlyDocumentCount = ApplicantDocument::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $payslipUploadCount = PayslipUpload::query()->count();
        $processedPayslipCount = PayslipUpload::query()
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN (?, ?)", ['processed', 'scanned'])
            ->count();
        $payslipRecordCount = PayslipRecord::query()->count();
        $openPositionCount = OpenPosition::query()->count();
        $conversationCount = Conversation::query()->count();
        $resignationCount = Resignation::query()->count();

        $recordVolume = collect([
            'Employees' => $totalEmployees,
            'Attendance' => $attendanceTotal,
            'Leave' => $leaveApplications->count(),
            'Documents' => $monthlyDocumentCount,
            'Payslips' => $payslipRecordCount,
            'Messages' => $conversationCount,
        ]);

        $recentActivities = ActivityLog::query()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['action', 'description', 'user_name', 'created_at']);

        return view('Admin.adminReports', compact(
            'selectedMonth',
            'totalEmployees',
            'departmentCounts',
            'jobTypeCounts',
            'genderCounts',
            'roleGroupCounts',
            'joinYearCounts',
            'attendanceTotal',
            'attendancePresent',
            'attendanceAbsent',
            'attendanceTardy',
            'attendanceRate',
            'leaveStatusCounts',
            'leaveTypeDays',
            'resignationStatusCounts',
            'documentCount',
            'monthlyDocumentCount',
            'payslipUploadCount',
            'processedPayslipCount',
            'payslipRecordCount',
            'openPositionCount',
            'conversationCount',
            'resignationCount',
            'recordVolume',
            'recentActivities'
        ));
    }

    public function display_activity_logs(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $date = trim((string) $request->query('date', ''));
        $event = trim((string) $request->query('event', ''));

        $activityLogs = ActivityLog::query()
            ->whereRaw('LOWER(TRIM(method)) != ?', ['get'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(function ($innerQuery) use ($like) {
                    $innerQuery->where('user_name', 'like', $like)
                        ->orWhere('user_email', 'like', $like)
                        ->orWhere('action', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('notes', 'like', $like);
                });
            })
            ->when($role !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(user_role)) = ?', [strtolower($role)]))
            ->when($event !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(method)) = ?', [strtolower($event)]))
            ->when($date !== '', fn ($query) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('Admin.adminActivityLogs', compact('activityLogs', 'search', 'role', 'date', 'event'));
    }

    public function display_school_administrator(){
        $administrators = User::with([
            'employee',
            'education',
            'government',
            'license',
            'salary',
            'applicant.position:id,title,department,employment,benifits',
            'applicant.documents:id,applicant_id,filename,filepath,mime_type,type',
            'applicant.degrees:id,applicant_id,degree_level,degree_name,school_name,year_finished,sort_order',
        ])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(department_head, ''))) = ?", ['approved'])
            ->orderByRaw("
                CASE
                    WHEN LOWER(TRIM(COALESCE(job_role, position, ''))) = 'president' THEN 0
                    WHEN LOWER(TRIM(COALESCE(job_role, position, ''))) LIKE 'vice president%' THEN 1
                    WHEN LOWER(TRIM(COALESCE(job_role, position, ''))) LIKE 'vice-president%' THEN 1
                    WHEN LOWER(TRIM(COALESCE(job_role, position, ''))) LIKE 'dean%' THEN 2
                    WHEN LOWER(TRIM(COALESCE(job_role, position, ''))) LIKE '%department head%' THEN 3
                    ELSE 4
                END
            ")
            ->orderByRaw("LOWER(TRIM(COALESCE(job_role, position, '')))")
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('Admin.Matrix.adminSchoolAdministrator', compact('administrators'));
    }

    public function display_non_teaching_matrix()
    {
        $nonTeachingEmployees = User::with([
            'employee',
            'education',
            'government',
            'license',
            'salary',
            'applicant.position:id,title,department,employment,benifits,job_type,skills',
            'applicant.documents:id,applicant_id,filename,filepath,mime_type,type',
            'applicant.degrees:id,applicant_id,degree_level,degree_name,school_name,year_finished,sort_order',
        ])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("NOT (TRIM(COALESCE(job_role, '')) <> '' AND TRIM(COALESCE(department_head, '')) <> '')")
            ->where(function ($query) {
                $query
                    ->whereHas('employee', function ($employeeQuery) {
                        $employeeQuery->whereRaw("LOWER(TRIM(COALESCE(job_type, ''))) IN (?, ?, ?)", ['non-teaching', 'non teaching', 'nt']);
                    })
                    ->orWhereHas('applicant.position', function ($positionQuery) {
                        $positionQuery->whereRaw("LOWER(TRIM(COALESCE(job_type, ''))) IN (?, ?, ?)", ['non-teaching', 'non teaching', 'nt']);
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('Admin.Matrix.adminNon-TeachingMatrix', compact('nonTeachingEmployees'));
    }

    public function display_teaching_matrix()
    {
        $teachingEmployees = User::with([
            'employee',
            'education',
            'government',
            'license',
            'salary',
            'applicant.position:id,title,department,employment,benifits,job_type,skills,responsibilities,requirements',
            'applicant.documents:id,applicant_id,filename,filepath,mime_type,type',
            'applicant.degrees:id,applicant_id,degree_level,degree_name,school_name,year_finished,sort_order',
        ])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->where(function ($query) {
                $query
                    ->whereHas('employee', function ($employeeQuery) {
                        $employeeQuery->whereRaw("LOWER(TRIM(COALESCE(job_type, ''))) IN (?, ?, ?)", ['teaching', 'teacher', 'faculty']);
                    })
                    ->orWhereHas('applicant.position', function ($positionQuery) {
                        $positionQuery->whereRaw("LOWER(TRIM(COALESCE(job_type, ''))) IN (?, ?, ?)", ['teaching', 'teacher', 'faculty']);
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $this->attachSubjectLoadsToEmployees($teachingEmployees);

        return view('Admin.Matrix.adminTeachingMatrix', compact('teachingEmployees'));
    }

    private function attachSubjectLoadsToEmployees($employees): void
    {
        if (!$employees || $employees->isEmpty()) {
            return;
        }

        $loadsByEmployeeName = LoadsRecord::query()
            ->select([
                'id',
                'employee_name',
                'subject_name',
                'code',
                'course_no',
                'units',
                'lec_units',
                'lab_units',
                'schedule',
                'scanned_at',
            ])
            ->whereNotNull('employee_name')
            ->where('employee_name', '!=', '')
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(function ($record) {
                return $this->normalizeLoadsEmployeeName($record->employee_name);
            });

        foreach ($employees as $employee) {
            $matchedLoads = collect($this->buildLoadsEmployeeNameVariants(
                $employee->first_name,
                $employee->middle_name,
                $employee->last_name
            ))
                ->map(fn ($variant) => $this->normalizeLoadsEmployeeName($variant))
                ->filter()
                ->unique()
                ->flatMap(function ($normalizedName) use ($loadsByEmployeeName) {
                    return $loadsByEmployeeName->get($normalizedName, collect());
                })
                ->unique(function ($record) {
                    return strtolower(trim(implode('|', [
                        (string) ($record->subject_name ?? ''),
                        (string) ($record->units ?? ''),
                        (string) ($record->lec_units ?? ''),
                        (string) ($record->lab_units ?? ''),
                        (string) ($record->schedule ?? ''),
                    ])));
                })
                ->values()
                ->map(function ($record) {
                    return [
                        'subject_name' => trim((string) ($record->subject_name ?? '')),
                        'code' => trim((string) ($record->code ?? '')),
                        'course_no' => trim((string) ($record->course_no ?? '')),
                        'units' => trim((string) ($record->units ?? '')),
                        'lec_units' => trim((string) ($record->lec_units ?? '')),
                        'lab_units' => trim((string) ($record->lab_units ?? '')),
                        'schedule' => trim((string) ($record->schedule ?? '')),
                    ];
                })
                ->filter(function ($record) {
                    return collect($record)->contains(fn ($value) => trim((string) $value) !== '');
                })
                ->values();

            $employee->setAttribute('subject_loads', $matchedLoads->all());
        }
    }

    private function buildLoadsEmployeeNameVariants($firstName, $middleName, $lastName): array
    {
        $first = trim((string) ($firstName ?? ''));
        $middle = trim((string) ($middleName ?? ''));
        $last = trim((string) ($lastName ?? ''));

        if ($first === '' && $middle === '' && $last === '') {
            return [];
        }

        $middleInitial = $middle !== '' ? strtoupper(substr($middle, 0, 1)) : '';

        return array_values(array_unique(array_filter([
            trim(implode(' ', array_filter([$first, $middle, $last]))),
            trim(implode(' ', array_filter([$first, $last]))),
            $last !== '' ? trim($last.', '.implode(' ', array_filter([$first, $middle]))) : '',
            $last !== '' ? trim($last.', '.implode(' ', array_filter([$first, $middleInitial !== '' ? $middleInitial.'.' : '']))) : '',
            $last !== '' ? trim($last.', '.implode(' ', array_filter([$first, $middleInitial]))) : '',
        ], fn ($value) => trim((string) $value) !== '')));
    }

    private function normalizeLoadsEmployeeName($value): ?string
    {
        $name = trim((string) ($value ?? ''));
        if ($name === '') {
            return null;
        }

        $name = preg_replace('/\s+/', ' ', $name);
        $name = str_replace('.', '', $name);

        return strtolower(trim($name));
    }

    public function display_loads()
    {
        $loadsFiles = LoadsUpload::query()
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->get();

        $loadsSummary = LoadsRecord::query()
            ->selectRaw('employee_name')
            ->selectRaw('COUNT(subject_name) as subject_count')
            ->selectRaw('SUM(COALESCE(CAST(units as DECIMAL(10,2)), 0)) as total_units')
            ->selectRaw('SUM(COALESCE(CAST(lec_units as DECIMAL(10,2)), 0)) as total_lec_units')
            ->selectRaw('SUM(COALESCE(CAST(lab_units as DECIMAL(10,2)), 0)) as total_lab_units')
            ->whereNotNull('employee_name')
            ->where('employee_name', '!=', '')
            ->groupBy('employee_name')
            ->orderBy('employee_name')
            ->get();

        return view('Admin.adminLoads', compact('loadsFiles', 'loadsSummary'));
    }

    public function display_applicant(){
        $payload = $this->applicantPagePayload();

        return view('Admin.adminApplicant', [
            'applicant' => $payload['applicant'],
            'hired' => $payload['hired'],
            'count_applicant' => $payload['count_applicant'],
            'count_pending' => $payload['count_pending'],
            'count_final_interview' => $payload['count_final_interview'],
            'applicantSnapshotSignature' => $payload['signature'],
        ]);
    }

    public function display_applicant_snapshot(Request $request)
    {
        $payload = $this->applicantPagePayload();
        $clientSignature = (string) $request->query('signature', '');

        if ($clientSignature !== '' && hash_equals($payload['signature'], $clientSignature)) {
            return response()
                ->json([
                    'changed' => false,
                    'signature' => $payload['signature'],
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()
            ->json([
                'changed' => true,
                'signature' => $payload['signature'],
                'current_month' => now()->format('Y-m'),
                'counts' => [
                    'total' => $payload['count_applicant'],
                    'pending' => $payload['count_pending'],
                    'interview' => $payload['count_final_interview'],
                    'hired_month' => $payload['hired'],
                ],
                'applicants' => $payload['applicant']->values(),
                'position_options' => $payload['position_options'],
                'status_options' => $payload['status_options'],
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function applicantPagePayload(): array
    {
        $this->syncFinishedInterviewApplicantStatuses();

        $applicant = Applicant::with(
            'position:id,title,department,employment,work_mode,job_description,responsibilities,requirements,experience_level,location,skills,benifits,job_type,one,two'
        )->latest('created_at')->get();
        $count_applicant = Applicant::count();
        $count_pending = $applicant->where('application_status', 'pending')->count();
        $count_final_interview = $applicant
            ->whereIn('application_status', ['Initial Interview', 'Final Interview', 'Demo Teaching'])
            ->count();
        $hired = Applicant::where('application_status', 'Hired')
            ->whereMonth('date_hired', now()->month)
            ->whereYear('date_hired', now()->year)
            ->count();
        $positionOptions = $applicant
            ->map(fn($app) => trim((string) optional($app->position)->title))
            ->filter(fn($value) => $value !== '')
            ->unique()
            ->sort()
            ->values();
        $statusOptions = $applicant
            ->map(fn($app) => trim((string) ($app->application_status ?? '')))
            ->filter(fn($value) => $value !== '')
            ->unique()
            ->sort()
            ->values();

        $signature = md5(json_encode([
            'counts' => [$count_applicant, $count_pending, $count_final_interview, $hired],
            'applicants' => $applicant->map(fn($app) => [
                'id' => $app->id,
                'first_name' => $app->first_name,
                'last_name' => $app->last_name,
                'email' => $app->email,
                'position_id' => $app->position_id,
                'position_title' => optional($app->position)->title,
                'application_status' => $app->application_status,
                'starRatings' => $app->starRatings,
                'date_hired' => optional($app->date_hired)->toDateString(),
                'created_at' => optional($app->created_at)->toDateTimeString(),
                'updated_at' => optional($app->updated_at)->toDateTimeString(),
            ])->values(),
        ]));

        return compact(
            'applicant',
            'count_applicant',
            'count_pending',
            'count_final_interview',
            'hired',
            'positionOptions',
            'statusOptions',
            'signature'
        ) + [
            'position_options' => $positionOptions,
            'status_options' => $statusOptions,
        ];
    }

    public function display_applicant_ID($id){
        $this->syncFinishedInterviewApplicantStatuses();

        $app = Applicant::with(
            'documents:id,filename,applicant_id,filepath,type,reviewed_at,reviewed_by,created_at',
            'degrees:id,applicant_id,degree_level,degree_name,school_name,year_finished,sort_order',
            'position:id,title,department,employment,work_mode,job_description,responsibilities,requirements,experience_level,location,skills,benifits,job_type,one,two'
            )->findOrFail($id);
        $comparison = $this->buildApplicantComparisonMeta($app);
        $interviews = Interviewer::query()
            ->where('applicant_id', $app->id)
            ->get();
        $visibleInterviews = $this->collapseDuplicateActiveInterviews($interviews);
        $latestInterview = $visibleInterviews
            ->sortBy(function ($interview) {
                return Carbon::parse(optional($interview->date)->toDateString().' '.$interview->time)->timestamp;
            })
            ->last();
        $completedInterviewTypes = $interviews
            ->filter(function ($interview) {
                if ($interview->ended_at) {
                    return true;
                }

                if (!$interview->date || !$interview->time) {
                    return false;
                }

                $start = Carbon::parse($interview->date->toDateString().' '.$interview->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($interview->duration));

                return now()->gte($end);
            })
            ->map(fn ($interview) => strtolower(trim((string) $interview->interview_type)))
            ->unique()
            ->values();
        $completedInitialInterview = $completedInterviewTypes->contains('initial interview');
        $completedFinalInterview = $completedInterviewTypes->contains('final interview');
        $completedDemoTeaching = $completedInterviewTypes->contains('demo teaching');
        $normalizedJobType = strtolower(trim((string) ($app->position->job_type ?? '')));
        $isTeachingApplicant = str_contains($normalizedJobType, 'teaching')
            && !str_contains($normalizedJobType, 'non');
        $interviewProceedTarget = null;
        $normalizedApplicantStatus = strtolower(trim((string) ($app->application_status ?? '')));

        if ($isTeachingApplicant) {
            if ($normalizedApplicantStatus === 'initial interview' && $completedInitialInterview && !$completedDemoTeaching) {
                $interviewProceedTarget = 'Demo Teaching';
            } elseif ($normalizedApplicantStatus === 'demo teaching' && $completedInitialInterview && $completedDemoTeaching) {
                $interviewProceedTarget = 'Passing Document';
            }
        } else {
            if ($normalizedApplicantStatus === 'initial interview' && $completedInitialInterview && !$completedFinalInterview) {
                $interviewProceedTarget = 'Final Interview';
            } elseif ($normalizedApplicantStatus === 'final interview' && $completedInitialInterview && $completedFinalInterview) {
                $interviewProceedTarget = 'Passing Document';
            }
        }

        return response()->json([
            'id' => $app->id,
            'name' => $app->first_name.' '.$app->last_name,
            'email' => $app->email,
            'title' => $app->position->title,
            'job_type' => $app->position->job_type,
            'status' => $app->application_status,
            'date_hired' => optional($app->date_hired)->toDateString(),
            'location' => $app->address,
            'one' => $app->created_at->format('F d, Y'),
            'work_position' => $app->work_position,
            'work_employer' => $app->work_employer,
            'work_location' => $app->work_location,
            'work_date_from' => optional($app->work_date_from)->toDateString(),
            'work_date_to' => optional($app->work_date_to)->toDateString(),
            'work_duration' => $app->work_duration,
            'education_background' => $app->degrees
                ->sortBy(function ($degree) {
                    $levelOrder = [
                        'elementary' => 10,
                        'secondary' => 20,
                        'vocational_trade' => 30,
                        'college' => 40,
                        'bachelor' => 50,
                        'master' => 60,
                        'doctorate' => 70,
                    ];
                    $level = strtolower(trim((string) ($degree->degree_level ?? '')));

                    return sprintf(
                        '%03d-%03d-%08d',
                        $levelOrder[$level] ?? 999,
                        (int) ($degree->sort_order ?? 0),
                        (int) ($degree->id ?? 0)
                    );
                })
                ->values()
                ->map(function ($degree) {
                    return [
                        'level' => $degree->degree_level,
                        'degree_name' => $degree->degree_name,
                        'school_name' => $degree->school_name,
                        'year_finished' => $degree->year_finished,
                    ];
                }),
            'skills' => $app->skills_n_expertise,
            'number' => $app->phone,
            'star' => $app->starRatings,
            'comparison' => $comparison,
            'latest_interview' => $latestInterview ? [
                'id' => $latestInterview->id,
                'interview_type' => $latestInterview->interview_type,
                'date' => optional($latestInterview->date)->toDateString(),
                'time' => $latestInterview->time,
                'duration' => $latestInterview->duration,
                'interviewers' => $latestInterview->interviewers,
                'starts_at' => Carbon::parse(optional($latestInterview->date)->toDateString().' '.$latestInterview->time)->toIso8601String(),
                'ended_at' => optional($latestInterview->ended_at)->toIso8601String(),
            ] : null,
            'interviews' => $visibleInterviews
                ->sortBy(function ($interview) {
                    return Carbon::parse(optional($interview->date)->toDateString().' '.$interview->time)->timestamp;
                })
                ->values()
                ->map(function ($interview) {
                    $start = Carbon::parse(optional($interview->date)->toDateString().' '.$interview->time);
                    $end = (clone $start)->addMinutes($this->durationToMinutes($interview->duration));

                    $isFinished = $interview->ended_at || now()->gte($end);

                    return [
                        'id' => $interview->id,
                        'interview_type' => $interview->interview_type,
                        'date' => optional($interview->date)->toDateString(),
                        'time' => $interview->time,
                        'duration' => $interview->duration,
                        'interviewers' => $interview->interviewers,
                        'starts_at' => $start->toIso8601String(),
                        'ends_at' => $end->toIso8601String(),
                        'ended_at' => optional($interview->ended_at)->toIso8601String(),
                        'is_finished' => (bool) $isFinished,
                    ];
                }),
            'interview_progress' => [
                'completed_initial' => $completedInitialInterview,
                'completed_final' => $completedFinalInterview,
                'completed_demo_teaching' => $completedDemoTeaching,
                'can_proceed_passing_document' => $interviewProceedTarget !== null,
                'is_teaching' => $isTeachingApplicant,
                'proceed_target' => $interviewProceedTarget,
            ],
            'documents' => $app->documents->map(function ($doc) use ($comparison) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->filename,
                    'type' => $doc->type,
                    'url' => asset('storage/'.ltrim((string) ($doc->filepath ?? ''), '/')),
                    'preview_url' => route('admin.employeeDocuments.preview', ['id' => $doc->id]),
                    'download_url' => route('admin.employeeDocuments.download', ['id' => $doc->id]),
                    'is_reviewed' => (bool) $doc->reviewed_at,
                    'is_new' => (bool) ($comparison['is_rehire'] ?? false),
                ];
            }),
        ]);
    }

    public function display_edit_position($id){
        $open = OpenPosition::withTrashed()->findOrFail($id);

        if ($open->deleted_at) {
            return redirect()
                ->route('admin.adminPosition')
                ->with('error', 'This position is already closed and can no longer be edited.');
        }

        return view('Admin.adminEditPosition', compact('open'));
    }

    public function display_interview(){/////sync interview status to applicant status if interview is completed
        $this->syncFinishedInterviewApplicantStatuses();

        $allInterviews = Interviewer::with(['applicant.position'])
            ->whereHas('applicant')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        // Show all scheduled interviews in the list; card state is handled in the view.
        $interview = $allInterviews->values();
        $upcomingInterviews = $allInterviews
            ->filter(function ($item) {
                if ($item->ended_at) {
                    return false;
                }

                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return now()->lt($end);
            })
            ->values();
        $completedInterviews = $allInterviews
            ->filter(function ($item) {
                if ($item->ended_at) {
                    return true;
                }

                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return now()->gte($end);
            })
            ->values();

        $count_daily = $allInterviews
            ->filter(function ($item) {
                if ($item->ended_at) {
                    return $item->ended_at->isToday();
                }

                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return $end->isToday() && now()->gte($end);
            })
            ->count();
        $count_month = $allInterviews
            ->filter(function ($item) {
                if ($item->ended_at) {
                    return $item->ended_at->isCurrentMonth() && $item->ended_at->isCurrentYear();
                }

                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return $end->isCurrentMonth() && $end->isCurrentYear() && now()->gte($end);
            })
            ->count();
        $count_year = $allInterviews
            ->filter(function ($item) {
                if ($item->ended_at) {
                    return $item->ended_at->isCurrentYear();
                }

                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return $end->isCurrentYear() && now()->gte($end);
            })
            ->count();
        $count_upcoming = $allInterviews
            ->filter(function ($item) {
                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                return now()->lt($start);
            })
            ->count();
        return view('Admin.adminInterview', compact(
            'interview',
            'upcomingInterviews',
            'completedInterviews',
            'count_daily',
            'count_month',
            'count_year',
            'count_upcoming'
        ));
    }

    private function syncFinishedInterviewApplicantStatuses(): void
    {
        $allInterviews = Interviewer::query()
            ->select(['applicant_id', 'interview_type', 'date', 'time', 'duration', 'ended_at'])
            ->whereNotNull('applicant_id')
            ->get();

        if ($allInterviews->isEmpty()) {
            return;
        }

        $latestByApplicant = $allInterviews
            ->groupBy('applicant_id')
            ->map(function ($items) {
                return $items->sortBy(function ($item) {
                    $start = Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                    $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                    return $end->timestamp;
                })->last();
            })
            ->filter();

        $completedLatestInterviews = $latestByApplicant
            ->filter(function ($item) {
                if ($item->ended_at) {
                    return true;
                }

                $start = Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return now()->gte($end);
            });

        if ($completedLatestInterviews->isEmpty()) {
            return;
        }

        // HR decides the next step from the finished-interview decision buttons.
    }

    private function durationToMinutes(?string $duration): int
    {
        if (!$duration) {
            return 0;
        }

        if (preg_match('/(\d+)/', $duration, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function collapseDuplicateActiveInterviews($interviews)
    {
        $seenActiveTypes = [];

        return $interviews
            ->sortByDesc(fn ($interview) => optional($interview->created_at)->timestamp ?? 0)
            ->filter(function ($interview) use (&$seenActiveTypes) {
                if ($this->interviewIsFinishedForDisplay($interview)) {
                    return true;
                }

                $type = strtolower(trim((string) $interview->interview_type));
                if ($type === '') {
                    return true;
                }

                if (isset($seenActiveTypes[$type])) {
                    return false;
                }

                $seenActiveTypes[$type] = true;
                return true;
            })
            ->values();
    }

    private function interviewIsFinishedForDisplay(Interviewer $interview): bool
    {
        if ($interview->ended_at) {
            return true;
        }

        if (!$interview->date || !$interview->time) {
            return false;
        }

        $start = Carbon::parse($interview->date->toDateString().' '.$interview->time);
        $end = (clone $start)->addMinutes($this->durationToMinutes($interview->duration));

        return now()->gte($end);
    }

    public function display_interview_ID($id){
        $app = Interviewer::with([
            'applicant:id,first_name,last_name,open_position_id',
            'applicant.position:id,title,department,employment,work_mode,job_description,responsibilities,requirements,experience_level,location,skills,benifits,job_type,one,two'
        ])->where('applicant_id', $id)->firstOrFail();


        return response()->json([
            'id' => $app->id,
            'name' => $app->applicant->first_name.' '.$app->applicant->last_name,
            'email' => $app->email_link,
            'title' => $app->applicant->position->title,
            'status' => $app->application_status,
            'applicant_id' => $app->applicant_id,
            'interview_type' => $app->interview_type,
            'date' => $app->date->format('Y-m-d'),
            'time' => \Carbon\Carbon::parse($app->time)->format('H:i'),
            'duration' => $app->duration,
            'interviewers' => $app->interviewers,
            'email_link' => $app->email_link,
            'url' => $app->url,
            'notes' => $app->notes,
        ]);
    }

    public function display_meeting(){
        return view('Admin.adminMeeting');
    }

    public function display_calendar(){
        return view('Admin.adminCalendar');
    }

    public function display_position(){
        $openPosition = OpenPosition::withTrashed()
            ->withCount('applicants')
            ->latest('created_at')
            ->latest('id')
            ->get();
        $openPositions = OpenPosition::withTrashed()->get();
        $countApplication = Applicant::groupBy('open_position_id')->count();
        $logs = GuestLog::count();
        $positionCounts = $openPositions->count();
        $applicantCounts = Applicant::count();
        return view('Admin.adminPosition', compact('openPosition',
        'logs', 'positionCounts', 'applicantCounts','countApplication'));
    }

    public function display_show_position($id){
        $open = OpenPosition::withTrashed()->findOrFail($id);
        $titles = OpenPosition::withTrashed()->pluck('id');
        $admin = User::admins()->get();
        $countApplication = Applicant::whereIn('open_position_id', $titles)->count();
        return view('Admin.adminShowPosition', compact('open','countApplication','admin'));
    }

    public function display_overview(){
        return view('Admin.adminEmployeeOverview');
    }

    public function employee_documents($id){
        $requiredPrefix = '__REQUIRED__::';
        $noticeType = '__NOTICE__';
        $folderType = '__FOLDER__';
        $employee = User::with([
            'applicant.documents' => function ($query) use ($requiredPrefix, $noticeType, $folderType) {
                $query->select([
                    'id',
                    'applicant_id',
                    'filename',
                    'filepath',
                    'type',
                    'mime_type',
                    'size',
                    'created_at',
                ])
                ->where('type', 'not like', $requiredPrefix.'%')
                ->where('type', '!=', $noticeType)
                ->orderByDesc('created_at');
            },
        ])->where('role', 'Employee')->findOrFail($id);

        $currentApplicant = $employee->applicant;
        $comparison = $this->buildApplicantComparisonMeta($currentApplicant);
        $previousApplicant = null;
        if (!empty($comparison['previous_applicant_id'])) {
            $previousApplicant = Applicant::with([
                'documents' => function ($query) use ($requiredPrefix, $noticeType, $folderType) {
                    $query->select([
                        'id',
                        'applicant_id',
                        'filename',
                        'filepath',
                        'type',
                        'mime_type',
                        'size',
                        'created_at',
                    ])
                    ->where('type', 'not like', $requiredPrefix.'%')
                    ->where('type', '!=', $noticeType)
                    ->orderByDesc('created_at');
                },
            ])->find((int) $comparison['previous_applicant_id']);
        }

        $storedItems = collect()
            ->concat($currentApplicant?->documents?->values() ?? collect())
            ->concat($previousApplicant?->documents?->values() ?? collect())
            ->values();
        $folders = $storedItems
            ->filter(fn (ApplicantDocument $document) => $this->isFolderDocumentRecord($document))
            ->map(function (ApplicantDocument $document) use ($storedItems) {
                $folderKey = $this->folderKeyFromFolderRecord($document);

                return [
                    'key' => $folderKey,
                    'name' => trim((string) $document->filename),
                    'count' => $storedItems
                        ->reject(fn (ApplicantDocument $item) => $this->isFolderDocumentRecord($item))
                        ->filter(fn (ApplicantDocument $item) => $this->folderKeyFromFileRecord($item) === $folderKey)
                        ->count(),
                ];
            })
            ->filter(fn (array $folder) => $folder['key'] !== '')
            ->sortBy('name')
            ->values();

        $allDocuments = $storedItems
            ->reject(fn (ApplicantDocument $document) => $this->isFolderDocumentRecord($document))
            ->sortByDesc(function (ApplicantDocument $document) {
                return optional($document->created_at)->timestamp ?? 0;
            })
            ->values();
        $documents = $allDocuments;
        $unfiledCount = $allDocuments
            ->filter(fn (ApplicantDocument $document) => $this->folderKeyFromFileRecord($document) === '')
            ->count();
        $applicantId = (int) ($currentApplicant?->id ?? 0);
        $requiredConfig = $this->getRequiredDocumentConfigForApplicant($applicantId);
        $requiredDocuments = collect($requiredConfig['required_documents'] ?? [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        $uploadedDocumentTypesNormalized = $documents
            ->map(function ($doc) {
                return $this->normalizeDocumentLabel((string) ($doc->type ?: $doc->filename));
            })
            ->filter()
            ->unique()
            ->values();

        $missingDocuments = collect($requiredDocuments)
            ->filter(function ($required) use ($uploadedDocumentTypesNormalized) {
                return !$uploadedDocumentTypesNormalized->contains(
                    $this->normalizeDocumentLabel((string) $required)
                );
            })
            ->values()
            ->all();

        $documents = $this->decorateApplicantDocumentsForHistory($documents, $currentApplicant, $previousApplicant, $comparison);
        $allDocuments = $this->decorateApplicantDocumentsForHistory($allDocuments, $currentApplicant, $previousApplicant, $comparison);

        return response()->json([
            'documents' => $documents,
            'all_documents' => $allDocuments,
            'folders' => $folders,
            'unfiled_count' => $unfiledCount,
            'total_documents' => $allDocuments->count(),
            'required_documents' => $requiredDocuments,
            'required_documents_text' => implode("\n", $requiredDocuments),
            'document_notice' => (string) ($requiredConfig['document_notice'] ?? ''),
            'missing_documents' => $missingDocuments,
            'comparison' => $comparison,
        ]);
    }

    //Personal Detail
    public function display_documents(){
        return view('Admin.PersonalDetail.adminEmployeeDocuments');
    }

    public function display_pd(){
        return view('Admin.PersonalDetail.adminEmployeePD');
    }

    public function display_personal_detail_overview(){
        return view('Admin.PersonalDetail.adminEmployeeOverview');
    }

    public function display_performance(){
        return view('Admin.PersonalDetail.adminEmployeePerformance');
    }

    public function display_edit(){
        return view('Admin.PersonalDetail.editProfile');
    }

    public function display_service_record_edit(Request $request){
        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return redirect()
                ->route('admin.adminEmployee')
                ->with('error', 'Employee not found for service record edit.');
        }

        $employeeUser = User::with([
            'employee',
            'applicant.position:id,title,department,employment',
            'government',
            'salary',
            'positionHistories' => function ($query) {
                $query
                    ->select([
                        'id',
                        'user_id',
                        'old_position',
                        'old_classification',
                        'old_department',
                        'old_salary',
                        'note',
                        'changed_at',
                        'created_at',
                    ])
                    ->orderBy('changed_at')
                    ->orderBy('id');
            },
        ])
            ->where('id', $userId)
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->first();

        if (!$employeeUser) {
            return redirect()
                ->route('admin.adminEmployee')
                ->with('error', 'Employee not found for service record edit.');
        }

        return view('Admin.PersonalDetail.serviceRecordEdit', compact('employeeUser'));
    }

    public function download_service_record_word(Request $request)
    {
        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return redirect()
                ->route('admin.adminEmployee')
                ->with('error', 'Employee not found for service record download.');
        }

        $employeeUser = User::with([
            'employee',
            'applicant.position:id,title,department,employment',
            'government',
            'salary',
            'positionHistories' => function ($query) {
                $query
                    ->select([
                        'id',
                        'user_id',
                        'old_position',
                        'old_classification',
                        'old_department',
                        'old_salary',
                        'note',
                        'changed_at',
                        'created_at',
                    ])
                    ->orderBy('changed_at')
                    ->orderBy('id');
            },
        ])
            ->where('id', $userId)
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->first();

        if (!$employeeUser) {
            return redirect()
                ->route('admin.adminEmployee')
                ->with('error', 'Employee not found for service record download.');
        }

        $employeeId = trim((string) ($employeeUser->employee?->employee_id ?? ('EMP-'.$employeeUser->id)));
        $safeEmployeeId = preg_replace('/[^A-Za-z0-9_-]+/', '-', $employeeId) ?: ('EMP-'.$employeeUser->id);
        $filename = 'service-record-'.$safeEmployeeId.'.doc';
        $html = view('Admin.PersonalDetail.serviceRecordDownload', compact('employeeUser'))->render();
        ActivityChangeLogger::downloadedFile($employeeUser, 'Service Record');

        $bannerCandidates = [
            public_path('images/logo.png'),
        ];

        $bannerPath = null;
        foreach ($bannerCandidates as $candidate) {
            if (is_file($candidate)) {
                $bannerPath = $candidate;
                break;
            }
        }

        if (!$bannerPath) {
            return response($html)
                ->header('Content-Type', 'application/msword; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        }

        $bannerMime = (string) (mime_content_type($bannerPath) ?: 'image/jpeg');
        $bannerData = (string) file_get_contents($bannerPath);
        $boundary = '----=_NextPart_'.md5((string) microtime(true));

        $mhtml = "MIME-Version: 1.0\r\n";
        $mhtml .= "Content-Type: multipart/related; boundary=\"{$boundary}\"; type=\"text/html\"\r\n\r\n";
        $mhtml .= "This is a multi-part message in MIME format.\r\n\r\n";

        $mhtml .= "--{$boundary}\r\n";
        $mhtml .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
        $mhtml .= "Content-Transfer-Encoding: 8bit\r\n";
        $mhtml .= "Content-Location: file:///service-record.htm\r\n\r\n";
        $mhtml .= $html."\r\n\r\n";

        $mhtml .= "--{$boundary}\r\n";
        $mhtml .= "Content-Type: {$bannerMime}\r\n";
        $mhtml .= "Content-Transfer-Encoding: base64\r\n";
        $mhtml .= "Content-Location: file:///service-record-banner\r\n";
        $mhtml .= "Content-ID: <service-record-banner>\r\n\r\n";
        $mhtml .= chunk_split(base64_encode($bannerData), 76, "\r\n")."\r\n";
        $mhtml .= "--{$boundary}--";

        return response($mhtml)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function download_employee_document(int $id)
    {
        $document = ApplicantDocument::query()
            ->where('id', $id)
            ->firstOrFail();

        if ($this->isFolderDocumentRecord($document)) {
            abort(404);
        }

        $relativePath = ltrim((string) ($document->filepath ?? ''), '/');
        if ($relativePath === '') {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($relativePath)) {
            abort(404);
        }

        ActivityChangeLogger::downloadedFile($document, 'Employee Document');

        return $disk->download($relativePath, (string) ($document->filename ?: basename($relativePath)));
    }

    public function preview_resignation_attachment(Resignation $resignation)
    {
        $disk = Storage::disk('public');
        $relativePath = ltrim((string) ($resignation->attachment_path ?? ''), '/');
        if ($relativePath === '' || !$disk->exists($relativePath)) {
            abort(404);
        }

        $fileName = (string) ($resignation->attachment_name ?: basename($relativePath));
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = (string) ($resignation->attachment_mime ?: $disk->mimeType($relativePath) ?: '');
        $viewUrl = route('admin.resignationAttachment.view', ['resignation' => $resignation->id]);
        $wordText = null;
        $wordImages = [];

        if ($extension === 'docx') {
            $absolutePath = $disk->path($relativePath);
            $wordText = $this->extractDocxText($absolutePath);
            $wordImages = $this->extractDocxImages($absolutePath);
        } elseif ($extension === 'doc') {
            $wordText = $this->extractLegacyDocText($disk->path($relativePath));
        }

        return view('Admin.adminDocumentPreview', [
            'document' => $resignation,
            'fileName' => $fileName,
            'extension' => $extension,
            'mimeType' => $mimeType,
            'viewUrl' => $viewUrl,
            'wordText' => $wordText,
            'wordImages' => $wordImages,
            'isPdf' => $extension === 'pdf' || $mimeType === 'application/pdf',
            'isImage' => in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true) || str_starts_with($mimeType, 'image/'),
            'isText' => $extension === 'txt' || str_starts_with($mimeType, 'text/'),
        ]);
    }

    public function view_resignation_attachment(Resignation $resignation)
    {
        $disk = Storage::disk('public');
        $relativePath = ltrim((string) ($resignation->attachment_path ?? ''), '/');
        if ($relativePath === '' || !$disk->exists($relativePath)) {
            abort(404);
        }

        $absolutePath = $disk->path($relativePath);
        $fileName = (string) ($resignation->attachment_name ?: basename($relativePath));
        $safeFileName = str_replace('"', '', $fileName);
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = (string) ($resignation->attachment_mime ?: $disk->mimeType($relativePath) ?: '');

        if ($mimeType === '' || $mimeType === 'application/octet-stream') {
            $mimeType = match ($extension) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'txt' => 'text/plain',
                default => 'application/octet-stream',
            };
        }

        return Response::file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$safeFileName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function preview_employee_document(int $id)
    {
        $document = ApplicantDocument::query()
            ->where('id', $id)
            ->firstOrFail();

        if ($this->isFolderDocumentRecord($document)) {
            abort(404);
        }

        $relativePath = ltrim((string) ($document->filepath ?? ''), '/');
        if ($relativePath === '') {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($relativePath)) {
            abort(404);
        }

        $this->markEmployeeDocumentReviewed($document);

        $fileName = (string) ($document->filename ?: basename($relativePath));
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = (string) ($document->mime_type ?: $disk->mimeType($relativePath) ?: '');
        $viewUrl = route('admin.employeeDocuments.view', ['id' => $document->id]);
        $wordText = null;
        $wordImages = [];

        if ($extension === 'docx') {
            $absolutePath = $disk->path($relativePath);
            $wordText = $this->extractDocxText($absolutePath);
            $wordImages = $this->extractDocxImages($absolutePath);
        } elseif ($extension === 'doc') {
            $wordText = $this->extractLegacyDocText($disk->path($relativePath));
        }

        return view('Admin.adminDocumentPreview', [
            'document' => $document,
            'fileName' => $fileName,
            'extension' => $extension,
            'mimeType' => $mimeType,
            'viewUrl' => $viewUrl,
            'wordText' => $wordText,
            'wordImages' => $wordImages,
            'isPdf' => $extension === 'pdf' || $mimeType === 'application/pdf',
            'isImage' => in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true) || str_starts_with($mimeType, 'image/'),
            'isText' => $extension === 'txt' || str_starts_with($mimeType, 'text/'),
        ]);
    }

    public function view_employee_document(int $id)
    {
        $document = ApplicantDocument::query()
            ->where('id', $id)
            ->firstOrFail();

        if ($this->isFolderDocumentRecord($document)) {
            abort(404);
        }

        $relativePath = ltrim((string) ($document->filepath ?? ''), '/');
        if ($relativePath === '') {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($relativePath)) {
            abort(404);
        }

        $absolutePath = $disk->path($relativePath);
        $fileName = (string) ($document->filename ?: basename($relativePath));
        $safeFileName = str_replace('"', '', $fileName);
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = (string) ($document->mime_type ?: $disk->mimeType($relativePath) ?: '');

        if ($mimeType === '' || $mimeType === 'application/octet-stream') {
            $mimeType = match ($extension) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'txt' => 'text/plain',
                default => 'application/octet-stream',
            };
        }

        return Response::file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$safeFileName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function extractDocxText(string $absolutePath): ?string
    {
        $xml = null;

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($absolutePath) === true) {
                $entry = $zip->getFromName('word/document.xml');
                $zip->close();

                if (is_string($entry) && trim($entry) !== '') {
                    $xml = $entry;
                }
            }
        }

        $entryNames = [
            'word/document.xml',
            'word/header1.xml',
            'word/header2.xml',
            'word/header3.xml',
            'word/footer1.xml',
            'word/footer2.xml',
            'word/footer3.xml',
            'word/footnotes.xml',
            'word/endnotes.xml',
            'word/comments.xml',
        ];

        $xmlParts = [];
        foreach ($entryNames as $entryName) {
            $entryXml = null;

            if ($entryName === 'word/document.xml' && is_string($xml) && trim($xml) !== '') {
                $entryXml = $xml;
            } else {
                $entryXml = $this->readZipEntry($absolutePath, $entryName);
            }

            if (is_string($entryXml) && trim($entryXml) !== '') {
                $xmlParts[] = $entryXml;
            }
        }

        if (empty($xmlParts)) {
            return null;
        }

        $joinedXml = implode("\n\n", $xmlParts);
        $joinedXml = preg_replace('/<w:tab\b[^>]*\/>/i', "\t", $joinedXml) ?? $joinedXml;
        $joinedXml = preg_replace('/<w:br\b[^>]*\/>/i', "\n", $joinedXml) ?? $joinedXml;
        $joinedXml = preg_replace('/<\/w:tc>/i', "\t", $joinedXml) ?? $joinedXml;
        $joinedXml = preg_replace('/<\/w:tr>/i', "\n", $joinedXml) ?? $joinedXml;
        $joinedXml = preg_replace('/<\/w:p>/i', "\n\n", $joinedXml) ?? $joinedXml;
        $text = html_entity_decode(strip_tags($joinedXml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\t+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text) !== '' ? trim($text) : null;
    }

    private function extractDocxImages(string $absolutePath): array
    {
        $documentXml = $this->readZipEntry($absolutePath, 'word/document.xml');
        $relationshipsXml = $this->readZipEntry($absolutePath, 'word/_rels/document.xml.rels');

        if (!is_string($documentXml) || !is_string($relationshipsXml)) {
            return [];
        }

        preg_match_all('/r:embed="([^"]+)"/', $documentXml, $embedMatches);
        $embeddedIds = collect($embedMatches[1] ?? [])->unique()->values();
        if ($embeddedIds->isEmpty()) {
            return [];
        }

        preg_match_all('/<Relationship\b[^>]*Id="([^"]+)"[^>]*Target="([^"]+)"[^>]*>/i', $relationshipsXml, $relationshipMatches, PREG_SET_ORDER);
        $relationships = collect($relationshipMatches)
            ->mapWithKeys(fn (array $match): array => [$match[1] => html_entity_decode($match[2], ENT_QUOTES | ENT_XML1, 'UTF-8')]);

        return $embeddedIds
            ->map(function (string $id) use ($relationships, $absolutePath): ?array {
                $target = (string) $relationships->get($id, '');
                if ($target === '' || str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
                    return null;
                }

                $entryName = str_starts_with($target, '/')
                    ? ltrim($target, '/')
                    : 'word/'.ltrim($target, '/');
                $entryName = preg_replace('#(^|/)[^/]+/\.\./#', '$1', $entryName) ?? $entryName;
                $imageBytes = $this->readZipEntry($absolutePath, $entryName);
                if (!is_string($imageBytes) || $imageBytes === '') {
                    return null;
                }

                $extension = strtolower((string) pathinfo($entryName, PATHINFO_EXTENSION));
                $mimeType = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'bmp' => 'image/bmp',
                    default => null,
                };

                if ($mimeType === null) {
                    return null;
                }

                return [
                    'name' => basename($entryName),
                    'data_uri' => 'data:'.$mimeType.';base64,'.base64_encode($imageBytes),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function extractLegacyDocText(string $absolutePath): ?string
    {
        $contents = @file_get_contents($absolutePath);
        if (!is_string($contents) || $contents === '') {
            return null;
        }

        $decoded = @mb_convert_encoding($contents, 'UTF-8', 'UTF-16LE');
        $candidates = [];

        if (is_string($decoded)) {
            preg_match_all('/[\p{L}\p{N}\p{P}\p{S} \t\r\n]{12,}/u', $decoded, $unicodeMatches);
            $candidates = array_merge($candidates, $unicodeMatches[0] ?? []);
        }

        preg_match_all('/[\x20-\x7E\r\n\t]{12,}/', $contents, $asciiMatches);
        $candidates = array_merge($candidates, $asciiMatches[0] ?? []);

        $lines = collect($candidates)
            ->map(function (string $value): string {
                $value = str_replace("\0", '', $value);
                $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
                $value = preg_replace("/\r\n|\r/", "\n", $value) ?? $value;
                return trim($value);
            })
            ->filter(fn (string $value): bool => strlen($value) >= 12)
            ->reject(fn (string $value): bool => preg_match('/^[^a-zA-Z0-9]*$/', $value) === 1)
            ->unique()
            ->values()
            ->all();

        if (empty($lines)) {
            return null;
        }

        $text = implode("\n\n", $lines);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text) !== '' ? trim($text) : null;
    }

    private function readZipEntry(string $absolutePath, string $entryName): ?string
    {
        $contents = @file_get_contents($absolutePath);
        if (!is_string($contents) || $contents === '') {
            return null;
        }

        $eocdOffset = strrpos($contents, "PK\x05\x06");
        if ($eocdOffset === false) {
            return null;
        }

        $centralDirectoryOffset = $this->littleEndianInt(substr($contents, $eocdOffset + 16, 4));
        $centralDirectorySize = $this->littleEndianInt(substr($contents, $eocdOffset + 12, 4));
        if ($centralDirectoryOffset === null || $centralDirectorySize === null) {
            return null;
        }

        $cursor = $centralDirectoryOffset;
        $centralDirectoryEnd = $centralDirectoryOffset + $centralDirectorySize;
        while ($cursor + 46 <= $centralDirectoryEnd && substr($contents, $cursor, 4) === "PK\x01\x02") {
            $compressionMethod = $this->littleEndianInt(substr($contents, $cursor + 10, 2));
            $compressedSize = $this->littleEndianInt(substr($contents, $cursor + 20, 4));
            $fileNameLength = $this->littleEndianInt(substr($contents, $cursor + 28, 2));
            $extraLength = $this->littleEndianInt(substr($contents, $cursor + 30, 2));
            $commentLength = $this->littleEndianInt(substr($contents, $cursor + 32, 2));
            $localHeaderOffset = $this->littleEndianInt(substr($contents, $cursor + 42, 4));

            if (
                $compressionMethod === null
                || $compressedSize === null
                || $fileNameLength === null
                || $extraLength === null
                || $commentLength === null
                || $localHeaderOffset === null
            ) {
                return null;
            }

            $name = substr($contents, $cursor + 46, $fileNameLength);
            if ($name === $entryName) {
                if (substr($contents, $localHeaderOffset, 4) !== "PK\x03\x04") {
                    return null;
                }

                $localFileNameLength = $this->littleEndianInt(substr($contents, $localHeaderOffset + 26, 2));
                $localExtraLength = $this->littleEndianInt(substr($contents, $localHeaderOffset + 28, 2));
                if ($localFileNameLength === null || $localExtraLength === null) {
                    return null;
                }

                $dataOffset = $localHeaderOffset + 30 + $localFileNameLength + $localExtraLength;
                $compressedData = substr($contents, $dataOffset, $compressedSize);

                if ($compressionMethod === 0) {
                    return $compressedData;
                }

                if ($compressionMethod === 8 && function_exists('gzinflate')) {
                    $inflated = @gzinflate($compressedData);
                    return is_string($inflated) ? $inflated : null;
                }

                return null;
            }

            $cursor += 46 + $fileNameLength + $extraLength + $commentLength;
        }

        return null;
    }

    private function littleEndianInt(string $bytes): ?int
    {
        $length = strlen($bytes);
        if ($length === 2) {
            $value = unpack('v', $bytes);
            return is_array($value) ? (int) $value[1] : null;
        }

        if ($length === 4) {
            $value = unpack('V', $bytes);
            return is_array($value) ? (int) $value[1] : null;
        }

        return null;
    }

    private function markEmployeeDocumentReviewed(ApplicantDocument $document): void
    {
        if ($document->reviewed_at) {
            return;
        }

        $document->forceFill([
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ])->save();
    }

    public function display_create_position(){
        return view('Admin.adminCreatePosition');
    }

    private function getRequiredDocumentConfigForApplicant(int $applicantId): array
    {
        if ($applicantId <= 0) {
            return [];
        }

        $requiredPrefix = '__REQUIRED__::';
        $noticeType = '__NOTICE__';
        $metaDocuments = ApplicantDocument::query()
            ->where('applicant_id', $applicantId)
            ->where(function ($query) use ($requiredPrefix, $noticeType) {
                $query
                    ->where('type', 'like', $requiredPrefix.'%')
                    ->orWhere('type', $noticeType);
            })
            ->orderByDesc('id')
            ->get();

        if ($metaDocuments->isNotEmpty()) {
            $requiredDocuments = $metaDocuments
                ->filter(fn ($doc) => str_starts_with((string) ($doc->type ?? ''), $requiredPrefix))
                ->map(function ($doc) use ($requiredPrefix) {
                    return trim((string) substr((string) $doc->type, strlen($requiredPrefix)));
                })
                ->filter()
                ->unique(function ($value) {
                    return strtolower($value);
                })
                ->values()
                ->all();

            $notice = (string) optional($metaDocuments->firstWhere('type', $noticeType))->filename;

            return [
                'required_documents' => $requiredDocuments,
                'document_notice' => $notice,
            ];
        }

        $disk = Storage::disk('local');
        $path = 'required_employee_documents.json';
        if (!$disk->exists($path)) {
            return $this->defaultRequiredDocumentConfig();
        }

        $payload = json_decode((string) $disk->get($path), true);
        if (!is_array($payload)) {
            return $this->defaultRequiredDocumentConfig();
        }

        $applicants = is_array($payload['applicants'] ?? null) ? $payload['applicants'] : [];
        $entry = $applicants[(string) $applicantId] ?? null;
        if (!is_array($entry)) {
            return $this->defaultRequiredDocumentConfig();
        }

        return $entry;
    }

    private function defaultRequiredDocumentConfig(): array
    {
        return [
            'required_documents' => [
                'Resume/CV',
                'Cover Letter',
                'Personal Data Sheet',
                'Transcript Of Records',
                'Diploma',
                'PRC License/Board Rating',
                'Certificate Of Eligibility / Certificate of Passing',
                'Certifications & Supporting Document',
                'Membership/Affiliation',
            ],
            'document_notice' => '',
        ];
    }

    private function normalizeDocumentLabel(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return '';
        }

        return preg_replace('/\s+/', ' ', $normalized);
    }

    private function isFolderDocumentRecord(ApplicantDocument $document): bool
    {
        return trim((string) ($document->type ?? '')) === '__FOLDER__';
    }

    private function folderKeyFromFolderRecord(ApplicantDocument $document): string
    {
        $path = trim(str_replace('\\', '/', (string) ($document->filepath ?? '')), '/');
        if (str_starts_with($path, 'system/folders/')) {
            return trim((string) Str::after($path, 'system/folders/'));
        }

        return '';
    }

    private function folderKeyFromFileRecord(ApplicantDocument $document): string
    {
        $path = trim(str_replace('\\', '/', (string) ($document->filepath ?? '')), '/');
        if (!preg_match('#^uploads/applicant-documents/\d+/([^/]+)/#', $path, $matches)) {
            return '';
        }

        $folderKey = trim((string) ($matches[1] ?? ''));
        if ($folderKey === '' || $folderKey === 'unfiled') {
            return '';
        }

        return $folderKey;
    }

    private function attachApplicantComparisonMeta(?Applicant $applicant): void
    {
        if (!$applicant) {
            return;
        }

        $applicant->setAttribute('comparison', $this->buildApplicantComparisonMeta($applicant));
    }

    private function buildApplicantComparisonMeta(?Applicant $applicant): array
    {
        if (!$applicant) {
            return [
                'is_rehire' => false,
                'previous_applicant_id' => null,
                'changed_fields' => [],
                'changed_degree_levels' => [],
            ];
        }

        $previousApplicant = $this->resolvePreviousComparableApplicant($applicant);
        if (!$previousApplicant) {
            return [
                'is_rehire' => false,
                'previous_applicant_id' => null,
                'changed_fields' => [],
                'changed_degree_levels' => [],
            ];
        }

        $changedFields = [];
        $fieldComparisons = [
            'first_name' => [$applicant->first_name, $previousApplicant->first_name],
            'last_name' => [$applicant->last_name, $previousApplicant->last_name],
            'phone' => [$applicant->phone, $previousApplicant->phone],
            'address' => [$applicant->address, $previousApplicant->address],
            'skills_n_expertise' => [$applicant->skills_n_expertise, $previousApplicant->skills_n_expertise],
            'work_position' => [$applicant->work_position, $previousApplicant->work_position],
            'work_employer' => [$applicant->work_employer, $previousApplicant->work_employer],
            'work_location' => [$applicant->work_location, $previousApplicant->work_location],
            'work_duration' => [$applicant->work_duration, $previousApplicant->work_duration],
            'position' => [$applicant->open_position_id, $previousApplicant->open_position_id],
        ];

        foreach ($fieldComparisons as $field => [$currentValue, $previousValue]) {
            if ($this->normalizeComparisonValue($currentValue) !== $this->normalizeComparisonValue($previousValue)) {
                $changedFields[] = $field;
            }
        }

        $changedDegreeLevels = [];
        foreach (['bachelor', 'master', 'doctorate'] as $level) {
            if ($this->normalizedDegreeLevelValue($applicant, $level) !== $this->normalizedDegreeLevelValue($previousApplicant, $level)) {
                $changedDegreeLevels[] = $level;
            }
        }

        return [
            'is_rehire' => true,
            'previous_applicant_id' => (int) $previousApplicant->id,
            'changed_fields' => $changedFields,
            'changed_degree_levels' => $changedDegreeLevels,
        ];
    }

    private function decorateApplicantDocumentsForHistory($documents, ?Applicant $currentApplicant, ?Applicant $previousApplicant, array $comparison)
    {
        $documents = collect($documents)->values();
        $currentApplicantId = (int) ($currentApplicant?->id ?? 0);
        $previousApplicantId = (int) ($previousApplicant?->id ?? 0);
        $isRehire = (bool) ($comparison['is_rehire'] ?? false);
        $currentApplicantCreatedAt = $this->rawTimestampValue(
            $currentApplicant?->getRawOriginal('created_at') ?? $currentApplicant?->created_at
        );

        $currentReplacementTypes = $documents
            ->filter(function (ApplicantDocument $document) use ($currentApplicantId, $currentApplicantCreatedAt) {
                if ($currentApplicantId <= 0 || (int) $document->applicant_id !== $currentApplicantId) {
                    return false;
                }

                $documentCreatedAt = $this->rawTimestampValue($document->getRawOriginal('created_at') ?? $document->created_at);

                return $currentApplicantCreatedAt === null
                    || $documentCreatedAt === null
                    || $documentCreatedAt->greaterThanOrEqualTo($currentApplicantCreatedAt);
            })
            ->map(fn (ApplicantDocument $document) => $this->normalizeDocumentLabel((string) ($document->type ?: $document->filename)))
            ->filter()
            ->unique()
            ->values();

        return $documents->map(function (ApplicantDocument $document) use (
            $currentApplicantId,
            $previousApplicantId,
            $isRehire,
            $currentApplicantCreatedAt,
            $currentReplacementTypes
        ) {
            $documentType = $this->normalizeDocumentLabel((string) ($document->type ?: $document->filename));
            $documentCreatedAt = $this->rawTimestampValue($document->getRawOriginal('created_at') ?? $document->created_at);
            $isCurrentApplicationDocument = $currentApplicantId > 0 && (int) $document->applicant_id === $currentApplicantId;
            $isPreviousApplicationDocument = $previousApplicantId > 0 && (int) $document->applicant_id === $previousApplicantId;
            $isOlderDuplicateInCurrentApplication = $isRehire
                && $isCurrentApplicationDocument
                && $documentType !== ''
                && $currentReplacementTypes->contains($documentType)
                && $currentApplicantCreatedAt !== null
                && $documentCreatedAt !== null
                && $documentCreatedAt->lt($currentApplicantCreatedAt);

            if ($isOlderDuplicateInCurrentApplication) {
                $isCurrentApplicationDocument = false;
                $isPreviousApplicationDocument = true;
            }

            $document->setAttribute('is_new', $isRehire && $isCurrentApplicationDocument);
            $document->setAttribute('is_previous_application', $isPreviousApplicationDocument);
            $document->setAttribute('history_label', $isPreviousApplicationDocument ? 'Previous Application' : 'Current Application');

            return $document;
        })->values();
    }

    private function resolvePreviousComparableApplicant(Applicant $applicant): ?Applicant
    {
        $normalizedEmail = strtolower(trim((string) ($applicant->email ?? '')));
        $userId = (int) ($applicant->user_id ?? 0);

        $query = Applicant::query()
            ->with([
                'degrees:id,applicant_id,degree_level,degree_name,school_name,year_finished,sort_order',
            ])
            ->where('id', '!=', (int) $applicant->id)
            ->whereRaw("LOWER(TRIM(COALESCE(application_status, ''))) = ?", ['hired'])
            ->where(function ($innerQuery) use ($normalizedEmail, $userId) {
                if ($userId > 0) {
                    $innerQuery->orWhere('user_id', $userId);
                }

                if ($normalizedEmail !== '') {
                    $innerQuery->orWhereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail]);
                }
            })
            ->orderByDesc('date_hired')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query->first();
    }

    private function normalizeComparisonValue($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return strtolower(trim((string) preg_replace('/\s+/', ' ', (string) ($value ?? ''))));
    }

    private function normalizedDegreeLevelValue(Applicant $applicant, string $level): string
    {
        $rows = collect($applicant->degrees ?? [])
            ->filter(function ($row) use ($level) {
                return strtolower(trim((string) ($row->degree_level ?? ''))) === $level;
            })
            ->sortBy('sort_order')
            ->map(function ($row) {
                return implode('|', [
                    $this->normalizeComparisonValue($row->degree_name ?? ''),
                    $this->normalizeComparisonValue($row->school_name ?? ''),
                    $this->normalizeComparisonValue($row->year_finished ?? ''),
                ]);
            })
            ->values();

        if ($rows->isNotEmpty()) {
            return $rows->implode('||');
        }

        return match ($level) {
            'bachelor' => implode('|', [
                $this->normalizeComparisonValue($applicant->bachelor_degree ?? ''),
                $this->normalizeComparisonValue($applicant->bachelor_school_name ?? ''),
                $this->normalizeComparisonValue($applicant->bachelor_year_finished ?? ''),
            ]),
            'master' => implode('|', [
                $this->normalizeComparisonValue($applicant->master_degree ?? ''),
                $this->normalizeComparisonValue($applicant->master_school_name ?? ''),
                $this->normalizeComparisonValue($applicant->master_year_finished ?? ''),
            ]),
            default => implode('|', [
                $this->normalizeComparisonValue($applicant->doctoral_degree ?? ''),
                $this->normalizeComparisonValue($applicant->doctoral_school_name ?? ''),
                $this->normalizeComparisonValue($applicant->doctoral_year_finished ?? ''),
            ]),
        };
    }

    private function buildAdminEmployeeLeaveSummary(User $user, string $selectedMonth): array
    {
        try {
            $monthCursor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthCursor = now()->startOfMonth();
        }

        $isTeaching = strcasecmp((string) ($user?->employee?->job_type ?? ''), 'Teaching') === 0;
        $joinDate = null;
        try {
            if ($isTeaching && !empty($user?->applicant?->date_hired)) {
                $joinDate = Carbon::parse($user->applicant->date_hired);
            } elseif (!empty($user?->employee?->employement_date)) {
                $joinDate = Carbon::parse($user->employee->employement_date);
            } elseif (!empty($user?->applicant?->date_hired)) {
                $joinDate = Carbon::parse($user->applicant->date_hired);
            }
        } catch (\Throwable $e) {
            $joinDate = null;
        }

        $resetCycleMonths = $isTeaching ? 10 : 12;
        $equalHalfEarnedDays = round(
            $this->calculateAdminEmployeeEarnedLeaveDays($joinDate, $monthCursor, $resetCycleMonths) / 2,
            1
        );

        $vacationLimit = $equalHalfEarnedDays;
        $sickLimit = $equalHalfEarnedDays;
        $vacationAvailable = max($vacationLimit, 0);
        $sickAvailable = max($sickLimit, 0);

        $leaveRows = collect($user->leaveApplications ?? [])
            ->filter(function ($row) {
                $status = strtolower(trim((string) ($row->status ?? '')));
                return in_array($status, ['approved', 'completed'], true);
            })
            ->sortByDesc(function ($row) {
                return optional($row->filing_date ?? $row->created_at)?->timestamp ?? 0;
            })
            ->values();

        $latestLeaveApplication = $leaveRows->first();
        if ($latestLeaveApplication) {
            $vacationLimit = round((float) ($latestLeaveApplication->beginning_vacation ?? 0) + (float) ($latestLeaveApplication->earned_vacation ?? 0), 1);
            $sickLimit = round((float) ($latestLeaveApplication->beginning_sick ?? 0) + (float) ($latestLeaveApplication->earned_sick ?? 0), 1);
            $vacationAvailable = round((float) ($latestLeaveApplication->ending_vacation ?? 0), 1);
            $sickAvailable = round((float) ($latestLeaveApplication->ending_sick ?? 0), 1);
        }

        return [
            'vacation_limit' => max($vacationLimit, 0),
            'vacation_available' => max($vacationAvailable, 0),
            'sick_limit' => max($sickLimit, 0),
            'sick_available' => max($sickAvailable, 0),
        ];
    }

    private function calculateAdminEmployeeEarnedLeaveDays(?Carbon $joinDate, Carbon $monthCursor, ?int $resetCycleMonths = null): int
    {
        if (!$joinDate) {
            return 0;
        }

        $accrualStartDate = $joinDate->copy()->addYear()->startOfDay();
        $accrualStartMonth = $accrualStartDate->copy()->startOfMonth();
        $selectedMonthEnd = $monthCursor->copy()->endOfMonth();
        $todayEnd = now()->endOfDay();
        $accrualCutoff = $selectedMonthEnd->lte($todayEnd) ? $selectedMonthEnd : $todayEnd;

        if ($accrualCutoff->lt($accrualStartDate)) {
            return 0;
        }

        $months = $accrualStartMonth->diffInMonths($accrualCutoff->copy()->startOfMonth()) + 1;
        $months = max(0, $months);

        if (!is_null($resetCycleMonths) && $resetCycleMonths > 0 && $months > 0) {
            $months = (($months - 1) % $resetCycleMonths) + 1;
        }

        return $months;
    }

    private function rawTimestampValue($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

}
