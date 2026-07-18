<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\ApplicantDegree;
use App\Models\ApplicantDocument;
use App\Models\Conversation;
use App\Models\Education;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\Government;
use App\Models\Interviewer;
use App\Models\License;
use App\Models\LeaveApplication;
use App\Models\LoadsRecord;
use App\Models\LoadsUpload;
use App\Models\OpenPosition;
use App\Models\PayslipRecord;
use App\Models\PayslipUpload;
use App\Models\Resignation;
use App\Models\Salary;
use App\Models\User;
use App\Support\ActivityChangeLogger;
use App\Support\EmployeeAccountStatusManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Mail\ApplicationUpdatedMail;
use App\Mail\ApplicationInterviewMail;
use Illuminate\Support\Facades\Mail;


class AdministratorStoreController extends Controller
{
    public function mark_applicant_document_reviewed(Request $request, ApplicantDocument $document)
    {
        if (!$document->reviewed_at) {
            $document->forceFill([
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
            ])->save();
        }

        return response()->json([
            'ok' => true,
            'document_id' => $document->id,
            'reviewed_at' => optional($document->reviewed_at)->toIso8601String(),
        ]);
    }

    public function send_communication_message(Request $request)
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('conversation_messages')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Communication tables are not ready yet. Please run the latest migration.',
                ], 503);
            }
            return redirect()->back()->withErrors(['body' => 'Communication tables are not ready yet. Please run the latest migration.']);
        }

        $attrs = $request->validate([
            'participant_user_id' => 'required|integer|exists:users,id',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'body' => 'nullable|string|max:4000|required_without:attachments',
            'attachments' => 'nullable|array|max:6|required_without:body',
            'attachments.*' => 'file|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'tab_session' => 'nullable|string|max:120',
        ], [
            'body.required_without' => 'Enter a message or choose an image.',
            'attachments.required_without' => 'Enter a message or choose at least one image.',
            'attachments.max' => 'You can attach up to 6 images per message.',
            'attachments.*.image' => 'Every attachment must be an image.',
            'attachments.*.mimes' => 'Use JPG, JPEG, PNG, GIF, or WEBP images.',
            'attachments.*.max' => 'Each image must not be larger than 10 MB.',
        ]);

        $authUser = Auth::user();
        if (!$authUser) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired. Please sign in again.'], 401);
            }
            return redirect()->route('login_display', array_filter([
                'tab_session' => $request->input('tab_session'),
            ]));
        }

        if (!in_array(strtolower(trim((string) ($authUser->role ?? ''))), ['admin', 'administrator'], true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You must be logged in as an admin account to send messages from the admin communication page.',
                ], 403);
            }
            return redirect()->route('employee.employeeCommunication', array_filter([
                    'tab_session' => $request->input('tab_session'),
                ]))
                ->withErrors(['body' => 'You must be logged in as an admin account to send messages from the admin communication page.']);
        }

        $participant = User::query()->findOrFail((int) $attrs['participant_user_id']);
        if ((int) $participant->id === (int) $authUser->id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You cannot message yourself.'], 422);
            }
            return redirect()->back()->withErrors(['body' => 'You cannot message yourself.']);
        }

        if (strcasecmp(trim((string) ($participant->role ?? '')), 'employee') !== 0) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Admins can only start chats with employee users here.'], 422);
            }
            return redirect()->back()->withErrors(['body' => 'Admins can only start chats with employee users here.']);
        }

        $conversation = Conversation::findOrCreateBetweenUsers((int) $authUser->id, (int) $participant->id);
        $message = $conversation->messages()->create([
            'sender_user_id' => (int) $authUser->id,
            'body' => trim((string) ($attrs['body'] ?? '')),
        ]);
        foreach ($request->file('attachments', []) as $attachment) {
            $path = $attachment->store('chat-images', 'public');
            $message->attachments()->create([
                'path' => $path,
                'name' => $attachment->getClientOriginalName(),
                'mime' => $attachment->getClientMimeType(),
                'size' => $attachment->getSize(),
            ]);
        }
        $message->load('attachments');
        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();

        $chatRouteParameters = array_filter([
            'conversation' => $conversation->id,
            'user' => $participant->id,
            'tab_session' => $request->input('tab_session'),
        ], fn ($value) => !is_null($value) && $value !== '');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Message sent.',
                'conversation_id' => (int) $conversation->id,
                'participant_user_id' => (int) $participant->id,
                'chat_url' => route('admin.adminCommunication', $chatRouteParameters),
                'sent_message' => [
                    'id' => (int) $message->id,
                    'body' => (string) $message->body,
                    'attachments' => $message->attachments->map(fn ($attachment) => [
                        'id' => (int) $attachment->id,
                        'name' => $attachment->name,
                        'url' => route('admin.communication.attachment.view', array_filter([
                            'attachment' => $attachment->id,
                            'tab_session' => $request->input('tab_session'),
                        ])),
                    ])->values()->all(),
                ],
            ]);
        }

        return redirect()->route('admin.adminCommunication', [
            'conversation' => $conversation->id,
            'user' => $participant->id,
            'tab_session' => $request->input('tab_session'),
        ])->with('success', 'Message sent.');
    }

    public function sync_hidden_official_holidays(Request $request)
    {
        $attrs = $request->validate([
            'hidden_official_holidays' => 'nullable|array',
            'custom_holidays' => 'nullable|array',
            'recurring_holidays' => 'nullable|array',
        ]);

        $hiddenMap = $attrs['hidden_official_holidays'] ?? [];
        $customHolidayMap = $attrs['custom_holidays'] ?? [];
        $recurringHolidayMap = $attrs['recurring_holidays'] ?? [];
        $hiddenDates = collect($hiddenMap)
            ->filter(function ($names, $date) {
                return is_string($date)
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                    && is_array($names)
                    && !empty($names);
            })
            ->keys()
            ->values()
            ->all();

        $normalizedCustomHolidays = collect($customHolidayMap)
            ->filter(function ($names, $date) {
                return is_string($date)
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                    && is_array($names)
                    && !empty($names);
            })
            ->map(function ($names) {
                return array_values(array_filter(array_map(function ($name) {
                    return is_string($name) ? trim($name) : '';
                }, $names), fn ($name) => $name !== ''));
            })
            ->filter(fn ($names) => !empty($names))
            ->all();

        $normalizedRecurringHolidays = collect($recurringHolidayMap)
            ->filter(function ($names, $monthDay) {
                return is_string($monthDay)
                    && preg_match('/^\d{2}-\d{2}$/', $monthDay)
                    && is_array($names)
                    && !empty($names);
            })
            ->map(function ($names) {
                return array_values(array_filter(array_map(function ($name) {
                    return is_string($name) ? trim($name) : '';
                }, $names), fn ($name) => $name !== ''));
            })
            ->filter(fn ($names) => !empty($names))
            ->all();

        Storage::disk('local')->put('calendar_hidden_holidays.json', json_encode([
            'dates' => $hiddenDates,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        Storage::disk('local')->put('calendar_holiday_config.json', json_encode([
            'hidden_official_holidays' => $hiddenMap,
            'custom_holidays' => $normalizedCustomHolidays,
            'recurring_holidays' => $normalizedRecurringHolidays,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        return response()->json([
            'success' => true,
            'hidden_dates' => $hiddenDates,
        ]);
    }


    //STORE
    public function store_new_position(Request $request){
        Log::info($request);
        $attrs = $request->validate([
            'title' => 'required',
            'department' => 'required',
            'employment' => 'required',
            'mode' => 'required',
            'description' => 'required',
            'responsibilities' => 'required',
            'requirements' => 'required',
            // 'min' => 'required',
            // 'max' => 'required',
            'level' => 'required',
            'location' => 'required',
            'skills' => 'required',
            'benefits' => 'required',
            'job_type' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $store = OpenPosition::create([
            'title' => $attrs['title'],
            'department' => $attrs['department'],
            'employment' => $attrs['employment'],
            'work_mode' => $attrs['mode'],
            'job_description' => $attrs['description'],
            'responsibilities' => $attrs['responsibilities'],
            'requirements' => $attrs['requirements'],
            // 'min_salary' => $attrs['min'],
            // 'max_salary' => $attrs['max'],
            'experience_level' => $attrs['level'],
            'location' => $attrs['location'],
            'skills' => $attrs['skills'],
            'benifits' => $attrs['benefits'],
            'job_type' => $attrs['job_type'],
            'one' => $attrs['start_date'],
            'two' => $attrs['end_date'],
        ]);

        return redirect()
            ->route('admin.adminPosition')
            ->with('success', 'Position successfully created: '.$store->title.' (#'.$store->id.').')
            ->with('position_created', true);
    }

    public function store_interview(Request $request){ /// Update applicant status to "For Interview" when interview is scheduled
        Log::info($request);
        $attrs = $request->validate([
            'applicants_id' => 'required',
            'interview_type' => 'required',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'required',
            'interviewers' => 'required',
            'email_link' => 'required',
            'url' => 'nullable',
            'notes' => 'nullable',
            'next_interview_confirmed' => 'nullable|in:0,1',
        ]);

        $applicant = Applicant::findOrFail((int) $attrs['applicants_id']);
        $requestedInterviewType = (string) $attrs['interview_type'];
        $normalizedCurrentStatus = strtolower(trim((string) $applicant->application_status));
        $normalizedRequestedType = strtolower(trim($requestedInterviewType));

        if (in_array($normalizedCurrentStatus, ['hired', 'rejected', 'completed', 'passing document'], true)) {
            return redirect()
                ->back()
                ->with('error', 'A new interview cannot be scheduled because this applicant is already in the '.$applicant->application_status.' stage.')
                ->with('interview_schedule_conflict', 'This applicant is already marked '.$applicant->application_status.'. No additional interview schedule can be created.')
                ->with('scheduled_applicant_id', $attrs['applicants_id']);
        }

        $requiresProceedConfirmation = in_array($normalizedRequestedType, ['final interview', 'demo teaching'], true)
            && $normalizedCurrentStatus !== $normalizedRequestedType;

        if ($requiresProceedConfirmation) {
            $confirmedByProceedAction = (string) ($attrs['next_interview_confirmed'] ?? '0') === '1';
            if (!$confirmedByProceedAction || !$this->hasRequiredPreviousInterviewStage((int) $attrs['applicants_id'], $requestedInterviewType)) {
                return redirect()
                    ->back()
                    ->with('error', 'Click Proceed before scheduling '.$requestedInterviewType.'.')
                    ->with('scheduled_applicant_id', $attrs['applicants_id']);
            }
        }

        if ($this->hasCompletedInterviewStage((int) $attrs['applicants_id'], (string) $attrs['interview_type'])) {
            return redirect()
                ->back()
                ->with('error', $attrs['interview_type'].' is already finished for this applicant and cannot be scheduled again.')
                ->with('scheduled_applicant_id', $attrs['applicants_id']);
        }

        if ($this->findActiveInterviewSchedule((int) $attrs['applicants_id'], (string) $attrs['interview_type'])) {
            return redirect()
                ->back()
                ->with('error', $attrs['interview_type'].' is already scheduled for this applicant. Please reschedule or cancel the existing interview instead.')
                ->with('scheduled_applicant_id', $attrs['applicants_id']);
        }

        DB::beginTransaction();

        try {
            $store = Interviewer::create([
                'applicant_id' => $attrs['applicants_id'],
                'interview_type' => $attrs['interview_type'],
                'date' => $attrs['date'],
                'time' => $attrs['time'],
                'duration' => $attrs['duration'],
                'ended_at' => null,
                'interviewers' => $attrs['interviewers'],
                'email_link' => $attrs['email_link'],
                'url' => $attrs['url'],
                'notes' => $attrs['notes'],
            ]);

            // Updates applicant status based on the single active interview.
            Applicant::where('id', $attrs['applicants_id'])->update([
                'application_status' => $this->resolveApplicantStatusFromInterviewType($attrs['interview_type']),
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        $successMessage = 'Success Added Interview';

        try {
            Mail::to($this->mailToAddress($store->applicant->email))
                    ->queue(new ApplicationInterviewMail($store));
        } catch (\Throwable $exception) {
            Log::warning('Interview created but applicant email could not be queued.', [
                'applicant_id' => $store->applicant?->id,
                'email' => $store->applicant?->email,
                'to_override' => config('mail.to_override'),
                'error' => $exception->getMessage(),
            ]);

            $successMessage .= ' Email notification was not queued. Please check the queue configuration.';
        }

        return redirect()
            ->back()
            ->with('success', $successMessage)
            ->with('scheduled_applicant_id', $attrs['applicants_id']);
    }

    public function store_star_ratings(Request $request){
        $attrs = $request->validate([
            'ratingId' => 'required',
            'rating' => 'required|string',
        ]);

        $review = Applicant::findOrFail($attrs['ratingId']);

        $review->update([
            'starRatings' => $attrs['rating'],
        ]);

        return redirect()->back()->with('success','Success Rating Store');
    }

    public function store_document(Request $request){
        Log::info($request);
        $attrs = $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
            'user_id' => 'required|exists:users,id',
            'document_name' => 'required|string|max:255',
            'documents' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $applicant = null;
        if (!empty($attrs['applicant_id'])) {
            $applicant = Applicant::find((int) $attrs['applicant_id']);
        }
        if (!$applicant && !empty($attrs['user_id'])) {
            $applicant = Applicant::query()
                ->where('user_id', (int) $attrs['user_id'])
                ->orderByDesc('id')
                ->first();
        }
        if (!$applicant) {
            return back()->withErrors(['documents' => 'Applicant record not found for this employee.']);
        }

        $file = $request->file('documents');

        if (!$file || !$file->isValid()) {
            return back()->withErrors(['documents' => 'Invalid file upload.']);
        }

        $originalName = $file->getClientOriginalName();
        $mimeType     = $file->getMimeType();
        $size         = $file->getSize();

        $fileName = time() . '_' . $originalName;

        // Store file
        $filePath = $file->storeAs('uploads', $fileName, 'public');

        $saved = ApplicantDocument::create([
            'applicant_id' => $applicant->id,
            'type'         => $attrs['document_name'],
            'filename'     => $originalName,
            'filepath'     => $filePath, // already "uploads/filename"
            'mime_type'    => $mimeType,
            'size'         => $size,
        ]);

        if (!$saved || !$saved->id) {
            return back()->withErrors(['documents' => 'Document upload failed to save in database.']);
        }

        $this->clearMatchingRequiredDocumentMeta((int) $applicant->id, (string) ($attrs['document_name'] ?? ''));
        $this->clearMatchingRequiredDocumentMeta(
            (int) $applicant->id,
            (string) pathinfo((string) $originalName, PATHINFO_FILENAME)
        );
        $this->clearDocumentNoticeIfNoRequiredDocuments((int) $applicant->id);

        return back()->with('success', 'Document uploaded successfully.');

    }

    public function store_required_documents(Request $request)
    {
        $attrs = $request->validate([
            'applicant_id' => 'nullable|exists:applicants,id',
            'user_id' => 'nullable|exists:users,id',
            'required_documents' => 'nullable|string',
            'document_notice' => 'nullable|string|max:1000',
        ]);

        $requiredDocuments = collect(
            preg_split('/[\r\n,]+/', (string) ($attrs['required_documents'] ?? ''))
        )
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique(function ($item) {
                return strtolower($item);
            })
            ->values()
            ->all();

        $notice = trim((string) ($attrs['document_notice'] ?? ''));
        $applicant = null;
        if (!empty($attrs['applicant_id'])) {
            $applicant = Applicant::find((int) $attrs['applicant_id']);
        }
        if (!$applicant && !empty($attrs['user_id'])) {
            $applicant = Applicant::query()
                ->where('user_id', (int) $attrs['user_id'])
                ->orderByDesc('id')
                ->first();
        }
        if (!$applicant) {
            return back()->withErrors(['documents' => 'Applicant record not found for this employee.']);
        }
        $applicantId = (int) $applicant->id;

        $requiredPrefix = '__REQUIRED__::';
        $noticeType = '__NOTICE__';

        ApplicantDocument::query()
            ->where('applicant_id', $applicantId)
            ->where(function ($query) use ($requiredPrefix, $noticeType) {
                $query
                    ->where('type', 'like', $requiredPrefix.'%')
                    ->orWhere('type', $noticeType);
            })
            ->delete();

        foreach ($requiredDocuments as $requiredDocument) {
            ApplicantDocument::create([
                'applicant_id' => $applicantId,
                'filename' => 'Required Document',
                'filepath' => 'system/meta/required-document',
                'size' => 0,
                'mime_type' => 'text/plain',
                'type' => $requiredPrefix.$requiredDocument,
            ]);
        }

        if ($notice !== '') {
            ApplicantDocument::create([
                'applicant_id' => $applicantId,
                'filename' => $notice,
                'filepath' => 'system/meta/document-notice',
                'size' => 0,
                'mime_type' => 'text/plain',
                'type' => $noticeType,
            ]);
        }

        return back()->with('success', 'Required document notice saved.');
    }


    public function store_payslip_file(Request $request)
    {
        $request->validate([
            'payslip_file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $file = $request->file('payslip_file');
        if (!$file || !$file->isValid()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid file upload.',
                    'errors' => ['payslip_file' => ['Invalid file upload.']],
                ], 422);
            }

            return back()->withErrors(['payslip_file' => 'Invalid file upload.']);
        }

        $originalName = $file->getClientOriginalName();
        $fileName = time().'_'.$originalName;
        $filePath = $file->storeAs('payslip_uploads', $fileName, 'public');

        $upload = PayslipUpload::create([
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'status' => 'Uploaded',
            'processed_rows' => 0,
            'uploaded_at' => Carbon::now('Asia/Manila'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Payslip file uploaded successfully.',
                'upload' => [
                    'id' => (int) $upload->id,
                    'original_name' => (string) $upload->original_name,
                    'status' => (string) $upload->status,
                ],
            ], 201);
        }

        return back()->with('success', 'Payslip file uploaded successfully.');
    }

    public function store_employee_import_file(Request $request)
    {
        $attrs = $request->validate([
            'employee_file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $file = $attrs['employee_file'];
        if (!$file->isValid()) {
            return back()->withErrors(['employee_file' => 'Invalid employee spreadsheet upload.']);
        }

        $originalName = $file->getClientOriginalName();
        $expectedBaseName = '201-file-'.Carbon::now('Asia/Manila')->format('M-Y');
        $uploadedBaseName = pathinfo($originalName, PATHINFO_FILENAME);
        if (strcasecmp($uploadedBaseName, $expectedBaseName) !== 0) {
            return back()->withErrors([
                'employee_file' => "Invalid filename. Rename the file to {$expectedBaseName}.xlsx or {$expectedBaseName}.csv.",
            ]);
        }

        try {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $rawRows = $extension === 'xlsx'
                ? $this->extractRawRowsFromXlsx($file->getRealPath(), '201 file', true)
                : $this->extractRawRowsFromCsv($file->getRealPath());
            $rows = $this->mapEmployeeImportRows($rawRows);
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'employee_file' => $exception->getMessage(),
            ]);
        }

        if (empty($rows)) {
            return back()->withErrors([
                'employee_file' => 'No employee rows were found. Put the column headers on the first populated row of the 201 file worksheet.',
            ]);
        }

        $created = 0;
        $skipped = 0;
        $warnings = [];
        $originalBcryptRounds = (int) config('hashing.bcrypt.rounds', 12);

        // A 201 file can contain hundreds of employees. Imported passwords are
        // temporary, so use a still-secure but faster bcrypt cost for this batch.
        config(['hashing.bcrypt.rounds' => min($originalBcryptRounds, 10)]);
        set_time_limit(300);

        try {
            foreach ($rows as $index => $row) {
                $sheetRow = $index + 2;

                try {
                    $result = DB::transaction(fn () => $this->createEmployeeAccountFromImportRow($row));
                    if ($result === null) {
                        continue;
                    }
                    $created++;
                } catch (\Throwable $exception) {
                    $skipped++;
                    if (count($warnings) < 50) {
                        $warnings[] = [
                            'row' => $sheetRow,
                            'name' => $this->employeeImportWarningValue($row, [
                                'name', 'employee_name', 'full_name', 'first_name', 'firstname',
                            ]),
                            'employee_id' => $this->employeeImportWarningValue($row, [
                                'employee_id', 'employee_number', 'employee_no', 'id_number',
                            ]),
                            'reason' => $exception->getMessage(),
                        ];
                    }
                }
            }
        } finally {
            config(['hashing.bcrypt.rounds' => $originalBcryptRounds]);
        }

        $storedName = now()->format('Ymd_His').'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $file->storeAs('employee_imports', $storedName, 'local');

        if ($created === 0) {
            return back()
                ->withErrors(['employee_file' => 'No accounts were created. Review the row details below.'])
                ->with('import_warnings', $warnings)
                ->with('import_skipped_count', $skipped);
        }

        $message = "{$created} employee account".($created === 1 ? '' : 's')." created successfully from '{$originalName}'.";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) were skipped.";
        }

        return back()
            ->with('success', $message)
            ->with('import_warnings', $warnings)
            ->with('import_skipped_count', $skipped);
    }

    private function employeeImportWarningValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '—';
    }

    private function createEmployeeAccountFromImportRow(array $row): ?User
    {
        $pick = static function (array $keys) use ($row): ?string {
            foreach ($keys as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }

            return null;
        };

        $firstName = $pick(['first_name', 'firstname', 'given_name']);
        $lastName = $pick(['last_name', 'lastname', 'surname', 'family_name']);
        $middleName = $pick([
            'middle_name', 'middlename', 'middle', 'middle_initial',
            'middle_initial_name', 'mi', 'm_i', 'middle_name_m_i',
        ]);
        $fullName = $pick(['name', 'employee_name', 'full_name']);
        $employeeId = $this->normalizeEmployeeImportId($pick(['employee_id', 'employee_number', 'employee_no', 'id_number']));

        if ((!$firstName || !$lastName) && $fullName) {
            [$parsedFirstName, $parsedMiddleName, $parsedLastName] = $this->parseEmployeeImportName($fullName);
            $firstName = $firstName ?: $parsedFirstName;
            $middleName = $middleName ?: $parsedMiddleName;
            $lastName = $lastName ?: $parsedLastName;
        }

        $password = $pick(['password', 'temporary_password', 'temp_password']) ?: Str::random(64);

        if (!$firstName && !$lastName && !$employeeId) {
            return null;
        }
        if (!$firstName || !$lastName) {
            throw new \RuntimeException('First Name and Last Name are required.');
        }
        if (!$employeeId) {
            throw new \RuntimeException('ID number is required.');
        }
        if (!$password || strlen($password) < 8) {
            throw new \RuntimeException('Password must contain at least 8 characters.');
        }
        if ($employeeId && Employee::query()->where('employee_id', $employeeId)->exists()) {
            throw new \RuntimeException("Employee ID {$employeeId} already exists.");
        }

        $department = $pick(['department', 'office_department', 'office', 'unit']);
        $position = $pick(['position', 'job_position', 'job_title', 'designation']);
        $resignedDate = $this->normalizeDate($pick(['date_resigned', 'resignation_date']));

        $user = User::withoutEvents(fn () => User::create([
            'first_name' => $firstName,
            'middle_name' => $middleName ?: '',
            'last_name' => $lastName,
            'email' => null,
            'password' => $password,
            'role' => 'Employee',
            'job_role' => $position ?: 'Employee',
            'position' => $position ?: 'Employee',
            'department' => $department ?: 'Unassigned',
            'department_head' => $pick(['department_head', 'head_of_department']),
            'status' => 'Approved',
            'account_status' => $resignedDate ? 'Inactive' : 'Active',
        ]));

        $employeeValues = [
            'employee_id' => $employeeId,
            'email' => null,
            'employement_date' => $this->normalizeDate($pick(['employment_date', 'employement_date', 'date_hired', 'hire_date'])),
            'birthday' => $this->normalizeDate($pick(['birthday', 'birth_date', 'date_of_birth'])),
            'account_number' => $pick(['account_number', 'account_no', 'bank_account_number']),
            'sex' => $pick(['sex', 'gender']),
            'civil_status' => $pick(['civil_status', 'marital_status']),
            'contact_number' => $pick(['contact_number', 'contact_no', 'phone', 'phone_number', 'mobile_number']),
            'address' => $pick(['address', 'home_address', 'residential_address']),
            'department' => $department,
            'position' => $position,
            'classification' => $pick(['classification', 'employment_status', 'rank']),
            'classification_salary' => $pick(['classification_salary', 'salary_classification', 'grade']),
            'job_type' => $this->normalizeEmployeeJobType($pick(['job_type', 'employee_type', 'class'])),
            'emergency_contact_name' => $pick(['emergency_contact_name']),
            'emergency_contact_relationship' => $pick(['emergency_contact_relationship', 'emergency_contact_relation']),
            'emergency_contact_number' => $pick(['emergency_contact_number', 'emergency_phone']),
        ];

        $employmentHistory = $pick(['employment_history', 'employement_history']);
        if ($employmentHistory && Schema::hasColumn('employees', 'service_record_rows')) {
            $employeeValues['service_record_rows'] = [[
                'from_date' => $employeeValues['employement_date'] ?? '',
                'to_date' => $resignedDate ?: '',
                'designation' => $position ?: '',
                'status' => $employeeValues['classification'] ?? '',
                'salary' => $pick(['salary', 'monthly_salary', 'basic_salary']) ?: '',
                'office' => $department ?: '',
                'separation_date' => $resignedDate ?: '',
                'separation_cause' => $resignedDate ? 'Resigned' : '',
                'remarks' => $employmentHistory,
            ]];
        }

        $employee = Employee::query()->firstOrNew(['user_id' => $user->id]);
        if (!$employee->exists) {
            $employee->fill([
                'employee_id' => $employeeId,
                'employement_date' => $employeeValues['employement_date'] ?? now()->toDateString(),
                'birthday' => $employeeValues['birthday'] ?? now()->subYears(18)->toDateString(),
                'account_number' => $employeeValues['account_number'] ?? 'N/A',
                'sex' => $employeeValues['sex'] ?? 'Unspecified',
                'civil_status' => $employeeValues['civil_status'] ?? 'Single',
                'contact_number' => $employeeValues['contact_number'] ?? 'N/A',
                'address' => $employeeValues['address'] ?? 'N/A',
                'department' => $department ?: 'Unassigned',
                'position' => $position ?: 'Employee',
                'classification' => $employeeValues['classification'] ?? 'Probationary',
            ]);
        }
        $employee->fill($this->nonNullImportValues($employeeValues));
        Employee::withoutEvents(fn () => $employee->save());

        $this->ensureImportedEmployeeApplicantRecord(
            $user,
            $firstName,
            $middleName,
            $lastName,
            $position,
            $employeeValues['employement_date'] ?? null
        );

        $this->saveEmployeeImportRelatedRecords($user, $pick);

        if ($resignedDate) {
            Resignation::query()->create([
                'user_id' => $user->id,
                'employee_id' => $employeeId,
                'employee_name' => trim($firstName.' '.($middleName ? $middleName.' ' : '').$lastName),
                'department' => $department,
                'position' => $position,
                'submitted_at' => $resignedDate,
                'effective_date' => $resignedDate,
                'reason' => 'Imported from the employee 201 file.',
                'status' => 'Approved',
                'processed_at' => now(),
            ]);
        }

        return $user;
    }

    private function ensureImportedEmployeeApplicantRecord(
        User $user,
        string $firstName,
        ?string $middleName,
        string $lastName,
        ?string $position,
        ?string $dateHired
    ): void {
        if (Applicant::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $openPositionId = $this->resolveFallbackOpenPositionId();
        if (!$openPositionId) {
            return;
        }

        Applicant::withoutEvents(fn () => Applicant::create([
            'user_id' => $user->id,
            'open_position_id' => $openPositionId,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => '',
            'field_study' => '-',
            'work_position' => $position ?: 'Employee',
            'work_employer' => 'Northeastern College',
            'work_location' => '-',
            'work_duration' => '-',
            'experience_years' => '0',
            'skills_n_expertise' => '-',
            'application_status' => 'Hired',
            'fresh_graduate' => false,
            'date_hired' => $dateHired,
        ]));
    }

    private function saveEmployeeImportRelatedRecords(User $user, callable $pick): void
    {
        $governmentValues = [
            'SSS' => $pick(['sss', 'sss_number']),
            'TIN' => $pick(['tin', 'tin_number']),
            'PhilHealth' => $pick(['philhealth', 'philhealth_number']),
            'RTN' => $pick(['rtn', 'rtn_number', 'pag_ibig_rtn']),
            'MID' => $pick(['mid', 'pag_ibig', 'pagibig', 'pag_ibig_number', 'pag_ibig_mid']),
        ];
        if ($this->nonNullImportValues($governmentValues)) {
            Government::query()->updateOrCreate(
                ['user_id' => $user->id],
                array_map(static fn ($value) => $value ?: '-', $governmentValues)
            );
        }

        $salaryValues = [
            'salary' => $pick(['salary', 'monthly_salary', 'basic_salary']),
            'rate_per_hour' => $pick(['rate_per_hour', 'hourly_rate']),
            'cola' => $pick(['cola', 'allowance']),
        ];
        if ($this->nonNullImportValues($salaryValues)) {
            Salary::query()->updateOrCreate(
                ['user_id' => $user->id],
                array_map(static fn ($value) => $value ?: '0', $salaryValues)
            );
        }

        $licenseValues = [
            'license' => $pick(['license', 'license_name', 'professional_license', 'eligibility', 'with_without_license']),
            'registration_number' => $pick(['registration_number', 'registration_no', 'license_number']),
            'registration_date' => $this->normalizeDate($pick(['registration_date', 'license_registration_date'])),
            'valid_until' => $this->normalizeDate($pick(['valid_until', 'license_valid_until', 'expiration_date'])),
        ];
        if (count($this->nonNullImportValues($licenseValues)) === count($licenseValues)) {
            License::query()->updateOrCreate(['user_id' => $user->id], $licenseValues);
        }

        $educationValues = [
            'elementary_school_name' => $pick(['elementary_school_name', 'elementary_school']),
            'elementary_year_finished' => $pick(['elementary_year_finished']),
            'secondary_school_name' => $pick(['secondary_school_name', 'secondary_school']),
            'secondary_year_finished' => $pick(['secondary_year_finished']),
            'vocational_trade_school_name' => $pick(['vocational_trade_school_name', 'vocational_school']),
            'vocational_trade_year_finished' => $pick(['vocational_trade_year_finished']),
            'college_school_name' => $pick(['college_school_name', 'college_school']),
            'college_year_finished' => $pick(['college_year_finished']),
            'bachelor' => $pick(['bachelor', 'bachelors_degree']),
            'master' => $pick(['master', 'masters_degree', 'master_s_degree']),
            'doctorate' => $pick(['doctorate', 'doctorate_degree']),
        ];
        if ($this->nonNullImportValues($educationValues)) {
            $educationValues['bachelor'] ??= '';
            $educationValues['master'] ??= '';
            $educationValues['doctorate'] ??= '';
            Education::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->nonNullImportValues($educationValues) + [
                    'bachelor' => '',
                    'master' => '',
                    'doctorate' => '',
                ]
            );
        }
    }

    private function nonNullImportValues(array $values): array
    {
        return array_filter($values, static fn ($value) => $value !== null && $value !== '');
    }

    private function mapEmployeeImportRows(array $rows): array
    {
        $headerIndex = null;
        $bestScore = 0;
        $knownHeaders = [
            'name', 'first_name', 'last_name', 'id_number', 'employee_id',
            'account_no', 'sex', 'civil_status', 'address', 'contact_no',
            'date_of_birth', 'employment_date', 'position', 'department',
            'sss', 'tin', 'philhealth', 'pag_ibig_mid', 'basic_salary',
        ];

        foreach (array_slice($rows, 0, 20, true) as $index => $row) {
            $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $row);
            $score = count(array_intersect($headers, $knownHeaders));
            if ($score > $bestScore) {
                $bestScore = $score;
                $headerIndex = $index;
            }
        }

        if ($headerIndex === null || $bestScore < 2) {
            throw new \RuntimeException('The 201 file column header row could not be recognized. Include Name and ID number columns.');
        }

        return $this->mapRowsUsingGenericHeader(array_slice($rows, $headerIndex));
    }

    private function parseEmployeeImportName(string $fullName): array
    {
        $fullName = trim((string) preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return [null, null, null];
        }

        if (str_contains($fullName, ',')) {
            [$lastName, $givenNames] = array_map('trim', explode(',', $fullName, 2));
            $givenNames = trim($givenNames, " \t\n\r\0\x0B,");
            $givenParts = preg_split('/\s+/', $givenNames) ?: [];

            if ($givenParts && $this->isEmployeeNameSuffix((string) end($givenParts))) {
                $suffix = array_pop($givenParts);
                $lastName = trim($lastName.' '.$suffix);
            }

            if (count($givenParts) === 1) {
                return [$givenParts[0] ?: null, null, $lastName ?: null];
            }

            $middleNameStart = $this->employeeMiddleNameStartIndex($givenParts);
            $firstNameParts = array_slice($givenParts, 0, $middleNameStart);
            $middleNameParts = array_slice($givenParts, $middleNameStart);

            return [
                $firstNameParts ? implode(' ', $firstNameParts) : null,
                $middleNameParts ? implode(' ', $middleNameParts) : null,
                $lastName ?: null,
            ];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        if (count($parts) === 1) {
            return [$parts[0], null, 'Employee'];
        }

        $firstName = array_shift($parts);
        $suffix = $parts && $this->isEmployeeNameSuffix((string) end($parts))
            ? array_pop($parts)
            : null;
        $lastName = array_pop($parts);
        if ($suffix) {
            $lastName = trim($lastName.' '.$suffix);
        }

        return [$firstName ?: null, $parts ? implode(' ', $parts) : null, $lastName ?: null];
    }

    private function isEmployeeNameSuffix(string $value): bool
    {
        $suffix = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $value));

        return in_array($suffix, ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true);
    }

    private function employeeMiddleNameStartIndex(array $parts): int
    {
        $count = count($parts);
        $normalized = array_map(
            static fn ($part) => strtolower((string) preg_replace('/[^a-z]/i', '', (string) $part)),
            $parts
        );

        $compoundPrefixes = [
            ['de', 'la'], ['de', 'los'], ['de', 'las'],
            ['dela'], ['delos'], ['delas'], ['de'], ['del'],
            ['van'], ['von'], ['san'], ['santa'],
        ];

        foreach ($compoundPrefixes as $prefix) {
            $start = $count - count($prefix) - 1;
            if ($start >= 1 && array_slice($normalized, $start, count($prefix)) === $prefix) {
                return $start;
            }
        }

        return $count - 1;
    }

    private function normalizeEmployeeImportId(?string $employeeId): ?string
    {
        $employeeId = trim((string) $employeeId);
        if ($employeeId === '') {
            return null;
        }

        if (preg_match('/^[+-]?\d+(?:\.\d+)?e[+-]?\d+$/i', $employeeId) === 1) {
            return number_format((float) $employeeId, 0, '.', '');
        }

        return $employeeId;
    }

    public function store_loads_file(Request $request)
    {
        $request->validate([
            'loads_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('loads_file');
        if (!$file || !$file->isValid()) {
            return back()->withErrors(['loads_file' => 'Invalid file upload.']);
        }

        $originalName = $file->getClientOriginalName();
        $fileName = time().'_'.$originalName;
        $filePath = $file->storeAs('loads_uploads', $fileName, 'public');

        LoadsUpload::create([
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'status' => 'Uploaded',
            'processed_rows' => 0,
            'uploaded_at' => Carbon::now('Asia/Manila'),
        ]);

        return back()->with('success', 'Loads file uploaded successfully.');
    }

    public function delete_loads_file($id)
    {
        $loadsFile = LoadsUpload::findOrFail($id);

        if (!empty($loadsFile->file_path) && Storage::disk('public')->exists($loadsFile->file_path)) {
            Storage::disk('public')->delete($loadsFile->file_path);
        }

        $loadsFile->delete();

        return back()->with('success', 'Loads file removed successfully.');
    }

    public function scan_loads_file($id, Request $request)
    {
        try {
            $loadsFile = LoadsUpload::findOrFail($id);

            $attrs = $request->validate([
                'status' => 'nullable|string',
            ]);

            $status = trim((string) ($attrs['status'] ?? 'Scanned'));
            if ($status === '') {
                $status = 'Scanned';
            }

            $extension = strtolower((string) pathinfo($loadsFile->file_path, PATHINFO_EXTENSION));
            if ($extension === 'xls') {
                throw new \RuntimeException('Scanning .xls files is not supported yet. Please upload .xlsx or .csv.');
            }

            $absolutePath = Storage::disk('public')->path($loadsFile->file_path);
            $rows = $this->extractLoadsRowsFromExcel($absolutePath, $extension);
            $records = $this->buildLoadsRecords($rows, $loadsFile);
            $processedRows = 0;

            DB::transaction(function () use ($loadsFile, $status, $records, &$processedRows) {
                if (!empty($records)) {
                    LoadsRecord::insert($records);
                }

                $processedRows = count($records);
                $loadsFile->update([
                    'status' => $status,
                    'processed_rows' => $processedRows,
                ]);
            });

            ActivityChangeLogger::scannedFile($loadsFile->fresh(), $processedRows, 'Loads File');

            return response()->json([
                'success' => true,
                'message' => 'Loads file scanned successfully.',
                'status' => $loadsFile->status,
                'upload_id' => $loadsFile->id,
                'processed_rows' => $processedRows,
            ]);
        } catch (\Exception $e) {
            Log::error('Error scanning loads file: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error scanning loads file: '.$e->getMessage(),
            ], 500);
        }
    }

    public function scan_payslip_file($id, Request $request)
    {
        try {
            $payslipFile = PayslipUpload::findOrFail($id);

            $attrs = $request->validate([
                'status' => 'nullable|string',
            ]);

            $status = trim((string) ($attrs['status'] ?? 'Scanned'));
            if ($status === '') {
                $status = 'Scanned';
            }
            $absolutePath = Storage::disk('public')->path($payslipFile->file_path);
            $extension = pathinfo($payslipFile->file_path, PATHINFO_EXTENSION);
            $rows = $this->extractRowsFromExcel($absolutePath, $extension, 'PMENUCL', true);
            $fallbackPayDate = optional($payslipFile->uploaded_at)->format('Y-m-d') ?: now()->toDateString();
            $records = $this->buildPayslipRecords($rows, (int) $payslipFile->id, $fallbackPayDate);
            $processedRows = 0;

            DB::transaction(function () use ($payslipFile, $status, $records, &$processedRows) {
                PayslipRecord::query()
                    ->where('payslip_upload_id', (int) $payslipFile->id)
                    ->delete();

                if (!empty($records)) {
                    PayslipRecord::insert($records);
                }

                $processedRows = count($records);
                $payslipFile->update([
                    'status' => $status,
                    'processed_rows' => $processedRows,
                ]);
            });

            ActivityChangeLogger::scannedFile($payslipFile->fresh(), $processedRows, 'Payslip File');

            return response()->json([
                'success' => true,
                'message' => 'Payslip file scanned successfully.',
                'status' => $payslipFile->status,
                'upload_id' => $payslipFile->id,
                'processed_rows' => $processedRows,
            ]);
        } catch (\Exception $e) {
            Log::error('Error scanning payslip file: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error scanning payslip file: '.$e->getMessage(),
            ], 500);
        }
    }

    private function extractRowsFromExcel(
        string $absolutePath,
        string $extension,
        ?string $preferredSheetName = null,
        bool $strictPreferredSheet = false
    ): array
    {
        $extension = strtolower($extension);

        if ($extension === 'xlsx') {
            return $this->extractRowsFromXlsx($absolutePath, $preferredSheetName, $strictPreferredSheet);
        }

        if ($extension === 'csv') {
            return $this->extractRowsFromCsv($absolutePath);
        }

        throw new \RuntimeException('Only .xlsx and .csv files are supported.');
    }

    private function extractLoadsRowsFromExcel(string $absolutePath, string $extension): array
    {
        $extension = strtolower($extension);

        if ($extension === 'xlsx') {
            $rows = $this->extractRawRowsFromXlsx($absolutePath);
        } elseif ($extension === 'csv') {
            $rows = $this->extractRawRowsFromCsv($absolutePath);
        } else {
            throw new \RuntimeException('Only .xlsx and .csv files are supported for loads scanning.');
        }

        if (count($rows) < 2) {
            return [];
        }

        return $this->mapRowsUsingGenericHeader($rows);
    }

    private function extractRawRowsFromXlsx(
        string $absolutePath,
        ?string $preferredSheetName = null,
        bool $strictPreferredSheet = false
    ): array
    {
        if (!class_exists(\ZipArchive::class) && !class_exists(\PharData::class)) {
            throw new \RuntimeException('XLSX parsing requires ZipArchive or PharData support in PHP.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $this->readXlsxEntry($absolutePath, 'xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $item) {
                    if (isset($item->t)) {
                        $sharedStrings[] = trim((string) $item->t);
                        continue;
                    }

                    $richText = '';
                    if (isset($item->r)) {
                        foreach ($item->r as $run) {
                            $richText .= (string) ($run->t ?? '');
                        }
                    }
                    $sharedStrings[] = trim($richText);
                }
            }
        }

        $sheetXml = false;
        if (!empty($preferredSheetName)) {
            $preferredWorksheetEntry = $this->findXlsxWorksheetEntryBySheetName($absolutePath, $preferredSheetName);
            if (!$preferredWorksheetEntry && $strictPreferredSheet) {
                throw new \RuntimeException("Worksheet '{$preferredSheetName}' was not found in the uploaded xlsx.");
            }

            if ($preferredWorksheetEntry) {
                $sheetXml = $this->readXlsxEntry($absolutePath, $preferredWorksheetEntry);
                if ($sheetXml === false && $strictPreferredSheet) {
                    throw new \RuntimeException("Worksheet '{$preferredSheetName}' could not be read from the uploaded xlsx.");
                }
            }
        }

        if ($sheetXml === false) {
            $sheetXml = $this->readXlsxEntry($absolutePath, 'xl/worksheets/sheet1.xml');
        }
        if ($sheetXml === false) {
            foreach ($this->listXlsxWorksheetEntries($absolutePath) as $worksheetEntry) {
                $sheetXml = $this->readXlsxEntry($absolutePath, $worksheetEntry);
                if ($sheetXml !== false) {
                    break;
                }
            }
        }

        if ($sheetXml === false) {
            throw new \RuntimeException('No worksheet found in xlsx.');
        }

        $sheet = simplexml_load_string($sheetXml);
        $rowsNode = $sheet ? $sheet->xpath("//*[local-name()='sheetData']/*[local-name()='row']") : false;
        if (!$sheet || $rowsNode === false) {
            throw new \RuntimeException('Invalid worksheet data.');
        }

        $rows = [];
        foreach ($rowsNode as $row) {
            $rowData = [];
            $cells = $row->xpath("./*[local-name()='c']") ?: [];
            foreach ($cells as $cell) {
                $reference = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $reference);
                $type = (string) $cell['t'];
                $value = null;

                if ($type === 's') {
                    $index = (int) ($cell->v ?? 0);
                    $value = $sharedStrings[$index] ?? null;
                } elseif ($type === 'inlineStr') {
                    $value = trim((string) ($cell->is->t ?? ''));
                } else {
                    $value = isset($cell->v) ? trim((string) $cell->v) : null;
                }

                if ($column !== '' && $value !== null && $value !== '') {
                    $rowData[$column] = $value;
                }
            }

            if (!empty($rowData)) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    private function extractRawRowsFromCsv(string $absolutePath): array
    {
        if (!is_readable($absolutePath)) {
            return [];
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $rowData = [];
            foreach ($data as $index => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $column = $this->columnNameFromIndex((int) $index);
                if ($column !== '') {
                    $rowData[$column] = $value;
                }
            }

            if (!empty($rowData)) {
                $rows[] = $rowData;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function mapRowsUsingGenericHeader(array $rows): array
    {
        $headerIndex = null;
        $sample = array_slice($rows, 0, 15);

        foreach ($sample as $index => $row) {
            $values = array_values(array_filter(array_map(
                fn ($value) => trim((string) $value),
                $row
            ), fn ($value) => $value !== ''));

            if (count($values) >= 2) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $headerRow = $rows[$headerIndex];
        $dataRows = array_slice($rows, $headerIndex + 1);
        $headers = [];
        $usedHeaders = [];

        foreach ($headerRow as $column => $headerText) {
            $headerKey = $this->normalizeHeader((string) $headerText);
            if ($headerKey === '') {
                $headerKey = 'column_'.strtolower($column);
            }

            $headerKey = $this->makeUniqueHeaderKey($headerKey, $usedHeaders);
            $usedHeaders[$headerKey] = true;
            $headers[$column] = $headerKey;
        }

        $mapped = [];
        foreach ($dataRows as $row) {
            $item = [];
            foreach ($headers as $column => $header) {
                $item[$header] = $row[$column] ?? null;
            }

            if (!empty(array_filter($item, fn ($value) => $value !== null && $value !== ''))) {
                $mapped[] = $item;
            }
        }

        return $mapped;
    }

    private function makeUniqueHeaderKey(string $headerKey, array $usedHeaders): string
    {
        if (!isset($usedHeaders[$headerKey])) {
            return $headerKey;
        }

        $suffix = 2;
        while (isset($usedHeaders[$headerKey.'_'.$suffix])) {
            $suffix++;
        }

        return $headerKey.'_'.$suffix;
    }

    private function extractRowsFromXlsx(
        string $absolutePath,
        ?string $preferredSheetName = null,
        bool $strictPreferredSheet = false
    ): array
    {
        $rows = $this->extractRawRowsFromXlsx($absolutePath, $preferredSheetName, $strictPreferredSheet);

        if (count($rows) < 2) {
            return [];
        }

        $mapped = $this->mapRowsUsingDetectedHeader($rows);
        if (!empty($mapped)) {
            return $mapped;
        }

        // Fallback for payslip-style sheets where data is stored as label/value pairs
        // instead of a strict tabular header row.
        return $this->extractPayslipRowsFromLabelValueGrid($rows);
    }

    private function extractRowsFromCsv(string $absolutePath): array
    {
        $rows = $this->extractRawRowsFromCsv($absolutePath);

        if (count($rows) < 2) {
            return [];
        }

        $mapped = $this->mapRowsUsingDetectedHeader($rows);
        if (!empty($mapped)) {
            return $mapped;
        }

        return $this->extractPayslipRowsFromLabelValueGrid($rows);
    }

    private function mapRowsUsingDetectedHeader(array $rows): array
    {
        $headerIndex = $this->detectHeaderRowIndex($rows);
        if ($headerIndex === null) {
            return [];
        }

        $headerRow = $rows[$headerIndex];
        $rows = array_slice($rows, $headerIndex + 1);
        $headers = [];
        foreach ($headerRow as $column => $headerText) {
            $headers[$column] = $this->normalizeHeader((string) $headerText);
        }

        $mapped = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($headers as $column => $header) {
                if ($header === '') {
                    continue;
                }
                $item[$header] = $row[$column] ?? null;
            }

            if (!empty(array_filter($item, fn ($value) => $value !== null && $value !== ''))) {
                $mapped[] = $item;
            }
        }

        return $mapped;
    }

    private function extractPayslipRowsFromLabelValueGrid(array $rows): array
    {
        $result = [];
        $current = [];

        foreach ($rows as $row) {
            $values = $this->orderedRowValues($row);
            if (count($values) < 2) {
                continue;
            }

            // Read as (label,value) pairs across the row: A/B, C/D, E/F...
            for ($i = 0; $i < count($values) - 1; $i += 2) {
                $label = trim((string) ($values[$i] ?? ''));
                $value = trim((string) ($values[$i + 1] ?? ''));
                if ($label === '' || $value === '') {
                    continue;
                }

                $field = $this->resolvePayslipFieldFromLabel($label);
                if (!$field) {
                    continue;
                }

                // New employee block detected.
                if ($field === 'emp_id_no' && !empty($current['emp_id_no'])) {
                    if (!empty($current['emp_id_no'])) {
                        $result[] = $current;
                    }
                    $current = [];
                }

                $current[$field] = $value;
            }
        }

        if (!empty($current['emp_id_no'])) {
            $result[] = $current;
        }

        return $result;
    }

    private function orderedRowValues(array $row): array
    {
        if (empty($row)) {
            return [];
        }

        $items = [];
        foreach ($row as $column => $value) {
            $items[] = [
                'column' => (string) $column,
                'index' => $this->columnToIndex((string) $column),
                'value' => (string) $value,
            ];
        }

        usort($items, fn ($a, $b) => $a['index'] <=> $b['index']);
        return array_map(fn ($item) => $item['value'], $items);
    }

    private function columnToIndex(string $column): int
    {
        $column = strtoupper(trim($column));
        if ($column === '' || !preg_match('/^[A-Z]+$/', $column)) {
            return PHP_INT_MAX;
        }

        $index = 0;
        for ($i = 0; $i < strlen($column); $i++) {
            $index = $index * 26 + (ord($column[$i]) - 64);
        }

        return $index;
    }

    private function resolvePayslipFieldFromLabel(string $label): ?string
    {
        $normalized = $this->normalizeHeader($label);

        $map = [
            'pay_date' => 'pay_date',
            'pay_period' => 'pay_date',
            'period' => 'pay_date',
            'date_covered' => 'pay_date',
            'emp_id_no' => 'emp_id_no',
            'employee_id_no' => 'emp_id_no',
            'employee_id' => 'emp_id_no',
            'emp_id' => 'emp_id_no',
            'empid' => 'emp_id_no',
            'id_no' => 'emp_id_no',
            'idno' => 'emp_id_no',
            'acct' => 'acct_no',
            'acct_no' => 'acct_no',
            'account_no' => 'acct_no',
            'account_number' => 'acct_no',
            'emp_name' => 'employee_name',
            'employee_name' => 'employee_name',
            'name' => 'employee_name',
            'full_name' => 'employee_name',
            'total_salary' => 'total_salary',
            'gross_pay' => 'total_salary',
            'gross_salary' => 'total_salary',
            'total_deduction' => 'total_deduction',
            'total_deductions' => 'total_deduction',
            'net_pay' => 'net_pay',
            'take_home_pay' => 'net_pay',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        // Handle labels like "Acct #".
        if (str_starts_with($normalized, 'acct')) {
            return 'acct_no';
        }

        return null;
    }

    private function columnNameFromIndex(int $index): string
    {
        $index = max(0, $index);
        $name = '';
        do {
            $name = chr(($index % 26) + 65).$name;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $name;
    }

    private function buildLoadsRecords(array $rows, LoadsUpload $loadsFile): array
    {
        $records = [];
        $now = now();
        $employeeNameLookup = $this->buildLoadsEmployeeNameLookup();

        foreach ($rows as $row) {
            if (!is_array($row) || empty(array_filter($row, fn ($value) => $value !== null && $value !== ''))) {
                continue;
            }

            $employeeName = $this->pickValue($row, ['employee_name', 'instnm', 'instructor_name', 'faculty_name', 'full_name']);
            $classCd = $this->pickValue($row, ['class_cd', 'classcd', 'class_code', 'class']);
            $sectionCd = $this->pickValue($row, ['section_cd', 'sectioncd', 'section_code', 'section']);
            $code = $this->pickValue($row, ['code', 'subject_code']);
            $courseNo = $this->pickValue($row, ['course_no', 'courseno', 'course_number', 'course']);
            $subjectName = $this->pickValue($row, ['subject_name', 'name', 'subject', 'descriptive_title', 'title']);
            $schedule = $this->pickValue($row, ['schedule', 'schnm', 'day_time', 'time_schedule']);
            $units = $this->pickValue($row, ['units', 'sizeval', 'total_units']);
            $lecUnits = $this->pickValue($row, ['lec_units', 'lecunits', 'lecture_units', 'lec']);
            $labUnits = $this->pickValue($row, ['lab_units', 'labunits', 'laboratory_units', 'lab']);
            $hours = $this->pickValue($row, ['hours', 'contact_hours', 'hrs']);

            if (
                !$classCd &&
                !$sectionCd &&
                !$code &&
                !$courseNo &&
                !$subjectName &&
                !$schedule &&
                !$units &&
                !$lecUnits &&
                !$labUnits &&
                !$hours
            ) {
                continue;
            }

            $normalizedEmployeeName = $this->normalizeLoadsEmployeeName($employeeName);
            if ($normalizedEmployeeName === null || !isset($employeeNameLookup[$normalizedEmployeeName])) {
                continue;
            }

            $records[] = [
                'employee_name' => $employeeName ? trim((string) $employeeName) : null,
                'class_cd' => $classCd ? trim((string) $classCd) : null,
                'section_cd' => $sectionCd ? trim((string) $sectionCd) : null,
                'code' => $code ? trim((string) $code) : null,
                'course_no' => $courseNo ? trim((string) $courseNo) : null,
                'subject_name' => $subjectName ? trim((string) $subjectName) : null,
                'schedule' => $schedule ? trim((string) $schedule) : null,
                'units' => $units ? trim((string) $units) : null,
                'lec_units' => $lecUnits ? trim((string) $lecUnits) : null,
                'lab_units' => $labUnits ? trim((string) $labUnits) : null,
                'hours' => $hours ? trim((string) $hours) : null,
                'scanned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $records;
    }

    private function buildLoadsEmployeeNameLookup(): array
    {
        $lookup = [];

        User::query()
            ->select(['first_name', 'middle_name', 'last_name', 'role'])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->chunk(500, function ($users) use (&$lookup) {
                foreach ($users as $user) {
                    foreach ($this->buildLoadsEmployeeNameVariants($user->first_name, $user->middle_name, $user->last_name) as $variant) {
                        $normalized = $this->normalizeLoadsEmployeeName($variant);
                        if ($normalized !== null) {
                            $lookup[$normalized] = true;
                        }
                    }
                }
            });

        return $lookup;
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
        $variants = array_filter([
            trim(implode(' ', array_filter([$first, $middle, $last]))),
            trim(implode(' ', array_filter([$first, $last]))),
            $last !== '' ? trim($last.', '.implode(' ', array_filter([$first, $middle]))) : '',
            $last !== '' ? trim($last.', '.implode(' ', array_filter([$first, $middleInitial !== '' ? $middleInitial.'.' : '']))) : '',
            $last !== '' ? trim($last.', '.implode(' ', array_filter([$first, $middleInitial]))) : '',
        ], fn ($value) => trim((string) $value) !== '');

        return array_values(array_unique($variants));
    }

    private function normalizeLoadsEmployeeName($value): ?string
    {
        $name = trim((string) ($value ?? ''));
        if ($name === '') {
            return null;
        }

        $name = preg_replace('/\s+/', ' ', $name);
        $name = str_replace(['.', ','], ['', ','], $name);

        return strtolower(trim($name));
    }

    private function buildKnownEmployeeIdLookupFromRows(array $rows): array
    {
        $employeeIds = collect($rows)
            ->map(function ($row) {
                $employeeId = $this->pickValue($row, [
                    'employee_id', 'employeeid', 'id_no', 'idno', 'emp_id', 'empid',
                ]);

                return $this->normalizeEmployeeId($employeeId);
            })
            ->filter()
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            return [];
        }

        return Employee::query()
            ->select(['employee_id'])
            ->whereIn('employee_id', $employeeIds->all())
            ->get()
            ->map(function ($employee) {
                return $this->normalizeEmployeeId($employee->employee_id);
            })
            ->filter()
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    private function buildEmployeeJobTypeMapFromRows(array $rows): array
    {
        $employeeIds = collect($rows)
            ->map(function ($row) {
                $employeeId = $this->pickValue($row, [
                    'employee_id', 'employeeid', 'id_no', 'idno', 'emp_id', 'empid',
                ]);

                return $this->normalizeEmployeeId($employeeId);
            })
            ->filter()
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            return [];
        }

        if (!Schema::hasColumn('employees', 'job_type')) {
            return [];
        }

        $this->syncEmployeeJobTypesFromOpenPositions($employeeIds->all());

        return Employee::query()
            ->select(['employee_id', 'job_type'])
            ->whereIn('employee_id', $employeeIds->all())
            ->get()
            ->mapWithKeys(function ($employee) {
                $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                if ($employeeId === '') {
                    return [];
                }

                $jobType = $this->normalizeEmployeeJobType($employee->job_type);

                return [$employeeId => $jobType];
            })
            ->all();
    }

    private function syncEmployeeJobTypesFromOpenPositions(array $employeeIds = []): void
    {
        if (!Schema::hasColumn('employees', 'job_type')) {
            return;
        }

        $employees = Employee::query()
            ->select(['id', 'user_id', 'employee_id', 'job_type'])
            ->whereNotNull('user_id')
            ->when(!empty($employeeIds), function ($query) use ($employeeIds) {
                $query->whereIn('employee_id', $employeeIds);
            })
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $userIds = $employees->pluck('user_id')->filter()->unique()->values();
        if ($userIds->isEmpty()) {
            return;
        }

        $latestApplicantsByUser = Applicant::query()
            ->select(['id', 'user_id', 'open_position_id'])
            ->whereIn('user_id', $userIds->all())
            ->whereNotNull('open_position_id')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        if ($latestApplicantsByUser->isEmpty()) {
            return;
        }

        $openPositionIds = $latestApplicantsByUser
            ->pluck('open_position_id')
            ->filter()
            ->unique()
            ->values();

        if ($openPositionIds->isEmpty()) {
            return;
        }

        $openPositionJobTypeMap = OpenPosition::query()
            ->whereIn('id', $openPositionIds->all())
            ->pluck('job_type', 'id');

        foreach ($employees as $employee) {
            $openPositionId = optional($latestApplicantsByUser->get($employee->user_id))->open_position_id;
            if (!$openPositionId) {
                continue;
            }

            $jobTypeFromOpenPosition = $this->normalizeEmployeeJobType($openPositionJobTypeMap->get($openPositionId));
            if (!$jobTypeFromOpenPosition) {
                continue;
            }

            if ($this->normalizeEmployeeJobType($employee->job_type) === $jobTypeFromOpenPosition) {
                continue;
            }

            Employee::query()
                ->whereKey($employee->id)
                ->update(['job_type' => $jobTypeFromOpenPosition]);
        }
    }

    private function resolveJobTypeFromOpenPositionForUser($userId): ?string
    {
        if (!$userId) {
            return null;
        }

        $applicant = Applicant::query()
            ->select(['open_position_id'])
            ->where('user_id', $userId)
            ->whereNotNull('open_position_id')
            ->orderByDesc('id')
            ->first();

        if (!$applicant || !$applicant->open_position_id) {
            return null;
        }

        $jobType = OpenPosition::query()
            ->whereKey($applicant->open_position_id)
            ->value('job_type');

        return $this->normalizeEmployeeJobType($jobType);
    }

    private function buildEmployeeDepartmentMapFromRows(array $rows): array
    {
        $employeeIds = collect($rows)
            ->map(function ($row) {
                $employeeId = $this->pickValue($row, [
                    'employee_id', 'employeeid', 'id_no', 'idno', 'emp_id', 'empid',
                ]);

                return $this->normalizeEmployeeId($employeeId);
            })
            ->filter()
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            return [];
        }

        if (!Schema::hasColumn('employees', 'department')) {
            return [];
        }

        return Employee::query()
            ->select(['employee_id', 'department'])
            ->whereIn('employee_id', $employeeIds->all())
            ->get()
            ->mapWithKeys(function ($employee) {
                $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                if ($employeeId === '') {
                    return [];
                }

                return [$employeeId => $employee->department ? (string) $employee->department : null];
            })
            ->all();
    }

    private function readXlsxEntry(string $absolutePath, string $entry): string|false
    {
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($absolutePath) === true) {
                $contents = $zip->getFromName($entry);
                $zip->close();
                if ($contents !== false) {
                    return $contents;
                }
            }
        }

        if (class_exists(\PharData::class)) {
            $pharEntry = 'phar://'.$absolutePath.'/'.$entry;
            if (is_file($pharEntry)) {
                $contents = @file_get_contents($pharEntry);
                if ($contents !== false) {
                    return $contents;
                }
            }
        }

        return false;
    }

    private function listXlsxWorksheetEntries(string $absolutePath): array
    {
        $entries = [];

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($absolutePath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if ($name && str_starts_with($name, 'xl/worksheets/') && str_ends_with($name, '.xml')) {
                        $entries[] = $name;
                    }
                }
                $zip->close();
            }
        } elseif (class_exists(\PharData::class)) {
            try {
                $phar = new \PharData($absolutePath);
                $prefix = 'phar://'.$absolutePath.'/';
                foreach (new \RecursiveIteratorIterator($phar) as $filePath => $fileInfo) {
                    $entry = str_replace($prefix, '', str_replace('\\', '/', (string) $filePath));
                    if (str_starts_with($entry, 'xl/worksheets/') && str_ends_with($entry, '.xml')) {
                        $entries[] = $entry;
                    }
                }
            } catch (\Throwable $e) {
                // Keep empty result; caller handles missing worksheet.
            }
        }

        sort($entries);
        return $entries;
    }

    private function findXlsxWorksheetEntryBySheetName(string $absolutePath, string $sheetName): ?string
    {
        $sheetName = trim($sheetName);
        if ($sheetName === '') {
            return null;
        }

        $workbookXml = $this->readXlsxEntry($absolutePath, 'xl/workbook.xml');
        if ($workbookXml === false) {
            return null;
        }

        $workbook = simplexml_load_string($workbookXml);
        if (!$workbook) {
            return null;
        }

        $relsXml = $this->readXlsxEntry($absolutePath, 'xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            return null;
        }

        $rels = simplexml_load_string($relsXml);
        if (!$rels) {
            return null;
        }

        $relationshipTargets = [];
        $relationships = $rels->xpath("//*[local-name()='Relationship']") ?: [];
        foreach ($relationships as $relationship) {
            $id = trim((string) ($relationship['Id'] ?? ''));
            $target = trim((string) ($relationship['Target'] ?? ''));
            if ($id !== '' && $target !== '') {
                $relationshipTargets[$id] = $target;
            }
        }

        if (empty($relationshipTargets)) {
            return null;
        }

        $targetSheetName = strtolower($sheetName);
        $sheets = $workbook->xpath("//*[local-name()='sheet']") ?: [];
        foreach ($sheets as $sheet) {
            $currentSheetName = trim((string) ($sheet['name'] ?? ''));
            if ($currentSheetName === '' || strtolower($currentSheetName) !== $targetSheetName) {
                continue;
            }

            $relAttributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationId = trim((string) ($relAttributes['id'] ?? ''));
            if ($relationId === '' || !isset($relationshipTargets[$relationId])) {
                continue;
            }

            $target = str_replace('\\', '/', ltrim((string) $relationshipTargets[$relationId], '/'));
            if (!str_starts_with($target, 'xl/')) {
                $target = 'xl/'.ltrim($target, '/');
            }

            if (str_starts_with($target, 'xl/worksheets/') && str_ends_with($target, '.xml')) {
                return $target;
            }
        }

        return null;
    }

    private function detectHeaderRowIndex(array $rows): ?int
    {
        $sample = array_slice($rows, 0, 25);
        foreach ($sample as $index => $row) {
            $headers = [];
            foreach ($row as $value) {
                $headers[] = $this->normalizeHeader((string) $value);
            }

            $hasEmployeeId = $this->hasAnyKey($headers, ['employee_id', 'employee_id_no', 'employeeid', 'id_no', 'idno', 'emp_id', 'empid', 'emp_id_no']);
            $hasAmPmColumns = $this->hasAnyKey($headers, ['am_time', 'am_in', 'morning_in', 'am'])
                && $this->hasAnyKey($headers, ['pm_time', 'pm_in', 'afternoon_in', 'pm']);
            $hasRawPunchColumns = $this->hasAnyKey($headers, ['date', 'attendance_date'])
                && $this->hasAnyKey($headers, ['time'])
                && $this->hasAnyKey($headers, ['type']);
            $hasPayslipColumns = $this->hasAnyKey($headers, [
                'pay_date',
                'pay_period',
                'period',
                'date_covered',
                'employee_name',
                'emp_name',
                'no',
                'no_',
                'basic_salary',
                'basic_salar',
                'living_allowance',
                'extra_load',
                'other_income',
                'absences_date',
                'absences_amount',
                'withholding_tax',
                'salary_loan_ale',
                'salary_vale',
                'pag_ibig_loan',
                'pag_ibig_share',
                'pag_ibig_premium',
                'sss_peraa_loan',
                'sss_peraa_share',
                'sss_loan',
                'sss_premium',
                'philhealth_share',
                'philhealth_premium',
                'others',
                'other_deduction',
                'amount_due',
                'account_credited',
                'total_salary',
                'total_deduction',
                'net_pay',
            ]);

            if ($hasEmployeeId && ($hasAmPmColumns || $hasRawPunchColumns || $hasPayslipColumns)) {
                return $index;
            }
        }

        return null;
    }

    private function hasAnyKey(array $keys, array $candidates): bool
    {
        $lookup = array_fill_keys($keys, true);
        foreach ($candidates as $candidate) {
            if (isset($lookup[$candidate])) {
                return true;
            }
        }

        return false;
    }

    private function pickValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        return null;
    }

    private function buildPayslipRecords(array $rows, int $uploadId, ?string $fallbackPayDate = null): array
    {
        $employees = Employee::query()
            ->select(['user_id', 'employee_id'])
            ->whereNotNull('employee_id')
            ->get();

        $employeesByExactId = [];
        foreach ($employees as $employee) {
            $employeeId = $this->normalizeEmployeeId($employee->employee_id);
            if ($employeeId === '') {
                continue;
            }

            $employeesByExactId[$employeeId] = $employee;
        }

        $records = [];
        $now = now();
        $detectedSheetPayDate = $this->detectPayslipDateFromRows($rows);

        foreach ($rows as $row) {
            $employeeIdRaw = $this->pickValue($row, [
                'emp_id_no',
                'employee_id_no',
                'employee_id',
                'employee_no',
                'employee_number',
                'emp_id',
                'empid',
                'id_no',
                'idno',
            ]);
            $employeeId = $this->normalizeEmployeeId($employeeIdRaw);
            if ($employeeId === '') {
                continue;
            }

            $employee = $employeesByExactId[$employeeId] ?? null;
            if (!$employee) {
                // Strict match only: insert only if Excel Employee ID equals employees.employee_id.
                continue;
            }

            $matchedEmployeeId = $this->normalizeEmployeeId((string) $employee->employee_id);
            if ($matchedEmployeeId === '') {
                continue;
            }

            $employeeName = $this->pickValue($row, ['employee_name', 'emp_name', 'name', 'full_name']);
            $payDateText = $this->pickValue($row, ['pay_date', 'pay_period', 'period', 'date_covered', 'date']);
            $rowPayDate = $this->normalizeDate($this->pickValue($row, ['pay_date', 'date_covered', 'pay_period', 'period', 'date']));
            $payDate = $rowPayDate ?: $detectedSheetPayDate ?: $fallbackPayDate;
            $rowNoRaw = $this->pickValue($row, ['no', 'no_']);
            $rowNo = is_numeric((string) $rowNoRaw) ? (int) $rowNoRaw : null;
            $basicSalary = $this->normalizeMoneyValue($this->pickValue($row, ['basic_salary', 'basic_salar']));
            $livingAllowance = $this->normalizeMoneyValue($this->pickValue($row, ['living_allowance']));
            $extraLoad = $this->normalizeMoneyValue($this->pickValue($row, ['extra_load']));
            $otherIncome = $this->normalizeMoneyValue($this->pickValue($row, ['other_income']));
            $absencesDate = $this->pickValue($row, ['absences_date', 'absence_date']);
            $absencesAmount = $this->normalizeMoneyValue($this->pickValue($row, ['absences_amount', 'absence_amount']));
            $withholdingTax = $this->normalizeMoneyValue($this->pickValue($row, ['withholding_tax']));
            $salaryVale = $this->normalizeMoneyValue($this->pickValue($row, ['salary_vale', 'salary_loan_ale']));
            $pagIbigLoan = $this->normalizeMoneyValue($this->pickValue($row, ['pag_ibig_loan', 'pagibig_loan']));
            $pagIbigPremium = $this->normalizeMoneyValue($this->pickValue($row, ['pag_ibig_premium', 'pagibig_premium', 'pag_ibig_share']));
            $sssLoan = $this->normalizeMoneyValue($this->pickValue($row, ['sss_loan', 'sss_peraa_loan']));
            $sssPremium = $this->normalizeMoneyValue($this->pickValue($row, ['sss_premium', 'sss_peraa_share']));
            $peraaLoan = $this->normalizeMoneyValue($this->pickValue($row, ['peraa_loan']));
            $peraaPremium = $this->normalizeMoneyValue($this->pickValue($row, ['peraa_premium']));
            $philhealthPremium = $this->normalizeMoneyValue($this->pickValue($row, ['philhealth_premium', 'philhealth_share']));
            $otherDeduction = $this->normalizeMoneyValue($this->pickValue($row, ['other_deduction', 'others']));
            $amountDue = $this->normalizeMoneyValue($this->pickValue($row, ['amount_due']));
            $accountCredited = $this->pickValue($row, ['account_credited', 'acct_credited', 'account_credit', 'acct_no', 'account_no', 'account_number']);
            $totalSalary = $this->normalizeMoneyValue($this->pickValue($row, ['total_salary', 'gross_pay', 'gross_salary']));
            $totalDeduction = $this->normalizeMoneyValue($this->pickValue($row, ['total_deduction', 'deductions_total']));
            $netPay = $this->normalizeMoneyValue($this->pickValue($row, ['net_pay', 'net_salary', 'take_home_pay'])) ?? $amountDue;

            $records[] = [
                'payslip_upload_id' => $uploadId,
                'user_id' => $employee->user_id ? (int) $employee->user_id : null,
                'employee_id' => $matchedEmployeeId,
                'employee_name' => $employeeName ? trim((string) $employeeName) : null,
                'row_no' => $rowNo,
                'basic_salary' => $basicSalary,
                'living_allowance' => $livingAllowance,
                'extra_load' => $extraLoad,
                'other_income' => $otherIncome,
                'absences_date' => $absencesDate ? trim((string) $absencesDate) : null,
                'absences_amount' => $absencesAmount,
                'withholding_tax' => $withholdingTax,
                'salary_vale' => $salaryVale,
                'pag_ibig_loan' => $pagIbigLoan,
                'pag_ibig_premium' => $pagIbigPremium,
                'sss_loan' => $sssLoan,
                'sss_premium' => $sssPremium,
                'peraa_loan' => $peraaLoan,
                'peraa_premium' => $peraaPremium,
                'philhealth_premium' => $philhealthPremium,
                'other_deduction' => $otherDeduction,
                'amount_due' => $amountDue,
                'account_credited' => $accountCredited ? trim((string) $accountCredited) : null,
                'pay_date_text' => $payDateText ? trim((string) $payDateText) : null,
                'pay_date' => $payDate,
                'total_salary' => $totalSalary,
                'total_deduction' => $totalDeduction,
                'net_pay' => $netPay,
                'payload' => json_encode($row),
                'scanned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $records;
    }

    private function detectPayslipDateFromRows(array $rows): ?string
    {
        foreach ($rows as $row) {
            $candidate = $this->pickValue($row, ['pay_date', 'date_covered', 'pay_period', 'period', 'date']);
            $normalized = $this->normalizeDate($candidate);
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeMoneyValue(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '' || $text === '-') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $text);
        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return null;
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function normalizeHeader(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = trim((string) $normalized, '_ ');

        return $normalized;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            $datePart = (int) floor($serial);
            if ($datePart > 0) {
                return Carbon::create(1899, 12, 30)->addDays($datePart)->toDateString();
            }
        }

        $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'm-d-Y', 'd-m-Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->toDateString();
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;
            $fraction = $numeric > 1 ? $numeric - floor($numeric) : $numeric;
            if ($fraction >= 0 && $fraction < 1) {
                $seconds = (int) round($fraction * 86400);
                $hours = intdiv($seconds, 3600);
                $minutes = intdiv($seconds % 3600, 60);
                return sprintf('%02d:%02d:00', $hours, $minutes);
            }
        }

        $formats = ['H:i', 'H:i:s', 'g:i A', 'g:iA', 'h:i A', 'h:iA'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->format('H:i:s');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function calculateLateMinutes(?string $morningIn, ?string $afternoonIn): int
    {
        $late = 0;

        if ($morningIn) {
            $morningActual = Carbon::createFromFormat('H:i:s', $morningIn);
            $morningExpected = Carbon::createFromFormat('H:i:s', '08:00:00');
            $morningGraceEnd = Carbon::createFromFormat('H:i:s', '08:15:00');
            if ($morningActual->greaterThan($morningGraceEnd)) {
                $late += $morningExpected->diffInMinutes($morningActual);
            }
        }

        if ($afternoonIn) {
            $afternoonActual = Carbon::createFromFormat('H:i:s', $afternoonIn);
            $afternoonExpected = Carbon::createFromFormat('H:i:s', '13:00:00');
            $afternoonGraceEnd = Carbon::createFromFormat('H:i:s', '13:15:00');
            if ($afternoonActual->greaterThan($afternoonGraceEnd)) {
                $late += $afternoonExpected->diffInMinutes($afternoonActual);
            }
        }

        return $late;
    }

    //UPDATE
    public function update_position(Request $request, $id){
        Log::info($request);
        $attrs = $request->validate([
            'title' => 'required',
            'department' => 'required',
            'employment' => 'required',
            //'mode' => 'required',
            'job_description' => 'required',
            'responsibilities' => 'required',
            'requirements' => 'required',
            // 'min' => 'required',
            // 'max' => 'required',
            'experience_level' => 'required',
            'location' => 'required',
            'skills' => 'required',
            //'benefits' => 'required',
            'job_type' => 'required',
            'one' => 'required|date',
            'two' => 'required|date',
        ]);

        $open = OpenPosition::findOrFail($id);
        $normalizedJobType = $this->normalizeEmployeeJobType($attrs['job_type']);

        $open->update([
            'title' => $attrs['title'],
            'department' => $attrs['department'],
            'employment' => $attrs['employment'],
            //'work_mode' => $attrs['mode'],
            'job_description' => $attrs['job_description'],
            'responsibilities' => $attrs['responsibilities'],
            'requirements' => $attrs['requirements'],
            // 'min_salary' => $attrs['min'],
            // 'max_salary' => $attrs['max'],
            'experience_level' => $attrs['experience_level'],
            'location' => $attrs['location'],
            'skills' => $attrs['skills'],
            //'benifits' => $attrs['benefits'],
            'job_type' => $normalizedJobType,
            'one' => $attrs['one'],
            'two' => $attrs['two'],
        ]);

        // Keep employee records aligned with the updated open-position job type.
        if (Schema::hasColumn('employees', 'job_type')) {
            $relatedUserIds = Applicant::query()
                ->where('open_position_id', $open->id)
                ->whereNotNull('user_id')
                ->pluck('user_id');

            if ($relatedUserIds->isNotEmpty()) {
                Employee::query()
                    ->whereIn('user_id', $relatedUserIds)
                    ->update(['job_type' => $normalizedJobType]);
            }
        }

        return redirect()->route('admin.adminPosition')->with('success','Success Added Position');
    }

    // === APPLICANT STATUS UPDATE #2 === Direct Status Update Method
    // Allows direct manual update of applicant status from request
    public function update_application_status(Request $request){
        $attrs = $request->validate([
            'reviewId' => 'required',
            'status' => 'required|string',
            'date_hired' => 'nullable|required_if:status,Hired|date',
        ]);

        $review = Applicant::findOrFail($attrs['reviewId']);

        if (strcasecmp(trim((string) $attrs['status']), 'Hired') === 0) {
            $this->reactivateResignedEmployeeAccountForApplicant($review);
        }

        $updatePayload = [
            'application_status' => $attrs['status'],
        ];

        if (strcasecmp(trim((string) $attrs['status']), 'Hired') === 0) {
            $updatePayload['date_hired'] = $attrs['date_hired'];
        }

        $review->update($updatePayload);

        if (
            strcasecmp(trim((string) $attrs['status']), 'Hired') === 0
            && !empty($attrs['date_hired'])
            && (int) ($review->user_id ?? 0) > 0
        ) {
            Employee::query()
                ->where('user_id', (int) $review->user_id)
                ->update(['employement_date' => $attrs['date_hired']]);
        }

        $review = $review->fresh(['position']);
        $this->syncDepartmentHeadFromApplicant($review);

        $successMessage = 'Success Update Application Status';

        try {
            Mail::to($this->mailToAddress($review->email))
                    ->queue(new ApplicationUpdatedMail($review));
        } catch (\Throwable $exception) {
            Log::warning('Applicant status updated but notification email could not be queued.', [
                'applicant_id' => $review->id,
                'email' => $review->email,
                'to_override' => config('mail.to_override'),
                'status' => $attrs['status'],
                'error' => $exception->getMessage(),
            ]);

            $successMessage .= ' Email notification was not queued. Please check the queue configuration.';
        }

        return redirect()
            ->back()
            ->with('success', $successMessage)
            ->with('updated_applicant_id', $review->id)
            ->with('updated_applicant_status', $attrs['status']);
    }

    public function updated_interview(Request $request){
        Log::info($request);
        $attrs = $request->validate([
            'interviewId' => 'required',
            'applicantId' => 'required',
            'interview_type' => 'required',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i,H:i:s',
            'duration' => 'required',
            'interviewers' => 'required',
            'email_link' => 'required',
            'url' => 'nullable',
            'notes' => 'nullable',
        ]);

        $interview = Interviewer::findOrFail($attrs['interviewId']);
        $newApplicantId = (int) $attrs['applicantId'];
        $newInterviewType = (string) $attrs['interview_type'];

        if ($this->interviewIsFinished($interview)) {
            return redirect()
                ->back()
                ->with('error', $interview->interview_type.' is already finished and cannot be rescheduled.')
                ->with('updated_applicant_id', $newApplicantId)
                ->with('updated_applicant_status', $this->resolveApplicantStatusFromInterviewType((string) $interview->interview_type));
        }

        if (
            (
                (int) $interview->applicant_id !== $newApplicantId
                || strcasecmp(trim((string) $interview->interview_type), trim($newInterviewType)) !== 0
            )
            && $this->hasCompletedInterviewStage($newApplicantId, $newInterviewType)
        ) {
            return redirect()
                ->back()
                ->with('error', $newInterviewType.' is already finished for this applicant and cannot be scheduled again.')
                ->with('updated_applicant_id', $newApplicantId)
                ->with('updated_applicant_status', $this->resolveApplicantStatusFromInterviewType($newInterviewType));
        }

        $conflictingInterview = $this->findActiveInterviewSchedule($newApplicantId, (int) $interview->id);
        if ($conflictingInterview) {
            return redirect()
                ->back()
                ->with('error', 'Only one interview schedule is allowed per applicant. Another interview is already active.')
                ->with('interview_schedule_conflict', 'This applicant already has an active '.$conflictingInterview->interview_type.'. Reschedule, finish, or cancel it before moving this schedule.')
                ->with('updated_applicant_id', $newApplicantId)
                ->with('updated_applicant_status', $this->resolveApplicantStatusFromInterviewType($newInterviewType));
        }

        $interview->update([
            'applicant_id' => $attrs['applicantId'],
            'interview_type' => $attrs['interview_type'],
            'date' => $attrs['date'],
            'time' => $attrs['time'],
            'duration' => $attrs['duration'],
            'ended_at' => null,
            'interviewers' => $attrs['interviewers'],
            'email_link' => $attrs['email_link'],
            'url' => $attrs['url'],
            'notes' => $attrs['notes'],
        ]);

        // === APPLICANT STATUS UPDATE #3 === Updated Interview Method
        // Updates applicant status when an existing interview is modified
        Applicant::where('id', $attrs['applicantId'])->update([
            'application_status' => $this->resolveApplicantStatusFromInterviewType($attrs['interview_type']),
        ]);

        // Mail::to($store->applicant->email)
        //         ->send(new ApplicationInterviewMail($store));

        return redirect()->back()->with('success','Success Added Interview');
    }

    public function end_interview_now(Request $request, Interviewer $interview)
    {
        if (!$interview->ended_at) {
            $interview->forceFill([
                'ended_at' => now(),
            ])->save();
        }

        return response()->json([
            'ok' => true,
            'interview_id' => $interview->id,
            'ended_at' => optional($interview->ended_at)->toIso8601String(),
            'duration' => $interview->duration,
        ]);
    }

    public function extend_interview(Request $request, Interviewer $interview)
    {
        $attrs = $request->validate([
            'minutes' => 'nullable|integer|min:1|max:240',
        ]);

        if ($interview->ended_at) {
            return response()->json([
                'ok' => false,
                'message' => 'This interview is already finished.',
            ], 422);
        }

        $extraMinutes = (int) ($attrs['minutes'] ?? 15);
        $nextDuration = max(1, $this->durationToMinutes($interview->duration)) + $extraMinutes;

        $interview->update([
            'duration' => $nextDuration.' minutes',
        ]);

        $start = Carbon::parse($interview->date->toDateString().' '.$interview->time);
        $end = (clone $start)->addMinutes($nextDuration);

        return response()->json([
            'ok' => true,
            'interview_id' => $interview->id,
            'duration' => $interview->duration,
            'ends_at' => $end->toIso8601String(),
        ]);
    }

    private function resolveApplicantStatusFromInterviewType(string $interviewType): string
    {
        $normalizedInterviewType = trim($interviewType);

        if (strcasecmp($normalizedInterviewType, 'Final Interview') === 0) {
            return 'Final Interview';
        }

        if (strcasecmp($normalizedInterviewType, 'Demo Teaching') === 0) {
            return 'Demo Teaching';
        }

        return 'Initial Interview';
    }

    private function hasCompletedInterviewStage(int $applicantId, string $interviewType): bool
    {
        $normalizedType = strtolower(trim($interviewType));
        if ($applicantId <= 0 || $normalizedType === '') {
            return false;
        }

        return Interviewer::query()
            ->where('applicant_id', $applicantId)
            ->whereRaw('LOWER(TRIM(interview_type)) = ?', [$normalizedType])
            ->get()
            ->contains(fn (Interviewer $interview) => $this->interviewIsFinished($interview));
    }

    private function hasRequiredPreviousInterviewStage(int $applicantId, string $interviewType): bool
    {
        $normalizedType = strtolower(trim($interviewType));

        if ($normalizedType === 'final interview' || $normalizedType === 'demo teaching') {
            return $this->hasCompletedInterviewStage($applicantId, 'Initial Interview');
        }

        return true;
    }

    private function findActiveInterviewSchedule(int $applicantId, string $interviewType): ?Interviewer
    {
        $normalizedType = strtolower(trim($interviewType));
        if ($applicantId <= 0 || $normalizedType === '') {
            return null;
        }

        return Interviewer::query()
            ->where('applicant_id', $applicantId)
            ->whereRaw('LOWER(TRIM(interview_type)) = ?', [$normalizedType])
            ->orderByDesc('created_at')
            ->get()
            ->first(fn (Interviewer $interview) => !$this->interviewIsFinished($interview));
    }

    private function interviewIsFinished(Interviewer $interview): bool
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

    public function update_employee($id){


        $open = User::findOrFail($id);

        $payload = [
            'status' => 'Approved',
        ];

        if ($this->shouldAutoApproveDepartmentHead(
            $open->position,
            optional($open->applicant)->position->title ?? null
        )) {
            $payload['department_head'] = 'Approved';
        }

        $open->update($payload);

        return redirect()->back()->with('success','Employee can now login');
    }

    private function syncDepartmentHeadFromApplicant(?Applicant $applicant): void
    {
        if (!$applicant) {
            return;
        }

        $userId = (int) ($applicant->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        if (strcasecmp(trim((string) ($applicant->application_status ?? '')), 'Hired') !== 0) {
            return;
        }

        $positionTitle = trim((string) (optional($applicant->position)->title ?? ''));
        if (!$this->shouldAutoApproveDepartmentHead($positionTitle)) {
            return;
        }

        User::query()
            ->where('id', $userId)
            ->update([
                'department_head' => 'Approved',
            ]);
    }

    private function shouldAutoApproveDepartmentHead(?string ...$positionCandidates): bool
    {
        foreach ($positionCandidates as $positionCandidate) {
            $position = strtolower(trim((string) ($positionCandidate ?? '')));
            if ($position !== '' && str_contains($position, 'director')) {
                return true;
            }
        }

        return false;
    }

    public function update_service_record(Request $request)
    {
        $attrs = $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'date_hired' => 'nullable|date',
            'SSS' => 'nullable|string|max:255',
            'TIN' => 'nullable|string|max:255',
            'PhilHealth' => 'nullable|string|max:255',
            'MID' => 'nullable|string|max:255',
            'RTN' => 'nullable|string|max:255',
            'service_rows' => 'nullable|array|max:20',
            'service_rows.*.from_date' => 'nullable|date',
            'service_rows.*.to_date' => 'nullable|date',
            'service_rows.*.designation' => 'nullable|string|max:255',
            'service_rows.*.status' => 'nullable|string|max:255',
            'service_rows.*.salary' => 'nullable|string|max:255',
            'service_rows.*.office' => 'nullable|string|max:255',
            'service_rows.*.separation_date' => 'nullable|date',
            'service_rows.*.separation_cause' => 'nullable|string|max:255',
            'service_rows.*.remarks' => 'nullable|string|max:255',
        ]);

        $normalize = static function ($value): ?string {
            $text = trim((string) ($value ?? ''));
            return $text === '' ? null : $text;
        };
        $normalizeServiceStatus = static function ($value) use ($normalize): ?string {
            $text = $normalize($value);
            if ($text === null) {
                return null;
            }
            $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $text));
            $normalized = trim((string) preg_replace('/\s+/', ' ', (string) $normalized));
            if (str_contains($normalized, 'full')) {
                return 'Full-Time';
            }
            if (str_contains($normalized, 'part')) {
                return 'Part-Time';
            }
            return $text;
        };
        $parseServiceRemarkAction = static function ($value) use ($normalize): array {
            $text = $normalize($value);
            if ($text === null) {
                return ['action' => null, 'title' => null];
            }

            $action = null;
            if (preg_match('/\bpromoted\b/i', $text) === 1) {
                $action = 'promoted';
            } elseif (preg_match('/\b(resigned|resign)\b/i', $text) === 1) {
                $action = 'resigned';
            }

            if ($action === null) {
                return ['action' => null, 'title' => null];
            }

            $title = null;
            if (preg_match('/\b(?:promoted|resigned|resign)\s+as\s+(.+?)\s*$/i', $text, $matches) === 1) {
                $title = $normalize($matches[1] ?? null);
            }

            return ['action' => $action, 'title' => $title];
        };

        $normalizeRow = static function (array $row) use ($normalize): array {
            return [
                'from_date' => $normalize($row['from_date'] ?? null),
                'to_date' => $normalize($row['to_date'] ?? null),
                'designation' => $normalize($row['designation'] ?? null),
                'status' => $normalize($row['status'] ?? null),
                'salary' => $normalize($row['salary'] ?? null),
                'office' => $normalize($row['office'] ?? null),
                'separation_date' => $normalize($row['separation_date'] ?? null),
                'separation_cause' => $normalize($row['separation_cause'] ?? null),
                'remarks' => $normalize($row['remarks'] ?? null),
            ];
        };

        $serviceRows = collect($attrs['service_rows'] ?? [])
            ->map(fn ($row) => is_array($row) ? $normalizeRow($row) : [])
            ->filter(function (array $row) {
                foreach ($row as $value) {
                    if (filled($value)) {
                        return true;
                    }
                }
                return false;
            })
            ->values()
            ->all();

        $user = User::query()->findOrFail((int) $attrs['user_id']);
        $existingApplicantRecord = Applicant::query()
            ->where('user_id', (int) $attrs['user_id'])
            ->orderByDesc('id')
            ->first();

        $rowCollection = collect($serviceRows);
        $firstRow = $rowCollection->first() ?? [];
        $latestActionableRow = $rowCollection
            ->reverse()
            ->first(function (array $row) {
                return filled($row['designation'] ?? null)
                    || filled($row['status'] ?? null)
                    || filled($row['salary'] ?? null)
                    || filled($row['office'] ?? null)
                    || filled($row['remarks'] ?? null);
            }) ?? ($firstRow ?? []);
        $latestCurrentRow = $rowCollection
            ->reverse()
            ->first(function (array $row) use ($parseServiceRemarkAction) {
                $remark = $parseServiceRemarkAction($row['remarks'] ?? null);
                if (($remark['action'] ?? null) === 'resigned') {
                    return false;
                }

                return filled($remark['title'] ?? null)
                    || filled($row['designation'] ?? null)
                    || filled($row['status'] ?? null)
                    || filled($row['salary'] ?? null)
                    || filled($row['office'] ?? null);
            }) ?? ($latestActionableRow ?? $firstRow ?? []);
        $latestRemarkAction = $parseServiceRemarkAction($latestActionableRow['remarks'] ?? null);
        $currentRemarkAction = $parseServiceRemarkAction($latestCurrentRow['remarks'] ?? null);
        $latestResolvedTitle = $currentRemarkAction['title']
            ?? ($latestCurrentRow['designation'] ?? null)
            ?? ($latestActionableRow['designation'] ?? null);

        $effectiveDateHired = $attrs['date_hired']
            ?? optional($existingApplicantRecord?->date_hired)->toDateString()
            ?? optional($existingApplicantRecord?->created_at)->toDateString()
            ?? ($firstRow['from_date'] ?? null);
        $effectivePosition = $attrs['position']
            ?? $latestResolvedTitle
            ?? ($firstRow['designation'] ?? null);
        $effectiveDepartment = $attrs['department']
            ?? ($latestCurrentRow['office'] ?? null)
            ?? ($latestActionableRow['office'] ?? null)
            ?? ($firstRow['office'] ?? null);
        $effectiveClassification = $normalizeServiceStatus($latestCurrentRow['status'] ?? ($firstRow['status'] ?? null));
        $effectiveSalary = $normalize($latestCurrentRow['salary'] ?? ($firstRow['salary'] ?? null));
        $effectiveJobRole = null;
        $serviceDesignationText = $normalize($effectivePosition);
        $effectivePositionText = $serviceDesignationText;
        $effectiveDepartmentHead = null;
        $effectiveAccountStatus = ($latestRemarkAction['action'] ?? null) === 'resigned' ? 'Inactive' : null;
        $designationNormalized = $serviceDesignationText !== null ? strtolower($serviceDesignationText) : null;
        $isVicePresidentDesignation = $designationNormalized !== null
            && preg_match('/(^|[^a-z])(vp|v\.p\.|vice president)([^a-z]|$)/i', $serviceDesignationText) === 1;
        $isTeachingHeadDesignation = $designationNormalized !== null && (
            str_contains($designationNormalized, 'vice dean')
            || preg_match('/(^|[^a-z])head([^a-z]|$)/i', $serviceDesignationText) === 1
        );
        $isNonTeachingHeadDesignation = $designationNormalized !== null && (
            str_contains($designationNormalized, 'legal counsel')
            || str_contains($designationNormalized, 'director')
            || preg_match('/(^|[^a-z])(oic|o\.i\.c\.|office in charge)([^a-z]|$)/i', $serviceDesignationText) === 1
            || str_contains($designationNormalized, 'school treasurer')
            || str_contains($designationNormalized, 'school accountant')
            || str_contains($designationNormalized, 'chief librarian')
            || str_contains($designationNormalized, 'guidance counselor')
            || str_contains($designationNormalized, 'guidance counsellor')
            || str_contains($designationNormalized, 'focal person')
            || str_contains($designationNormalized, 'coordinator')
            || str_contains($designationNormalized, 'principal')
            || str_contains($designationNormalized, 'building & property custodian')
            || str_contains($designationNormalized, 'building and property custodian')
            || str_contains($designationNormalized, 'building property custodian')
            || str_contains($designationNormalized, 'supervisor')
        );
        if (in_array($designationNormalized, ['president', 'dean'], true)) {
            $effectiveDepartmentHead = 'Approved';
        }
        if ($designationNormalized === 'president') {
            $effectiveJobRole = 'President';
            $effectivePositionText = 'Dean';
        }
        if ($isVicePresidentDesignation) {
            $effectiveJobRole = $serviceDesignationText;
            $effectivePositionText = 'Dean';
            $effectiveDepartmentHead = 'Approved';
        }
        if ($isNonTeachingHeadDesignation) {
            $effectiveDepartmentHead = 'Approved';
        }
        if ($isTeachingHeadDesignation) {
            $effectiveDepartmentHead = 'Approved';
        }
        $hasClassificationSalaryColumn = Schema::hasColumn('employees', 'classification_salary');

        $existingEmployeeForHistory = Employee::query()->where('user_id', (int) $attrs['user_id'])->first();
        $oldPositionForHistory = trim((string) ($existingEmployeeForHistory?->position ?? $user->position ?? ''));
        $oldDepartmentForHistory = trim((string) ($existingEmployeeForHistory?->department ?? $user->department ?? ''));
        $oldClassificationForHistory = trim((string) ($existingEmployeeForHistory?->classification ?? ''));

        $user->update([
            'position' => $effectivePositionText ?? $normalize($user->position),
            'department' => $normalize($effectiveDepartment) ?? $normalize($user->department),
            'job_role' => $effectiveJobRole ?? $user->job_role,
            'department_head' => $effectiveDepartmentHead ?? $user->department_head,
            'account_status' => $effectiveAccountStatus ?? $user->account_status,
        ]);

        $employee = $existingEmployeeForHistory;
        $employeePayload = [
            'position' => $normalize($effectivePosition)
                ?? ($employee?->position ?? $normalize($user->position) ?? '-'),
            'department' => $normalize($effectiveDepartment)
                ?? ($employee?->department ?? $normalize($user->department) ?? '-'),
            'classification' => $normalize($effectiveClassification)
                ?? ($employee?->classification ?? null),
            'employement_date' => $effectiveDateHired ?? ($employee?->employement_date ?? null),
            'service_record_rows' => $serviceRows,
        ];
        if ($hasClassificationSalaryColumn) {
            $employeePayload['classification_salary'] = $effectiveSalary
                ?? ($employee?->classification_salary ?? null);
        }

        if ($employee) {
            $employee->update($employeePayload);
        } else {
            $employeeCreatePayload = [
                'user_id' => (int) $attrs['user_id'],
                'employee_id' => '',
                'employement_date' => $effectiveDateHired ?? (optional($user->created_at)->toDateString() ?? now()->toDateString()),
                'birthday' => now()->subYears(18)->toDateString(),
                'account_number' => 'N/A',
                'sex' => 'Unspecified',
                'civil_status' => 'Single',
                'contact_number' => 'N/A',
                'address' => 'N/A',
                'department' => $employeePayload['department'] ?? '-',
                'position' => $employeePayload['position'] ?? '-',
                'classification' => $employeePayload['classification'] ?? 'Probationary',
                'service_record_rows' => $serviceRows,
            ];
            if ($hasClassificationSalaryColumn) {
                $employeeCreatePayload['classification_salary'] = $effectiveSalary;
            }
            Employee::create($employeeCreatePayload);
        }

        $existingSalary = Salary::query()->where('user_id', (int) $attrs['user_id'])->first();
        $oldSalaryForHistory = trim((string) ($existingSalary?->salary ?? ''));
        if ($existingSalary || filled($effectiveSalary)) {
            Salary::updateOrCreate(
                ['user_id' => (int) $attrs['user_id']],
                [
                    'salary' => $effectiveSalary ?? ($existingSalary?->salary ?? null),
                    'rate_per_hour' => $existingSalary?->rate_per_hour ?? null,
                    'cola' => $existingSalary?->cola ?? null,
                ]
            );
        }

        $governmentPayload = [
            'SSS' => $normalize($attrs['SSS'] ?? null),
            'TIN' => $normalize($attrs['TIN'] ?? null),
            'PhilHealth' => $normalize($attrs['PhilHealth'] ?? null),
            'MID' => $normalize($attrs['MID'] ?? null),
            'RTN' => $normalize($attrs['RTN'] ?? null),
        ];

        $hasAnyGovernmentData = collect($governmentPayload)->contains(fn ($value) => filled($value));
        $existingGovernment = Government::query()->where('user_id', (int) $attrs['user_id'])->first();

        if ($existingGovernment || $hasAnyGovernmentData) {
            Government::updateOrCreate(
                ['user_id' => (int) $attrs['user_id']],
                [
                    'SSS' => $governmentPayload['SSS'] ?? '',
                    'TIN' => $governmentPayload['TIN'] ?? '',
                    'PhilHealth' => $governmentPayload['PhilHealth'] ?? '',
                    'MID' => $governmentPayload['MID'] ?? '',
                    'RTN' => $governmentPayload['RTN'] ?? '',
                ]
            );
        }

        $userId = (int) $attrs['user_id'];
        $email = $normalize($user->email);
        $firstName = $normalize($user->first_name);
        $lastName = $normalize($user->last_name);

        $applicant = Applicant::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        if (!$applicant && $email) {
            $applicant = Applicant::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($email)])
                ->orderByDesc('id')
                ->first();
        }

        if (!$applicant) {
            $openPositionId = $this->resolveFallbackOpenPositionId();

            if ($openPositionId) {
                $applicant = Applicant::create([
                    'user_id' => $userId,
                    'open_position_id' => (int) $openPositionId,
                    'first_name' => $firstName ?: 'Employee',
                    'last_name' => $lastName ?: ('#'.$userId),
                    'email' => $email ?: ('employee-'.$userId.'@placeholder.local'),
                    'field_study' => '-',
                    'work_position' => $normalize($attrs['position'] ?? null) ?: '-',
                    'work_employer' => '-',
                    'work_location' => '-',
                    'work_duration' => '-',
                    'experience_years' => '0',
                    'skills_n_expertise' => '-',
                    'application_status' => 'Hired',
                    'fresh_graduate' => false,
                    'date_hired' => $effectiveDateHired ?? null,
                ]);
            }
        }

        if ($applicant) {
            $newPositionForHistory = trim((string) ($effectivePositionText ?? $applicant->work_position ?? ''));
            $mergedRelevantExperiencePosition = $this->buildRelevantExperiencePositions(
                (string) ($applicant->work_position ?? ''),
                $oldPositionForHistory,
                $newPositionForHistory
            );
            $applicant->update([
                'user_id' => $userId,
                'first_name' => $firstName ?: $applicant->first_name,
                'last_name' => $lastName ?: $applicant->last_name,
                'email' => $email ?: $applicant->email,
                'work_position' => $mergedRelevantExperiencePosition
                    ?? ($effectivePositionText ?? $applicant->work_position),
                'date_hired' => $effectiveDateHired ?? $applicant->date_hired,
            ]);
        }

        $effectiveDepartmentText = $normalize($effectiveDepartment);
        $effectiveClassificationText = $normalize($effectiveClassification);

        // Final sync pass: enforce service-record values across users/employees after applicant save hooks run.
        User::query()
            ->where('id', $userId)
            ->update([
                'position' => $effectivePositionText ?? $user->position,
                'department' => $effectiveDepartmentText ?? $user->department,
                'job_role' => $effectiveJobRole ?? $user->job_role,
                'department_head' => $effectiveDepartmentHead ?? $user->department_head,
                'account_status' => $effectiveAccountStatus ?? $user->account_status,
            ]);

        $employeeSyncPayload = [
            'position' => $effectivePositionText ?? ($employeePayload['position'] ?? null),
            'department' => $effectiveDepartmentText ?? ($employeePayload['department'] ?? null),
            'classification' => $effectiveClassificationText ?? ($employeePayload['classification'] ?? null),
            'employement_date' => $effectiveDateHired ?? ($employeePayload['employement_date'] ?? null),
        ];
        if ($hasClassificationSalaryColumn) {
            $employeeSyncPayload['classification_salary'] = $effectiveSalary
                ?? ($employeePayload['classification_salary'] ?? null);
        }

        Employee::query()
            ->where('user_id', $userId)
            ->update($employeeSyncPayload);

        $this->recordCareerProgressionIfChanged(
            $userId,
            $oldPositionForHistory,
            trim((string) ($employeeSyncPayload['position'] ?? '')),
            $oldClassificationForHistory,
            trim((string) ($employeeSyncPayload['classification'] ?? '')),
            'Updated from service record',
            $oldDepartmentForHistory,
            trim((string) ($employeeSyncPayload['department'] ?? '')),
            $oldSalaryForHistory,
            trim((string) ($effectiveSalary ?? ($existingSalary?->salary ?? '')))
        );

        return redirect()
            ->route('admin.PersonalDetail.serviceRecordEdit', ['user_id' => (int) $attrs['user_id']])
            ->with('success', 'Service record updated successfully.');
    }

    private function resolveFallbackOpenPositionId(): ?int
    {
        $openPositionId = DB::table('open_positions')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        if ($openPositionId) {
            return (int) $openPositionId;
        }

        $fallback = OpenPosition::query()->create([
            'title' => 'Unassigned Employee',
            'department' => 'General',
            'employment' => 'Full-Time',
            'work_mode' => 'Onsite',
            'job_description' => 'Auto-generated fallback position for employee sync.',
            'responsibilities' => '-',
            'requirements' => '-',
            'experience_level' => 'Entry Level',
            'location' => 'N/A',
            'skills' => '-',
            'benifits' => '-',
            'job_type' => 'NT',
        ]);

        return (int) $fallback->id;
    }

    public function update_general_profile(Request $request){
        $attrs = $request->validate([
            'user_id' => 'required|exists:users,id',
            'first' => 'required|string|max:255',
            'middle' => 'nullable|string|max:255',
            'last' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,'.$request->input('user_id'),
            'employee_id' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'classification' => 'nullable|string|max:255',
            'job_type' => 'nullable|string|max:50',
            'barangay' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:255',
            'SSS' => 'nullable|string|max:255',
            'TIN' => 'nullable|string|max:255',
            'PhilHealth' => 'nullable|string|max:255',
            'MID' => 'nullable|string|max:255',
            'RTN' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($attrs['user_id']);
        $existingEmployee = Employee::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingGovernment = Government::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingLicense = License::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingEducation = Education::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingSalary = Salary::query()->where('user_id', (int) $attrs['user_id'])->first();
        $oldPosition = trim((string) ($existingEmployee?->position ?? ''));
        $oldClassification = trim((string) ($existingEmployee?->classification ?? ''));
        $oldDepartment = trim((string) ($existingEmployee?->department ?? $user->department ?? ''));
        $oldSalary = trim((string) ($existingSalary?->salary ?? ''));
        $hasAllRequired = function (array $payload, array $requiredKeys): bool {
            foreach ($requiredKeys as $key) {
                if (!filled($payload[$key] ?? null)) {
                    return false;
                }
            }
            return true;
        };

        $userPayload = [
            'first_name' => $attrs['first'],
            'middle_name' => $attrs['middle'] ?? null,
            'last_name' => $attrs['last'],
        ];

        if (!empty($attrs['email'])) {
            $userPayload['email'] = $attrs['email'];
        }

        $user->update($userPayload);

        $addressParts = array_filter([
            $attrs['barangay'] ?? null,
            $attrs['municipality'] ?? null,
            $attrs['province'] ?? null,
        ], function ($value) {
            return filled($value);
        });

        $employeePayload = [
            'employee_id' => $attrs['employee_id'] ?? null,
            'account_number' => $attrs['account_number'] ?? null,
            'sex' => $attrs['gender'] ?? null,
            'contact_number' => $attrs['contact_number'] ?? null,
            'birthday' => $attrs['birthday'] ?? null,
            'position' => $attrs['position'] ?? null,
            'department' => $attrs['department'] ?? null,
            'classification' => $attrs['classification'] ?? ($existingEmployee?->classification ?? null),
            'address' => count($addressParts) ? implode(', ', $addressParts) : null,
            'emergency_contact_name' => $attrs['emergency_contact_name'] ?? null,
            'emergency_contact_relationship' => $attrs['emergency_contact_relationship'] ?? null,
            'emergency_contact_number' => $attrs['emergency_contact_number'] ?? null,
        ];

        if (Schema::hasColumn('employees', 'job_type')) {
            $employeePayload['job_type'] = $this->resolveJobTypeFromOpenPositionForUser($attrs['user_id'])
                ?? (array_key_exists('job_type', $attrs)
                    ? $this->normalizeEmployeeJobType($attrs['job_type'])
                    : null);
        }

        Employee::updateOrCreate(
            ['user_id' => $attrs['user_id']],
            $employeePayload
        );

        $this->recordCareerProgressionIfChanged(
            (int) $attrs['user_id'],
            $oldPosition,
            trim((string) ($employeePayload['position'] ?? '')),
            $oldClassification,
            trim((string) ($employeePayload['classification'] ?? '')),
            'Updated from general profile',
            $oldDepartment,
            trim((string) ($employeePayload['department'] ?? '')),
            $oldSalary,
            trim((string) ($existingSalary?->salary ?? ''))
        );

        $profileApplicant = Applicant::query()
            ->where('user_id', (int) $attrs['user_id'])
            ->orderByDesc('id')
            ->first();

        if ($profileApplicant) {
            $newPositionForHistory = trim((string) ($employeePayload['position'] ?? ''));
            $mergedRelevantExperiencePosition = $this->buildRelevantExperiencePositions(
                (string) ($profileApplicant->work_position ?? ''),
                $oldPosition,
                $newPositionForHistory
            );

            if ($mergedRelevantExperiencePosition !== null) {
                $profileApplicant->update([
                    'work_position' => $mergedRelevantExperiencePosition,
                ]);
            }
        }

        $existingGovernment = Government::query()->where('user_id', (int) $attrs['user_id'])->first();
        $governmentPayload = [
            'SSS' => trim((string) ((array_key_exists('SSS', $attrs) ? $attrs['SSS'] : ($existingGovernment?->SSS ?? '')) ?? '')),
            'TIN' => trim((string) ((array_key_exists('TIN', $attrs) ? $attrs['TIN'] : ($existingGovernment?->TIN ?? '')) ?? '')),
            'PhilHealth' => trim((string) ((array_key_exists('PhilHealth', $attrs) ? $attrs['PhilHealth'] : ($existingGovernment?->PhilHealth ?? '')) ?? '')),
            'MID' => trim((string) ((array_key_exists('MID', $attrs) ? $attrs['MID'] : ($existingGovernment?->MID ?? '')) ?? '')),
            'RTN' => trim((string) ((array_key_exists('RTN', $attrs) ? $attrs['RTN'] : ($existingGovernment?->RTN ?? '')) ?? '')),
        ];
        $hasAnyGovernmentData = collect($governmentPayload)->contains(fn ($value) => $value !== '');
        if ($existingGovernment || $hasAnyGovernmentData) {
            Government::updateOrCreate(
                ['user_id' => $attrs['user_id']],
                $governmentPayload
            );
        }

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function update_leave_request_status($id, Request $request)
    {
        $attrs = $request->validate([
            'status' => 'required|string|in:Approved,Rejected',
            'leave_type' => 'nullable|string|max:50',
            'month' => 'nullable|string',
            'redirect_back' => 'nullable|boolean',
        ]);

        $leaveApplication = LeaveApplication::findOrFail($id);
        $previousStatus = strtolower(trim((string) ($leaveApplication->status ?? '')));
        $newStatus = trim((string) $attrs['status']);

        DB::transaction(function () use ($leaveApplication, $newStatus, $previousStatus, $attrs) {
            $updates = ['status' => $newStatus];
            if (!empty($attrs['leave_type'])) {
                $updates['leave_type'] = trim((string) $attrs['leave_type']);
            }
            $leaveApplication->update($updates);

            if (!empty($leaveApplication->user_id)) {
                $resolvedAccountStatus = app(EmployeeAccountStatusManager::class)
                    ->syncUserAccountStatus((int) $leaveApplication->user_id);
                User::query()
                    ->where('id', (int) $leaveApplication->user_id)
                    ->update(['account_status' => $resolvedAccountStatus]);
            }
        });

        $month = trim((string) ($attrs['month'] ?? ''));
        $query = [];
        if ($month !== '') {
            $query['month'] = $month;
        }

        if ((bool) ($attrs['redirect_back'] ?? false)) {
            if ($request->expectsJson()) {
                return response()->json(array_merge([
                    'message' => 'Leave request status updated.',
                    'id' => (int) $leaveApplication->id,
                    'status' => $newStatus,
                ], $this->homeLeaveQueuePayload()));
            }

            return redirect()->back()->with('success', 'Leave request status updated.');
        }

        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'message' => 'Leave request status updated.',
                'id' => (int) $leaveApplication->id,
                'status' => $newStatus,
            ], $this->homeLeaveQueuePayload()));
        }

        return redirect()->route('admin.adminLeaveManagement', $query)
            ->with('success', 'Leave request status updated.');
    }

    private function homeLeaveQueuePayload(): array
    {
        $pendingQuery = $this->pendingLeaveRequestQuery();

        $pendingRequests = (clone $pendingQuery)
            ->orderByDesc('created_at')
            ->take(3)
            ->get()
            ->map(fn (LeaveApplication $request) => $this->formatHomeLeaveRequest($request))
            ->values()
            ->all();

        return [
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_requests' => $pendingRequests,
        ];
    }

    private function pendingLeaveRequestQuery()
    {
        return LeaveApplication::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw("TRIM(status) = ''")
                    ->orWhereRaw("LOWER(TRIM(status)) = ?", ['pending']);
            });
    }

    private function formatHomeLeaveRequest(LeaveApplication $request): array
    {
        $requestName = trim((string) ($request->employee_name ?? ''));
        if ($requestName === '') {
            $requestName = 'Unknown Employee';
        }

        $initials = '';
        foreach (array_slice(preg_split('/\s+/', $requestName) ?: [], 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        $startDate = $request->filing_date
            ? Carbon::parse($request->filing_date)->startOfDay()
            : Carbon::parse($request->created_at)->startOfDay();

        $days = (float) ($request->number_of_working_days ?? 0);
        if ($days <= 0) {
            $days = max(
                (float) ($request->days_with_pay ?? 0),
                (float) ($request->applied_total ?? 0)
            );
        }

        $endDate = $startDate->copy()->addDays(max((int) ceil($days), 1) - 1);

        return [
            'id' => (int) $request->id,
            'employee_name' => $requestName,
            'initials' => $initials !== '' ? $initials : 'NA',
            'leave_type' => (string) ($request->leave_type ?: 'Leave Request'),
            'date_label' => $startDate->isSameDay($endDate)
                ? $startDate->format('M d, Y')
                : $startDate->format('M d').' - '.$endDate->format('M d, Y'),
            'action_url' => route('admin.updateLeaveRequestStatus', $request->id),
        ];
    }

    public function store_resignation(Request $request)
    {
        $attrs = $request->validate([
            'employee_user_id' => 'required|exists:users,id',
            'submitted_at' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:submitted_at',
            'reason' => 'nullable|string|max:4000',
        ]);

        $employeeUser = User::query()
            ->with('employee')
            ->findOrFail((int) $attrs['employee_user_id']);

        if (strcasecmp((string) ($employeeUser->role ?? ''), 'Employee') !== 0) {
            return redirect()->back()->with('error', 'Selected account is not an employee.');
        }

        $employeeName = trim(implode(' ', array_filter([
            trim((string) ($employeeUser->first_name ?? '')),
            trim((string) ($employeeUser->middle_name ?? '')),
            trim((string) ($employeeUser->last_name ?? '')),
        ])));

        Resignation::create([
            'user_id' => $employeeUser->id,
            'employee_id' => (string) ($employeeUser->employee?->employee_id ?? ''),
            'employee_name' => $employeeName !== '' ? $employeeName : (string) ($employeeUser->email ?? 'Unknown Employee'),
            'department' => (string) ($employeeUser->employee?->department ?? ''),
            'position' => (string) ($employeeUser->employee?->position ?? ''),
            'submitted_at' => $attrs['submitted_at'],
            'effective_date' => $attrs['effective_date'],
            'reason' => trim((string) ($attrs['reason'] ?? '')),
            'status' => 'Pending',
        ]);

        return redirect()->route('admin.adminResignations')
            ->with('success', 'Resignation record saved.');
    }

    public function update_resignation_status($id, Request $request)
    {
        $attrs = $request->validate([
            'status' => 'required|string|in:Pending,Approved,Rejected,Completed,Cancelled',
            'admin_note' => 'nullable|string|max:4000',
        ]);

        $resignation = Resignation::findOrFail($id);
        $status = trim((string) $attrs['status']);

        $updatePayload = [
            'status' => $status,
            'admin_note' => trim((string) ($attrs['admin_note'] ?? '')),
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ];

        $employeeUser = null;
        if (!empty($resignation->user_id)) {
            $employeeUser = User::query()
                ->with('employee')
                ->find($resignation->user_id);
        } elseif (!empty($resignation->employee_id)) {
            $mappedUserId = Employee::query()
                ->where('employee_id', trim((string) $resignation->employee_id))
                ->value('user_id');

            if (!empty($mappedUserId)) {
                $employeeUser = User::query()
                    ->with('employee')
                    ->find((int) $mappedUserId);

                if ($employeeUser) {
                    $updatePayload['user_id'] = (int) $employeeUser->id;
                }
            }
        }

        // On approval, store a fresh snapshot of employee identity fields
        // in the resignation record for audit/history purposes.
        if (strcasecmp($status, 'Approved') === 0 && $employeeUser) {
            $employeeName = trim(implode(' ', array_filter([
                trim((string) ($employeeUser->first_name ?? '')),
                trim((string) ($employeeUser->middle_name ?? '')),
                trim((string) ($employeeUser->last_name ?? '')),
            ])));

            $updatePayload['employee_id'] = (string) ($employeeUser->employee?->employee_id ?? $resignation->employee_id ?? '');
            $updatePayload['employee_name'] = $employeeName !== ''
                ? $employeeName
                : (string) ($employeeUser->email ?? $resignation->employee_name ?? 'Unknown Employee');
            $updatePayload['department'] = (string) ($employeeUser->employee?->department ?? $resignation->department ?? '');
            $updatePayload['position'] = (string) ($employeeUser->employee?->position ?? $resignation->position ?? '');
        }

        $resignation->update($updatePayload);

        // Keep employee account status dynamic based on resignation/leave outcomes.
        if ($employeeUser) {
            $employeeUser->update([
                'account_status' => app(EmployeeAccountStatusManager::class)
                    ->syncUserAccountStatus((int) $employeeUser->id),
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $excludeCancelled = function ($query) {
                return $query->whereRaw("LOWER(TRIM(COALESCE(status, ''))) <> ?", ['cancelled']);
            };

            return response()->json([
                'message' => 'Resignation status updated.',
                'id' => (int) $resignation->id,
                'status' => $status,
                'statusCounts' => [
                    'Pending' => (int) $excludeCancelled(Resignation::query())->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])->count(),
                    'Approved' => (int) $excludeCancelled(Resignation::query())->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])->count(),
                    'Rejected' => (int) $excludeCancelled(Resignation::query())->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['rejected'])->count(),
                    'Cancelled' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['cancelled'])->count(),
                ],
            ]);
        }

        return redirect()->route('admin.adminResignations')
            ->with('success', 'Resignation status updated.');
    }

    private function resolveAccountStatusByRecords(int $userId): string
    {
        return app(EmployeeAccountStatusManager::class)->resolveAccountStatus($userId);
    }

    private function reactivateResignedEmployeeAccountForApplicant(Applicant $applicant): void
    {
        $normalizedEmail = strtolower(trim((string) ($applicant->email ?? '')));
        if ($normalizedEmail === '') {
            return;
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->orderByDesc('id')
            ->first();

        if (!$existingUser) {
            return;
        }

        $hasApprovedResignation = Resignation::query()
            ->where('user_id', (int) $existingUser->id)
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN (?, ?)", ['approved', 'completed'])
            ->exists();

        if (!$hasApprovedResignation) {
            return;
        }

        $payload = [
            'role' => 'Employee',
            'status' => 'Approved',
            'account_status' => 'Active',
            'email' => $applicant->email,
        ];

        if (trim((string) ($applicant->first_name ?? '')) !== '') {
            $payload['first_name'] = $applicant->first_name;
        }

        if (trim((string) ($applicant->last_name ?? '')) !== '') {
            $payload['last_name'] = $applicant->last_name;
        }

        $existingUser->update($payload);

        if ((int) ($applicant->user_id ?? 0) !== (int) $existingUser->id) {
            $applicant->forceFill([
                'user_id' => (int) $existingUser->id,
            ])->save();
        }
    }

    private function isLeaveApplicationActiveOnDate(LeaveApplication $leaveApplication, ?Carbon $targetDate = null): bool
    {
        return app(EmployeeAccountStatusManager::class)
            ->isLeaveApplicationActiveOnDate($leaveApplication, $targetDate);
    }

    private function resolveLeaveApplicationDateRange(LeaveApplication $leaveApplication): array
    {
        return app(EmployeeAccountStatusManager::class)
            ->resolveLeaveApplicationDateRange($leaveApplication);
    }

    public function update_bio(Request $request){
        Log::info($request);
        $attrs = $request->validate([
            //User Model
            'user_id' => 'required|exists:users,id',
            'tab_session' => 'nullable|string|max:120',
            'first' => 'required|string|max:255',
            'middle' => 'nullable|string|max:255',
            'last' => 'required|string|max:255',

            //Employee Model
            'employee_id' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'civil_status' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'employment_date' => 'nullable|date',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'classification' => 'nullable|string|max:255',
            'job_type' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:255',

            //Government Model
            'SSS' => 'nullable|string|max:255',
            'TIN' => 'nullable|string|max:255',
            'PhilHealth' => 'nullable|string|max:255',
            'MID' => 'nullable|string|max:255',
            'RTN' => 'nullable|string|max:255',

            //License Model
            'license' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'registration_date' => 'nullable|date',
            'valid_until' => 'nullable|date',

            //Education Model
            'elementary_school_name' => 'nullable|string|max:255',
            'elementary_year_finished' => 'nullable|string|max:50',
            'secondary_school_name' => 'nullable|string|max:255',
            'secondary_year_finished' => 'nullable|string|max:50',
            'vocational_trade_school_name' => 'nullable|string|max:255',
            'vocational_trade_year_finished' => 'nullable|string|max:50',
            'bachelor' => 'nullable|string|max:255',
            'master' => 'nullable|string|max:255',
            'doctorate' => 'nullable|string|max:255',
            'bachelor_school_name' => 'nullable|string|max:255',
            'bachelor_year_finished' => 'nullable|string|max:50',
            'master_school_name' => 'nullable|string|max:255',
            'master_year_finished' => 'nullable|string|max:50',
            'doctoral_school_name' => 'nullable|string|max:255',
            'doctoral_year_finished' => 'nullable|string|max:50',
            'degree_inputs' => 'nullable|array',
            'degree_inputs.bachelor' => 'nullable|array',
            'degree_inputs.bachelor.*.degree_name' => 'nullable|string|max:255',
            'degree_inputs.bachelor.*.school_name' => 'nullable|string|max:255',
            'degree_inputs.bachelor.*.year_finished' => 'nullable|string|max:50',
            'degree_inputs.master' => 'nullable|array',
            'degree_inputs.master.*.degree_name' => 'nullable|string|max:255',
            'degree_inputs.master.*.school_name' => 'nullable|string|max:255',
            'degree_inputs.master.*.year_finished' => 'nullable|string|max:50',
            'degree_inputs.doctorate' => 'nullable|array',
            'degree_inputs.doctorate.*.degree_name' => 'nullable|string|max:255',
            'degree_inputs.doctorate.*.school_name' => 'nullable|string|max:255',
            'degree_inputs.doctorate.*.year_finished' => 'nullable|string|max:50',

            //Salary Model
            'salary' => 'nullable|string|max:255',
            'rate_per_hour' => 'nullable|string|max:255',
            'cola' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'remove_profile_picture' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($attrs['user_id']);
        $existingEmployee = Employee::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingGovernment = Government::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingLicense = License::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingEducation = Education::query()->where('user_id', (int) $attrs['user_id'])->first();
        $existingSalary = Salary::query()->where('user_id', (int) $attrs['user_id'])->first();
        $oldPosition = trim((string) ($existingEmployee?->position ?? ''));
        $oldClassification = trim((string) ($existingEmployee?->classification ?? ''));
        $oldDepartment = trim((string) ($existingEmployee?->department ?? $user->department ?? ''));
        $oldSalary = trim((string) ($existingSalary?->salary ?? ''));
        $hasAllRequired = function (array $payload, array $requiredKeys): bool {
            foreach ($requiredKeys as $key) {
                if (!filled($payload[$key] ?? null)) {
                    return false;
                }
            }
            return true;
        };

        $user->update([
            //'' => $attrs[''],
            'first_name' => $attrs['first'],
            'middle_name' => $attrs['middle'] ?? null,
            'last_name' => $attrs['last'],
        ]);

        $employeePayload = [
            'user_id' => $attrs['user_id'],
            'employee_id' => $attrs['employee_id'] ?? ($existingEmployee?->employee_id ?? null),
            'employement_date' => $attrs['employment_date'] ?? ($existingEmployee?->employement_date ?? null),
            'birthday' => $attrs['birthday'] ?? ($existingEmployee?->birthday ?? null),
            'account_number' => $attrs['account_number'] ?? ($existingEmployee?->account_number ?? null),
            'sex' => $attrs['gender'] ?? ($existingEmployee?->sex ?? null),
            'civil_status' => $attrs['civil_status'] ?? ($existingEmployee?->civil_status ?? null),
            'contact_number' => $attrs['contact_number'] ?? ($existingEmployee?->contact_number ?? null),
            'address' => $attrs['address'] ?? ($existingEmployee?->address ?? null),
            'department' => $attrs['department'] ?? ($existingEmployee?->department ?? null),
            'position' => $attrs['position'] ?? ($existingEmployee?->position ?? null),
            'classification' => $attrs['classification'] ?? ($existingEmployee?->classification ?? null),
            ...(Schema::hasColumn('employees', 'job_type')
                ? ['job_type' => $this->resolveJobTypeFromOpenPositionForUser($attrs['user_id'])
                    ?? $this->normalizeEmployeeJobType(($attrs['job_type'] ?? null) ?: ($attrs['classification'] ?? ($existingEmployee?->classification ?? null)))
                    ?? ($existingEmployee?->job_type ?? null)]
                : []),
            'emergency_contact_name' => $attrs['emergency_contact_name'] ?? ($existingEmployee?->emergency_contact_name ?? null),
            'emergency_contact_relationship' => $attrs['emergency_contact_relationship'] ?? ($existingEmployee?->emergency_contact_relationship ?? null),
            'emergency_contact_number' => $attrs['emergency_contact_number'] ?? ($existingEmployee?->emergency_contact_number ?? null),
        ];

        Employee::updateOrCreate(
            ['user_id' => $attrs['user_id']],
            $employeePayload
        );

        $this->recordCareerProgressionIfChanged(
            (int) $attrs['user_id'],
            $oldPosition,
            trim((string) ($employeePayload['position'] ?? ($existingEmployee?->position ?? ''))),
            $oldClassification,
            trim((string) ($employeePayload['classification'] ?? ($existingEmployee?->classification ?? ''))),
            'Updated from profile edit',
            $oldDepartment,
            trim((string) ($employeePayload['department'] ?? ($existingEmployee?->department ?? ''))),
            $oldSalary,
            trim((string) ($attrs['salary'] ?? ($existingSalary?->salary ?? '')))
        );

        $governmentPayload = [
            'SSS' => trim((string) ((array_key_exists('SSS', $attrs) ? $attrs['SSS'] : ($existingGovernment?->SSS ?? '')) ?? '')),
            'TIN' => trim((string) ((array_key_exists('TIN', $attrs) ? $attrs['TIN'] : ($existingGovernment?->TIN ?? '')) ?? '')),
            'PhilHealth' => trim((string) ((array_key_exists('PhilHealth', $attrs) ? $attrs['PhilHealth'] : ($existingGovernment?->PhilHealth ?? '')) ?? '')),
            'RTN' => trim((string) ((array_key_exists('RTN', $attrs) ? $attrs['RTN'] : ($existingGovernment?->RTN ?? '')) ?? '')),
            'MID' => trim((string) ((array_key_exists('MID', $attrs) ? $attrs['MID'] : ($existingGovernment?->MID ?? '')) ?? '')),
        ];
        $hasAnyGovernmentData = collect($governmentPayload)->contains(fn ($value) => $value !== '');
        if ($existingGovernment || $hasAnyGovernmentData) {
            Government::updateOrCreate(
                ['user_id' => $attrs['user_id']],
                $governmentPayload
            );
        }

        $licensePayload = [
            'license' => $attrs['license'] ?? ($existingLicense?->license ?? null),
            'registration_number' => $attrs['registration_number'] ?? ($existingLicense?->registration_number ?? null),
            'registration_date' => $attrs['registration_date'] ?? ($existingLicense?->registration_date ?? null),
            'valid_until' => $attrs['valid_until'] ?? ($existingLicense?->valid_until ?? null),
        ];
        if ($existingLicense || $hasAllRequired($licensePayload, ['license', 'registration_number', 'registration_date', 'valid_until'])) {
            License::updateOrCreate(
                ['user_id' => $attrs['user_id']],
                $licensePayload
            );
        }

        $educationPayload = [
            'elementary_school_name' => $attrs['elementary_school_name'] ?? ($existingEducation?->elementary_school_name ?? null),
            'elementary_year_finished' => $attrs['elementary_year_finished'] ?? ($existingEducation?->elementary_year_finished ?? null),
            'secondary_school_name' => $attrs['secondary_school_name'] ?? ($existingEducation?->secondary_school_name ?? null),
            'secondary_year_finished' => $attrs['secondary_year_finished'] ?? ($existingEducation?->secondary_year_finished ?? null),
            'vocational_trade_school_name' => $attrs['vocational_trade_school_name'] ?? ($existingEducation?->vocational_trade_school_name ?? null),
            'vocational_trade_year_finished' => $attrs['vocational_trade_year_finished'] ?? ($existingEducation?->vocational_trade_year_finished ?? null),
            'bachelor' => $attrs['bachelor'] ?? ($existingEducation?->bachelor ?? null),
            'master' => $attrs['master'] ?? ($existingEducation?->master ?? null),
            'doctorate' => $attrs['doctorate'] ?? ($existingEducation?->doctorate ?? null),
        ];
        $hasAnyEducationData = collect($educationPayload)->contains(fn ($value) => filled($value));
        if ($existingEducation || $hasAnyEducationData) {
            Education::updateOrCreate(
                ['user_id' => $attrs['user_id']],
                [
                    ...$educationPayload,
                    'bachelor' => $educationPayload['bachelor'] ?? '',
                    'master' => $educationPayload['master'] ?? '',
                    'doctorate' => $educationPayload['doctorate'] ?? '',
                ]
            );
        }

        $applicant = Applicant::query()
            ->where('user_id', (int) $attrs['user_id'])
            ->orderByDesc('id')
            ->first();

        if ($applicant) {
            $newPositionForHistory = trim((string) ($employeePayload['position'] ?? ($existingEmployee?->position ?? '')));
            $mergedRelevantExperiencePosition = $this->buildRelevantExperiencePositions(
                (string) ($applicant->work_position ?? ''),
                $oldPosition,
                $newPositionForHistory
            );

            $applicant->update([
                'bachelor_degree' => $attrs['bachelor'] ?? null,
                'bachelor_school_name' => $attrs['bachelor_school_name'] ?? null,
                'bachelor_year_finished' => $attrs['bachelor_year_finished'] ?? null,
                'master_degree' => $attrs['master'] ?? null,
                'master_school_name' => $attrs['master_school_name'] ?? null,
                'master_year_finished' => $attrs['master_year_finished'] ?? null,
                'doctoral_degree' => $attrs['doctorate'] ?? null,
                'doctoral_school_name' => $attrs['doctoral_school_name'] ?? null,
                'doctoral_year_finished' => $attrs['doctoral_year_finished'] ?? null,
                'work_position' => $mergedRelevantExperiencePosition ?? ($applicant->work_position ?? null),
            ]);

            $degreeInputs = $attrs['degree_inputs'] ?? [];
            $normalizeRows = function (string $level, ?array $fallback = null) use ($degreeInputs) {
                $rows = collect($degreeInputs[$level] ?? [])
                    ->map(function ($row) use ($level) {
                        return [
                            'degree_level' => $level,
                            'degree_name' => trim((string) ($row['degree_name'] ?? '')),
                            'school_name' => trim((string) ($row['school_name'] ?? '')),
                            'year_finished' => trim((string) ($row['year_finished'] ?? '')),
                        ];
                    })
                    ->filter(function ($row) {
                        return $row['degree_name'] !== '' || $row['school_name'] !== '' || $row['year_finished'] !== '';
                    })
                    ->values();

                if ($rows->isNotEmpty()) {
                    return $rows;
                }

                $fallbackDegree = trim((string) ($fallback['degree_name'] ?? ''));
                $fallbackSchool = trim((string) ($fallback['school_name'] ?? ''));
                $fallbackYear = trim((string) ($fallback['year_finished'] ?? ''));
                if ($fallbackDegree === '' && $fallbackSchool === '' && $fallbackYear === '') {
                    return collect();
                }

                return collect([[
                    'degree_level' => $level,
                    'degree_name' => $fallbackDegree,
                    'school_name' => $fallbackSchool,
                    'year_finished' => $fallbackYear,
                ]]);
            };

            $allDegreeRows = collect()
                ->concat($normalizeRows('elementary', [
                    'degree_name' => 'Elementary',
                    'school_name' => $attrs['elementary_school_name'] ?? null,
                    'year_finished' => $attrs['elementary_year_finished'] ?? null,
                ]))
                ->concat($normalizeRows('secondary', [
                    'degree_name' => 'Secondary',
                    'school_name' => $attrs['secondary_school_name'] ?? null,
                    'year_finished' => $attrs['secondary_year_finished'] ?? null,
                ]))
                ->concat($normalizeRows('vocational_trade', [
                    'degree_name' => 'Vocational / Trade Course',
                    'school_name' => $attrs['vocational_trade_school_name'] ?? null,
                    'year_finished' => $attrs['vocational_trade_year_finished'] ?? null,
                ]))
                ->concat($normalizeRows('bachelor', [
                    'degree_name' => $attrs['bachelor'] ?? null,
                    'school_name' => $attrs['bachelor_school_name'] ?? null,
                    'year_finished' => $attrs['bachelor_year_finished'] ?? null,
                ]))
                ->concat($normalizeRows('master', [
                    'degree_name' => $attrs['master'] ?? null,
                    'school_name' => $attrs['master_school_name'] ?? null,
                    'year_finished' => $attrs['master_year_finished'] ?? null,
                ]))
                ->concat($normalizeRows('doctorate', [
                    'degree_name' => $attrs['doctorate'] ?? null,
                    'school_name' => $attrs['doctoral_school_name'] ?? null,
                    'year_finished' => $attrs['doctoral_year_finished'] ?? null,
                ]))
                ->values();

            ApplicantDegree::query()
                ->where('applicant_id', (int) $applicant->id)
                ->delete();

            $allDegreeRows
                ->groupBy('degree_level')
                ->each(function ($rows, $level) use ($applicant) {
                    foreach ($rows->values() as $index => $row) {
                        ApplicantDegree::create([
                            'applicant_id' => (int) $applicant->id,
                            'degree_level' => (string) $level,
                            'degree_name' => $row['degree_name'],
                            'school_name' => $row['school_name'] !== '' ? $row['school_name'] : null,
                            'year_finished' => $row['year_finished'] !== '' ? $row['year_finished'] : null,
                            'sort_order' => $index,
                        ]);
                    }
                });
        }

        $removeProfilePicture = (bool) ($attrs['remove_profile_picture'] ?? false);
        if ($applicant && ($removeProfilePicture || $request->hasFile('profile_picture'))) {
                $existingProfilePhotos = ApplicantDocument::query()
                    ->where('applicant_id', $applicant->id)
                    ->where('type', 'PROFILE_PHOTO')
                    ->get();

                foreach ($existingProfilePhotos as $existingProfilePhoto) {
                    $relativePath = ltrim((string) ($existingProfilePhoto->filepath ?? ''), '/');
                    if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
                        Storage::disk('public')->delete($relativePath);
                    }
                    $existingProfilePhoto->delete();
                }

                if ($request->hasFile('profile_picture')) {
                    $file = $request->file('profile_picture');
                    if ($file && $file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $mimeType = $file->getMimeType();
                        $size = $file->getSize();
                        $fileName = time().'_'.$originalName;
                        $filePath = $file->storeAs('uploads', $fileName, 'public');

                        ApplicantDocument::create([
                            'applicant_id' => $applicant->id,
                            'type' => 'PROFILE_PHOTO',
                            'filename' => $originalName,
                            'filepath' => $filePath,
                            'mime_type' => $mimeType,
                            'size' => $size,
                        ]);
                    }
                }
        }

        $salaryPayload = [
            'salary' => $attrs['salary'] ?? ($existingSalary?->salary ?? null),
            'rate_per_hour' => $attrs['rate_per_hour'] ?? ($existingSalary?->rate_per_hour ?? null),
            'cola' => $attrs['cola'] ?? ($existingSalary?->cola ?? null),
        ];
        if ($existingSalary || $hasAllRequired($salaryPayload, ['salary', 'rate_per_hour', 'cola'])) {
            Salary::updateOrCreate(
                ['user_id' => $attrs['user_id']],
                $salaryPayload
            );
        }

        return redirect()->route('admin.adminEmployee', array_filter([
            'user_id' => (int) $attrs['user_id'],
            'tab' => 'biometric',
            'tab_session' => $attrs['tab_session'] ?? null,
        ]))->with('success', 'Save Successfully');
    }

    public function mark_employee_permanent(Request $request, $id)
    {
        $userId = (int) $id;
        $employee = Employee::query()->where('user_id', $userId)->firstOrFail();

        $oldClassification = trim((string) ($employee->classification ?? ''));
        $oldPosition = trim((string) ($employee->position ?? ''));
        $oldDepartment = trim((string) ($employee->department ?? ''));
        $existingSalary = Salary::query()->where('user_id', $userId)->first();
        $currentSalary = trim((string) ($existingSalary?->salary ?? ''));
        $redirectParams = array_filter([
            'user_id' => $userId,
            'tab' => $request->input('tab') ?: 'overview',
            'tab_session' => $request->input('tab_session'),
        ]);

        if ($this->isPermanentEmployeeClassification($oldClassification)) {
            return redirect()->route('admin.adminEmployee', $redirectParams)
                ->with('success', 'Employee is already marked as Permanent.');
        }

        $regularizationDate = $this->resolveEmployeeRegularizationDateForUser($userId);
        if (!$regularizationDate || now()->startOfDay()->lt($regularizationDate->copy()->startOfDay())) {
            return redirect()->route('admin.adminEmployee', $redirectParams)
                ->withErrors(['permanent' => 'This employee is not yet eligible to be marked as Permanent.']);
        }

        $employee->update([
            'classification' => 'Permanent',
        ]);

        $this->recordCareerProgressionIfChanged(
            $userId,
            $oldPosition,
            trim((string) ($employee->position ?? '')),
            $oldClassification,
            'Permanent',
            'Marked as Permanent by admin',
            $oldDepartment,
            trim((string) ($employee->department ?? '')),
            $currentSalary,
            $currentSalary
        );

        return redirect()->route('admin.adminEmployee', $redirectParams)
            ->with('success', 'Employee marked as Permanent successfully.');
    }

    private function normalizeEmployeeJobType($value): ?string
    {
        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['teaching', 't'], true)) {
            return 'Teaching';
        }

        if (preg_match('/^t\s*\//', $normalized) === 1) {
            return 'Teaching';
        }

        if (in_array($normalized, [
            'non-teaching',
            'non teaching',
            'nonteaching',
            'nt',
            'full-time',
            'full time',
            'fulltime',
            'part-time',
            'part time',
            'parttime',
        ], true)) {
            return 'Non-Teaching';
        }

        if (preg_match('/^nt(?:\s*\/|$)/', $normalized) === 1) {
            return 'Non-Teaching';
        }

        return 'Non-Teaching';
    }

    private function isPermanentEmployeeClassification(?string $classification): bool
    {
        $normalized = strtolower(trim((string) $classification));

        return $normalized !== ''
            && (str_contains($normalized, 'permanent') || str_contains($normalized, 'regular'));
    }

    private function resolveEmployeeRegularizationDateForUser(int $userId): ?Carbon
    {
        if ($userId <= 0) {
            return null;
        }

        $user = User::query()
            ->with(['employee', 'applicant.position:id,job_type'])
            ->find($userId);
        $employee = $user?->employee;

        if (!$employee) {
            return null;
        }

        $rawJoinDate = $employee->employement_date ?? $user?->applicant?->date_hired;
        if (empty($rawJoinDate)) {
            return null;
        }

        $jobTypeRaw = $employee->job_type ?: $user?->applicant?->position?->job_type;
        $jobType = strtolower(trim((string) $jobTypeRaw));
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

    private function recordCareerProgressionIfChanged(
        int $userId,
        string $oldPosition,
        string $newPosition,
        string $oldClassification = '',
        string $newClassification = '',
        string $note = '',
        string $oldDepartment = '',
        string $newDepartment = '',
        string $oldSalary = '',
        string $newSalary = ''
    ): void {
        if ($userId <= 0) {
            return;
        }

        $oldNormalized = strtolower(trim($oldPosition));
        $newNormalized = strtolower(trim($newPosition));
        $oldClassNormalized = strtolower(trim($oldClassification));
        $newClassNormalized = strtolower(trim($newClassification));
        $oldDepartmentNormalized = strtolower(trim($oldDepartment));
        $newDepartmentNormalized = strtolower(trim($newDepartment));
        $oldSalaryNormalized = strtolower(trim($oldSalary));
        $newSalaryNormalized = strtolower(trim($newSalary));

        $positionChanged = $newNormalized !== '' && $oldNormalized !== $newNormalized;
        $classificationChanged = $newClassNormalized !== '' && $oldClassNormalized !== $newClassNormalized;
        $departmentChanged = $newDepartmentNormalized !== '' && $oldDepartmentNormalized !== $newDepartmentNormalized;
        $salaryChanged = $newSalaryNormalized !== '' && $oldSalaryNormalized !== $newSalaryNormalized;

        if (!$positionChanged && !$classificationChanged && !$departmentChanged && !$salaryChanged) {
            return;
        }

        $finalNewPosition = trim($newPosition);
        if ($finalNewPosition === '') {
            $finalNewPosition = trim($oldPosition);
        }
        if ($finalNewPosition === '') {
            $finalNewPosition = 'Position Unchanged';
        }

        EmployeePositionHistory::create([
            'user_id' => $userId,
            'old_position' => trim($oldPosition) !== '' ? trim($oldPosition) : null,
            'new_position' => $finalNewPosition,
            'old_classification' => trim($oldClassification) !== '' ? trim($oldClassification) : null,
            'new_classification' => trim($newClassification) !== '' ? trim($newClassification) : null,
            'old_department' => trim($oldDepartment) !== '' ? trim($oldDepartment) : null,
            'new_department' => trim($newDepartment) !== '' ? trim($newDepartment) : null,
            'old_salary' => trim($oldSalary) !== '' ? trim($oldSalary) : null,
            'new_salary' => trim($newSalary) !== '' ? trim($newSalary) : null,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'note' => trim($note) !== '' ? trim($note) : null,
        ]);
    }

    private function buildRelevantExperiencePositions(?string $existingWorkPosition, string $oldPosition, string $newPosition): ?string
    {
        $positions = $this->parseRelevantExperiencePositions($existingWorkPosition);
        $old = trim($oldPosition);
        $new = trim($newPosition);

        if ($old !== '' && strcasecmp($old, $new) !== 0) {
            $positions = $this->appendUniqueRelevantPosition($positions, $old);
        }
        if ($new !== '') {
            $positions = $this->appendUniqueRelevantPosition($positions, $new);
        }

        return empty($positions) ? null : implode(' | ', $positions);
    }

    private function parseRelevantExperiencePositions(?string $raw): array
    {
        $text = trim((string) ($raw ?? ''));
        if ($text === '') {
            return [];
        }

        return collect(preg_split('/\s*(?:\||\/|,|;|\r?\n)\s*/', $text) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    private function appendUniqueRelevantPosition(array $positions, string $candidate): array
    {
        $normalizedCandidate = strtolower(trim($candidate));
        if ($normalizedCandidate === '') {
            return $positions;
        }

        foreach ($positions as $existing) {
            if (strtolower(trim((string) $existing)) === $normalizedCandidate) {
                return $positions;
            }
        }

        $positions[] = trim($candidate);
        return $positions;
    }

    private function normalizeEmployeeId($value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        // Excel text-formatted IDs may include a leading apostrophe.
        $normalized = ltrim($normalized, "'");

        // Excel often exports numeric IDs as "123.0"; map these back to the base ID.
        if (preg_match('/^(\d+)\.0+$/', $normalized, $matches)) {
            return $matches[1];
        }

        return $normalized;
    }

    private function normalizeEmployeeIdForMatch(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = ltrim($normalized, "'");
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);
        if (!is_string($normalized) || $normalized === '') {
            return '';
        }

        if (preg_match('/^\d+$/', $normalized)) {
            $normalized = ltrim($normalized, '0');
            return $normalized !== '' ? $normalized : '0';
        }

        return $normalized;
    }

    private function clearMatchingRequiredDocumentMeta(int $applicantId, string $submittedDocumentName): void
    {
        if ($applicantId <= 0) {
            return;
        }

        $submittedNormalized = $this->normalizeDocumentRequirementLabel($submittedDocumentName);
        if ($submittedNormalized === '') {
            return;
        }

        $requiredPrefix = '__REQUIRED__::';
        $requiredMetaDocs = ApplicantDocument::query()
            ->where('applicant_id', $applicantId)
            ->where('type', 'like', $requiredPrefix.'%')
            ->get();

        foreach ($requiredMetaDocs as $metaDoc) {
            $requiredLabel = trim((string) substr((string) $metaDoc->type, strlen($requiredPrefix)));
            if ($this->normalizeDocumentRequirementLabel($requiredLabel) === $submittedNormalized) {
                $metaDoc->delete();
            }
        }
    }

    private function clearDocumentNoticeIfNoRequiredDocuments(int $applicantId): void
    {
        if ($applicantId <= 0) {
            return;
        }

        $requiredPrefix = '__REQUIRED__::';
        $hasRemainingRequirements = ApplicantDocument::query()
            ->where('applicant_id', $applicantId)
            ->where('type', 'like', $requiredPrefix.'%')
            ->exists();

        if ($hasRemainingRequirements) {
            return;
        }

        ApplicantDocument::query()
            ->where('applicant_id', $applicantId)
            ->where('type', '__NOTICE__')
            ->delete();
    }

    private function normalizeDocumentRequirementLabel(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return (string) $normalized;
    }


    //DELETE
    public function destroy_position($id){
        $delete = OpenPosition::findOrFail($id);

        $delete->delete();

        return redirect()->route('admin.adminPosition')->with('success','Successfully deleted Position');

    }

    public function restore_position($id){
        $position = OpenPosition::withTrashed()->findOrFail($id);

        if ($position->trashed()) {
            $position->restore();
        }

        return redirect()->route('admin.adminPosition')->with('success', 'Position reopened successfully.');
    }

    public function destroy_interview($id){
        $delete = Interviewer::where('applicant_id', $id)
            ->latest()
            ->first();

        if (!$delete) {
            return redirect()->back()->with('error', 'No scheduled interview found for this applicant.');
        }

        $delete->delete();

        Applicant::where('id', $id)
            ->whereIn('application_status', ['Initial Interview', 'Final Interview', 'Demo Teaching'])
            ->update(['application_status' => 'Under Review']);

        return redirect()->back()->with('success','Interview schedule cancelled successfully.');
    }

    public function destroy_employee($id){


        $open = User::findOrFail($id);

        $open->update([
            'status' => 'Not Approved',
        ]);

        return redirect()->back()->with('success','Employee not Approve');
    }



    public function update_activity_log_note(ActivityLog $activityLog, Request $request)
    {
        $attrs = $request->validate([
            'notes' => 'nullable|string|max:5000',
        ]);

        $activityLog->update([
            'notes' => trim((string) ($attrs['notes'] ?? '')),
        ]);

        return redirect()->back()->with('success', 'Log note saved.');
    }

    private function mailToAddress(?string $recipient): string
    {
        $override = trim((string) config('mail.to_override'));

        return $override !== '' ? $override : (string) $recipient;
    }

}
