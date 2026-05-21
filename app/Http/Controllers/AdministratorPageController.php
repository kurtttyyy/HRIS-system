<?php

namespace App\Http\Controllers;

use App\Models\AttendanceUpload;
use App\Models\AttendanceRecord;
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
        $isTodaySundayNoClass = $this->isSundayDate($todayDate);
        $isTodayHoliday = $this->isHolidayDate($todayDate);

        $presentTodayCount = 0;
        if (!$isTodaySundayNoClass) {
            $todayRecords = AttendanceRecord::query()
                ->whereDate('attendance_date', $todayDate)
                ->orderByDesc('id')
                ->get()
                ->filter(function ($row) {
                    return $this->normalizeEmployeeId($row->employee_id) !== '';
                })
                ->unique(function ($row) {
                    return $this->normalizeEmployeeId($row->employee_id);
                })
                ->values();

            if ($isTodayHoliday && $todayRecords->isEmpty()) {
                // On holidays with no uploads, treat approved employees as present for the day.
                $presentTodayCount = $totalEmployeeCount;
            } else {
                $presentTodayCount = $todayRecords
                    ->filter(function ($row) use ($isTodayHoliday) {
                        $hasAnyTimeLog = !empty($row->morning_in)
                            || !empty($row->morning_out)
                            || !empty($row->afternoon_in)
                            || !empty($row->afternoon_out);
                        $isHolidayPresent = (bool) ($row->is_holiday_present ?? false);
                        $mainGate = strtolower(trim((string) ($row->main_gate ?? '')));
                        $isWithPayLeave = $mainGate === 'leave - with pay';
                        return !(bool) ($row->is_absent ?? false)
                            && ($hasAnyTimeLog || $isHolidayPresent || $isTodayHoliday || $isWithPayLeave);
                    })
                    ->count();
            }
        }

        $presentTodayRate = $totalEmployeeCount > 0
            ? round(($presentTodayCount / $totalEmployeeCount) * 100, 1)
            : 0;

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
            'presentTodayCount',
            'presentTodayRate',
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
                    $query->with('sender')->orderBy('created_at');
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

    public function display_attendance(Request $request){
        return $this->buildAttendanceView($request, 'all');
    }

    public function display_attendance_present(Request $request){
        return $this->buildAttendanceView($request, 'present');
    }

    public function display_attendance_absent(Request $request){
        return $this->buildAttendanceView($request, 'absent');
    }

    public function display_attendance_tardiness(Request $request){
        return $this->buildAttendanceView($request, 'tardiness');
    }

    public function display_attendance_total_employee(Request $request){
        return $this->buildAttendanceView($request, 'total_employee');
    }

    private function buildAttendanceView(Request $request, string $activeAttendanceTab = 'all'){// activeAttendanceTab can be 'all', 'present', 'absent', 'tardiness', 'total_employee'
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $selectedUploadId = $request->query('upload_id');
        $searchName = trim((string) $request->query('search_name', ''));
        if ($searchName === '') {
            $searchName = null;
        }
        $normalizedFromDate = $this->normalizeFilterDate($fromDate);
        $normalizedToDate = $this->normalizeFilterDate($toDate);
        $selectedJobType = $this->normalizeJobType($request->query('job_type'));
        $allowedJobTypes = ['Teaching', 'Non-Teaching'];
        if ($selectedJobType && !in_array($selectedJobType, $allowedJobTypes, true)) {
            $selectedJobType = null;
        }

        $hasDateFilter = (bool) ($normalizedFromDate || $normalizedToDate);
        $exactDateFilter = null;
        $rangeStartDate = null;
        $rangeEndDate = null;

        if ($normalizedFromDate && $normalizedToDate) {
            if ($normalizedFromDate === $normalizedToDate) {
                $exactDateFilter = $normalizedFromDate;
            } elseif ($normalizedFromDate < $normalizedToDate) {
                $rangeStartDate = $normalizedFromDate;
                $rangeEndDate = $normalizedToDate;
            } else {
                $rangeStartDate = $normalizedToDate;
                $rangeEndDate = $normalizedFromDate;
            }
        } elseif ($normalizedFromDate) {
            $exactDateFilter = $normalizedFromDate;
        } elseif ($normalizedToDate) {
            $exactDateFilter = $normalizedToDate;
        }

        $defaultedAttendanceTabToToday = false;
        if (!$hasDateFilter && $activeAttendanceTab !== 'all') {
            $exactDateFilter = now()->toDateString();
            $fromDate = $exactDateFilter;
            $hasDateFilter = true;
            $selectedUploadId = null;
            $defaultedAttendanceTabToToday = true;
        }

        $attendanceFiles = AttendanceUpload::query()
            ->when($hasDateFilter, function ($query) use ($exactDateFilter, $rangeStartDate, $rangeEndDate) {
                $query->whereHas('records', function ($recordsQuery) use ($exactDateFilter, $rangeStartDate, $rangeEndDate) {
                    if ($exactDateFilter) {
                        $recordsQuery->whereDate('attendance_date', $exactDateFilter);
                    } elseif ($rangeStartDate && $rangeEndDate) {
                        $recordsQuery->whereDate('attendance_date', '>=', $rangeStartDate)
                            ->whereDate('attendance_date', '<=', $rangeEndDate);
                    }
                });
            })
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'files_page')
            ->withQueryString();

        $attendanceFileItems = collect($attendanceFiles->items());

        if (!$selectedUploadId && !$hasDateFilter) {
            $selectedUploadId = optional(
                $attendanceFileItems->firstWhere('status', 'Processed') ?? $attendanceFileItems->first()
            )->id;
        }

        $jobTypeOptions = collect($allowedJobTypes);

        if ($activeAttendanceTab === 'all') {
            $processedUploadIds = AttendanceUpload::query()
                ->select('id')
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['processed']);

            $summaryQuery = AttendanceRecord::query()
                ->whereIn('attendance_upload_id', $processedUploadIds);

            if ($hasDateFilter) {
                if ($exactDateFilter) {
                    $summaryQuery->whereDate('attendance_date', $exactDateFilter);
                } elseif ($rangeStartDate && $rangeEndDate) {
                    $summaryQuery->whereDate('attendance_date', '>=', $rangeStartDate)
                        ->whereDate('attendance_date', '<=', $rangeEndDate);
                }
            } else {
                $summaryQuery->whereDate('attendance_date', now()->toDateString());
            }

            $quickTotalCount = (clone $summaryQuery)->count();
            $tardyCount = (clone $summaryQuery)
                ->where(function ($query) {
                    $query->where('is_tardy', true)
                        ->orWhere('late_minutes', '>', 0);
                })
                ->count();

            if (!$hasDateFilter && $quickTotalCount > 0) {
                $attendanceEmployeeLookupMaps = $this->getAttendanceEmployeeLookupMaps();
                $allEmployeeIds = collect($attendanceEmployeeLookupMaps['job_type'] ?? [])
                    ->keys()
                    ->map(fn ($id) => $this->normalizeEmployeeId($id))
                    ->filter()
                    ->unique()
                    ->values();
                $presentEmployeeIds = (clone $summaryQuery)
                    ->where(function ($query) {
                        $query->whereNotNull('morning_in')
                            ->orWhereNotNull('afternoon_in');
                    })
                    ->pluck('employee_id')
                    ->map(fn ($id) => $this->normalizeEmployeeId($id))
                    ->filter()
                    ->unique()
                    ->values();

                $presentCount = $presentEmployeeIds->count();
                $absentCount = $allEmployeeIds->diff($presentEmployeeIds)->count();
                $totalCount = $allEmployeeIds->count();
            } else {
                $absentCount = (clone $summaryQuery)
                    ->where(function ($query) {
                        $query->where('is_absent', true)
                            ->orWhere(function ($innerQuery) {
                                $innerQuery
                                    ->whereNull('morning_in')
                                    ->whereNull('morning_out')
                                    ->whereNull('afternoon_in')
                                    ->whereNull('afternoon_out');
                            });
                    })
                    ->count();
                $presentCount = max($quickTotalCount - $absentCount, 0);
                $totalCount = $quickTotalCount;
            }

            $presentEmployees = collect();
            $absentEmployees = collect();
            $tardyEmployees = collect();
            $allEmployees = collect();
            $attendanceRows = null;
            $attendancePerPage = 25;

            return view('Admin.adminAttendance', compact(
                'attendanceFiles',
                'fromDate',
                'toDate',
                'selectedUploadId',
                'selectedJobType',
                'searchName',
                'jobTypeOptions',
                'activeAttendanceTab',
                'presentEmployees',
                'absentEmployees',
                'tardyEmployees',
                'allEmployees',
                'presentCount',
                'absentCount',
                'tardyCount',
                'totalCount',
                'attendanceRows',
                'attendancePerPage'
            ));
        }

        $records = collect();
        if ($hasDateFilter) {
            $recordsQuery = AttendanceRecord::query()->select($this->attendanceRecordSelectColumns());
            if ($exactDateFilter) {
                $recordsQuery->whereDate('attendance_date', $exactDateFilter);
            } elseif ($rangeStartDate && $rangeEndDate) {
                $recordsQuery->whereDate('attendance_date', '>=', $rangeStartDate)
                    ->whereDate('attendance_date', '<=', $rangeEndDate);
            }

            $records = $recordsQuery
                ->orderByDesc('attendance_date')
                ->orderBy('employee_id')
                ->get();
        } elseif ($selectedUploadId) {
            $records = AttendanceRecord::query()
                ->select($this->attendanceRecordSelectColumns())
                ->where('attendance_upload_id', $selectedUploadId)
                ->orderBy('employee_id')
                ->get();
        }

        // If an official holiday was hidden from the Admin Calendar,
        // do not keep previously generated holiday-present rows for that date.
        $records = collect($records)
            ->filter(function ($row) {
                $isHolidayPresent = (bool) ($row->is_holiday_present ?? false);
                if (!$isHolidayPresent) {
                    return true;
                }

                try {
                    $date = $row->attendance_date
                        ? Carbon::parse($row->attendance_date)->toDateString()
                        : null;
                } catch (\Throwable $e) {
                    $date = null;
                }

                if (!$date) {
                    return true;
                }

                return !$this->isHiddenOfficialHolidayDate($date);
            })
            ->values();

        $attendanceEmployeeLookupMaps = $this->getAttendanceEmployeeLookupMaps();
        $employeeJobTypeMap = collect($attendanceEmployeeLookupMaps['job_type'] ?? []);
        $employeeDepartmentMap = collect($attendanceEmployeeLookupMaps['department'] ?? []);
        $employeeDisplayNameMap = collect($attendanceEmployeeLookupMaps['display_name'] ?? []);

        $isSundayNoClassDate = $exactDateFilter ? $this->isSundayDate($exactDateFilter) : false;
        $isHolidayDate = $exactDateFilter ? $this->isHolidayDate($exactDateFilter) : false;
        $isSingleSidedDateFilter = ($normalizedFromDate && !$normalizedToDate)
            || (!$normalizedFromDate && $normalizedToDate);
        $shouldAutoPresentHolidayDate = $isHolidayDate
            && !$isSundayNoClassDate
            && !$isSingleSidedDateFilter;

        // No-class Sundays are excluded from attendance counting.
        if ($isSundayNoClassDate) {
            $records = collect();
        } elseif ($shouldAutoPresentHolidayDate) {
            if ($exactDateFilter) {
                $hasAnyRecordForDate = AttendanceRecord::query()
                    ->whereDate('attendance_date', $exactDateFilter)
                    ->exists();

                // Only auto-generate holiday-present rows when there are no records at all for that date.
                if (!$hasAnyRecordForDate) {
                    $this->persistHolidayAttendanceForDate($exactDateFilter, $employeeJobTypeMap);
                }
                $records = $this->getAttendanceRecordsByDate($exactDateFilter);
            } else {
                $records = $this->buildHolidayPresentEmployees($fromDate, $selectedJobType, $employeeJobTypeMap);
            }
        }

        // For date ranges, auto-add present rows on holiday dates (excluding Sundays)
        // so holidays are represented as present without requiring manual uploads.
        if ($rangeStartDate && $rangeEndDate && !$isSundayNoClassDate) {
            $records = $this->appendHolidayPresentRowsForRange(
                $records,
                $rangeStartDate,
                $rangeEndDate,
                $selectedJobType,
                $employeeJobTypeMap
            );
        }

        if ($selectedJobType) {
            $records = $records
                ->filter(function ($row) use ($employeeJobTypeMap, $selectedJobType) {
                    $employeeId = $this->normalizeEmployeeId($row->employee_id);
                    $employeeJobType = $this->normalizeJobType($employeeJobTypeMap->get($employeeId));
                    return $employeeJobType === $selectedJobType;
                })
                ->values();
        }

        $records = $records->map(function ($row) use ($employeeJobTypeMap, $employeeDepartmentMap, $employeeDisplayNameMap) {
            $employeeId = $this->normalizeEmployeeId($row->employee_id);
            $rowJobType = $this->normalizeJobType($employeeJobTypeMap->get($employeeId));
            $rowDepartment = $employeeDepartmentMap->get($employeeId);
            $rowDepartmentRaw = trim((string) ($row->department ?? ''));
            $rowDepartmentKey = strtolower($rowDepartmentRaw);
            $rowDepartmentIsPlaceholder = in_array($rowDepartmentKey, ['', '-', 'n/a', 'na', 'none', 'null'], true);
            $resolvedDepartment = $rowDepartmentIsPlaceholder
                ? $rowDepartment
                : $rowDepartmentRaw;
            $rowName = $employeeDisplayNameMap->get($employeeId) ?: $this->normalizeLooseDisplayName($row->employee_name ?? null);
            $computedLateMinutes = $this->calculateLateMinutesFromInTimes($row);
            $isWithinPresentWindow = $this->isPresentByTimeWindow($row);
            $rowDate = null;
            try {
                $rowDate = $row->attendance_date ? Carbon::parse($row->attendance_date)->toDateString() : null;
            } catch (\Throwable $e) {
                $rowDate = null;
            }
            $isHolidayPresent = (bool) ($row->is_holiday_present ?? false);
            if ($isHolidayPresent && $rowDate && !$this->isHolidayDate($rowDate)) {
                $isHolidayPresent = false;
            }
            $hasAnyTimeLog = !empty($row->morning_in)
                || !empty($row->morning_out)
                || !empty($row->afternoon_in)
                || !empty($row->afternoon_out);
            $mainGate = strtolower(trim((string) ($row->main_gate ?? '')));
            $isWithPayLeave = $mainGate === 'leave - with pay';
            $isWithoutPayLeave = $mainGate === 'leave - without pay';
            // Business rule: if there is no scan log for a class day, mark as absent.
            // Keep holiday and leave-with-pay rows excluded from this rule.
            $isAbsentByRule = !$isHolidayPresent && !$isWithPayLeave && !$hasAnyTimeLog;
            $isAbsent = (bool) ($row->is_absent ?? false) || $isAbsentByRule;
            if ($isWithoutPayLeave) {
                $isAbsent = true;
            }
            $isTardyByRule = !$isAbsent && !$isWithinPresentWindow && $computedLateMinutes > 0;
            $gateLabel = $row->main_gate;
            if ($isHolidayPresent && !$isAbsent && (is_null($gateLabel) || trim((string) $gateLabel) === '')) {
                $gateLabel = 'Holiday - No Class';
            }
            if ($isAbsent && trim((string) $gateLabel) === 'Holiday - No Class') {
                $gateLabel = null;
            }

            if (method_exists($row, 'setAttribute')) {
                $row->setAttribute('job_type', $rowJobType);
                $row->setAttribute('department', $resolvedDepartment);
                $row->setAttribute('main_gate', $gateLabel);
                $row->setAttribute('employee_name', $rowName);
                $row->setAttribute('computed_late_minutes', $computedLateMinutes);
                $row->setAttribute('is_absent', $isAbsent);
                $row->setAttribute('is_tardy_by_rule', $isTardyByRule);
                $row->setAttribute('is_holiday_present', $isHolidayPresent);
            } else {
                $row->job_type = $rowJobType;
                $row->department = $resolvedDepartment;
                $row->main_gate = $gateLabel;
                $row->employee_name = $rowName;
                $row->computed_late_minutes = $computedLateMinutes;
                $row->is_absent = $isAbsent;
                $row->is_tardy_by_rule = $isTardyByRule;
                $row->is_holiday_present = $isHolidayPresent;
            }
            return $row;
        });

        // Enforce exact row-level filtering by normalized job type.
        if ($selectedJobType) {
            $records = $records
                ->filter(fn ($row) => $this->normalizeJobType($row->job_type) === $selectedJobType)
                ->values();
        }

        $isExpandedRangeRecords = false;
        if (!$shouldAutoPresentHolidayDate && !$isSundayNoClassDate && $rangeStartDate && $rangeEndDate) {
            // For range filters, build a full employee-day matrix so absences always appear.
            $records = $this->expandRecordsForDateRange(
                $records,
                $rangeStartDate,
                $rangeEndDate,
                $selectedJobType,
                $employeeJobTypeMap,
                $employeeDepartmentMap
            );
            $isExpandedRangeRecords = true;
        }

        $rowLevelAbsentEmployees = $records
            ->filter(fn ($row) => (bool) ($row->is_absent ?? false))
            ->values();
        $presentEmployees = $records
            ->reject(fn ($row) => (bool) ($row->is_absent ?? false))
            ->values();
        $absentEmployees = $rowLevelAbsentEmployees;

        // Business rule: employees with no attendance row for a day are also absent.
        if (!$shouldAutoPresentHolidayDate && !$isSundayNoClassDate && (!$defaultedAttendanceTabToToday || $records->isNotEmpty())) {
            if ($exactDateFilter) {
                $absentEmployees = $absentEmployees
                    ->concat($this->buildMissingEmployeeAbsences($records, $exactDateFilter, $selectedJobType, $employeeJobTypeMap, $employeeDepartmentMap))
                    ->values();
            } elseif ($rangeStartDate && $rangeEndDate) {
                if (!$isExpandedRangeRecords) {
                    $absentEmployees = $absentEmployees
                        ->concat($this->buildMissingEmployeeAbsencesForRange($records, $rangeStartDate, $rangeEndDate, $selectedJobType, $employeeJobTypeMap, $employeeDepartmentMap))
                        ->values();
                }
            } else {
                // No explicit date filter: infer the covered dates from current rows and
                // build absences per employee per day (not just per employee).
                $recordDates = $records
                    ->map(function ($row) {
                        try {
                            return $row->attendance_date ? Carbon::parse($row->attendance_date)->toDateString() : null;
                        } catch (\Throwable $e) {
                            return null;
                        }
                    })
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                if ($recordDates->count() >= 2) {
                    $absentEmployees = $absentEmployees
                        ->concat($this->buildMissingEmployeeAbsencesForRange(
                            $records,
                            (string) $recordDates->first(),
                            (string) $recordDates->last(),
                            $selectedJobType,
                            $employeeJobTypeMap,
                            $employeeDepartmentMap
                        ))
                        ->values();
                } else {
                    $fallbackDate = $exactDateFilter
                        ?? $normalizedFromDate
                        ?? $normalizedToDate
                        ?? $recordDates->first();

                    $absentEmployees = $absentEmployees
                        ->concat($this->buildMissingEmployeeAbsences(
                            $records,
                            $fallbackDate ? (string) $fallbackDate : null,
                            $selectedJobType,
                            $employeeJobTypeMap,
                            $employeeDepartmentMap
                        ))
                        ->values();
                }
            }
        }
        $tardyEmployees = $records
            ->filter(fn ($row) => (bool) ($row->is_tardy_by_rule ?? false))
            ->map(function ($row) {
                // Keep Blade compatibility by showing the computed late minutes in the existing column.
                $row->late_minutes = (int) ($row->computed_late_minutes ?? 0);
                return $row;
            })
            ->values();
        $allEmployees = $records
            ->map(function ($row) {
                $row->late_minutes = (int) ($row->computed_late_minutes ?? 0);
                return $row;
            })
            ->values();

        if ($activeAttendanceTab === 'total_employee' && $absentEmployees->isNotEmpty() && !$isExpandedRangeRecords) {
            $allEmployees = $allEmployees
                ->concat($absentEmployees->map(function ($row) {
                    $row->late_minutes = (int) ($row->late_minutes ?? 0);
                    return $row;
                }))
                ->sortBy(function ($row) {
                    $date = null;
                    try {
                        $date = $row->attendance_date ? Carbon::parse($row->attendance_date)->toDateString() : '';
                    } catch (\Throwable $e) {
                        $date = '';
                    }

                    return $date.'|'.$this->normalizeEmployeeId($row->employee_id);
                })
                ->values();
        }

        if ($searchName) {
            $presentEmployees = $this->filterAttendanceRowsByEmployeeName($presentEmployees, $searchName);
            $absentEmployees = $this->filterAttendanceRowsByEmployeeName($absentEmployees, $searchName);
            $tardyEmployees = $this->filterAttendanceRowsByEmployeeName($tardyEmployees, $searchName);
            $allEmployees = $this->filterAttendanceRowsByEmployeeName($allEmployees, $searchName);
        }

        $presentCount = $presentEmployees->count();
        $absentCount = $absentEmployees->count();
        $tardyCount = $tardyEmployees->count();
        $totalCount = $presentEmployees->count() + $absentEmployees->count();

        $attendancePerPage = (int) $request->query('attendance_per_page', 25);
        if (!in_array($attendancePerPage, [10, 25, 50], true)) {
            $attendancePerPage = 25;
        }

        $paginateAttendanceRows = function ($rows) use ($request, $attendancePerPage) {
            $rows = collect($rows)->values();
            $lastPage = max((int) ceil($rows->count() / $attendancePerPage), 1);
            $page = min(max((int) $request->query('attendance_page', 1), 1), $lastPage);

            return new LengthAwarePaginator(
                $rows->forPage($page, $attendancePerPage)->values(),
                $rows->count(),
                $attendancePerPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                    'pageName' => 'attendance_page',
                ]
            );
        };

        $attendanceRows = match ($activeAttendanceTab) {
            'present' => $paginateAttendanceRows($presentEmployees),
            'absent' => $paginateAttendanceRows($absentEmployees),
            'tardiness' => $paginateAttendanceRows($tardyEmployees),
            'total_employee' => $paginateAttendanceRows($allEmployees),
            default => null,
        };

        return view('Admin.adminAttendance', compact(
            'attendanceFiles',
            'fromDate',
            'toDate',
            'selectedUploadId',
            'selectedJobType',
            'searchName',
            'jobTypeOptions',
            'activeAttendanceTab',
            'presentEmployees',
            'absentEmployees',
            'tardyEmployees',
            'allEmployees',
            'presentCount',
            'absentCount',
            'tardyCount',
            'totalCount',
            'attendanceRows',
            'attendancePerPage'
        ));
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

    private function attendanceRecordSelectColumns(): array
    {
        static $columns = null;

        if (!is_null($columns)) {
            return $columns;
        }

        $wantedColumns = [
            'id',
            'attendance_upload_id',
            'employee_id',
            'attendance_date',
            'morning_in',
            'morning_out',
            'afternoon_in',
            'afternoon_out',
            'late_minutes',
            'missing_time_logs',
            'is_absent',
            'is_tardy',
            'employee_name',
            'main_gate',
            'job_type',
            'department',
            'is_holiday_present',
        ];

        $availableColumns = collect(Schema::getColumnListing('attendance_records'))->flip();

        $columns = collect($wantedColumns)
            ->filter(fn ($column) => $availableColumns->has($column))
            ->unique()
            ->values()
            ->all();

        return $columns;
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

    private function getAttendanceRecordsByDate(string $date)  // Retrieves attendance records for a specific date, ensuring uniqueness by normalized employee ID and sorted by employee ID for consistent display.
    {
        return AttendanceRecord::query()
            ->select($this->attendanceRecordSelectColumns())
            ->whereDate('attendance_date', $date)
            ->orderByDesc('id')
            ->get()
            ->unique(function ($row) {
                return $this->normalizeEmployeeId($row->employee_id);
            })
            ->sortBy('employee_id')
            ->values();
    }

    private function persistHolidayAttendanceForDate(string $date, $employeeJobTypeMap = null): void // For a given date, creates a synthetic AttendanceUpload record if not already exists, and inserts AttendanceRecord entries for all employees without existing records on that date, marking them as present for the holiday. This ensures that holiday attendance is consistently represented in the system, even if the holiday is auto-detected after the fact.
    {
        $holidayUpload = AttendanceUpload::query()->firstOrCreate(
            ['original_name' => "System Holiday Attendance {$date}"],
            [
                'file_path' => "attendance_excels/system_holiday_{$date}.txt",
                'file_size' => 0,
                'status' => 'Processed',
                'processed_rows' => 0,
                'uploaded_at' => Carbon::parse($date)->endOfDay(),
            ]
        );

        $existingEmployeeIds = AttendanceRecord::query()
            ->whereDate('attendance_date', $date)
            ->pluck('employee_id')
            ->map(fn ($id) => $this->normalizeEmployeeId($id))
            ->filter()
            ->values()
            ->all();

        $employees = User::query()
            ->with('employee')
            ->where('role', 'Employee')
            ->whereHas('employee', function ($query) {
                $query->whereNotNull('employee_id')
                    ->where('employee_id', '!=', '');
            })
            ->orderBy('id')
            ->get();

        $hasEmployeeNameColumn = Schema::hasColumn('attendance_records', 'employee_name');
        $hasDepartmentColumn = Schema::hasColumn('attendance_records', 'department');
        $hasMainGateColumn = Schema::hasColumn('attendance_records', 'main_gate');
        $hasJobTypeColumn = Schema::hasColumn('attendance_records', 'job_type');
        $hasHolidayPresentColumn = Schema::hasColumn('attendance_records', 'is_holiday_present');
        $now = now();
        $recordsToInsert = [];

        foreach ($employees as $user) {
            $employeeId = $this->normalizeEmployeeId($user->employee?->employee_id);
            if ($employeeId === '' || in_array($employeeId, $existingEmployeeIds, true)) {
                continue;
            }

            $name = $this->formatEmployeeDisplayName(
                $user->first_name,
                $user->middle_name,
                $user->last_name
            );

            $record = [
                'attendance_upload_id' => $holidayUpload->id,
                'employee_id' => (string) $employeeId,
                'attendance_date' => $date,
                'morning_in' => null,
                'morning_out' => null,
                'afternoon_in' => null,
                'afternoon_out' => null,
                'late_minutes' => 0,
                'missing_time_logs' => json_encode([]),
                'is_absent' => false,
                'is_tardy' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasEmployeeNameColumn) {
                $record['employee_name'] = $name;
            }
            if ($hasDepartmentColumn) {
                $record['department'] = $user->employee?->department ?: null;
            }
            if ($hasMainGateColumn) {
                $record['main_gate'] = 'Holiday - No Class';
            }
            if ($hasJobTypeColumn) {
                $record['job_type'] = $this->normalizeJobType($employeeJobTypeMap?->get($employeeId));
            }
            if ($hasHolidayPresentColumn) {
                $record['is_holiday_present'] = true;
            }

            $recordsToInsert[] = $record;
        }

        if (!empty($recordsToInsert)) {
            AttendanceRecord::insert($recordsToInsert);
        }

        $holidayUpload->update([
            'status' => 'Processed',
            'processed_rows' => AttendanceRecord::query()
                ->where('attendance_upload_id', $holidayUpload->id)
                ->count(),
        ]);
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

        $pendingLeaveRequests = $monthApplications
            ->filter(function ($application) {
                $status = trim((string) ($application->status ?? ''));
                return $status === '' || strcasecmp($status, 'Pending') === 0;
            })
            ->sortByDesc('created_at')
            ->values();

        $pendingLeaveDays = (float) $pendingLeaveRequests->sum(function ($row) {
            return (float) ($row->number_of_working_days ?? 0);
        });

        return view('Admin.adminLeaveManagement', compact(
            'selectedMonth',
            'totalLeaveUsedDays',
            'sickLeaveUsedDays',
            'monthRecords',
            'leaveTypeCounts',
            'pendingLeaveRequests',
            'pendingLeaveDays'
        ));
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

        $resignationsQuery = Resignation::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email',
                'processor:id,first_name,last_name',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

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
            'Pending' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])->count(),
            'Approved' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])->count(),
            'Completed' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['completed'])->count(),
            'Rejected' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['rejected'])->count(),
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

    public function display_reports(){
        return view('Admin.adminReports');
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
        $this->syncFinishedInterviewApplicantStatuses();

        $applicant = Applicant::with(
            'position:id,title,department,employment,work_mode,job_description,responsibilities,requirements,experience_level,location,skills,benifits,job_type,one,two'
        )->latest('created_at')->get();
        $count_applicant = Applicant::count();
        $count_pending = $applicant->where('application_status','pending')->count();
        $count_final_interview = $applicant
            ->whereIn('application_status', ['Initial Interview', 'Final Interview', 'Demo Teaching'])
            ->count();
        $hired = Applicant::where('application_status', 'Hired')->whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count();

        return view('Admin.adminApplicant', compact('applicant', 'hired',
                                            'count_applicant','count_pending'
                                            ,'count_final_interview'));
    }

    public function display_applicant_ID($id){
        $this->syncFinishedInterviewApplicantStatuses();

        $app = Applicant::with(
            'documents:id,filename,applicant_id,filepath,type,created_at',
            'degrees:id,applicant_id,degree_level,degree_name,school_name,year_finished,sort_order',
            'position:id,title,department,employment,work_mode,job_description,responsibilities,requirements,experience_level,location,skills,benifits,job_type,one,two'
            )->findOrFail($id);
        $comparison = $this->buildApplicantComparisonMeta($app);
        $interviews = Interviewer::query()
            ->where('applicant_id', $app->id)
            ->get();
        $latestInterview = $interviews
            ->sortBy(function ($interview) {
                return Carbon::parse(optional($interview->date)->toDateString().' '.$interview->time)->timestamp;
            })
            ->last();
        $completedInterviewTypes = $interviews
            ->filter(function ($interview) {
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
            'location' => $app->address,
            'one' => $app->created_at->format('F d, Y'),
            'work_position' => $app->work_position,
            'work_employer' => $app->work_employer,
            'work_location' => $app->work_location,
            'work_duration' => $app->work_duration,
            'skills' => $app->skills_n_expertise,
            'number' => $app->phone,
            'star' => $app->starRatings,
            'comparison' => $comparison,
            'latest_interview' => $latestInterview ? [
                'interview_type' => $latestInterview->interview_type,
                'date' => optional($latestInterview->date)->toDateString(),
                'time' => $latestInterview->time,
                'duration' => $latestInterview->duration,
                'interviewers' => $latestInterview->interviewers,
                'starts_at' => Carbon::parse(optional($latestInterview->date)->toDateString().' '.$latestInterview->time)->toIso8601String(),
            ] : null,
            'interviews' => $interviews
                ->sortBy(function ($interview) {
                    return Carbon::parse(optional($interview->date)->toDateString().' '.$interview->time)->timestamp;
                })
                ->values()
                ->map(function ($interview) {
                    $start = Carbon::parse(optional($interview->date)->toDateString().' '.$interview->time);
                    $end = (clone $start)->addMinutes($this->durationToMinutes($interview->duration));

                    return [
                        'interview_type' => $interview->interview_type,
                        'date' => optional($interview->date)->toDateString(),
                        'time' => $interview->time,
                        'duration' => $interview->duration,
                        'interviewers' => $interview->interviewers,
                        'starts_at' => $start->toIso8601String(),
                        'ends_at' => $end->toIso8601String(),
                        'is_finished' => now()->gte($end),
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
                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return now()->lt($end);
            })
            ->values();
        $completedInterviews = $allInterviews
            ->filter(function ($item) {
                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return now()->gte($end);
            })
            ->values();

        $count_daily = $allInterviews
            ->filter(function ($item) {
                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return $end->isToday() && now()->gte($end);
            })
            ->count();
        $count_month = $allInterviews
            ->filter(function ($item) {
                $start = \Carbon\Carbon::parse($item->date->format('Y-m-d').' '.$item->time);
                $end = (clone $start)->addMinutes($this->durationToMinutes($item->duration));
                return $end->isCurrentMonth() && $end->isCurrentYear() && now()->gte($end);
            })
            ->count();
        $count_year = $allInterviews
            ->filter(function ($item) {
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
            ->select(['applicant_id', 'interview_type', 'date', 'time', 'duration'])
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
