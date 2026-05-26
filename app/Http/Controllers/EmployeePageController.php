<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\Conversation;
use App\Models\AttendanceRecord;
use App\Models\PayslipRecord;
use App\Models\Resignation;
use App\Models\User;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeePageController extends Controller
{
    private const FOLDER_TYPE = '__FOLDER__';

    public function display_home(){  // Employee Home page
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display')->withErrors([
                'email' => 'Please sign in to continue.',
            ]);
        }

        $selectedMonth = now()->format('Y-m');
        $leaveMetrics = $this->buildEmployeeLeaveMetrics($user, $selectedMonth);
        $attendanceMetrics = $this->buildEmployeeAttendanceMetrics($user, $selectedMonth);
        $weeklyAttendance = $this->buildWeeklyAttendanceData($user);
        [, $notificationStats] = $this->buildEmployeeNotifications($user);
        $accountAlerts = $this->buildEmployeeAccountAlerts($user, (int) ($notificationStats['total'] ?? 0));

        return view('employee.employeeHome', array_merge(
            [
                'user' => $user,
                'notifications' => (int) ($notificationStats['total'] ?? 0),
                'accountAlerts' => $accountAlerts,
            ],
            $leaveMetrics,
            $attendanceMetrics,
            $weeklyAttendance
        ));
    }

    private function buildEmployeeAccountAlerts(User $user, int $notificationTotal = 0): array
    {
        $alerts = collect();

        $unreadMessages = 0;
        if (Schema::hasTable('conversations') && Schema::hasTable('conversation_messages')) {
            $unreadMessages = \App\Models\ConversationMessage::query()
                ->whereNull('read_at')
                ->where('sender_user_id', '!=', (int) $user->id)
                ->whereHas('conversation', function ($query) use ($user) {
                    $query->where(function ($innerQuery) use ($user) {
                        $innerQuery->where('user_one_id', (int) $user->id)
                            ->orWhere('user_two_id', (int) $user->id);
                    });
                })
                ->count();
        }
        if ($unreadMessages > 0) {
            $alerts->push([
                'title' => $unreadMessages.' unread message'.($unreadMessages === 1 ? '' : 's'),
                'desc' => 'You have pending conversation replies.',
                'href' => route('employee.employeeCommunication', ['focus' => 'chat-panel']),
                'tone' => 'rose',
            ]);
        }

        $leaveDecisionCount = 0;
        if (Schema::hasTable('leave_applications')) {
            $leaveDecisionCount = LeaveApplication::query()
                ->where('user_id', (int) $user->id)
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['rejected'])
                ->count();
        }
        if ($leaveDecisionCount > 0) {
            $alerts->push([
                'title' => $leaveDecisionCount.' rejected leave request'.($leaveDecisionCount === 1 ? '' : 's'),
                'desc' => 'A leave request was rejected and needs your attention.',
                'href' => route('employee.employeeLeave', ['focus' => 'leave-history-section']),
                'tone' => 'amber',
            ]);
        }

        $missingDocumentCount = 0;
        if (Schema::hasTable('applicants') && Schema::hasTable('applicant_documents')) {
            $applicant = Applicant::query()
                ->where('user_id', (int) $user->id)
                ->where('application_status', 'Hired')
                ->latest('id')
                ->first();

            if ($applicant) {
                $requiredPrefix = '__REQUIRED__::';
                $noticeType = '__NOTICE__';
                $folderType = self::FOLDER_TYPE;
                $requiredConfig = $this->getRequiredDocumentConfigForApplicant((int) $applicant->id);
                $requiredDocuments = collect($requiredConfig['required_documents'] ?? [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->values();

                $accessibleApplicantIds = $this->documentAccessibleApplicantIds($applicant);
                $uploadedDocumentTypesNormalized = ApplicantDocument::query()
                    ->whereIn('applicant_id', $accessibleApplicantIds)
                    ->where('type', 'not like', $requiredPrefix.'%')
                    ->where('type', '!=', $noticeType)
                    ->where('type', '!=', $folderType)
                    ->get()
                    ->map(fn ($doc) => $this->normalizeDocumentLabel((string) ($doc->type ?: $doc->filename)))
                    ->filter()
                    ->unique()
                    ->values();

                $missingDocumentCount = (int) $requiredDocuments
                    ->filter(function ($required) use ($uploadedDocumentTypesNormalized) {
                        return !$uploadedDocumentTypesNormalized->contains(
                            $this->normalizeDocumentLabel((string) $required)
                        );
                    })
                    ->count();
            }
        }
        if ($missingDocumentCount > 0) {
            $alerts->push([
                'title' => $missingDocumentCount.' missing required document'.($missingDocumentCount === 1 ? '' : 's'),
                'desc' => 'Upload missing files to complete your records.',
                'href' => route('employee.employeeDocument', ['focus' => 'missing-documents-section']),
                'tone' => 'violet',
            ]);
        }

        if ($notificationTotal > 0) {
            $alerts->push([
                'title' => $notificationTotal.' account notification'.($notificationTotal === 1 ? '' : 's'),
                'desc' => 'Review all recent updates in your notification center.',
                'href' => route('employee.employeeNotifications'),
                'tone' => 'sky',
            ]);
        }

        return $alerts->values()->all();
    }

    public function display_notifications()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display');
        }

        [$notificationItems, $notificationStats] = $this->buildEmployeeNotifications($user);

        return view('employee.employeeNotifications', compact(
            'user',
            'notificationItems',
            'notificationStats'
        ));
    }

    public function notification_summary()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['total' => 0, 'stats' => [], 'items' => []], 401);
        }

        [$notificationItems, $notificationStats] = $this->buildEmployeeNotifications($user);

        return response()->json([
            'total' => (int) ($notificationStats['total'] ?? 0),
            'stats' => $notificationStats,
            'items' => $notificationItems->map(function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'category' => $item['category'] ?? 'Update',
                    'date' => optional($item['date'] ?? null)?->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    public function display_hierarchy()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display');
        }

        $departmentName = $this->resolveEmployeeDepartmentName($user);

        $departmentEmployees = User::with([
            'employee',
            'applicant.position',
            'applicant.documents:id,applicant_id,filename,filepath,mime_type,type',
        ])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get()
            ->filter(function (User $employeeUser) use ($departmentName) {
                return strcasecmp(
                    $this->resolveEmployeeDepartmentName($employeeUser),
                    $departmentName
                ) === 0;
            })
            ->values();

        if ($departmentEmployees->isEmpty()) {
            $departmentEmployees = collect([$user]);
        }

        $head = $departmentEmployees->first(function (User $employeeUser) {
            return strtolower(trim((string) ($employeeUser->department_head ?? ''))) === 'approved';
        });

        if (!$head) {
            $head = $departmentEmployees
                ->sortByDesc(fn (User $employeeUser) => $this->hierarchyManagerScore($employeeUser))
                ->first();
        }

        $remainingEmployees = $departmentEmployees
            ->reject(fn (User $employeeUser) => (int) $employeeUser->id === (int) optional($head)->id)
            ->values();

        $managerCandidates = $remainingEmployees
            ->filter(fn (User $employeeUser) => $this->hierarchyManagerScore($employeeUser) > 0)
            ->sortByDesc(fn (User $employeeUser) => $this->hierarchyManagerScore($employeeUser))
            ->values();

        if ($managerCandidates->isEmpty() && $remainingEmployees->isNotEmpty()) {
            $managerCandidates = $remainingEmployees->take(min(3, $remainingEmployees->count()))->values();
        }

        $staffPool = $remainingEmployees
            ->reject(function (User $employeeUser) use ($managerCandidates) {
                return $managerCandidates->contains(fn (User $manager) => (int) $manager->id === (int) $employeeUser->id);
            })
            ->values();

        $managerNodes = $managerCandidates->map(function (User $manager) {
            return [
                'id' => (int) $manager->id,
                'name' => $this->buildHierarchyDisplayName($manager),
                'initials' => $manager->initials ?: $this->buildHierarchyFallbackInitials($manager),
                'photo_url' => $this->resolveEmployeeProfilePhotoUrl($manager),
                'title' => $this->resolveEmployeePositionName($manager, 'Team Lead'),
                'team' => $this->resolveEmployeeDepartmentName($manager),
                'employee_id' => $this->resolveHierarchyEmployeeId($manager),
                'email' => $this->resolveHierarchyEmployeeEmail($manager),
                'status' => $this->resolveHierarchyEmploymentStatus($manager),
                'employees' => collect(),
            ];
        })->values();

        $staffPool->values()->each(function (User $employeeUser, int $index) use (&$managerNodes) {
            if ($managerNodes->isEmpty()) {
                return;
            }

            $managerIndex = $index % $managerNodes->count();
            $managerNodes[$managerIndex]['employees'] = $managerNodes[$managerIndex]['employees']->push([
                'id' => (int) $employeeUser->id,
                'name' => $this->buildHierarchyDisplayName($employeeUser),
                'initials' => $employeeUser->initials ?: $this->buildHierarchyFallbackInitials($employeeUser),
                'photo_url' => $this->resolveEmployeeProfilePhotoUrl($employeeUser),
                'title' => $this->resolveEmployeePositionName($employeeUser, 'Staff Member'),
                'team' => $this->resolveEmployeeDepartmentName($employeeUser),
                'employee_id' => $this->resolveHierarchyEmployeeId($employeeUser),
                'email' => $this->resolveHierarchyEmployeeEmail($employeeUser),
                'status' => $this->resolveHierarchyEmploymentStatus($employeeUser),
            ]);
        });

        $managerNodes = $managerNodes->map(function (array $managerNode) {
            $managerNode['employees'] = $managerNode['employees']->values();
            return $managerNode;
        })->values();

        $headNode = $head
            ? [
                'id' => (int) $head->id,
                'name' => $this->buildHierarchyDisplayName($head),
                'initials' => $head->initials ?: $this->buildHierarchyFallbackInitials($head),
                'photo_url' => $this->resolveEmployeeProfilePhotoUrl($head),
                'title' => $this->resolveEmployeePositionName($head, 'Head of Department'),
                'team' => $this->resolveEmployeeDepartmentName($head),
            ]
            : null;

        return view('employee.employeeHierarchy', [
            'user' => $user,
            'departmentName' => $departmentName,
            'headNode' => $headNode,
            'managerNodes' => $managerNodes,
            'departmentEmployeeCount' => (int) $departmentEmployees->count(),
            'managerCount' => (int) $managerNodes->count(),
            'staffCount' => (int) $staffPool->count(),
        ]);
    }

    public function display_evaluation()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display');
        }

        $departmentName = $this->resolveEmployeeDepartmentName($user);
        $isDepartmentHead = strtolower(trim((string) ($user->department_head ?? ''))) === 'approved';
        $evaluationEmployees = collect();

        if ($isDepartmentHead) {
            $evaluationEmployees = User::with(['employee', 'applicant.position'])
                ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
                ->where('id', '!=', (int) $user->id)
                ->get()
                ->filter(function (User $employeeUser) use ($departmentName) {
                    return strcasecmp(
                        $this->resolveEmployeeDepartmentName($employeeUser),
                        $departmentName
                    ) === 0;
                })
                ->map(function (User $employeeUser) {
                    return [
                        'id' => (int) $employeeUser->id,
                        'name' => $this->buildHierarchyDisplayName($employeeUser),
                        'position' => $this->resolveEmployeePositionName($employeeUser, 'Staff Member'),
                        'department' => $this->resolveEmployeeDepartmentName($employeeUser),
                        'employee_id' => $this->resolveHierarchyEmployeeId($employeeUser),
                        'email' => $this->resolveHierarchyEmployeeEmail($employeeUser),
                        'status' => $this->resolveHierarchyEmploymentStatus($employeeUser),
                    ];
                })
                ->values();
        }

        return view('employee.employeeEvaluation', [
            'user' => $user,
            'notifications' => 0,
            'departmentName' => $departmentName,
            'isDepartmentHead' => $isDepartmentHead,
            'evaluationEmployees' => $evaluationEmployees,
        ]);
    }

    public function display_staff_guide()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display')->withErrors([
                'email' => 'Please sign in to continue.',
            ]);
        }

        [$notificationItems, $notificationStats] = $this->buildEmployeeNotifications($user);

        return view('employee.employeeStaffGuide', [
            'user' => $user,
            'notifications' => (int) ($notificationStats['total'] ?? 0),
        ]);
    }

    private function buildEmployeeNotifications($user): array
    {

        $employeeId = trim((string) ($user->employee->employee_id ?? ''));

        $leaveNotifications = LeaveApplication::query()
            ->where('user_id', $user->id)
            ->orderByDesc('filing_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($application) {
                $status = strtolower(trim((string) ($application->status ?? 'pending')));
                $statusLabel = $status !== '' ? Str::title($status) : 'Pending';
                $filedAt = $application->filing_date
                    ? Carbon::parse($application->filing_date)
                    : Carbon::parse($application->created_at);

                return [
                    'category' => 'Leave',
                    'title' => $status === 'approved'
                        ? 'Leave request approved'
                        : ($status === 'rejected' ? 'Leave request needs attention' : 'Leave request submitted'),
                    'message' => trim((string) ($application->leave_type ?? 'Leave')).' update: '.$statusLabel.'.',
                    'date' => $filedAt,
                    'href' => route('employee.employeeLeave', ['focus' => 'leave-history-section']),
                    'badge' => $statusLabel,
                    'tone' => $status === 'approved' ? 'emerald' : ($status === 'rejected' ? 'rose' : 'amber'),
                ];
            });

        $payslipNotifications = PayslipRecord::query()
            ->where(function ($query) use ($user, $employeeId) {
                $query->where('user_id', (int) $user->id);
                if ($employeeId !== '') {
                    $query->orWhere('employee_id', $employeeId);
                }
            })
            ->orderByDesc('pay_date')
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get()
            ->map(function ($record) {
                $payDate = $record->pay_date
                    ? Carbon::parse($record->pay_date)
                    : Carbon::parse($record->created_at);

                return [
                    'category' => 'Payslip',
                    'title' => 'New payslip available',
                    'message' => 'Payroll record for '.$payDate->format('F j, Y').' is ready to review.',
                    'date' => $payDate,
                    'href' => route('employee.employeePayslip', ['record_id' => $record->id, 'focus' => 'payslip-history-section']),
                    'badge' => 'Payslip',
                    'tone' => 'violet',
                ];
            });

        $attendanceNotifications = AttendanceRecord::query()
            ->where(function ($query) use ($user, $employeeId) {
                if ($employeeId !== '') {
                    $query->where('employee_id', $employeeId);
                } else {
                    $query->whereRaw('LOWER(TRIM(COALESCE(employee_name, \'\'))) = ?', [
                        strtolower($this->formatEmployeeDisplayName($user->first_name, $user->middle_name, $user->last_name) ?? ''),
                    ]);
                }
            })
            ->whereDate('attendance_date', '>=', now()->subDays(21)->toDateString())
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->filter(function ($record) {
                $lateMinutes = (int) ($record->late_minutes ?? 0);
                $hasMissingLogs = !empty($record->missing_time_logs) && $record->missing_time_logs !== '[]';
                return !empty($record->is_absent) || $lateMinutes > 0 || $hasMissingLogs;
            })
            ->map(function ($record) {
                $attendanceDate = $record->attendance_date
                    ? Carbon::parse($record->attendance_date)
                    : Carbon::parse($record->created_at);
                $lateMinutes = (int) ($record->late_minutes ?? 0);
                $statusLabel = !empty($record->is_absent)
                    ? 'Absent'
                    : ($lateMinutes > 0 ? 'Tardy' : 'Missing Logs');

                $message = !empty($record->is_absent)
                    ? 'You were marked absent on '.$attendanceDate->format('F j, Y').'.'
                    : ($lateMinutes > 0
                        ? 'Recorded '.$lateMinutes.' late minute(s) on '.$attendanceDate->format('F j, Y').'.'
                        : 'Your attendance entry on '.$attendanceDate->format('F j, Y').' has incomplete logs.');

                return [
                    'category' => 'Attendance',
                    'title' => 'Attendance follow-up',
                    'message' => $message,
                    'date' => $attendanceDate,
                    'href' => route('employee.employeeHome', ['focus' => 'weekly-attendance-section']),
                    'badge' => $statusLabel,
                    'tone' => !empty($record->is_absent) ? 'rose' : ($lateMinutes > 0 ? 'amber' : 'sky'),
                ];
            });

        $resignationNotifications = Resignation::query()
            ->where('user_id', $user->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(function ($resignation) {
                $status = trim((string) ($resignation->status ?? 'Pending'));
                $statusKey = strtolower($status);
                $submittedAt = $resignation->submitted_at
                    ? Carbon::parse($resignation->submitted_at)
                    : Carbon::parse($resignation->created_at);

                return [
                    'category' => 'Resignation',
                    'title' => 'Resignation request update',
                    'message' => 'Current status: '.$status.'.',
                    'date' => $submittedAt,
                    'href' => route('employee.employeeResignation', ['focus' => 'resignation-timeline-section']),
                    'badge' => $status,
                    'tone' => in_array($statusKey, ['approved', 'completed'], true) ? 'slate' : 'amber',
                ];
            });

        $notificationItems = collect()
            ->concat($leaveNotifications)
            ->concat($payslipNotifications)
            ->concat($attendanceNotifications)
            ->concat($resignationNotifications)
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
            'leave' => $leaveNotifications->count(),
            'payslip' => $payslipNotifications->count(),
            'attendance' => $attendanceNotifications->count(),
            'resignation' => $resignationNotifications->count(),
        ];

        return [$notificationItems, $notificationStats];
    }

    public function display_leave(){
        $user = Auth::user();
        $selectedMonth = trim((string) request()->query('month', now()->format('Y-m')));
        try {
            $monthCursor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthCursor = now()->startOfMonth();
            $selectedMonth = $monthCursor->format('Y-m');
        }

        $employeeDisplayName = $this->formatEmployeeDisplayName(
            $user?->first_name,
            $user?->middle_name,
            $user?->last_name
        );

        $isTeaching = strcasecmp((string) ($user?->employee?->job_type ?? ''), 'Teaching') === 0;
        $joinDate = null;
        if ($isTeaching && !empty($user?->applicant?->date_hired)) {
            $joinDate = Carbon::parse($user->applicant->date_hired);
        } elseif (!empty($user?->employee?->employement_date)) {
            $joinDate = Carbon::parse($user->employee->employement_date);
        } elseif (!empty($user?->applicant?->date_hired)) {
            $joinDate = Carbon::parse($user->applicant->date_hired);
        }
        $resetCycleMonths = $isTeaching ? 10 : 12;

        $defaultLeaveAllowances = [
            'Study Leave' => 5,
            'Emergency Leave' => 3,
            'Maternity Leave' => 105,
            'Paternity Leave' => 7,
            'Bereavement Leave' => 5,
            'Service Incentive Leave' => 5,
        ];

        $beginningVacationBalance = 0.0;
        $beginningSickBalance = 0.0;
        $totalEarnedDays = $this->calculateMonthlyEarnedLeaveDays(
            $joinDate,
            $monthCursor,
            $resetCycleMonths
        );
        $earnedRangeLabel = $this->buildEarnedRangeLabel($joinDate, $monthCursor, $resetCycleMonths);
        $monthStart = $monthCursor->copy()->startOfMonth();
        $monthEnd = $monthCursor->copy()->endOfMonth();
        $monthApplications = LeaveApplication::query()
            ->where('user_id', $user?->id)
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
        $pendingMonthApplications = $monthApplications
            ->filter(function ($application) {
                $status = trim((string) ($application->status ?? ''));
                return $status === '' || strcasecmp($status, 'Pending') === 0;
            })
            ->values();

        $mapApplicationToRecord = function ($application) use ($employeeDisplayName) {
            $leaveType = (string) ($application->leave_type ?: 'Leave');
            $leaveTypeNormalized = strtolower(trim($leaveType));
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

            $reasonText = $application->inclusive_dates ?: '-';
            if (str_contains($leaveTypeNormalized, 'official business')) {
                $reasonText = 'Business Trip';
            } elseif (str_contains($leaveTypeNormalized, 'annual leave')) {
                $reasonText = 'Personal vacation';
            } elseif (str_contains($leaveTypeNormalized, 'sick leave')) {
                $reasonText = 'Not fit for work due to health reasons';
            }

            $statusText = trim((string) ($application->status ?? ''));
            if ($statusText === '') {
                $statusText = 'Pending';
            }

            return [
                'employee_name' => $application->employee_name ?: $employeeDisplayName,
                'leave_type' => $leaveType,
                'start_date_carbon' => $baseDate->copy(),
                'end_date_carbon' => $baseDate->copy()->addDays($rangeDays - 1),
                'days' => $days,
                'reason' => $reasonText,
                'status' => $statusText,
            ];
        };

        $monthRequestRecords = $monthApplications
            ->map($mapApplicationToRecord)
            ->values();

        $employeeMonthRecords = $approvedMonthApplications
            ->map($mapApplicationToRecord)
            ->values();

        $latestLeaveApplication = LeaveApplication::query()
            ->where('user_id', $user?->id)
            ->whereDate('created_at', '<=', $monthEnd->toDateString())
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->orderByDesc('created_at')
            ->first();

        if ($latestLeaveApplication) {
            $beginningVacationBalance = (float) ($latestLeaveApplication->ending_vacation ?? 0);
            $beginningSickBalance = (float) ($latestLeaveApplication->ending_sick ?? 0);
        }

        $hasExistingMonthApplication = LeaveApplication::query()
            ->where('user_id', $user?->id)
            ->whereBetween('created_at', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()])
            ->exists();

        $equalHalfEarnedDays = round($totalEarnedDays / 2, 1);
        $formEarnedVacation = $hasExistingMonthApplication ? 0.0 : $equalHalfEarnedDays;
        $formEarnedSick = $hasExistingMonthApplication ? 0.0 : $equalHalfEarnedDays;
        $formEarnedTotal = round($formEarnedVacation + $formEarnedSick, 1);

        $monthlyLeaveAllowances = collect($defaultLeaveAllowances)
            ->mapWithKeys(fn ($value, $leaveType) => [$leaveType => max(0, (int) $value)])
            ->all();
        $monthlyLeaveAllowances['Annual Leave'] = $equalHalfEarnedDays;
        $monthlyLeaveAllowances['Sick Leave'] = $equalHalfEarnedDays;

        $employeeLeaveUsageByType = $employeeMonthRecords
            ->groupBy(fn ($record) => (string) ($record['leave_type'] ?? 'Leave'))
            ->map(fn ($records) => (int) $records->sum('days'));

        $annualLimit = (float) ($monthlyLeaveAllowances['Annual Leave'] ?? 0);
        $annualUsed = (float) ($employeeLeaveUsageByType->get('Annual Leave', 0));
        $sickLimit = (float) ($monthlyLeaveAllowances['Sick Leave'] ?? 0);
        $sickUsed = (float) ($employeeLeaveUsageByType->get('Sick Leave', 0));
        $personalLimit = (int) ($monthlyLeaveAllowances['Personal Leave'] ?? 0);
        $personalUsed = (int) ($employeeLeaveUsageByType->get('Personal Leave', 0));
        $totalDaysUsed = (float) $employeeMonthRecords->sum('days');

        $vacationCardAvailable = max($annualLimit - $annualUsed, 0);
        $sickCardAvailable = max($sickLimit - $sickUsed, 0);
        $totalDaysUsedCard = $totalDaysUsed;

        $monthAppliedTotal = round((float) $approvedMonthApplications->sum('applied_total'), 1);
        $monthOfficialWithPayTotal = round((float) $approvedMonthApplications
            ->filter(function ($application) {
                $leaveType = strtolower(trim((string) ($application->leave_type ?? '')));

                return str_contains($leaveType, 'official business')
                    || str_contains($leaveType, 'official time')
                    || str_starts_with($leaveType, 'others');
            })
            ->sum('days_with_pay'), 1);
        $monthUsageTotal = round($monthAppliedTotal + $monthOfficialWithPayTotal, 1);
        $pendingLeaveDays = round((float) $pendingMonthApplications->sum(function ($application) {
            return (float) ($application->number_of_working_days ?? 0);
        }), 1);

        if ($latestLeaveApplication) {
            $annualLimit = round((float) ($latestLeaveApplication->beginning_vacation ?? 0) + (float) ($latestLeaveApplication->earned_vacation ?? 0), 1);
            $annualUsed = round((float) ($latestLeaveApplication->applied_vacation ?? 0), 1);
            $sickLimit = round((float) ($latestLeaveApplication->beginning_sick ?? 0) + (float) ($latestLeaveApplication->earned_sick ?? 0), 1);
            $sickUsed = round((float) ($latestLeaveApplication->applied_sick ?? 0), 1);
            $vacationCardAvailable = round((float) ($latestLeaveApplication->ending_vacation ?? 0), 1);
            $sickCardAvailable = round((float) ($latestLeaveApplication->ending_sick ?? 0), 1);
            $fallbackUsedDays = (float) ($latestLeaveApplication->applied_total ?? $totalDaysUsed);
            $totalDaysUsedCard = round($monthUsageTotal > 0 ? $monthUsageTotal : $fallbackUsedDays, 1);
        }

        return view('employee.employeeLeave', compact(
            'selectedMonth',
            'employeeDisplayName',
            'monthRequestRecords',
            'employeeMonthRecords',
            'pendingMonthApplications',
            'pendingLeaveDays',
            'annualLimit',
            'annualUsed',
            'sickLimit',
            'sickUsed',
            'personalLimit',
            'personalUsed',
            'totalDaysUsed',
            'vacationCardAvailable',
            'sickCardAvailable',
            'totalDaysUsedCard',
            'beginningVacationBalance',
            'beginningSickBalance',
            'earnedRangeLabel',
            'totalEarnedDays',
            'formEarnedVacation',
            'formEarnedSick',
            'formEarnedTotal'
        ));
    }

    public function display_profile(){
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display')->withErrors([
                'email' => 'Please sign in to continue.',
            ]);
        }

        $emp = User::with([
            'employee',
            'applicant.documents',
            'education',
            'license',
            'salary',
            'government',
        ])->where('id', $user->id)->first();

        $serviceDurationText = '0Y 0M 0D';
        $joinDate = null;
        try {
            if (!empty($emp?->employee?->employement_date)) {
                $joinDate = Carbon::parse($emp->employee->employement_date)->startOfDay();
            } elseif (!empty($emp?->applicant?->date_hired)) {
                $joinDate = Carbon::parse($emp->applicant->date_hired)->startOfDay();
            }
        } catch (\Throwable $e) {
            $joinDate = null;
        }

        if ($joinDate) {
            $today = now()->startOfDay();
            if ($joinDate->gt($today)) {
                $joinDate = $today->copy();
            }
            $diff = $joinDate->diff($today);
            $serviceDurationText = $diff->y.'Y '.$diff->m.'M '.$diff->d.'D';
        }

        $ageDisplay = 'N/A';
        try {
            if (!empty($emp?->employee?->birthday)) {
                $birthday = Carbon::parse($emp->employee->birthday)->startOfDay();
                $today = now()->startOfDay();
                if ($birthday->lte($today)) {
                    $ageDiff = $birthday->diff($today);
                    $ageDisplay = $ageDiff->y.'Y '.$ageDiff->m.'M '.$ageDiff->d.'D';
                }
            }
        } catch (\Throwable $e) {
            $ageDisplay = 'N/A';
        }

        $attendanceMetrics = $this->buildEmployeeAttendanceMetrics($user, now()->format('Y-m'));
        $attendanceRatePercent = (float) ($attendanceMetrics['attendanceRatePercent'] ?? 0);
        $leaveMetrics = $this->buildEmployeeLeaveMetrics($user, now()->format('Y-m'));
        $leaveDaysUsed = (float) ($leaveMetrics['totalDaysUsedCard'] ?? 0);
        $today = now()->startOfDay();
        $isOnLeaveToday = LeaveApplication::query()
            ->where('user_id', $user?->id)
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get()
            ->contains(function ($application) use ($today) {
                try {
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
                    $endDate = $baseDate->copy()->addDays($rangeDays - 1);

                    return $today->betweenIncluded($baseDate, $endDate);
                } catch (\Throwable $e) {
                    return false;
                }
            });
        $employmentStatus = $isOnLeaveToday ? 'On Leave' : 'Active';
        $employmentStatusClass = $isOnLeaveToday ? 'text-amber-600' : 'text-emerald-600';
        $comparison = $this->buildApplicantComparisonMeta($emp?->applicant);
        $rehireMeta = [
            'is_rehire' => (bool) ($comparison['is_rehire'] ?? false),
            'label' => null,
        ];

        if ($rehireMeta['is_rehire'] && !empty($comparison['previous_applicant_id'])) {
            $rehireDate = null;
            try {
                $rehireDateValue = $emp?->applicant?->date_hired
                    ?: ($emp?->applicant?->getRawOriginal('created_at') ?? $emp?->applicant?->created_at);
                if (!empty($rehireDateValue)) {
                    $rehireDate = Carbon::parse($rehireDateValue)->format('M j, Y');
                }
            } catch (\Throwable $e) {
                $rehireDate = null;
            }

            $rehireMeta['label'] = $rehireDate ? 'Rehired on '.$rehireDate : 'Rehired Employee';
        }

        $profilePhotoDocument = optional($emp?->applicant)->documents
            ?->first(function ($doc) {
                return strtoupper(trim((string) ($doc->type ?? ''))) === 'PROFILE_PHOTO' && !empty($doc->filepath);
            });

        if (!$profilePhotoDocument) {
            $profilePhotoDocument = optional($emp?->applicant)->documents
                ?->first(function ($doc) {
                    $mime = strtolower(trim((string) ($doc->mime_type ?? '')));
                    $filename = strtolower(trim((string) ($doc->filename ?? '')));

                    return !empty($doc->filepath) && (str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|gif|webp)$/i', $filename));
                });
        }

        $profilePhotoUrl = $profilePhotoDocument?->filepath ? asset('storage/'.$profilePhotoDocument->filepath) : null;

        return view('employee.employeeProfile', compact(
            'emp',
            'serviceDurationText',
            'ageDisplay',
            'attendanceRatePercent',
            'leaveDaysUsed',
            'employmentStatus',
            'employmentStatusClass',
            'profilePhotoUrl',
            'rehireMeta'
        ));
    }

    public function display_payslip(){
        $user = Auth::user();
        $employeeId = trim((string) ($user?->employee?->employee_id ?? ''));
        $recordId = (int) request()->query('record_id', 0);

        $recentPayslips = PayslipRecord::query()
            ->where(function ($query) use ($user, $employeeId) {
                $query->where('user_id', (int) ($user?->id ?? 0));
                if ($employeeId !== '') {
                    $query->orWhere('employee_id', $employeeId);
                }
            })
            ->orderByDesc('pay_date')
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $latestPayslip = $recentPayslips->first();
        $selectedPayslip = null;
        if ($recordId > 0) {
            $selectedPayslip = $recentPayslips->firstWhere('id', $recordId);
        }
        if (!$selectedPayslip) {
            $selectedPayslip = $latestPayslip;
        }

        $grossSalary = null;
        $deductions = null;
        $netSalary = null;
        $others = null;

        if ($latestPayslip) {
            $salaryFields = ['basic_salary', 'living_allowance', 'extra_load', 'other_income'];
            $deductionFields = [
                'absences_amount',
                'withholding_tax',
                'salary_vale',
                'pag_ibig_loan',
                'pag_ibig_premium',
                'sss_loan',
                'sss_premium',
                'peraa_loan',
                'peraa_premium',
                'philhealth_premium',
                'other_deduction',
            ];

            $grossSalary = 0.0;
            $hasSalary = false;
            foreach ($salaryFields as $field) {
                $value = $latestPayslip->{$field};
                if ($value !== null && $value !== '') {
                    $grossSalary += (float) $value;
                    $hasSalary = true;
                }
            }
            if (!$hasSalary) {
                $grossSalary = null;
            }

            $deductions = 0.0;
            $hasDeduction = false;
            foreach ($deductionFields as $field) {
                $value = $latestPayslip->{$field};
                if ($value !== null && $value !== '') {
                    $deductions += (float) $value;
                    $hasDeduction = true;
                }
            }
            if (!$hasDeduction) {
                $deductions = null;
            }

            $netSalary = $latestPayslip->net_pay;
            if (($netSalary === null || $netSalary === '') && $grossSalary !== null && $deductions !== null) {
                $netSalary = $grossSalary - $deductions;
            }

            $others = $latestPayslip->other_income;
        }

        return view('employee.employeePayslip', compact(
            'recentPayslips',
            'latestPayslip',
            'selectedPayslip',
            'grossSalary',
            'deductions',
            'netSalary',
            'others'
        ));
    }

    public function display_document(){
        $user_id = Auth::id();
        $applicant = Applicant::where('user_id', $user_id)
                                ->where('application_status','Hired')
                                ->latest('id')
                                ->first();

        $documents = collect();
        $allDocuments = collect();
        $folders = collect();
        $latestDocument = null;
        $requiredDocuments = [];
        $missingDocuments = [];
        $documentNotice = '';
        $unfiledCount = 0;
        $selectedFolderKey = trim((string) request()->query('folder', 'all'));
        $activeFolderLabel = 'All Files';
        if ($applicant) {
            $requiredPrefix = '__REQUIRED__::';
            $noticeType = '__NOTICE__';
            $comparison = $this->buildApplicantComparisonMeta($applicant);
            $previousApplicant = null;
            if (!empty($comparison['previous_applicant_id'])) {
                $previousApplicant = Applicant::query()->find((int) $comparison['previous_applicant_id']);
            }

            $applicantIds = collect([(int) $applicant->id]);
            if ($previousApplicant) {
                $applicantIds->push((int) $previousApplicant->id);
            }

            $storedItems = ApplicantDocument::whereIn('applicant_id', $applicantIds->filter()->unique()->values()->all())
                ->where('type', 'not like', $requiredPrefix.'%')
                ->where('type', '!=', $noticeType)
                ->latest('created_at')
                ->get();
            $folders = $storedItems
                ->filter(fn (ApplicantDocument $document) => $this->isFolderDocument($document))
                ->map(function (ApplicantDocument $document) use ($storedItems) {
                    $folderKey = $this->folderKeyFromFolderDocument($document);

                    return [
                        'key' => $folderKey,
                        'name' => trim((string) $document->filename),
                        'count' => $storedItems
                            ->reject(fn (ApplicantDocument $item) => $this->isFolderDocument($item))
                            ->filter(fn (ApplicantDocument $item) => $this->folderKeyFromFileDocument($item) === $folderKey)
                            ->count(),
                    ];
                })
                ->filter(fn (array $folder) => $folder['key'] !== '')
                ->sortBy('name')
                ->values();

            $allDocuments = $this->decorateApplicantDocumentsForHistory(
                $storedItems
                    ->reject(fn (ApplicantDocument $document) => $this->isFolderDocument($document))
                    ->values(),
                $applicant,
                $previousApplicant,
                $comparison
            );
            $unfiledCount = $allDocuments
                ->filter(fn (ApplicantDocument $document) => $this->folderKeyFromFileDocument($document) === '')
                ->count();
            $latestDocument = $allDocuments->first();

            if ($selectedFolderKey === 'unfiled') {
                $documents = $allDocuments
                    ->filter(fn (ApplicantDocument $document) => $this->folderKeyFromFileDocument($document) === '')
                    ->values();
                $activeFolderLabel = 'Unfiled';
            } elseif ($selectedFolderKey !== 'all') {
                $selectedFolder = $folders->firstWhere('key', $selectedFolderKey);
                if ($selectedFolder) {
                    $documents = $allDocuments
                        ->filter(fn (ApplicantDocument $document) => $this->folderKeyFromFileDocument($document) === $selectedFolderKey)
                        ->values();
                    $activeFolderLabel = (string) ($selectedFolder['name'] ?? 'Folder');
                } else {
                    $selectedFolderKey = 'all';
                    $documents = $allDocuments;
                }
            } else {
                $documents = $allDocuments;
            }

            $requiredConfig = $this->getRequiredDocumentConfigForApplicant((int) $applicant->id);
            $requiredDocuments = collect($requiredConfig['required_documents'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all();
            $documentNotice = (string) ($requiredConfig['document_notice'] ?? '');

            $uploadedDocumentTypesNormalized = $allDocuments
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

            if (empty($missingDocuments)) {
                $documentNotice = '';
            }
        }

        return view('employee.employeeDocument', compact(
            'documents',
            'folders',
            'allDocuments',
            'latestDocument',
            'requiredDocuments',
            'missingDocuments',
            'documentNotice',
            'unfiledCount',
            'selectedFolderKey',
            'activeFolderLabel'
        ));
    }

    public function view_document($id)
    {
        $userId = Auth::id();
        $applicant = Applicant::where('user_id', $userId)
            ->where('application_status', 'Hired')
            ->latest('id')
            ->first();

        if (!$applicant) {
            abort(403);
        }

        $allowedApplicantIds = $this->documentAccessibleApplicantIds($applicant);
        $document = ApplicantDocument::where('id', $id)
            ->whereIn('applicant_id', $allowedApplicantIds)
            ->firstOrFail();
        if ($this->isFolderDocument($document)) {
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
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function display_document_preview($id)
    {
        $userId = Auth::id();
        $applicant = Applicant::where('user_id', $userId)
            ->where('application_status', 'Hired')
            ->latest('id')
            ->first();

        if (!$applicant) {
            abort(403);
        }

        $allowedApplicantIds = $this->documentAccessibleApplicantIds($applicant);
        $document = ApplicantDocument::where('id', $id)
            ->whereIn('applicant_id', $allowedApplicantIds)
            ->firstOrFail();
        if ($this->isFolderDocument($document)) {
            abort(404);
        }

        $fileName = (string) ($document->filename ?: 'Document');
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $previewUrl = route('employee.employeeDocument.view', ['id' => $document->id]);
        $isPdf = $extension === 'pdf';
        $isImage = in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true);

        return view('employee.employeeDocumentPreview', compact(
            'document',
            'previewUrl',
            'isPdf',
            'isImage',
            'extension'
        ));
    }

    public function display_communication(){
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display');
        }

        $admins = User::query()
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) IN (?, ?)", ['admin', 'administrator'])
            ->orderBy('first_name')
            ->get();

        if (!Schema::hasTable('conversations') || !Schema::hasTable('conversation_messages')) {
            return view('employee.employeeCommunication', [
                'admins' => $admins,
                'conversations' => collect(),
                'conversationSummaries' => collect(),
                'selectedConversation' => null,
                'selectedParticipant' => null,
            ])->with('warning', 'Communication tables are not ready yet. Please run the latest migration.');
        }

        $resetChat = request()->boolean('reset_chat');
        $selectedParticipantId = $resetChat ? 0 : (int) request()->query('user', 0);
        $selectedConversationId = $resetChat ? 0 : (int) request()->query('conversation', 0);
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
            $selectedParticipant = $admins->firstWhere('id', $selectedParticipantId);
            if ($selectedParticipant) {
                $selectedConversation = $conversations->first(function (Conversation $conversation) use ($selectedParticipant, $user) {
                    $otherParticipant = $conversation->otherParticipantFor((int) $user->id);
                    return (int) ($otherParticipant?->id ?? 0) === (int) $selectedParticipant->id;
                });
            }
        }

        if (!$resetChat && !$selectedConversation && !$selectedParticipant) {
            $selectedConversation = $conversations
                ->first(fn (Conversation $conversation) => (int) ($conversation->unread_count ?? 0) > 0)
                ?? $conversations->first();

            if ($selectedConversation) {
                $selectedParticipant = $selectedConversation->otherParticipantFor((int) $user->id);
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

        $admins = $admins->map(function ($admin) use ($unreadCountsByParticipant) {
            $admin->unread_message_count = (int) $unreadCountsByParticipant->get((int) $admin->id, 0);
            $admin->has_unread_messages = $admin->unread_message_count > 0;
            return $admin;
        });

        return view('employee.employeeCommunication', compact(
            'admins',
            'conversations',
            'conversationSummaries',
            'selectedConversation',
            'selectedParticipant'
        ));
    }

    public function display_resignation(){
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login_display');
        }

        $resignations = Resignation::query()
            ->where('user_id', $user->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        return view('employee.employeeResignation', compact('resignations'));
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

    private function resolveEmployeeDepartmentName(?User $user, string $fallback = 'Unassigned Department'): string
    {
        $department = trim((string) ($user?->department ?? ''));
        if ($department !== '') {
            return $department;
        }

        $employeeDepartment = trim((string) ($user?->employee?->department ?? ''));
        if ($employeeDepartment !== '') {
            return $employeeDepartment;
        }

        $applicantDepartment = trim((string) (optional($user?->applicant?->position)->department ?? ''));
        if ($applicantDepartment !== '') {
            return $applicantDepartment;
        }

        return $fallback;
    }

    private function resolveEmployeePositionName(?User $user, string $fallback = 'Employee'): string
    {
        $position = trim((string) ($user?->position ?? ''));
        if ($position !== '') {
            return $position;
        }

        $employeePosition = trim((string) ($user?->employee?->position ?? ''));
        if ($employeePosition !== '') {
            return $employeePosition;
        }

        $applicantPosition = trim((string) (optional($user?->applicant?->position)->title ?? ''));
        if ($applicantPosition !== '') {
            return $applicantPosition;
        }

        return $fallback;
    }

    private function buildHierarchyDisplayName(?User $user): string
    {
        $first = trim((string) ($user?->first_name ?? ''));
        $middle = trim((string) ($user?->middle_name ?? ''));
        $last = trim((string) ($user?->last_name ?? ''));
        $name = trim(implode(' ', array_filter([$first, $middle, $last], fn ($value) => $value !== '')));

        return $name !== '' ? $name : 'Employee';
    }

    private function buildHierarchyFallbackInitials(?User $user): string
    {
        $parts = array_values(array_filter([
            substr(trim((string) ($user?->first_name ?? '')), 0, 1),
            substr(trim((string) ($user?->last_name ?? '')), 0, 1),
        ], fn ($value) => $value !== ''));

        if (empty($parts)) {
            return 'EM';
        }

        return strtoupper(implode('', $parts));
    }

    private function resolveEmployeeProfilePhotoUrl(?User $user): ?string
    {
        $profilePhotoDocument = optional($user?->applicant)->documents
            ?->first(function ($doc) {
                return strtoupper(trim((string) ($doc->type ?? ''))) === 'PROFILE_PHOTO' && !empty($doc->filepath);
            });

        if (!$profilePhotoDocument) {
            $profilePhotoDocument = optional($user?->applicant)->documents
                ?->first(function ($doc) {
                    $mime = strtolower(trim((string) ($doc->mime_type ?? '')));
                    $filename = strtolower(trim((string) ($doc->filename ?? '')));

                    return !empty($doc->filepath) && (str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|gif|webp)$/i', $filename));
                });
        }

        return $profilePhotoDocument?->filepath ? asset('storage/'.$profilePhotoDocument->filepath) : null;
    }

    private function resolveHierarchyEmployeeId(?User $user): string
    {
        return trim((string) ($user?->employee?->employee_id ?? $user?->employee_id ?? '')) ?: 'Not set';
    }

    private function resolveHierarchyEmployeeEmail(?User $user): string
    {
        return trim((string) (optional($user?->applicant)->email_address ?? $user?->email ?? '')) ?: 'No email';
    }

    private function resolveHierarchyEmploymentStatus(?User $user): string
    {
        $classification = strtolower(trim((string) (
            $user?->employee?->classification
            ?? optional(optional($user?->applicant)->position)->employment
            ?? $user?->classification
            ?? ''
        )));

        if ($classification !== '') {
            if (str_contains($classification, 'permanent') || str_contains($classification, 'regular')) {
                return 'Permanent';
            }

            if (str_contains($classification, 'probationary')) {
                return 'Probationary';
            }
        }

        $jobType = strtolower(trim((string) (
            $user?->employee?->job_type
            ?? optional(optional($user?->applicant)->position)->job_type
            ?? ''
        )));
        $rawJoinDate = $user?->applicant?->date_hired ?? $user?->employee?->employement_date;

        if (in_array($jobType, ['non-teaching', 'teaching'], true)) {
            if (!empty($rawJoinDate)) {
                try {
                    $joinDate = Carbon::parse($rawJoinDate);
                    $threshold = $jobType === 'non-teaching'
                        ? $joinDate->copy()->addMonths(6)
                        : $joinDate->copy()->addYears(3);

                    return now()->lt($threshold) ? 'Probationary' : 'Permanent';
                } catch (\Throwable $e) {
                    return 'Probationary';
                }
            }

            return 'Probationary';
        }

        return 'Not set';
    }

    private function hierarchyManagerScore(?User $user): int
    {
        $position = strtolower($this->resolveEmployeePositionName($user, ''));
        if ($position === '') {
            return 0;
        }

        return match (true) {
            str_contains($position, 'head') => 5,
            str_contains($position, 'director') => 4,
            str_contains($position, 'manager') => 4,
            str_contains($position, 'supervisor') => 3,
            str_contains($position, 'lead') => 3,
            str_contains($position, 'chief') => 3,
            str_contains($position, 'coordinator') => 2,
            str_contains($position, 'officer') => 1,
            default => 0,
        };
    }

    private function getSharedLeaveRecords()
    {
        return collect([
            [
                'employee_name' => 'Santos, Maria L.',
                'department' => 'Faculty',
                'leave_type' => 'Sick Leave',
                'start_date' => '2026-02-12',
                'end_date' => '2026-02-12',
                'status' => 'Approved',
                'reason' => 'Flu and medical check-up',
            ],
            [
                'employee_name' => 'Reyes, John Paulo A.',
                'department' => 'Admin',
                'leave_type' => 'Annual Leave',
                'start_date' => '2026-02-10',
                'end_date' => '2026-02-14',
                'status' => 'Approved',
                'reason' => 'Family vacation',
            ],
            [
                'employee_name' => 'Dela Cruz, Anna P.',
                'department' => 'Faculty',
                'leave_type' => 'Study Leave',
                'start_date' => '2026-02-17',
                'end_date' => '2026-02-18',
                'status' => 'Pending',
                'reason' => 'Graduate exam preparation',
            ],
            [
                'employee_name' => 'Garcia, Miguel R.',
                'department' => 'Registrar',
                'leave_type' => 'Emergency Leave',
                'start_date' => '2026-02-08',
                'end_date' => '2026-02-08',
                'status' => 'Approved',
                'reason' => 'Immediate family concern',
            ],
            [
                'employee_name' => 'Lopez, Carla M.',
                'department' => 'Faculty',
                'leave_type' => 'Maternity Leave',
                'start_date' => '2026-01-20',
                'end_date' => '2026-02-20',
                'status' => 'Approved',
                'reason' => 'Maternity recovery',
            ],
            [
                'employee_name' => 'Torres, Noel B.',
                'department' => 'Guidance',
                'leave_type' => 'Paternity Leave',
                'start_date' => '2026-02-05',
                'end_date' => '2026-02-11',
                'status' => 'Approved',
                'reason' => 'Child birth support',
            ],
            [
                'employee_name' => 'Nolasco, Irene T.',
                'department' => 'HR',
                'leave_type' => 'Personal Leave',
                'start_date' => '2026-02-22',
                'end_date' => '2026-02-22',
                'status' => 'Declined',
                'reason' => 'Personal errand',
            ],
        ])->map(function ($record) {
            $start = Carbon::parse($record['start_date'])->startOfDay();
            $end = Carbon::parse($record['end_date'])->startOfDay();
            $days = $end->gte($start) ? ($start->diffInDays($end) + 1) : 1;
            $record['days'] = $days;
            $record['start_date_carbon'] = $start;
            $record['end_date_carbon'] = $end;
            return $record;
        });
    }

    private function calculateMonthlyEarnedLeaveDays(?Carbon $joinDate, Carbon $monthCursor, ?int $resetCycleMonths = null): int
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

    private function calculateCompletedMonthsUntilCutoff(?Carbon $joinDate, Carbon $monthCursor): int
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

        return max(0, $months);
    }

    private function buildEarnedRangeLabel(?Carbon $joinDate, Carbon $monthCursor, int $resetCycleMonths): string
    {
        if (!$joinDate || $resetCycleMonths <= 0) {
            return '-';
        }

        $completedMonths = $this->calculateCompletedMonthsUntilCutoff($joinDate, $monthCursor);
        if ($completedMonths <= 0) {
            return '-';
        }

        $accrualStartMonth = $joinDate->copy()->addYear()->startOfMonth();
        $monthsInCurrentCycle = (($completedMonths - 1) % $resetCycleMonths) + 1;
        $completedCycleMonths = $completedMonths - $monthsInCurrentCycle;

        $rangeStart = $accrualStartMonth->copy()->addMonths($completedCycleMonths)->startOfMonth();
        $rangeEnd = $rangeStart->copy()->addMonths($monthsInCurrentCycle - 1)->startOfMonth();

        if ($rangeStart->year === $rangeEnd->year) {
            if ($rangeStart->format('M') === $rangeEnd->format('M')) {
                return $rangeStart->format('M').', '.$rangeStart->year;
            }

            return $rangeStart->format('M').'-'.$rangeEnd->format('M').', '.$rangeStart->year;
        }

        return $rangeStart->format('M Y').' - '.$rangeEnd->format('M Y');
    }

    private function buildEmployeeLeaveMetrics($user, string $selectedMonth): array
    {
        try {
            $monthCursor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthCursor = now()->startOfMonth();
            $selectedMonth = $monthCursor->format('Y-m');
        }

        $isTeaching = strcasecmp((string) ($user?->employee?->job_type ?? ''), 'Teaching') === 0;
        $joinDate = null;
        if ($isTeaching && !empty($user?->applicant?->date_hired)) {
            $joinDate = Carbon::parse($user->applicant->date_hired);
        } elseif (!empty($user?->employee?->employement_date)) {
            $joinDate = Carbon::parse($user->employee->employement_date);
        } elseif (!empty($user?->applicant?->date_hired)) {
            $joinDate = Carbon::parse($user->applicant->date_hired);
        }

        $resetCycleMonths = $isTeaching ? 10 : 12;
        $monthEnd = $monthCursor->copy()->endOfMonth();
        $equalHalfEarnedDays = round(
            $this->calculateMonthlyEarnedLeaveDays($joinDate, $monthCursor, $resetCycleMonths) / 2,
            1
        );

        $annualLimit = $equalHalfEarnedDays;
        $sickLimit = $equalHalfEarnedDays;
        $annualUsed = 0.0;
        $sickUsed = 0.0;
        $vacationCardAvailable = max($annualLimit - $annualUsed, 0);
        $sickCardAvailable = max($sickLimit - $sickUsed, 0);

        $latestLeaveApplication = LeaveApplication::query()
            ->where('user_id', $user?->id)
            ->whereDate('created_at', '<=', $monthEnd->toDateString())
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->orderByDesc('created_at')
            ->first();

        if ($latestLeaveApplication) {
            $annualLimit = round((float) ($latestLeaveApplication->beginning_vacation ?? 0) + (float) ($latestLeaveApplication->earned_vacation ?? 0), 1);
            $annualUsed = round((float) ($latestLeaveApplication->applied_vacation ?? 0), 1);
            $sickLimit = round((float) ($latestLeaveApplication->beginning_sick ?? 0) + (float) ($latestLeaveApplication->earned_sick ?? 0), 1);
            $sickUsed = round((float) ($latestLeaveApplication->applied_sick ?? 0), 1);
            $vacationCardAvailable = round((float) ($latestLeaveApplication->ending_vacation ?? 0), 1);
            $sickCardAvailable = round((float) ($latestLeaveApplication->ending_sick ?? 0), 1);
        }

        // Keep Days Used consistent with employeeLeave summary logic.
        $monthApplications = LeaveApplication::query()
            ->where('user_id', $user?->id)
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

        $monthAppliedTotal = round((float) $approvedMonthApplications->sum('applied_total'), 1);
        $monthOfficialWithPayTotal = round((float) $approvedMonthApplications
            ->filter(function ($application) {
                $leaveType = strtolower(trim((string) ($application->leave_type ?? '')));

                return str_contains($leaveType, 'official business')
                    || str_contains($leaveType, 'official time')
                    || str_starts_with($leaveType, 'others');
            })
            ->sum('days_with_pay'), 1);
        $monthUsageTotal = round($monthAppliedTotal + $monthOfficialWithPayTotal, 1);
        $totalDaysUsedCard = $monthUsageTotal;

        if ($latestLeaveApplication) {
            $fallbackUsedDays = (float) ($latestLeaveApplication->applied_total ?? 0);
            $totalDaysUsedCard = round($monthUsageTotal > 0 ? $monthUsageTotal : $fallbackUsedDays, 1);
        }

        $combinedAvailable = round($vacationCardAvailable + $sickCardAvailable, 1);
        $combinedLimit = max(round($annualLimit + $sickLimit, 1), 0.0);
        $combinedUsed = round($annualUsed + $sickUsed, 1);
        $combinedUsagePercent = $combinedLimit > 0
            ? (int) round(min(100, max(0, ($combinedUsed / $combinedLimit) * 100)))
            : 0;

        return [
            'vacationCardAvailable' => $vacationCardAvailable,
            'sickCardAvailable' => $sickCardAvailable,
            'annualLimit' => $annualLimit,
            'annualUsed' => $annualUsed,
            'sickLimit' => $sickLimit,
            'sickUsed' => $sickUsed,
            'combinedLeaveAvailable' => $combinedAvailable,
            'combinedLeavePercentUsed' => $combinedUsagePercent,
            'totalDaysUsedCard' => $totalDaysUsedCard,
            'selectedMonth' => $selectedMonth,
        ];
    }

    private function buildEmployeeAttendanceMetrics($user, string $selectedMonth): array
    {
        try {
            $monthCursor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthCursor = now()->startOfMonth();
        }

        $rangeStart = $monthCursor->copy()->startOfMonth();
        $rangeEnd = $monthCursor->copy()->endOfMonth();
        $holidayNoClassDates = $this->getHolidayNoClassDatesForMonth($monthCursor);

        $eligibleDays = 0;
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            if (!$cursor->isSunday()) {
                $eligibleDays++;
            }
            $cursor->addDay();
        }

        $employeeIdCandidates = $this->buildEmployeeIdCandidatesForAttendance($user);
        if (empty($employeeIdCandidates)) {
            return [
                'attendanceRatePercent' => 0.0,
                'attendancePresentDays' => 0,
                'attendanceTotalDays' => $eligibleDays,
                'attendanceTardyDays' => 0,
                'attendanceStatusLabel' => 'No Data',
                'attendanceMonthLabel' => $monthCursor->format('F Y'),
            ];
        }

        $recordsByDate = AttendanceRecord::query()
            ->whereIn('employee_id', $employeeIdCandidates)
            ->whereDate('attendance_date', '>=', $rangeStart->toDateString())
            ->whereDate('attendance_date', '<=', $rangeEnd->toDateString())
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(function ($row) {
                try {
                    return $row->attendance_date
                        ? Carbon::parse($row->attendance_date)->toDateString()
                        : null;
                } catch (\Throwable $e) {
                    return null;
                }
            });

        $presentDays = 0;
        $tardyDays = 0;
        $cursor = $rangeStart->copy();

        while ($cursor->lte($rangeEnd)) {
            if ($cursor->isSunday()) {
                $cursor->addDay();
                continue;
            }

            $dateKey = $cursor->toDateString();
            if (in_array($dateKey, $holidayNoClassDates, true)) {
                $presentDays++;
                $cursor->addDay();
                continue;
            }
            $dateRows = $recordsByDate->get($dateKey, collect());
            $isPresent = false;
            $isTardy = false;

            foreach ($dateRows as $row) {
                if (!$this->isAttendanceRowPresent($row)) {
                    continue;
                }

                $isPresent = true;

                $isHolidayPresent = (bool) ($row->is_holiday_present ?? false)
                    || trim((string) ($row->main_gate ?? '')) === 'Holiday - No Class';
                if (!$isHolidayPresent && $this->calculateAttendanceLateMinutes($row) > 0) {
                    $isTardy = true;
                }
            }

            if ($isPresent) {
                $presentDays++;
            }
            if ($isTardy) {
                $tardyDays++;
            }

            $cursor->addDay();
        }

        $attendanceRate = $eligibleDays > 0
            ? round(($presentDays / $eligibleDays) * 100, 1)
            : 0.0;

        $statusLabel = $tardyDays > 0
            ? $tardyDays.' Late Day'.($tardyDays > 1 ? 's' : '')
            : 'On Time';

        return [
            'attendanceRatePercent' => $attendanceRate,
            'attendancePresentDays' => $presentDays,
            'attendanceTotalDays' => $eligibleDays,
            'attendanceTardyDays' => $tardyDays,
            'attendanceStatusLabel' => $statusLabel,
            'attendanceMonthLabel' => $monthCursor->format('F Y'),
        ];
    }

    private function buildWeeklyAttendanceData($user): array
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(5)->endOfDay(); // Monday-Saturday

        $employeeIdCandidates = $this->buildEmployeeIdCandidatesForAttendance($user);
        $recordsByDate = collect();

        if (!empty($employeeIdCandidates)) {
            $recordsByDate = AttendanceRecord::query()
                ->whereIn('employee_id', $employeeIdCandidates)
                ->whereDate('attendance_date', '>=', $weekStart->toDateString())
                ->whereDate('attendance_date', '<=', $weekEnd->toDateString())
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy(function ($row) {
                    try {
                        return $row->attendance_date
                            ? Carbon::parse($row->attendance_date)->toDateString()
                            : null;
                    } catch (\Throwable $e) {
                        return null;
                    }
                });
        }

        $weeklyRows = collect();
        $cursor = $weekStart->copy();
        while ($cursor->lte($weekEnd)) {
            $dateKey = $cursor->toDateString();
            $rows = $recordsByDate->get($dateKey, collect());
            $selectedRow = null;

            foreach ($rows as $row) {
                if ($this->isAttendanceRowPresent($row)) {
                    $selectedRow = $row;
                    break;
                }
            }
            if (!$selectedRow && $rows->isNotEmpty()) {
                $selectedRow = $rows->first();
            }

            $morningRange = $this->formatTimeRangeForDisplay(
                $selectedRow->morning_in ?? null,
                $selectedRow->morning_out ?? null
            );
            $afternoonRange = $this->formatTimeRangeForDisplay(
                $selectedRow->afternoon_in ?? null,
                $selectedRow->afternoon_out ?? null
            );
            $morningHours = $this->calculateWorkedHoursForDisplay(
                $selectedRow->morning_in ?? null,
                $selectedRow->morning_out ?? null
            );
            $afternoonHours = $this->calculateWorkedHoursForDisplay(
                $selectedRow->afternoon_in ?? null,
                $selectedRow->afternoon_out ?? null
            );

            $status = 'Absent';
            $statusClass = 'bg-rose-100 text-rose-600';
            if ($selectedRow) {
                $isHolidayPresent = (bool) ($selectedRow->is_holiday_present ?? false)
                    || trim((string) ($selectedRow->main_gate ?? '')) === 'Holiday - No Class';
                if ($isHolidayPresent) {
                    $status = 'No Class (Holiday)';
                    $statusClass = 'bg-indigo-100 text-indigo-600';
                } elseif ($this->isAttendanceRowPresent($selectedRow)) {
                    $late = $this->calculateAttendanceLateMinutes($selectedRow);
                    if ($late > 0) {
                        $status = 'Late ('.$late.' mins)';
                        $statusClass = 'bg-amber-100 text-amber-700';
                    } else {
                        $status = 'Present';
                        $statusClass = 'bg-green-100 text-green-600';
                    }
                }
            } else {
                $morningRange = 'No Log';
                $afternoonRange = 'No Log';
                $morningHours = '0 hrs worked';
                $afternoonHours = '0 hrs worked';
            }

            $weeklyRows->push([
                'day_short' => $cursor->format('D'),
                'day_number' => $cursor->format('d'),
                'date_label' => $cursor->format('M d, Y'),
                'morning_range' => $morningRange,
                'afternoon_range' => $afternoonRange,
                'morning_hours' => $morningHours,
                'afternoon_hours' => $afternoonHours,
                'status' => $status,
                'status_class' => $statusClass,
            ]);

            $cursor->addDay();
        }

        return [
            'weeklyAttendanceRows' => $weeklyRows->all(),
            'weeklyAttendanceRangeLabel' => $weekStart->format('M d').' - '.$weekEnd->format('M d'),
        ];
    }

    private function buildEmployeeIdCandidatesForAttendance($user): array
    {
        $raw = trim((string) ($user?->employee?->employee_id ?? ''));
        $normalized = $this->normalizeEmployeeIdForAttendance($raw);

        $candidates = collect();
        if ($normalized !== '') {
            $candidates->push($normalized);
            if (preg_match('/^\d+$/', $normalized)) {
                $candidates->push($normalized.'.0');
            }
        }

        // Fallback: resolve employee IDs from attendance employee_name for accounts
        // where employee_id mapping is missing or inconsistent.
        $firstName = trim((string) ($user?->first_name ?? ''));
        $lastName = trim((string) ($user?->last_name ?? ''));
        if ($firstName !== '' || $lastName !== '') {
            $matchedIds = AttendanceRecord::query()
                ->whereNotNull('employee_name')
                ->where(function ($query) use ($firstName, $lastName) {
                    if ($lastName !== '') {
                        $query->where('employee_name', 'like', '%'.$lastName.'%');
                    }
                    if ($firstName !== '') {
                        $query->where('employee_name', 'like', '%'.$firstName.'%');
                    }
                })
                ->distinct()
                ->pluck('employee_id')
                ->map(fn ($id) => $this->normalizeEmployeeIdForAttendance($id))
                ->filter()
                ->values();

            $candidates = $candidates->merge($matchedIds);
        }

        return $candidates
            ->flatMap(function ($id) {
                $idText = (string) $id;
                if (preg_match('/^\d+$/', $idText)) {
                    return [$idText, $idText.'.0'];
                }
                return [$idText];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function getHolidayNoClassDatesForMonth(Carbon $monthCursor): array
    {
        $monthStart = $monthCursor->copy()->startOfMonth();
        $monthEnd = $monthCursor->copy()->endOfMonth();
        $dates = collect();

        // Admin calendar-synced holiday config (custom + recurring holidays).
        try {
            if (Storage::disk('local')->exists('calendar_holiday_config.json')) {
                $payload = json_decode((string) Storage::disk('local')->get('calendar_holiday_config.json'), true);
                if (is_array($payload)) {
                    $customHolidays = is_array($payload['custom_holidays'] ?? null) ? $payload['custom_holidays'] : [];
                    foreach (array_keys($customHolidays) as $dateText) {
                        try {
                            $date = Carbon::parse((string) $dateText)->toDateString();
                            if ($date >= $monthStart->toDateString() && $date <= $monthEnd->toDateString()) {
                                $dates->push($date);
                            }
                        } catch (\Throwable $e) {
                        }
                    }

                    $recurringHolidays = is_array($payload['recurring_holidays'] ?? null) ? $payload['recurring_holidays'] : [];
                    foreach (array_keys($recurringHolidays) as $monthDay) {
                        if (!preg_match('/^\d{2}-\d{2}$/', (string) $monthDay)) {
                            continue;
                        }
                        [$month, $day] = array_map('intval', explode('-', (string) $monthDay));
                        if ($month !== (int) $monthCursor->month) {
                            continue;
                        }
                        if (!checkdate($month, $day, (int) $monthCursor->year)) {
                            continue;
                        }
                        $dates->push(Carbon::create((int) $monthCursor->year, $month, $day)->toDateString());
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        // Also include attendance rows tagged as holiday no-class.
        try {
            $dates = $dates->merge(
                AttendanceRecord::query()
                    ->whereDate('attendance_date', '>=', $monthStart->toDateString())
                    ->whereDate('attendance_date', '<=', $monthEnd->toDateString())
                    ->where('main_gate', 'Holiday - No Class')
                    ->pluck('attendance_date')
                    ->map(function ($date) {
                        try {
                            return Carbon::parse($date)->toDateString();
                        } catch (\Throwable $e) {
                            return null;
                        }
                    })
                    ->filter()
                    ->values()
            );
        } catch (\Throwable $e) {
        }

        return $dates->unique()->values()->all();
    }

    private function normalizeEmployeeIdForAttendance($value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^(\d+)\.0+$/', $normalized, $matches)) {
            return $matches[1];
        }

        return $normalized;
    }

    private function isAttendanceRowPresent($row): bool
    {
        $mainGate = strtolower(trim((string) ($row->main_gate ?? '')));
        if ($mainGate === 'leave - with pay') {
            return true;
        }
        if ($mainGate === 'leave - without pay') {
            return false;
        }

        $isHolidayPresent = (bool) ($row->is_holiday_present ?? false)
            || trim((string) ($row->main_gate ?? '')) === 'Holiday - No Class';
        if ($isHolidayPresent) {
            return true;
        }

        $hasAnyTimeLog = !empty($row->morning_in)
            || !empty($row->morning_out)
            || !empty($row->afternoon_in)
            || !empty($row->afternoon_out);
        if (!$hasAnyTimeLog) {
            return false;
        }

        return !(bool) ($row->is_absent ?? false);
    }

    private function calculateAttendanceLateMinutes($row): int
    {
        $late = 0;

        $morningIn = $this->normalizeTimeValue($row->morning_in ?? null);
        if ($morningIn) {
            try {
                $morningActual = Carbon::createFromFormat('H:i:s', $morningIn);
                $morningExpected = Carbon::createFromFormat('H:i:s', '08:00:00');
                $morningGraceEnd = Carbon::createFromFormat('H:i:s', '08:15:00');
                if ($morningActual->greaterThan($morningGraceEnd)) {
                    $late += $morningExpected->diffInMinutes($morningActual);
                }
            } catch (\Throwable $e) {
            }
        }

        $afternoonIn = $this->normalizeTimeValue($row->afternoon_in ?? null);
        if ($afternoonIn) {
            try {
                $afternoonActual = Carbon::createFromFormat('H:i:s', $afternoonIn);
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

    private function normalizeTimeValue(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        $value = trim((string) $time);
        if ($value === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i:s');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatTimeRangeForDisplay(?string $start, ?string $end): string
    {
        $startNorm = $this->normalizeTimeValue($start);
        $endNorm = $this->normalizeTimeValue($end);

        if (!$startNorm && !$endNorm) {
            return 'No Log';
        }

        $startText = $startNorm ? Carbon::createFromFormat('H:i:s', $startNorm)->format('h:i A') : '--';
        $endText = $endNorm ? Carbon::createFromFormat('H:i:s', $endNorm)->format('h:i A') : '--';

        return $startText.' - '.$endText;
    }

    private function calculateWorkedHoursForDisplay(?string $start, ?string $end): string
    {
        $startNorm = $this->normalizeTimeValue($start);
        $endNorm = $this->normalizeTimeValue($end);
        if (!$startNorm || !$endNorm) {
            return '0 hrs worked';
        }

        try {
            $startTime = Carbon::createFromFormat('H:i:s', $startNorm);
            $endTime = Carbon::createFromFormat('H:i:s', $endNorm);
            if ($endTime->lt($startTime)) {
                return '0 hrs worked';
            }
            $minutes = $startTime->diffInMinutes($endTime);
            $hours = round($minutes / 60, 1);
            return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.').' hrs worked';
        } catch (\Throwable $e) {
            return '0 hrs worked';
        }
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

    private function isFolderDocument(ApplicantDocument $document): bool
    {
        return trim((string) ($document->type ?? '')) === self::FOLDER_TYPE;
    }

    private function folderKeyFromFolderDocument(ApplicantDocument $document): string
    {
        $path = trim(str_replace('\\', '/', (string) ($document->filepath ?? '')), '/');
        if (str_starts_with($path, 'system/folders/')) {
            return trim((string) Str::after($path, 'system/folders/'));
        }

        return '';
    }

    private function folderKeyFromFileDocument(ApplicantDocument $document): string
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

    private function documentAccessibleApplicantIds(?Applicant $applicant): array
    {
        if (!$applicant) {
            return [];
        }

        $ids = collect([(int) $applicant->id]);
        $comparison = $this->buildApplicantComparisonMeta($applicant);
        if (!empty($comparison['previous_applicant_id'])) {
            $ids->push((int) $comparison['previous_applicant_id']);
        }

        return $ids
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function buildApplicantComparisonMeta(?Applicant $applicant): array
    {
        if (!$applicant) {
            return [
                'is_rehire' => false,
                'previous_applicant_id' => null,
            ];
        }

        $previousApplicant = $this->resolvePreviousComparableApplicant($applicant);
        if (!$previousApplicant) {
            return [
                'is_rehire' => false,
                'previous_applicant_id' => null,
            ];
        }

        return [
            'is_rehire' => true,
            'previous_applicant_id' => (int) $previousApplicant->id,
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

    private function resolvePreviousComparableApplicant(Applicant $applicant): ?Applicant
    {
        $normalizedEmail = strtolower(trim((string) ($applicant->email ?? '')));
        $userId = (int) ($applicant->user_id ?? 0);

        return Applicant::query()
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
            ->orderByDesc('id')
            ->first();
    }

}
