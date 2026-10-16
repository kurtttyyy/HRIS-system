<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\Conversation;
use App\Models\Resignation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeStoreController extends Controller
{
    private const FOLDER_TYPE = '__FOLDER__';

    public function upload_store(Request $request){
        Log::info($request->all());
        $attrs = $request->validate([
            'document_name' => 'required|string|max:255',
            'folder_key' => 'nullable|string|max:120',
            'uploadFile' => 'required|file|max:5120',
        ]);

        $user = Auth::id();

        $applicant = Applicant::where('user_id', $user)
                                    ->where('application_status', 'Hired')
                                    ->latest('id')
                                    ->first();

        if (!$applicant) {
            return redirect()->back()->with('error', 'No hired applicant record found.');
        }

        $file = $request->file('uploadFile');

        if (!$file || !$file->isValid()) {
            return back()->withErrors(['uploadFile' => 'Invalid file upload.']);
        }

        $documentName = trim((string) ($attrs['document_name'] ?? ''));
        $isProfilePhoto = strtoupper($documentName) === 'PROFILE_PHOTO';
        $allowedExtensions = $isProfilePhoto
            ? ['jpg', 'jpeg', 'png', 'gif', 'webp']
            : ['pdf', 'xlsx', 'doc', 'docx'];
        $fileExtension = strtolower((string) $file->getClientOriginalExtension());

        if (!in_array($fileExtension, $allowedExtensions, true)) {
            return back()->withErrors([
                'uploadFile' => $isProfilePhoto
                    ? 'Profile photo must be a JPG, JPEG, PNG, GIF, or WEBP file.'
                    : 'Document must be a PDF, XLSX, DOC, or DOCX file.',
            ])->withInput();
        }

        $originalName = $file->getClientOriginalName();
        $mimeType     = $file->getMimeType();
        $size         = $file->getSize();

        $fileName = time() . '_' . $originalName;
        $folderKey = trim((string) ($attrs['folder_key'] ?? ''));
        $folders = $this->folderOptionsForApplicant((int) $applicant->id);
        if ($folderKey !== '' && !array_key_exists($folderKey, $folders)) {
            return back()->withErrors(['folder_key' => 'Selected folder does not exist.'])->withInput();
        }
        if ($isProfilePhoto) {
            $folderKey = '';
            ApplicantDocument::query()
                ->where('applicant_id', $applicant->id)
                ->whereRaw("UPPER(TRIM(COALESCE(type, ''))) = 'PROFILE_PHOTO'")
                ->get()
                ->each(function ($document) {
                    $relativePath = ltrim((string) ($document->filepath ?? ''), '/');
                    if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
                        Storage::disk('public')->delete($relativePath);
                    }

                    $document->delete();
                });
        }

        // Store file
        $filePath = $file->storeAs(
            $this->employeeUploadDirectory((int) $applicant->id, $folderKey),
            $fileName,
            'public'
        );

        ApplicantDocument::create([
            'applicant_id' => $applicant->id,
            'type'         => $documentName,
            'filename'     => $originalName,
            'filepath'     => $filePath, // already "uploads/filename"
            'mime_type'    => $mimeType,
            'size'         => $size,
        ]);

        $this->clearMatchingRequiredDocumentMeta((int) $applicant->id, (string) ($attrs['document_name'] ?? ''));
        $this->clearMatchingRequiredDocumentMeta(
            (int) $applicant->id,
            (string) pathinfo((string) $originalName, PATHINFO_FILENAME)
        );
        $this->clearDocumentNoticeIfNoRequiredDocuments((int) $applicant->id);

        return back()->with('success', $isProfilePhoto ? 'Profile photo updated successfully.' : 'Document uploaded successfully.');
    }

    public function create_folder(Request $request)
    {
        $attrs = $request->validate([
            'folder_name' => 'required|string|max:80',
        ]);

        $userId = Auth::id();
        $applicant = Applicant::where('user_id', $userId)
            ->where('application_status', 'Hired')
            ->latest('id')
            ->first();

        if (!$applicant) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['folder_name' => 'No hired applicant record found.']);
        }

        $folderName = trim((string) $attrs['folder_name']);
        $folderKey = $this->normalizeFolderKey($folderName);
        if ($folderKey === '') {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['folder_name' => 'Folder name is invalid.'])->withInput();
        }

        $existingFolders = $this->folderOptionsForApplicant((int) $applicant->id);
        if (array_key_exists($folderKey, $existingFolders)) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['folder_name' => 'That folder already exists.'])->withInput();
        }

        ApplicantDocument::create([
            'applicant_id' => $applicant->id,
            'type' => self::FOLDER_TYPE,
            'filename' => $folderName,
            'filepath' => 'system/folders/'.$folderKey,
            'mime_type' => 'inode/directory',
            'size' => 0,
        ]);

        return redirect()->back()->withFragment('document-folder-area')->with('success', 'Folder created successfully.');
    }

    public function remove_document($id)
    {
        $userId = Auth::id();
        $applicant = Applicant::where('user_id', $userId)
            ->where('application_status', 'Hired')
            ->latest('id')
            ->first();

        if (!$applicant) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'No hired applicant record found.']);
        }

        $document = ApplicantDocument::where('id', $id)
            ->where('applicant_id', $applicant->id)
            ->first();

        if (!$document) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'Document not found or unauthorized.']);
        }

        $relativePath = ltrim((string) ($document->filepath ?? ''), '/');
        if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }

        $document->delete();

        return redirect()->back()->withFragment('document-folder-area')->with('success', 'Document removed successfully.');
    }

    public function move_document(Request $request, $id)
    {
        $attrs = $request->validate([
            'folder_key' => 'nullable|string|max:120',
        ]);

        $userId = Auth::id();
        $applicant = Applicant::where('user_id', $userId)
            ->where('application_status', 'Hired')
            ->latest('id')
            ->first();

        if (!$applicant) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'No hired applicant record found.']);
        }

        $allowedApplicantIds = $this->documentAccessibleApplicantIds($applicant);
        $document = ApplicantDocument::query()
            ->where('id', $id)
            ->whereIn('applicant_id', $allowedApplicantIds)
            ->where('type', '!=', self::FOLDER_TYPE)
            ->first();

        if (!$document) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'Document not found or unauthorized.']);
        }

        $folderKey = trim((string) ($attrs['folder_key'] ?? ''));
        $folders = $this->folderOptionsForApplicant((int) $applicant->id);
        if ($folderKey !== '' && !array_key_exists($folderKey, $folders)) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'Selected folder does not exist.']);
        }

        $currentRelativePath = ltrim((string) ($document->filepath ?? ''), '/');
        if ($currentRelativePath === '') {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'Document path is invalid.']);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($currentRelativePath)) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'File not found in storage.']);
        }

        $currentFolderKey = $this->folderKeyFromPath($currentRelativePath);
        $targetFolderKey = $folderKey === '' ? '' : $this->normalizeFolderKey($folderKey);
        if ($currentFolderKey === $targetFolderKey) {
            return redirect()->back()->withFragment('document-folder-area')->with('success', 'Document is already in that folder.');
        }

        $targetDirectory = trim($this->employeeUploadDirectory((int) $applicant->id, $targetFolderKey), '/');
        $baseFileName = basename($currentRelativePath);
        $targetRelativePath = $targetDirectory.'/'.$baseFileName;
        if ($disk->exists($targetRelativePath)) {
            $targetRelativePath = $targetDirectory.'/'.time().'_'.$baseFileName;
        }

        if (!$disk->move($currentRelativePath, $targetRelativePath)) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'Unable to move file right now.']);
        }

        $document->filepath = $targetRelativePath;
        $document->save();

        $targetLabel = $targetFolderKey === '' ? 'Unfiled' : ($folders[$targetFolderKey] ?? 'selected folder');

        return redirect()->back()->withFragment('document-folder-area')->with('success', 'Document moved to '.$targetLabel.'.');
    }

    public function remove_folder(string $folderKey)
    {
        $userId = Auth::id();
        $applicant = Applicant::where('user_id', $userId)
            ->where('application_status', 'Hired')
            ->latest('id')
            ->first();

        if (!$applicant) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'No hired applicant record found.']);
        }

        $normalizedFolderKey = $this->normalizeFolderKey($folderKey);
        if ($normalizedFolderKey === '') {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'Folder not found.']);
        }

        $folderRecord = ApplicantDocument::query()
            ->where('applicant_id', $applicant->id)
            ->where('type', self::FOLDER_TYPE)
            ->get()
            ->first(function (ApplicantDocument $folder) use ($normalizedFolderKey) {
                return $this->folderKeyFromPath((string) $folder->filepath) === $normalizedFolderKey;
            });

        if (!$folderRecord) {
            return redirect()->back()->withFragment('document-folder-area')->withErrors(['documents' => 'Folder not found.']);
        }

        $folderPrefix = trim($this->employeeUploadDirectory((int) $applicant->id, $normalizedFolderKey), '/').'/';
        $documentsInFolder = ApplicantDocument::query()
            ->where('applicant_id', $applicant->id)
            ->where('type', '!=', self::FOLDER_TYPE)
            ->get()
            ->filter(function (ApplicantDocument $document) use ($folderPrefix) {
                $relativePath = trim(str_replace('\\', '/', (string) ($document->filepath ?? '')), '/');

                return str_starts_with($relativePath, $folderPrefix);
            });

        foreach ($documentsInFolder as $document) {
            $relativePath = ltrim((string) ($document->filepath ?? ''), '/');
            if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }

            $document->delete();
        }

        $folderRecord->delete();

        return redirect()->to(route('employee.employeeDocument').'#document-folder-area')->with(
            'success',
            'Folder removed successfully.'.($documentsInFolder->isNotEmpty() ? ' Files inside it were also deleted.' : '')
        );
    }

    private function folderOptionsForApplicant(int $applicantId): array
    {
        return ApplicantDocument::query()
            ->where('applicant_id', $applicantId)
            ->where('type', self::FOLDER_TYPE)
            ->orderBy('filename')
            ->get(['filename', 'filepath'])
            ->mapWithKeys(function (ApplicantDocument $folder) {
                $key = $this->folderKeyFromPath((string) $folder->filepath);
                if ($key === '') {
                    $key = $this->normalizeFolderKey((string) $folder->filename);
                }

                return $key === ''
                    ? []
                    : [$key => trim((string) $folder->filename)];
            })
            ->all();
    }

    private function folderKeyFromPath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($normalized, 'system/folders/')) {
            return trim((string) Str::after($normalized, 'system/folders/'));
        }
        if (preg_match('#^uploads/applicant-documents/\d+/([^/]+)/#', $normalized, $matches)) {
            $folderKey = trim((string) ($matches[1] ?? ''));
            return $folderKey === 'unfiled' ? '' : $folderKey;
        }

        return '';
    }

    private function documentAccessibleApplicantIds(?Applicant $applicant): array
    {
        if (!$applicant) {
            return [];
        }

        $ids = collect([(int) $applicant->id]);
        $previousApplicant = $this->resolvePreviousComparableApplicant($applicant);
        if ($previousApplicant) {
            $ids->push((int) $previousApplicant->id);
        }

        return $ids
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
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

    private function normalizeFolderKey(string $value): string
    {
        return trim((string) Str::of($value)
            ->lower()
            ->squish()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->substr(0, 60));
    }

    private function employeeUploadDirectory(int $applicantId, string $folderKey = ''): string
    {
        $basePath = 'uploads/applicant-documents/'.$applicantId;
        if ($folderKey === '') {
            return $basePath.'/unfiled';
        }

        return $basePath.'/'.$folderKey;
    }

    public function store_resignation(Request $request)
    {
        $attrs = $request->validate([
            'submitted_at' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:submitted_at',
            'resignation_file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ], [
            'resignation_file.required' => 'Please upload your resignation letter.',
            'resignation_file.file' => 'Please upload a valid resignation letter file.',
            'resignation_file.mimes' => 'Please upload your resignation letter as a PDF, DOC, or DOCX file.',
            'resignation_file.max' => 'Your resignation letter is too large. Please upload a file that is 10 MB or smaller.',
        ]);

        $authUser = Auth::user();
        if (!$authUser) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please sign in again.',
                ], 401);
            }

            return redirect()->route('login_display');
        }

        $authUser->loadMissing('employee');

        $employeeName = trim(implode(' ', array_filter([
            trim((string) ($authUser->first_name ?? '')),
            trim((string) ($authUser->middle_name ?? '')),
            trim((string) ($authUser->last_name ?? '')),
        ])));

        $attachment = $request->file('resignation_file');
        $attachmentPath = $attachment->store('resignation-attachments', 'public');

        $resignation = Resignation::create([
            'user_id' => $authUser->id,
            'employee_id' => (string) ($authUser->employee?->employee_id ?? ''),
            'employee_name' => $employeeName !== '' ? $employeeName : (string) ($authUser->email ?? 'Unknown Employee'),
            'department' => (string) ($authUser->employee?->department ?? ''),
            'position' => (string) ($authUser->employee?->position ?? ''),
            'submitted_at' => $attrs['submitted_at'],
            'effective_date' => $attrs['effective_date'],
            'reason' => '',
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachment->getClientOriginalName(),
            'attachment_mime' => $attachment->getClientMimeType(),
            'status' => 'Pending',
        ]);

        if ($request->expectsJson()) {
            $allResignations = Resignation::query()
                ->where('user_id', $authUser->id)
                ->get(['status']);

            return response()->json([
                'message' => 'Resignation request submitted.',
                'resignation' => [
                    'id' => (int) $resignation->id,
                    'status' => (string) $resignation->status,
                    'submitted_at' => optional($resignation->submitted_at)->format('M d, Y'),
                    'effective_date' => optional($resignation->effective_date)->format('M d, Y'),
                    'effective_date_long' => optional($resignation->effective_date)->format('F d, Y'),
                    'attachment_name' => (string) $resignation->attachment_name,
                    'preview_url' => route('employee.resignationAttachment.preview', $resignation->id),
                    'cancel_url' => route('employee.cancelResignation', $resignation->id),
                ],
                'counts' => [
                    'all' => $allResignations->count(),
                    'pending' => $allResignations
                        ->filter(fn ($row) => strcasecmp((string) $row->status, 'Pending') === 0)
                        ->count(),
                    'processed' => $allResignations
                        ->filter(fn ($row) => in_array(strtolower(trim((string) $row->status)), ['approved', 'completed'], true))
                        ->count(),
                    'closed' => $allResignations
                        ->filter(fn ($row) => in_array(strtolower(trim((string) $row->status)), ['rejected', 'cancelled'], true))
                        ->count(),
                ],
            ], 201);
        }

        return redirect()->route('employee.employeeResignation')
            ->with('success', 'Resignation request submitted.');
    }

    public function cancel_resignation(Resignation $resignation)
    {
        $authUser = Auth::user();
        if (!$authUser || (int) $resignation->user_id !== (int) $authUser->id) {
            abort(403);
        }

        if (strcasecmp((string) ($resignation->status ?? ''), 'Pending') !== 0) {
            return redirect()->route('employee.employeeResignation')
                ->with('error', 'Only pending resignation requests can be cancelled.');
        }

        if (!empty($resignation->attachment_path)) {
            Storage::disk('public')->delete($resignation->attachment_path);
        }

        $resignation->update([
            'status' => 'Cancelled',
            'admin_note' => 'Employee cancelled the resignation request.',
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'processed_at' => now(),
        ]);

        return redirect()->route('employee.employeeResignation', ['status' => 'closed'])
            ->with('success', 'Resignation request cancelled and uploaded letter removed.');
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
            'attachments' => 'nullable|array|max:4|required_without:body',
            'attachments.*' => 'file|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'tab_session' => 'nullable|string|max:120',
        ], [
            'body.required_without' => 'Enter a message or choose an image.',
            'attachments.required_without' => 'Enter a message or choose at least one image.',
            'attachments.max' => 'You can attach up to 4 images per message.',
            'attachments.*.image' => 'Every attachment must be an image.',
            'attachments.*.mimes' => 'Use JPG, JPEG, PNG, GIF, or WEBP images.',
            'attachments.*.max' => 'Each image must not be larger than 5 MB.',
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

        $participant = User::query()->findOrFail((int) $attrs['participant_user_id']);
        if ((int) $participant->id === (int) $authUser->id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You cannot message yourself.'], 422);
            }
            return redirect()->back()->withErrors(['body' => 'You cannot message yourself.']);
        }

        if (strcasecmp(trim((string) ($participant->role ?? '')), 'employee') === 0) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Employees can only start chats with admin users.'], 422);
            }
            return redirect()->back()->withErrors(['body' => 'Employees can only start chats with admin users.']);
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
                'chat_url' => route('employee.employeeCommunication', $chatRouteParameters),
                'sent_message' => [
                    'id' => (int) $message->id,
                    'body' => (string) $message->body,
                    'created_at' => optional($message->created_at)?->toIso8601String(),
                    'attachments' => $message->attachments->map(fn ($attachment) => [
                        'id' => (int) $attachment->id,
                        'name' => $attachment->name,
                        'mime' => $attachment->mime,
                        'url' => route('employee.communication.attachment.view', array_filter([
                            'attachment' => $attachment->id,
                            'tab_session' => $request->input('tab_session'),
                        ])),
                        'preview_url' => route('employee.communication.attachment.view', array_filter([
                            'attachment' => $attachment->id,
                            'preview' => 1,
                            'tab_session' => $request->input('tab_session'),
                        ])),
                    ])->values()->all(),
                ],
            ]);
        }

        return redirect()->route('employee.employeeCommunication', [
            'conversation' => $conversation->id,
            'user' => $participant->id,
            'tab_session' => $request->input('tab_session'),
        ])->with('success', 'Message sent.');
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
}
