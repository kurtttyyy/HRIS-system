<?php

namespace App\Http\Controllers;

use App\Models\AttendanceUpload;
use App\Models\AttendanceRecord;
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
    public function send_communication_message(Request $request)
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('conversation_messages')) {
            return redirect()->back()->withErrors(['body' => 'Communication tables are not ready yet. Please run the latest migration.']);
        }

        $attrs = $request->validate([
            'participant_user_id' => 'required|integer|exists:users,id',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'body' => 'required|string|max:4000',
            'tab_session' => 'nullable|string|max:120',
        ]);

        $authUser = Auth::user();
        if (!$authUser) {
            return redirect()->route('login_display', array_filter([
                'tab_session' => $request->input('tab_session'),
            ]));
        }

        if (!in_array(strtolower(trim((string) ($authUser->role ?? ''))), ['admin', 'administrator'], true)) {
            return redirect()->route('employee.employeeCommunication', array_filter([
                    'tab_session' => $request->input('tab_session'),
                ]))
                ->withErrors(['body' => 'You must be logged in as an admin account to send messages from the admin communication page.']);
        }

        $participant = User::query()->findOrFail((int) $attrs['participant_user_id']);
        if ((int) $participant->id === (int) $authUser->id) {
            return redirect()->back()->withErrors(['body' => 'You cannot message yourself.']);
        }

        if (strcasecmp(trim((string) ($participant->role ?? '')), 'employee') !== 0) {
            return redirect()->back()->withErrors(['body' => 'Admins can only start chats with employee users here.']);
        }

        $conversation = Conversation::findOrCreateBetweenUsers((int) $authUser->id, (int) $participant->id);
        $conversation->messages()->create([
            'sender_user_id' => (int) $authUser->id,
            'body' => trim((string) $attrs['body']),
        ]);
        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();

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

        if (!empty($hiddenDates)) {
            $holidayUploadNames = array_map(
                fn ($date) => "System Holiday Attendance {$date}",
                $hiddenDates
            );

            $holidayUploadIds = AttendanceUpload::query()
                ->whereIn('original_name', $holidayUploadNames)
                ->pluck('id');

            if ($holidayUploadIds->isNotEmpty()) {
                AttendanceRecord::query()
                    ->whereIn('attendance_upload_id', $holidayUploadIds)
                    ->delete();

                AttendanceUpload::query()
                    ->whereIn('id', $holidayUploadIds)
                    ->delete();
            }
        }

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
        ]);

        $store = Interviewer::create([
            'applicant_id' => $attrs['applicants_id'],
            'interview_type' => $attrs['interview_type'],
            'date' => $attrs['date'],
            'time' => $attrs['time'],
            'duration' => $attrs['duration'],
            'interviewers' => $attrs['interviewers'],
            'email_link' => $attrs['email_link'],
            'url' => $attrs['url'],
            'notes' => $attrs['notes'],
        ]);

        // === APPLICANT STATUS UPDATE #1 === Store Interview Method
        // Updates applicant status based on interview type (Initial Interview or Final Interview)
        Applicant::where('id', $attrs['applicants_id'])->update([
            'application_status' => $this->resolveApplicantStatusFromInterviewType($attrs['interview_type']),
        ]);

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

    public function store_attendance_excel(Request $request){
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('excel_file');

        if (!$file || !$file->isValid()) {
            return back()->withErrors(['excel_file' => 'Invalid file upload.']);
        }

        $originalName = $file->getClientOriginalName();
        $fileName = time().'_'.$originalName;
        $filePath = $file->storeAs('attendance_excels', $fileName, 'public');

        $attendanceUpload = AttendanceUpload::create([
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'status' => 'Uploaded',
            'processed_rows' => 0,
            'uploaded_at' => Carbon::now('Asia/Manila'),
        ]);

        return back()->with('success', 'Excel file uploaded successfully. Select the file and click Scan to process it.');
    }

    public function store_payslip_file(Request $request)
    {
        $request->validate([
            'payslip_file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $file = $request->file('payslip_file');
        if (!$file || !$file->isValid()) {
            return back()->withErrors(['payslip_file' => 'Invalid file upload.']);
        }

        $originalName = $file->getClientOriginalName();
        $fileName = time().'_'.$originalName;
        $filePath = $file->storeAs('payslip_uploads', $fileName, 'public');

        PayslipUpload::create([
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'status' => 'Uploaded',
            'processed_rows' => 0,
            'uploaded_at' => Carbon::now('Asia/Manila'),
        ]);

        return back()->with('success', 'Payslip file uploaded successfully.');
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

    private function buildAttendanceRecords(array $rows, int $uploadId, ?string $fallbackAttendanceDate = null): array  // Accepts rows with either separate morning/afternoon columns or raw punch logs; returns normalized attendance record data ready for database insertion.
    {
        $rows = $this->expandRawPunchRows($rows);

        $records = [];
        $now = now();
        $recordColumns = $this->getAttendanceRecordColumnLookup();
        $knownEmployeeIdLookup = $this->buildKnownEmployeeIdLookupFromRows($rows);
        $employeeJobTypeMap = $this->buildEmployeeJobTypeMapFromRows($rows);
        $employeeDepartmentMap = $this->buildEmployeeDepartmentMapFromRows($rows);
        $availableKeys = $this->collectAvailableKeys($rows);
        $hasMorningOutColumn = $this->hasAnyKey($availableKeys, ['morning_out', 'am_out', 'time_out_am', 'morning_time_out', 'out_am']);
        $hasAfternoonOutColumn = $this->hasAnyKey($availableKeys, ['afternoon_out', 'pm_out', 'time_out_pm', 'afternoon_time_out', 'out_pm']);

        foreach ($rows as $row) {
            $employeeId = $this->pickValue($row, [
                'employee_id', 'employeeid', 'id_no', 'idno', 'emp_id', 'empid',
            ]);
            $employeeName = $this->pickValue($row, [
                'name', 'employee_name', 'full_name', 'employee',
            ]);
            $mainGate = $this->pickValue($row, [
                'main_gate', 'gate', 'entry_point', 'entrance',
            ]);

            if (!$employeeId) {
                continue;
            }
            $normalizedEmployeeId = $this->normalizeEmployeeId($employeeId);
            if ($normalizedEmployeeId === '' || !isset($knownEmployeeIdLookup[$normalizedEmployeeId])) {
                continue;
            }

            $attendanceDateRaw = $this->pickValue($row, ['date', 'attendance_date']);
            $morningInRaw = $this->pickValue($row, ['morning_in', 'am_in', 'time_in_am', 'morning_time_in', 'in_am', 'am_time', 'am']);
            $morningOutRaw = $this->pickValue($row, ['morning_out', 'am_out', 'time_out_am', 'morning_time_out', 'out_am']);
            $afternoonInRaw = $this->pickValue($row, ['afternoon_in', 'pm_in', 'time_in_pm', 'afternoon_time_in', 'in_pm', 'pm_time', 'pm']);
            $afternoonOutRaw = $this->pickValue($row, ['afternoon_out', 'pm_out', 'time_out_pm', 'afternoon_time_out', 'out_pm']);

            $attendanceDate = $this->normalizeDate($attendanceDateRaw) ?: $fallbackAttendanceDate;
            $morningIn = $this->normalizeTime($morningInRaw);
            $morningOut = $this->normalizeTime($morningOutRaw);
            $afternoonIn = $this->normalizeTime($afternoonInRaw);
            $afternoonOut = $this->normalizeTime($afternoonOutRaw);

            $missing = [];
            if (!$morningIn) {
                $missing[] = 'morning_in';
            }
            if ($hasMorningOutColumn && !$morningOut) {
                $missing[] = 'morning_out';
            }
            if (!$afternoonIn) {
                $missing[] = 'afternoon_in';
            }
            if ($hasAfternoonOutColumn && !$afternoonOut) {
                $missing[] = 'afternoon_out';
            }

            $lateMinutes = $this->calculateLateMinutes($morningIn, $afternoonIn);
            $actualTimeLogs = array_filter([
                'morning_in' => $morningIn,
                'morning_out' => $morningOut,
                'afternoon_in' => $afternoonIn,
                'afternoon_out' => $afternoonOut,
            ], fn ($value) => !empty($value));

            // Mark absent only when all four time logs are missing.
            $isAbsent = count($actualTimeLogs) === 0;
            $isTardy = !$isAbsent && $lateMinutes > 0;

            $record = [
                'attendance_upload_id' => $uploadId,
                'employee_id' => $normalizedEmployeeId,
                'attendance_date' => $attendanceDate,
                'morning_in' => $morningIn,
                'morning_out' => $morningOut,
                'afternoon_in' => $afternoonIn,
                'afternoon_out' => $afternoonOut,
                'late_minutes' => $lateMinutes,
                'missing_time_logs' => !empty($missing) ? json_encode($missing) : null,
                'is_absent' => $isAbsent,
                'is_tardy' => $isTardy,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Keep compatibility with databases that have not yet run the add_name/main_gate migration.
            if (isset($recordColumns['employee_name'])) {
                $record['employee_name'] = $employeeName ? (string) $employeeName : null;
            }
            if (isset($recordColumns['main_gate'])) {
                $record['main_gate'] = $mainGate ? (string) $mainGate : null;
            }
            if (isset($recordColumns['job_type'])) {
                $record['job_type'] = $employeeJobTypeMap[$normalizedEmployeeId] ?? null;
            }
            if (isset($recordColumns['department'])) {
                $record['department'] = $employeeDepartmentMap[$normalizedEmployeeId] ?? null;
            }

            $records[] = $record;
        }

        return $records;
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

    private function syncAttendanceRecordJobTypesForUpload(int $uploadId): void
    {
        if (!Schema::hasColumn('attendance_records', 'job_type') || !Schema::hasColumn('employees', 'job_type')) {
            return;
        }

        $records = AttendanceRecord::query()
            ->select(['id', 'employee_id', 'job_type'])
            ->where('attendance_upload_id', $uploadId)
            ->get();

        if ($records->isEmpty()) {
            return;
        }

        $employeeIds = $records
            ->pluck('employee_id')
            ->map(fn ($value) => $this->normalizeEmployeeId($value))
            ->filter()
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            return;
        }

        $employeeJobTypeMap = Employee::query()
            ->select(['employee_id', 'job_type'])
            ->whereIn('employee_id', $employeeIds->all())
            ->get()
            ->mapWithKeys(function ($employee) {
                $employeeId = $this->normalizeEmployeeId($employee->employee_id);
                if ($employeeId === '') {
                    return [];
                }

                return [$employeeId => $this->normalizeEmployeeJobType($employee->job_type)];
            });

        foreach ($records as $record) {
            $employeeId = $this->normalizeEmployeeId($record->employee_id);
            if ($employeeId === '') {
                continue;
            }

            $targetJobType = $employeeJobTypeMap->get($employeeId);
            if (!$targetJobType) {
                continue;
            }

            if ($this->normalizeEmployeeJobType($record->job_type) === $targetJobType) {
                continue;
            }

            AttendanceRecord::query()
                ->whereKey($record->id)
                ->update(['job_type' => $targetJobType]);
        }
    }

    private function getAttendanceRecordColumnLookup(): array
    {
        static $columns = null;

        if ($columns === null) {
            $columns = array_flip(Schema::getColumnListing('attendance_records'));
        }

        return $columns;
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

    private function expandRawPunchRows(array $rows): array
    {
        $keys = $this->collectAvailableKeys($rows);
        $hasAmPmColumns = $this->hasAnyKey($keys, ['am_time', 'am_in', 'morning_in', 'am'])
            && $this->hasAnyKey($keys, ['pm_time', 'pm_in', 'afternoon_in', 'pm']);
        if ($hasAmPmColumns) {
            return $rows;
        }

        $hasRawPunchColumns = $this->hasAnyKey($keys, ['date', 'attendance_date'])
            && $this->hasAnyKey($keys, ['time'])
            && $this->hasAnyKey($keys, ['type']);
        if (!$hasRawPunchColumns) {
            return $rows;
        }

        $grouped = [];
        foreach ($rows as $row) {
            $employeeId = $this->pickValue($row, ['employee_id', 'employeeid', 'id_no', 'idno', 'emp_id', 'empid']);
            $date = $this->normalizeDate($this->pickValue($row, ['attendance_date', 'date']));
            $time = $this->normalizeTime($this->pickValue($row, ['time']));
            $type = strtoupper(trim((string) $this->pickValue($row, ['type', 'log_type', 'status'])));

            if (!$employeeId || !$date || !$time || !in_array($type, ['IN', 'OUT'], true)) {
                continue;
            }

            $key = $employeeId.'|'.$date;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'employee_id' => (string) $employeeId,
                    'employee_name' => $this->pickValue($row, ['name', 'employee_name', 'full_name', 'employee']),
                    'main_gate' => $this->pickValue($row, ['main_gate', 'gate', 'entry_point', 'entrance']),
                    'attendance_date' => $date,
                    'morning_in' => null,
                    'morning_out' => null,
                    'afternoon_in' => null,
                    'afternoon_out' => null,
                ];
            }

            if (!$grouped[$key]['employee_name']) {
                $grouped[$key]['employee_name'] = $this->pickValue($row, ['name', 'employee_name', 'full_name', 'employee']);
            }

            if (!$grouped[$key]['main_gate']) {
                $grouped[$key]['main_gate'] = $this->pickValue($row, ['main_gate', 'gate', 'entry_point', 'entrance']);
            }

            if ($type === 'IN') {
                if ($time < '12:00:00') {
                    if (!$grouped[$key]['morning_in'] || $time < $grouped[$key]['morning_in']) {
                        $grouped[$key]['morning_in'] = $time;
                    }
                } else {
                    if (!$grouped[$key]['afternoon_in'] || $time < $grouped[$key]['afternoon_in']) {
                        $grouped[$key]['afternoon_in'] = $time;
                    }
                }
            } else {
                if ($time <= '12:30:00') {
                    if (!$grouped[$key]['morning_out'] || $time > $grouped[$key]['morning_out']) {
                        $grouped[$key]['morning_out'] = $time;
                    }
                } else {
                    if (!$grouped[$key]['afternoon_out'] || $time > $grouped[$key]['afternoon_out']) {
                        $grouped[$key]['afternoon_out'] = $time;
                    }
                }
            }
        }

        return array_values($grouped);
    }

    private function collectAvailableKeys(array $rows): array
    {
        $keys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
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
        ]);

        $review = Applicant::findOrFail($attrs['reviewId']);

        if (strcasecmp(trim((string) $attrs['status']), 'Hired') === 0) {
            $this->reactivateResignedEmployeeAccountForApplicant($review);
        }

        $review->update([
            'application_status' => $attrs['status'],
        ]);

        $this->syncDepartmentHeadFromApplicant($review->fresh(['position']));

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

        $interview->update([
            'applicant_id' => $attrs['applicantId'],
            'interview_type' => $attrs['interview_type'],
            'date' => $attrs['date'],
            'time' => $attrs['time'],
            'duration' => $attrs['duration'],
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
            'month' => 'nullable|string',
            'redirect_back' => 'nullable|boolean',
        ]);

        $leaveApplication = LeaveApplication::findOrFail($id);
        $previousStatus = strtolower(trim((string) ($leaveApplication->status ?? '')));
        $newStatus = trim((string) $attrs['status']);

        DB::transaction(function () use ($leaveApplication, $newStatus, $previousStatus) {
            $leaveApplication->update([
                'status' => $newStatus,
            ]);

            if (strcasecmp($newStatus, 'Approved') === 0) {
                $this->syncAttendanceRecordsForApprovedLeave($leaveApplication->fresh());
            } elseif (strcasecmp($newStatus, 'Rejected') === 0 && $previousStatus === 'approved') {
                $this->deleteGeneratedLeaveAttendanceRecords($leaveApplication);
            }

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
            return redirect()->back()->with('success', 'Leave request status updated.');
        }

        return redirect()->route('admin.adminLeaveManagement', $query)
            ->with('success', 'Leave request status updated.');
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
            return response()->json([
                'message' => 'Resignation status updated.',
                'id' => (int) $resignation->id,
                'status' => $status,
                'statusCounts' => [
                    'Pending' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])->count(),
                    'Approved' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])->count(),
                    'Rejected' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['rejected'])->count(),
                    'Cancelled' => (int) Resignation::query()->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['cancelled'])->count(),
                ],
            ]);
        }

        return redirect()->route('admin.adminResignations')
            ->with('success', 'Resignation status updated.');
    }

    private function syncAttendanceRecordsForApprovedLeave(LeaveApplication $leaveApplication): void
    {
        $startDate = $leaveApplication->filing_date
            ? Carbon::parse($leaveApplication->filing_date)->startOfDay()
            : Carbon::parse($leaveApplication->created_at)->startOfDay();

        $totalRequestedDays = (float) ($leaveApplication->number_of_working_days ?? 0);
        if ($totalRequestedDays <= 0) {
            $totalRequestedDays = max(
                (float) ($leaveApplication->applied_total ?? 0),
                (float) ($leaveApplication->days_with_pay ?? 0),
                (float) ($leaveApplication->days_without_pay ?? 0)
            );
        }

        $withPayDays = max((int) ceil((float) ($leaveApplication->days_with_pay ?? 0)), 0);
        $withoutPayDays = max((int) ceil((float) ($leaveApplication->days_without_pay ?? 0)), 0);
        $requestedDaysCount = max((int) ceil($totalRequestedDays), 0);

        if ($withPayDays + $withoutPayDays === 0 && $requestedDaysCount > 0) {
            $withPayDays = $requestedDaysCount;
        }

        if ($requestedDaysCount > ($withPayDays + $withoutPayDays)) {
            $withoutPayDays += $requestedDaysCount - ($withPayDays + $withoutPayDays);
        }

        $totalDays = $withPayDays + $withoutPayDays;
        if ($totalDays <= 0) {
            return;
        }

        $employee = Employee::where('user_id', $leaveApplication->user_id)->first();
        $employeeId = $this->normalizeEmployeeId(
            $leaveApplication->employee_id ?: ($employee?->employee_id ?? '')
        );
        if ($employeeId === '') {
            return;
        }

        $upload = AttendanceUpload::firstOrCreate(
            ['file_path' => 'attendance_excels/system_leave_application_'.$leaveApplication->id.'.txt'],
            [
                'original_name' => 'system_leave_application_'.$leaveApplication->id.'.txt',
                'file_size' => 0,
                'status' => 'Processed',
                'processed_rows' => 0,
                'uploaded_at' => now(),
            ]
        );

        $employeeName = trim((string) ($leaveApplication->employee_name ?? ''));
        if ($employeeName === '') {
            $employeeName = trim((string) optional(optional($employee)->user)->first_name);
        }

        $department = trim((string) ($leaveApplication->office_department ?? ''));
        if ($department === '') {
            $department = trim((string) ($employee?->department ?? ''));
        }

        $jobType = $this->normalizeEmployeeJobType($employee?->job_type ?? null);

        for ($dayIndex = 0; $dayIndex < $totalDays; $dayIndex++) {
            $attendanceDate = $startDate->copy()->addDays($dayIndex)->toDateString();
            $isWithPay = $dayIndex < $withPayDays;
            $gateLabel = $isWithPay ? 'Leave - With Pay' : 'Leave - Without Pay';
            $isAbsent = !$isWithPay;

            $existing = AttendanceRecord::query()
                ->where('employee_id', $employeeId)
                ->whereDate('attendance_date', $attendanceDate)
                ->orderByDesc('id')
                ->first();

            if ($existing && !$this->canApplyLeaveAttendanceOverride($existing)) {
                continue;
            }

            $payload = [
                'attendance_upload_id' => $upload->id,
                'employee_id' => $employeeId,
                'employee_name' => $employeeName !== '' ? $employeeName : null,
                'department' => $department !== '' ? $department : null,
                'job_type' => $jobType,
                'main_gate' => $gateLabel,
                'attendance_date' => $attendanceDate,
                'morning_in' => null,
                'morning_out' => null,
                'afternoon_in' => null,
                'afternoon_out' => null,
                'late_minutes' => 0,
                'missing_time_logs' => ['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'],
                'is_absent' => $isAbsent,
                'is_tardy' => false,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                AttendanceRecord::create($payload);
            }
        }

        $upload->update([
            'status' => 'Processed',
            'uploaded_at' => now(),
            'processed_rows' => AttendanceRecord::query()
                ->where('attendance_upload_id', $upload->id)
                ->count(),
        ]);
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

    private function canApplyLeaveAttendanceOverride(AttendanceRecord $record): bool
    {
        $hasAnyTimeLog = !empty($record->morning_in)
            || !empty($record->morning_out)
            || !empty($record->afternoon_in)
            || !empty($record->afternoon_out);
        if ($hasAnyTimeLog) {
            return false;
        }

        $mainGate = strtolower(trim((string) ($record->main_gate ?? '')));
        return $mainGate === '' || str_starts_with($mainGate, 'leave -');
    }

    private function deleteGeneratedLeaveAttendanceRecords(LeaveApplication $leaveApplication): void
    {
        $upload = AttendanceUpload::query()
            ->where('file_path', 'attendance_excels/system_leave_application_'.$leaveApplication->id.'.txt')
            ->first();
        if (!$upload) {
            return;
        }

        AttendanceRecord::query()
            ->where('attendance_upload_id', $upload->id)
            ->delete();

        $upload->delete();
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
            'bachelor' => $attrs['bachelor'] ?? ($existingEducation?->bachelor ?? null),
            'master' => $attrs['master'] ?? ($existingEducation?->master ?? null),
            'doctorate' => $attrs['doctorate'] ?? ($existingEducation?->doctorate ?? null),
        ];
        if ($existingEducation || $hasAllRequired($educationPayload, ['bachelor', 'master', 'doctorate'])) {
            Education::updateOrCreate(
                ['user_id' => $attrs['user_id']],
                $educationPayload
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

    public function update_attendance_status($id, Request $request){
        try {
            $attendanceFile = AttendanceUpload::findOrFail($id);

            $attrs = $request->validate([
                'status' => 'required|string',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date',
            ]);
            $status = $attrs['status'];
            $fromDate = $attrs['from_date'] ?? null;
            $toDate = $attrs['to_date'] ?? null;
            $processedRows = $attendanceFile->processed_rows ?? 0;

            if (strtolower($status) === 'processed') {
                $absolutePath = Storage::disk('public')->path($attendanceFile->file_path);
                $extension = pathinfo($attendanceFile->file_path, PATHINFO_EXTENSION);
                $rows = $this->extractRowsFromExcel($absolutePath, $extension);
                $fallbackAttendanceDate = $fromDate ?: optional($attendanceFile->uploaded_at)->format('Y-m-d');
                $records = $this->buildAttendanceRecords($rows, $attendanceFile->id, $fallbackAttendanceDate);

                DB::transaction(function () use ($attendanceFile, $status, $records, &$processedRows) {
                    AttendanceRecord::where('attendance_upload_id', $attendanceFile->id)->delete();

                    if (!empty($records)) {
                        AttendanceRecord::insert($records);
                        $this->syncAttendanceRecordJobTypesForUpload($attendanceFile->id);
                    }

                    $processedRows = count($records);
                    $attendanceFile->update([
                        'status' => $status,
                        'processed_rows' => $processedRows,
                    ]);
                });

                ActivityChangeLogger::scannedFile($attendanceFile->fresh(), $processedRows, 'Attendance File');
            } else {
                $attendanceFile->update([
                    'status' => $status
                ]);
            }

            $records = AttendanceRecord::query()
                ->where('attendance_upload_id', $attendanceFile->id)
                ->get();

            $presentCount = $records->where('is_absent', false)->where('late_minutes', 0)->count();
            $absentCount = $records->where('is_absent', true)->count();
            $tardyCount = $records->where('late_minutes', '>', 0)->count();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'status' => $status,
                'processed_rows' => $processedRows,
                'upload_id' => $attendanceFile->id,
                'counts' => [
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'tardiness' => $tardyCount,
                ],
                'redirect_url' => route('admin.attendance.present', array_filter([
                    'upload_id' => $attendanceFile->id,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ])),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating attendance status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status'
            ], 500);
        }
    }

    public function delete_attendance_file($id){
        try {
            $attendanceFile = AttendanceUpload::findOrFail($id);

            // Delete the physical file if it exists
            if ($attendanceFile->file_path && Storage::disk('public')->exists($attendanceFile->file_path)) {
                Storage::disk('public')->delete($attendanceFile->file_path);
            }

            // Delete the database record
            $attendanceFile->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting attendance file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file'
            ], 500);
        }
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
