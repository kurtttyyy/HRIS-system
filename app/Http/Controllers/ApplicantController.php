<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicantDegree;
use App\Models\ApplicantDocument;
use App\Mail\ApplicationTrackingNumberMail;
use App\Models\Education;
use App\Models\Resignation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicantController extends Controller
{
    public function scan_pds(Request $request)
    {
        $attrs = $request->validate([
            'pds_file' => 'required|file|max:5120',
        ]);

        $file = $attrs['pds_file'];
        $extension = Str::lower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xls', 'xlsx', 'xlsm', 'csv'], true)) {
            return response()->json([
                'message' => 'Please upload an Excel PDS file only: XLS, XLSX, XLSM, or CSV.',
            ], 422);
        }
        $scanRows = $this->extractPdsRows($file->getRealPath(), $extension);
        $scanText = $scanRows
            ? collect($scanRows)->map(fn ($row) => implode("\t", $row))->implode("\n")
            : $this->extractPdsText($file->getRealPath(), $extension);

        if (!$this->isOfficialPdsTemplate($scanRows, $scanText)) {
            return response()->json([
                'message' => 'Scan failed. The uploaded file does not match the official Personal Data Sheet (CS Form No. 212) format. Please upload the correct PDS template.',
            ], 422);
        }

        $debugUploadPath = $this->savePdsDebugUpload($file->getRealPath(), $extension);

        $coordinateData = in_array($extension, ['xlsx', 'xlsm', 'xls'], true)
            ? $this->extractOfficialPdsCoordinateData($file->getRealPath())
            : [];
        $openXmlCheckboxData = in_array($extension, ['xlsx', 'xlsm'], true)
            ? $this->extractOfficialPdsCheckboxData($file->getRealPath())
            : [];
        $excelComData = [];
        if (in_array($extension, ['xlsx', 'xlsm', 'xls'], true)) {
            $needsExcelCom = collect($coordinateData)->filter()->isEmpty()
                || (blank($coordinateData['sex'] ?? null) && blank($openXmlCheckboxData['sex'] ?? null))
                || (blank($coordinateData['civil_status'] ?? null) && blank($openXmlCheckboxData['civil_status'] ?? null));
            if ($needsExcelCom) {
                $excelComData = $this->extractOfficialPdsDataWithExcelCom($file->getRealPath());
                if (collect($coordinateData)->filter()->isEmpty()) {
                    $coordinateData = $excelComData;
                }
            }
        }
        $textData = $this->parsePdsText($scanText);
        $rowData = $this->parsePdsRows($scanRows);
        $pdsData = collect($textData)
            ->map(function ($value, $key) use ($rowData, $coordinateData, $openXmlCheckboxData, $excelComData) {
                if (in_array($key, ['sex', 'civil_status'], true) && filled($openXmlCheckboxData[$key] ?? null)) {
                    return $openXmlCheckboxData[$key];
                }

                if (in_array($key, ['sex', 'civil_status'], true) && filled($excelComData[$key] ?? null)) {
                    return $excelComData[$key];
                }

                if (filled($coordinateData[$key] ?? null)) {
                    return $coordinateData[$key];
                }

                return filled($rowData[$key] ?? null) ? $rowData[$key] : $value;
            })
            ->all();
        $pdsData = $this->removePdsTemplateNoise($pdsData);
        $pdsData['sex'] = $this->normalizePdsChoice($pdsData['sex'] ?? null, ['Male', 'Female']);
        $pdsData['civil_status'] = $this->normalizePdsChoice($pdsData['civil_status'] ?? null, ['Single', 'Married', 'Widowed', 'Separated']);
        if (blank($pdsData['civil_status'] ?? null)) {
            $pdsData['civil_status'] = $this->inferOfficialPdsCivilStatus($scanRows, $scanText, $pdsData);
        }
        $pdsData = $this->separatePdsPermanentAddressZipCode($pdsData);
        $pdsData['permanent_address'] = $this->completePdsPermanentAddress($pdsData);
        $responseFields = $this->appendPdsEducationResponseFields($pdsData, $scanRows);
        $responseFields['permanent_address'] = $pdsData['permanent_address'];

        $filledFields = collect($pdsData)
            ->reject(fn ($value) => blank($value))
            ->count();

        if ($filledFields === 0) {
            $debug = $this->debugPdsWorkbook($file->getRealPath(), $extension);

            return response()->json([
                'message' => 'No readable PDS values were found. Debug: '.$debug['summary'],
                'fields' => $pdsData,
                'filled_fields' => 0,
                'debug' => $debug,
            ], 422);
        }

        $pdsId = DB::table('pds_table')->insertGetId($pdsData);

        return response()->json([
            'id' => $pdsId,
            'fields' => $responseFields,
            'filled_fields' => $filledFields,
            'choice_debug' => [
                'sex' => $pdsData['sex'] ?? null,
                'civil_status' => $pdsData['civil_status'] ?? null,
                'upload_debug_path' => $debugUploadPath,
                'civil_status_rows' => $this->debugPdsChoiceRows($scanRows, ['civil status', 'single', 'widowed']),
            ],
            'message' => 'Personal Data Sheet scanned and saved.',
        ]);
    }

    private function savePdsDebugUpload(string $path, string $extension): ?string
    {
        $debugDir = storage_path('app'.DIRECTORY_SEPARATOR.'pds-debug');
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0775, true);
        }

        $debugPath = $debugDir.DIRECTORY_SEPARATOR.'latest-pds-scan.'.$extension;
        if (!@copy($path, $debugPath)) {
            return null;
        }

        return $debugPath;
    }

    private function isOfficialPdsTemplate(array $rows, string $text): bool
    {
        $rowText = collect($rows)
            ->take(90)
            ->map(fn ($row) => implode(' ', array_map('strval', $row)))
            ->implode(' ');
        $normalized = $this->normalizePdsLabel($rowText.' '.$text);

        $hasOfficialHeader = str_contains($normalized, 'personal data sheet')
            || str_contains($normalized, 'cs form no 212');

        $requiredLabels = [
            'personal information',
            'surname',
            'first name',
            'middle name',
            'date of birth',
            'place of birth',
            'sex',
            'civil status',
            'citizenship',
            'height',
            'weight',
            'blood type',
            'gsis id no',
            'pag ibig id no',
            'philhealth no',
            'sss no',
            'tin no',
            'residential address',
            'permanent address',
            'telephone no',
            'mobile no',
            'e mail address',
            'family background',
        ];

        $matchedLabels = collect($requiredLabels)
            ->filter(fn ($label) => str_contains($normalized, $this->normalizePdsLabel($label)))
            ->count();

        return ($hasOfficialHeader && $matchedLabels >= 6) || $matchedLabels >= 10;
    }

    private function removePdsTemplateNoise(array $pdsData): array
    {
        return collect($pdsData)
            ->map(function ($value, $field) {
                if (!is_string($value)) {
                    return $value;
                }

                $value = $this->cleanPdsValue($value);
                if ($field === 'name_extension') {
                    $value = $this->cleanPdsNameExtension((string) $value);
                }

                if (!$value || $this->looksLikePdsTemplateNoise($value, (string) $field)) {
                    return null;
                }

                if (in_array($field, ['telephone_no', 'mobile_no'], true) && !preg_match('/\d{3,}/', $value)) {
                    return null;
                }

                if ($field === 'email_address' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                return $value;
            })
            ->all();
    }

    private function looksLikePdsTemplateNoise(string $value, string $field = ''): bool
    {
        $normalized = $this->normalizePdsLabel($value);
        if ($normalized === '' || in_array($normalized, ['na', 'n a', 'none', 'null'], true)) {
            return true;
        }

        if (!preg_match('/[a-z0-9]/i', $value)) {
            return true;
        }

        $templateFragments = [
            'name extension',
            'jr sr',
            'surname',
            'first name',
            'middle name',
            'date of birth',
            'place of birth',
            'sex at birth',
            'civil status',
            'citizenship',
            'residential address',
            'permanent address',
            'house block lot no',
            'subdivision village',
            'city municipality',
            'zip code',
            'telephone no',
            'mobile no',
            'e mail address',
            'if any',
            'height m',
            'weight kg',
            'blood type',
            'gsis id no',
            'pag ibig id no',
            'philhealth no',
            'sss no',
            'tin no',
            'agency employee no',
            'family background',
        ];

        foreach ($templateFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        if (in_array($field, ['surname', 'first_name', 'middle_name', 'name_extension'], true)
            && preg_match('/\b(single|married|widow(?:ed|er)?|separated|dual citizenship|filipino|afghanistan|albania|algeria|andorra|angola|argentina|armenia|australia|austria|bahamas|bahrain|bangladesh|belgium|brazil|canada|china|denmark|france|germany|india|indonesia|italy|japan|malaysia|philippines|singapore|spain|thailand|united states)\b/iu', $value)) {
            return true;
        }

        if ($field === 'permanent_address' && $this->looksLikePdsCitizenshipCountry($value)) {
            return true;
        }

        return false;
    }

    private function debugPdsChoiceRows(array $rows, array $needles): array
    {
        $samples = [];
        foreach ($rows as $index => $row) {
            $rowText = $this->normalizePdsLabel(implode(' ', array_map('strval', $row)));
            foreach ($needles as $needle) {
                if (!str_contains($rowText, $this->normalizePdsLabel($needle))) {
                    continue;
                }

                $samples[] = [
                    'index' => $index,
                    'values' => array_values(array_filter(array_map('strval', $row), fn ($value) => trim($value) !== '')),
                ];
                break;
            }
        }

        return array_slice($samples, 0, 8);
    }

    public function upload_document_draft(Request $request)
    {
        $attrs = $request->validate([
            'draft_key' => 'required|string|max:120',
            'document_index' => 'required|integer|min:0|max:20',
            'document_type' => 'required|string|max:120',
            'draft_files' => 'required|array|min:1',
            'draft_files.*' => 'file|mimes:pdf,doc,docx|max:5120',
        ]);

        $draftKey = preg_replace('/[^A-Za-z0-9_-]/', '', $attrs['draft_key']);
        if (!$draftKey) {
            $draftKey = (string) Str::uuid();
        }

        $storedFiles = [];
        foreach ($request->file('draft_files', []) as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/application-drafts/'.$draftKey, $fileName, 'public');

            $storedFiles[] = [
                'type' => $attrs['document_type'],
                'filename' => $file->getClientOriginalName(),
                'filepath' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        return response()->json([
            'draft_key' => $draftKey,
            'document_index' => (string) $attrs['document_index'],
            'files' => $storedFiles,
        ]);
    }

    private function normalizeDraftDocumentRefs(array $draftDocuments): array
    {
        $normalized = [];
        foreach ($draftDocuments as $index => $documents) {
            foreach ((array) $documents as $document) {
                $payload = is_string($document) ? json_decode($document, true) : $document;
                if (!is_array($payload)) {
                    continue;
                }

                $path = (string) ($payload['filepath'] ?? '');
                if (!str_starts_with($path, 'uploads/application-drafts/')) {
                    continue;
                }

                $normalized[(string) $index][] = [
                    'type' => (string) ($payload['type'] ?? ''),
                    'filename' => (string) ($payload['filename'] ?? ''),
                    'filepath' => $path,
                    'mime_type' => (string) ($payload['mime_type'] ?? ''),
                    'size' => (int) ($payload['size'] ?? 0),
                ];
            }
        }

        return $normalized;
    }

    public function applicant_stores(Request $request){
        $request->request->remove('pds_file');
        $request->files->remove('pds_file');
        $draftDocumentRefs = $this->normalizeDraftDocumentRefs((array) $request->input('draft_documents', []));
        $draftDocuments = collect($draftDocumentRefs)
            ->map(fn ($documents) => collect((array) $documents)->filter()->values()->all())
            ->filter(fn ($documents) => count($documents) > 0);
        $hasDraftDocument = fn (int $index) => $draftDocuments->has((string) $index) || $draftDocuments->has($index);

        try {
            $attrs = $request->validate([
            'pds_record_id' => 'nullable|integer|exists:pds_table,id',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'name_extension' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'sex' => 'required|string',
            'civil_status' => 'required|string',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'education_levels' => 'nullable|array',
            'education_levels.*.level' => 'required|string',
            'education_levels.*.school_name' => 'nullable|string',
            'education_levels.*.year_graduated' => 'nullable|string',
            'bachelor_degree' => 'nullable|string',
            'bachelor_school_name' => 'nullable|string',
            'bachelor_year_finished' => 'nullable|string',
            'bachelor_degrees' => 'nullable|array|min:1',
            'bachelor_degrees.*.degree' => 'nullable|string',
            'bachelor_degrees.*.school_name' => 'nullable|string',
            'bachelor_degrees.*.year_finished' => 'nullable|string',
            'master_degrees' => 'nullable|array',
            'master_degrees.*.degree' => 'nullable|string',
            'master_degrees.*.school_name' => 'nullable|string',
            'master_degrees.*.year_finished' => 'nullable|string',
            'master_degree' => 'nullable|string',
            'master_school_name' => 'nullable|string',
            'master_year_finished' => 'nullable|string',
            'doctoral_degrees' => 'nullable|array',
            'doctoral_degrees.*.degree' => 'nullable|string',
            'doctoral_degrees.*.school_name' => 'nullable|string',
            'doctoral_degrees.*.year_finished' => 'nullable|string',
            'doctoral_degree' => 'nullable|string',
            'doctoral_school_name' => 'nullable|string',
            'doctoral_year_finished' => 'nullable|string',
            'position' => 'required|exists:open_positions,id',
            'fresh_graduate' => 'nullable|boolean',
            'experience_years' => 'required|string',
            'key_skills' => 'required|string',
            'documents' => 'required|array',
            'documents.*.file' => 'nullable',
            'documents.0.file' => ($hasDraftDocument(0) ? 'nullable' : 'required').'|file|mimes:pdf,doc,docx|max:5120',
            'documents.1.file' => ($hasDraftDocument(1) ? 'nullable' : 'required').'|file|mimes:pdf,doc,docx|max:5120',
            'documents.3.file' => ($hasDraftDocument(3) ? 'nullable' : 'required').'|file|mimes:pdf,doc,docx|max:5120',
            'documents.4.file' => 'nullable|array',
            'documents.4.file.*' => 'file|mimes:pdf,doc,docx|max:5120',
            'documents.5.file' => 'nullable|array',
            'documents.5.file.*' => 'file|mimes:pdf,doc,docx|max:5120',
            'documents.6.file' => 'nullable|array',
            'documents.6.file.*' => 'file|mimes:pdf,doc,docx|max:5120',
            'documents.7.file' => $hasDraftDocument(7) ? 'nullable|array' : 'required|array|min:1',
            'documents.7.file.*' => 'file|mimes:pdf,doc,docx|max:5120',
            'documents.8.file' => 'nullable|array',
            'documents.8.file.*' => 'file|mimes:pdf,doc,docx|max:5120',
            'documents.*.type' => 'required',
            'draft_documents' => 'nullable|array',
            'draft_documents.*' => 'nullable|array',
            'draft_documents.*.*' => 'nullable|string',
            'work_position' => 'required_unless:fresh_graduate,1|nullable|string',
            'work_employer' => 'required_unless:fresh_graduate,1|nullable|string',
            'work_location' => 'required_unless:fresh_graduate,1|nullable|string',
            'work_duration' => 'required_unless:fresh_graduate,1|nullable|string',
            ]);
        } catch (ValidationException $exception) {
            return redirect()->back()
                ->withErrors($exception->validator)
                ->withInput($this->safeApplicationOldInput($request));
        }
        $attrs['name_extension'] = $this->cleanPdsNameExtension((string) ($attrs['name_extension'] ?? ''));

        $normalizedEducationLevels = collect($attrs['education_levels'] ?? [])
            ->map(function ($education, $key) {
                return [
                    'key' => (string) $key,
                    'level' => trim((string) ($education['level'] ?? $key)),
                    'school_name' => trim((string) ($education['school_name'] ?? '')) ?: null,
                    'year_finished' => trim((string) ($education['year_graduated'] ?? '')) ?: null,
                ];
            })
            ->filter(fn ($education) => $education['school_name'] !== null || $education['year_finished'] !== null)
            ->values();

        $normalizedBachelorDegrees = collect($attrs['bachelor_degrees'] ?? [])
            ->map(function ($degree) {
                return [
                    'degree' => trim((string) ($degree['degree'] ?? '')),
                    'school_name' => trim((string) ($degree['school_name'] ?? '')) ?: null,
                    'year_finished' => trim((string) ($degree['year_finished'] ?? '')) ?: null,
                ];
            })
            ->filter(fn ($degree) => $degree['degree'] !== '')
            ->values();

        // Backward-compatible fallback for previous single-field payloads.
        if ($normalizedBachelorDegrees->isEmpty() && !empty($attrs['bachelor_degree'])) {
            $normalizedBachelorDegrees = collect([[
                'degree' => trim((string) $attrs['bachelor_degree']),
                'school_name' => trim((string) ($attrs['bachelor_school_name'] ?? '')) ?: null,
                'year_finished' => trim((string) ($attrs['bachelor_year_finished'] ?? '')) ?: null,
            ]]);
        }

        if ($normalizedBachelorDegrees->isEmpty()) {
            return redirect()->back()
                ->withInput($this->safeApplicationOldInput($request))
                ->withErrors(['bachelor_degrees' => 'Please add at least one bachelor degree.']);
        }

        $primaryBachelor = $normalizedBachelorDegrees->first();
        $normalizedMasterDegrees = collect($attrs['master_degrees'] ?? [])
            ->map(function ($degree) {
                return [
                    'degree' => trim((string) ($degree['degree'] ?? '')),
                    'school_name' => trim((string) ($degree['school_name'] ?? '')) ?: null,
                    'year_finished' => trim((string) ($degree['year_finished'] ?? '')) ?: null,
                ];
            })
            ->filter(fn ($degree) => $degree['degree'] !== '')
            ->values();

        if ($normalizedMasterDegrees->isEmpty() && !empty($attrs['master_degree'])) {
            $normalizedMasterDegrees = collect([[
                'degree' => trim((string) $attrs['master_degree']),
                'school_name' => trim((string) ($attrs['master_school_name'] ?? '')) ?: null,
                'year_finished' => trim((string) ($attrs['master_year_finished'] ?? '')) ?: null,
            ]]);
        }

        $primaryMaster = $normalizedMasterDegrees->first();

        $normalizedDoctoralDegrees = collect($attrs['doctoral_degrees'] ?? [])
            ->map(function ($degree) {
                return [
                    'degree' => trim((string) ($degree['degree'] ?? '')),
                    'school_name' => trim((string) ($degree['school_name'] ?? '')) ?: null,
                    'year_finished' => trim((string) ($degree['year_finished'] ?? '')) ?: null,
                ];
            })
            ->filter(fn ($degree) => $degree['degree'] !== '')
            ->values();

        if ($normalizedDoctoralDegrees->isEmpty() && !empty($attrs['doctoral_degree'])) {
            $normalizedDoctoralDegrees = collect([[
                'degree' => trim((string) $attrs['doctoral_degree']),
                'school_name' => trim((string) ($attrs['doctoral_school_name'] ?? '')) ?: null,
                'year_finished' => trim((string) ($attrs['doctoral_year_finished'] ?? '')) ?: null,
            ]]);
        }

        $primaryDoctoral = $normalizedDoctoralDegrees->first();

        // Keep applicant identity in session so vacancy pages can hide jobs already applied to.
        session(['applicant_email' => $attrs['email']]);

        $normalizedEmail = Str::lower(trim((string) $attrs['email']));
        $rehireUser = $this->findLatestResignedEmployeeByEmail($normalizedEmail);

        $existingApplication = Applicant::whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->where('open_position_id', $attrs['position'])
            ->when($rehireUser, function ($query) use ($rehireUser) {
                $latestResignationDate = $this->latestApprovedResignationDateForUser((int) $rehireUser->id);
                if ($latestResignationDate) {
                    $query->where(function ($innerQuery) use ($latestResignationDate) {
                        $innerQuery
                            ->whereNull('created_at')
                            ->orWhere('created_at', '>', $latestResignationDate);
                    });
                }
            })
            ->exists();

        if ($existingApplication) {
            return redirect()->back()
                ->withInput($this->safeApplicationOldInput($request))
                ->with('popup_error', 'You already submitted an application for this position using this email.');
        }

        $applicant = DB::transaction(function () use ($request, $attrs, $draftDocumentRefs, $primaryBachelor, $normalizedEducationLevels, $normalizedBachelorDegrees, $normalizedMasterDegrees, $normalizedDoctoralDegrees, $primaryMaster, $primaryDoctoral) {
            $normalizedEmail = Str::lower(trim((string) ($attrs['email'] ?? '')));
            $rehireUser = $this->findLatestResignedEmployeeByEmail($normalizedEmail);

            if ($rehireUser) {
                $this->releaseApplicantEmailForRehire($normalizedEmail);
            }

            $applicant_store = Applicant::create([
                'first_name' => $attrs['first_name'],
                'middle_name' => $attrs['middle_name'] ?? null,
                'last_name' => $attrs['last_name'],
                'name_extension' => $attrs['name_extension'] ?? null,
                'email' => $attrs['email'],
                'phone' => $attrs['phone'],
                'sex' => $attrs['sex'],
                'civil_status' => $attrs['civil_status'],
                'date_of_birth' => $attrs['date_of_birth'],
                'address' => $attrs['address'],
                'field_study' => $primaryBachelor['degree'],
                // Keep legacy columns populated using the first bachelor entry.
                'bachelor_degree' => $primaryBachelor['degree'],
                'bachelor_school_name' => $primaryBachelor['school_name'],
                'bachelor_year_finished' => $primaryBachelor['year_finished'],
                'master_degree' => $primaryMaster['degree'] ?? null,
                'master_school_name' => $primaryMaster['school_name'] ?? null,
                'master_year_finished' => $primaryMaster['year_finished'] ?? null,
                'doctoral_degree' => $primaryDoctoral['degree'] ?? null,
                'doctoral_school_name' => $primaryDoctoral['school_name'] ?? null,
                'doctoral_year_finished' => $primaryDoctoral['year_finished'] ?? null,
                'experience_years' => $attrs['experience_years'],
                'skills_n_expertise' => $attrs['key_skills'],
                'open_position_id' => $attrs['position'],
                'tracking_number' => $this->generateApplicationTrackingNumber(),
                'application_status' => 'pending',
                'fresh_graduate' => (bool) ($attrs['fresh_graduate'] ?? false),
                // Keep NOT NULL DB constraints satisfied for fresh graduates.
                'work_position' => !empty($attrs['fresh_graduate']) ? 'N/A' : ($attrs['work_position'] ?? 'N/A'),
                'work_employer' => !empty($attrs['fresh_graduate']) ? 'N/A' : ($attrs['work_employer'] ?? 'N/A'),
                'work_location' => !empty($attrs['fresh_graduate']) ? 'N/A' : ($attrs['work_location'] ?? 'N/A'),
                'work_duration' => !empty($attrs['fresh_graduate']) ? 'N/A' : ($attrs['work_duration'] ?? 'N/A'),
                'experience_years' => !empty($attrs['fresh_graduate']) ? '0-1' : $attrs['experience_years'],
            ]);

            foreach ($normalizedBachelorDegrees as $index => $degree) {
                ApplicantDegree::create([
                    'applicant_id' => $applicant_store->id,
                    'degree_level' => 'bachelor',
                    'degree_name' => $degree['degree'],
                    'school_name' => $degree['school_name'],
                    'year_finished' => $degree['year_finished'],
                    'sort_order' => $index,
                ]);
            }

            foreach ($normalizedEducationLevels as $index => $education) {
                ApplicantDegree::create([
                    'applicant_id' => $applicant_store->id,
                    'degree_level' => $education['key'],
                    'degree_name' => $education['level'],
                    'school_name' => $education['school_name'],
                    'year_finished' => $education['year_finished'],
                    'sort_order' => $index,
                ]);
            }

            foreach ($normalizedMasterDegrees as $index => $degree) {
                ApplicantDegree::create([
                    'applicant_id' => $applicant_store->id,
                    'degree_level' => 'master',
                    'degree_name' => $degree['degree'],
                    'school_name' => $degree['school_name'],
                    'year_finished' => $degree['year_finished'],
                    'sort_order' => $index,
                ]);
            }

            foreach ($normalizedDoctoralDegrees as $index => $degree) {
                ApplicantDegree::create([
                    'applicant_id' => $applicant_store->id,
                    'degree_level' => 'doctorate',
                    'degree_name' => $degree['degree'],
                    'school_name' => $degree['school_name'],
                    'year_finished' => $degree['year_finished'],
                    'sort_order' => $index,
                ]);
            }

            $educationLevel = fn (string $key) => $normalizedEducationLevels->firstWhere('key', $key);
            $elementary = $educationLevel('elementary');
            $secondary = $educationLevel('secondary');
            $vocationalTrade = $educationLevel('vocational_trade');
            $college = $educationLevel('college');

            $educationPayload = [
                'elementary_school_name' => $elementary['school_name'] ?? null,
                'elementary_year_finished' => $elementary['year_finished'] ?? null,
                'secondary_school_name' => $secondary['school_name'] ?? null,
                'secondary_year_finished' => $secondary['year_finished'] ?? null,
                'vocational_trade_school_name' => $vocationalTrade['school_name'] ?? null,
                'vocational_trade_year_finished' => $vocationalTrade['year_finished'] ?? null,
                'college_school_name' => $college['school_name'] ?? null,
                'college_year_finished' => $college['year_finished'] ?? null,
                'bachelor' => $primaryBachelor['degree'],
                'master' => $primaryMaster['degree'] ?? '',
                'doctorate' => $primaryDoctoral['degree'] ?? '',
            ];

            if (Schema::hasColumn('education', 'applicant_id')) {
                $educationValues = $educationPayload;

                if (Schema::hasColumn('education', 'user_id')) {
                    $userId = $applicant_store->user_id ? (int) $applicant_store->user_id : null;

                    if ($userId !== null || $this->educationUserIdAllowsNull()) {
                        $educationValues['user_id'] = $userId;
                    } else {
                        $educationValues = null;
                    }
                }

                if ($educationValues !== null) {
                    Education::updateOrCreate(
                        ['applicant_id' => (int) $applicant_store->id],
                        $educationValues
                    );
                }
            }

            if (!empty($attrs['pds_record_id'])) {
                $pdsPayload = $this->filterPayloadForTableColumns('pds_table', $this->buildPdsApplicationPayload(
                    $attrs,
                    $applicant_store->id,
                    $normalizedEducationLevels,
                    $normalizedMasterDegrees,
                    $normalizedDoctoralDegrees
                ));

                if ($pdsPayload !== []) {
                    DB::table('pds_table')
                        ->where('id', $attrs['pds_record_id'])
                        ->whereNull('applicant_id')
                        ->update($pdsPayload);
                }
            }

            foreach ((array) $request->input('documents', []) as $index => $docMeta) {
                $type = $docMeta['type'] ?? null;
                $draftFiles = $draftDocumentRefs[(string) $index] ?? [];
                if ($type && $draftFiles) {
                    foreach ($draftFiles as $file) {
                        if (($file['type'] ?? '') === '') {
                            $file['type'] = $type;
                        }

                        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($file['filepath'])) {
                            continue;
                        }

                        ApplicantDocument::create([
                            'applicant_id' => $applicant_store->id,
                            'type'         => $file['type'],
                            'filename'     => $file['filename'],
                            'filepath'     => $file['filepath'],
                            'mime_type'    => $file['mime_type'],
                            'size'         => $file['size'],
                        ]);
                    }

                    continue;
                }

                $uploadedFiles = $request->file("documents.$index.file");

                if (!$type || !$uploadedFiles) {
                    continue;
                }

                foreach (is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles] as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();

                    $file->storeAs('uploads', $fileName, 'public');

                    ApplicantDocument::create([
                        'applicant_id' => $applicant_store->id,
                        'type'         => $type,
                        'filename'     => $file->getClientOriginalName(),
                        'filepath'     => 'uploads/' . $fileName,
                        'mime_type'    => $file->getMimeType(),
                        'size'         => $file->getSize(),
                    ]);
                }
            }

            return $applicant_store->loadMissing('position');
        });

        try {
            Mail::to($applicant->email)->queue(new ApplicationTrackingNumberMail($applicant));
        } catch (\Throwable $exception) {
            Log::warning('Application submitted but tracking number email could not be queued.', [
                'applicant_id' => $applicant->id ?? null,
                'email' => $applicant->email ?? null,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('guest.index')
            ->with('success', 'Submitted successfully')
            ->with('application_review_email', $attrs['email'])
            ->with('application_tracking_number', $applicant->tracking_number)
            ->with('show_rating_modal', true);
    }

    private function generateApplicationTrackingNumber(): string
    {
        do {
            $trackingNumber = 'APP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Applicant::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    private function filterPayloadForTableColumns(string $table, array $payload): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $columns);
    }

    private function safeApplicationOldInput(Request $request): array
    {
        return collect($request->except([
            '_token',
            'pds_file',
            'documents',
            'draft_documents',
        ]))->map(function ($value) {
            if (is_string($value)) {
                return Str::limit($value, 1000, '');
            }

            return $value;
        })->all();
    }

    private function extractPdsText(string $path, string $extension): string
    {
        return match ($extension) {
            'xlsx', 'xlsm' => collect($this->extractXlsxRows($path))->map(fn ($row) => implode("\t", $row))->implode("\n"),
            'xls' => $this->extractBinarySpreadsheetText($path),
            'csv' => collect($this->extractCsvRows($path))->map(fn ($row) => implode("\t", $row))->implode("\n"),
            default => '',
        };
    }

    private function buildPdsApplicationPayload(
        array $attrs,
        int $applicantId,
        $normalizedEducationLevels,
        $normalizedMasterDegrees,
        $normalizedDoctoralDegrees
    ): array {
        $educationText = function (string $key) use ($normalizedEducationLevels) {
            $education = collect($normalizedEducationLevels)->firstWhere('key', $key);
            if (!$education) {
                return null;
            }

            return $this->cleanPdsValue(collect([
                $education['school_name'] ?? null,
                $education['year_finished'] ?? null,
            ])->filter()->implode(' - '));
        };

        $graduateStudies = collect()
            ->merge($normalizedMasterDegrees)
            ->merge($normalizedDoctoralDegrees)
            ->map(function ($degree) {
                return collect([
                    $degree['degree'] ?? null,
                    $degree['school_name'] ?? null,
                    $degree['year_finished'] ?? null,
                ])->filter()->implode(' - ');
            })
            ->filter()
            ->implode('; ');
        $collegeEducation = collect($normalizedEducationLevels)->firstWhere('key', 'college');

        return [
            'applicant_id' => $applicantId,
            'surname' => $attrs['last_name'] ?? null,
            'first_name' => $attrs['first_name'] ?? null,
            'middle_name' => $attrs['middle_name'] ?? null,
            'name_extension' => $attrs['name_extension'] ?? null,
            'date_of_birth' => $attrs['date_of_birth'] ?? null,
            'sex' => $attrs['sex'] ?? null,
            'civil_status' => $attrs['civil_status'] ?? null,
            'permanent_address' => $attrs['address'] ?? null,
            'zip_code' => null,
            'mobile_no' => $attrs['phone'] ?? null,
            'email_address' => $attrs['email'] ?? null,
            'elementary' => $educationText('elementary'),
            'secondary' => $educationText('secondary'),
            'vocational_trade_course' => $educationText('vocational_trade'),
            'college_school_name' => $collegeEducation['school_name'] ?? null,
            'college_year_graduated' => $collegeEducation['year_finished'] ?? null,
            'graduate_studies' => $this->cleanPdsValue($graduateStudies),
        ];
    }

    private function separatePdsPermanentAddressZipCode(array $pdsData): array
    {
        $address = $this->cleanPdsPermanentAddress((string) ($pdsData['permanent_address'] ?? ''));
        $pdsData['permanent_address'] = $address;

        if (blank($address) || filled($pdsData['zip_code'] ?? null)) {
            return $pdsData;
        }

        if (preg_match('/^(.*?)(?:\s+)?(?:zip\s*code\s*)?(\d{4})(?:\D*)$/i', $address, $matches)) {
            $pdsData['permanent_address'] = trim($matches[1]) ?: null;
            $pdsData['zip_code'] = trim($matches[2]);
        }

        return $pdsData;
    }

    private function completePdsPermanentAddress(array $pdsData): ?string
    {
        $address = $this->cleanPdsPermanentAddress(collect([
            $pdsData['permanent_address'] ?? null,
            $pdsData['zip_code'] ?? null,
        ])->filter()->implode(' '));

        return $address && $this->looksLikePdsCitizenshipCountry($address) ? null : $address;
    }

    private function appendPdsEducationResponseFields(array $pdsData, array $rows): array
    {
        $educationRows = [
            'elementary' => ['row' => 54, 'labels' => ['elementary'], 'source' => 'elementary'],
            'secondary' => ['row' => 55, 'labels' => ['secondary'], 'source' => 'secondary'],
            'vocational_trade' => ['row' => 56, 'labels' => ['vocational', 'trade course'], 'source' => 'vocational_trade_course'],
            'college' => ['row' => 57, 'labels' => ['college'], 'source' => null],
            'graduate_studies' => ['row' => 58, 'labels' => ['graduate studies'], 'source' => 'graduate_studies'],
        ];

        $education = [];
        foreach ($educationRows as $key => $config) {
            $rowData = $this->extractOfficialPdsEducationRow($rows, $config['row'], $config['labels']);
            if (!$rowData && $config['source']) {
                $rowData = $this->parsePdsEducationSummary((string) ($pdsData[$config['source']] ?? ''));
            }

            $education[$key] = $rowData ?: ['school_name' => null, 'degree' => null, 'year_graduated' => null];
        }

        foreach (['elementary', 'secondary', 'vocational_trade'] as $key) {
            $pdsData[$key.'_school_name'] = $education[$key]['school_name'] ?? null;
            $pdsData[$key.'_year_graduated'] = $education[$key]['year_graduated'] ?? null;
        }

        $pdsData['college_school_name'] = $education['college']['school_name'] ?? null;
        $pdsData['college_degree'] = $education['college']['degree'] ?? null;
        $pdsData['college_year_graduated'] = $education['college']['year_graduated'] ?? null;
        $pdsData['graduate_studies_school_name'] = $education['graduate_studies']['school_name'] ?? null;
        $pdsData['graduate_studies_degree'] = $education['graduate_studies']['degree'] ?? null;
        $pdsData['graduate_studies_year_graduated'] = $education['graduate_studies']['year_graduated'] ?? null;

        return $pdsData;
    }

    private function extractOfficialPdsEducationRow(array $rows, int $excelRowNumber, array $labels): ?array
    {
        foreach ($rows as $row) {
            $rowText = $this->normalizePdsLabel(implode(' ', array_map('strval', $row)));
            $firstValue = trim((string) ($row[0] ?? ''));
            $matchesRow = ((int) $firstValue === $excelRowNumber)
                || collect($labels)->contains(fn ($label) => str_contains($rowText, $this->normalizePdsLabel($label)));

            if (!$matchesRow) {
                continue;
            }

            $values = array_values(array_filter(array_map('strval', $row), fn ($value) => trim($value) !== ''));
            $labelIndex = null;
            foreach ($values as $index => $value) {
                $normalizedValue = $this->normalizePdsLabel($value);
                if (collect($labels)->contains(fn ($label) => str_contains($normalizedValue, $this->normalizePdsLabel($label)))) {
                    $labelIndex = $index;
                    break;
                }
            }

            $afterLabel = $labelIndex === null ? [] : array_slice($values, $labelIndex + 1);
            $afterLabel = array_values(array_filter(array_map(
                fn ($value) => $this->cleanPdsEducationCell((string) $value),
                $afterLabel
            )));
            $schoolName = $this->cleanPdsEducationCell((string) ($afterLabel[0] ?? ''));
            $year = $this->extractPdsGraduationYear($this->officialPdsCellValueFromNormalizedRow($row, 'M'))
                ?: $this->extractPdsGraduationYear(implode(' ', $afterLabel))
                ?: $this->extractPdsGraduationYear(implode(' ', array_map('strval', $row)));
            $degree = null;
            foreach (array_slice($afterLabel, 1) as $candidate) {
                if ($this->extractPdsGraduationYear($candidate) || $this->looksLikePdsCountryListValue($candidate)) {
                    continue;
                }

                $degree = $this->cleanPdsEducationCell($candidate);
                break;
            }

            if (!$schoolName && !$degree && !$year) {
                continue;
            }

            return [
                'school_name' => $schoolName,
                'degree' => $degree,
                'year_graduated' => $year,
            ];
        }

        return null;
    }

    private function officialPdsCellValueFromNormalizedRow(array $row, string $column): string
    {
        $columnIndex = $this->spreadsheetColumnIndex($column);

        return trim((string) ($row[$columnIndex] ?? ''));
    }

    private function looksLikePdsCountryListValue(string $value): bool
    {
        $normalized = $this->normalizePdsLabel(str_replace([',', "'"], ' ', $value));

        return in_array($normalized, [
            'congo republic of the',
            'costa rica',
            'cote d ivoire',
            'croatia',
            'cuba',
            'curacao',
        ], true);
    }

    private function parsePdsEducationSummary(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $year = $this->extractPdsGraduationYear($value);
        $schoolName = $this->cleanPdsEducationCell(preg_replace('/\s*-\s*\d{4}(?:\s*-\s*\d{4})?\s*$/', '', $value) ?? $value);

        return [
            'school_name' => $schoolName,
            'degree' => null,
            'year_graduated' => $year,
        ];
    }

    private function extractPdsGraduationYear(string $value): ?string
    {
        if (!preg_match_all('/(?:19|20)\d{2}/', $value, $matches) || empty($matches[0])) {
            return null;
        }

        return (string) end($matches[0]);
    }

    private function extractPdsRows(string $path, string $extension): array
    {
        return match ($extension) {
            'xlsx', 'xlsm' => $this->extractXlsxRows($path),
            'csv' => $this->extractCsvRows($path),
            default => [],
        };
    }

    private function debugPdsWorkbook(string $path, string $extension): array
    {
        if ($extension === 'csv') {
            $rows = $this->extractCsvRows($path);

            return [
                'summary' => 'CSV rows='.count($rows).', first_row="'.Str::limit(implode(' | ', $rows[0] ?? []), 120).'"',
                'extension' => $extension,
                'worksheet_count' => 0,
                'cell_count' => collect($rows)->flatten()->filter()->count(),
                'samples' => array_slice($rows[0] ?? [], 0, 8),
            ];
        }

        if (!in_array($extension, ['xlsx', 'xlsm'], true)) {
            return [
                'summary' => 'extension='.$extension.', xlsx_reader=not_xlsx',
                'extension' => $extension,
                'worksheet_count' => 0,
                'cell_count' => 0,
                'samples' => [],
            ];
        }

        if (!class_exists(\ZipArchive::class)) {
            $worksheets = $this->extractXlsxWorksheetXmlFilesWithPowerShell($path);
            $sharedStrings = $this->extractXlsxSharedStringsWithPowerShell($path);
            $worksheetCount = count($worksheets);
            $cellCount = 0;
            $samples = [];

            foreach ($worksheets as $sheetXml) {
                $cells = $this->readXlsxCellsByReference($sheetXml, $sharedStrings);
                $cellCount += count(array_filter($cells, fn ($value) => trim((string) $value) !== ''));

                foreach ($cells as $reference => $value) {
                    $value = trim((string) $value);
                    if ($value === '') {
                        continue;
                    }

                    $samples[] = $reference.'='.$value;
                    if (count($samples) >= 12) {
                        break 2;
                    }
                }
            }

            return [
                'summary' => 'extension='.$extension.', reader=powershell, worksheets='.$worksheetCount.', non_empty_cells='.$cellCount.', samples="'.Str::limit(implode(' | ', $samples), 180).'"',
                'extension' => $extension,
                'worksheet_count' => $worksheetCount,
                'cell_count' => $cellCount,
                'samples' => $samples,
            ];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [
                'summary' => 'xlsx_zip=open_failed',
                'extension' => $extension,
                'worksheet_count' => 0,
                'cell_count' => 0,
                'samples' => [],
            ];
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $worksheetCount = 0;
        $cellCount = 0;
        $samples = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (!preg_match('/^xl\/worksheets\/sheet\d+\.xml$/', $name)) {
                continue;
            }

            $worksheetCount++;
            $xml = $zip->getFromName($name) ?: '';
            $cells = $this->readXlsxCellsByReference($xml, $sharedStrings);
            $cellCount += count(array_filter($cells, fn ($value) => trim((string) $value) !== ''));

            foreach ($cells as $reference => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $samples[] = $reference.'='.$value;
                if (count($samples) >= 12) {
                    break 2;
                }
            }
        }

        $zip->close();

        return [
            'summary' => 'extension='.$extension.', worksheets='.$worksheetCount.', non_empty_cells='.$cellCount.', samples="'.Str::limit(implode(' | ', $samples), 180).'"',
            'extension' => $extension,
            'worksheet_count' => $worksheetCount,
            'cell_count' => $cellCount,
            'samples' => $samples,
        ];
    }

    private function extractXlsxRows(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return $this->extractXlsxRowsWithPowerShell($path);
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml') ?: '';
        if ($sharedStringsXml !== '') {
            preg_match_all('/<(?:\w+:)?si[^>]*>(.*?)<\/(?:\w+:)?si>/su', $sharedStringsXml, $matches);
            $sharedStrings = collect($matches[1] ?? [])
                ->map(function ($sharedStringXml) {
                    preg_match_all('/<(?:\w+:)?t[^>]*>(.*?)<\/(?:\w+:)?t>/su', $sharedStringXml, $textMatches);

                    return html_entity_decode(
                        collect($textMatches[1] ?? [])->implode(''),
                        ENT_QUOTES | ENT_XML1,
                        'UTF-8'
                    );
                })
                ->values()
                ->all();
        }

        $sheetRows = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (!preg_match('/^xl\/worksheets\/sheet\d+\.xml$/', $name)) {
                continue;
            }

            $xml = $zip->getFromName($name) ?: '';
            if ($xml === '') {
                continue;
            }

            preg_match_all('/<(?:\w+:)?row[^>]*>(.*?)<\/(?:\w+:)?row>/su', $xml, $rows);
            foreach ($rows[1] ?? [] as $rowXml) {
                preg_match_all('/<(?:\w+:)?c\b([^>]*)>(.*?)<\/(?:\w+:)?c>/su', $rowXml, $cells, PREG_SET_ORDER);

                $rowValues = [];
                foreach ($cells as $cell) {
                    $attributes = $cell[1] ?? '';
                    $column = 0;
                    if (preg_match('/\br="([A-Z]+)\d+"/', $attributes, $referenceMatch)) {
                        $column = $this->spreadsheetColumnIndex($referenceMatch[1]);
                    }

                    $rowValues[$column] = trim((string) (function () use ($cell, $sharedStrings) {
                        $attributes = $cell[1] ?? '';
                        $body = $cell[2] ?? '';

                        if (str_contains($attributes, 't="s"') && preg_match('/<(?:\w+:)?v>(.*?)<\/(?:\w+:)?v>/su', $body, $valueMatch)) {
                            return $sharedStrings[(int) $valueMatch[1]] ?? '';
                        }

                        if (preg_match_all('/<(?:\w+:)?t[^>]*>(.*?)<\/(?:\w+:)?t>/su', $body, $inlineMatches)) {
                            return html_entity_decode(
                                collect($inlineMatches[1] ?? [])->implode(''),
                                ENT_QUOTES | ENT_XML1,
                                'UTF-8'
                            );
                        }

                        if (preg_match('/<(?:\w+:)?v>(.*?)<\/(?:\w+:)?v>/su', $body, $valueMatch)) {
                            return html_entity_decode(strip_tags($valueMatch[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
                        }

                        return '';
                    })());
                }

                if ($rowValues) {
                    ksort($rowValues);
                    $maxColumn = max(array_keys($rowValues));
                    $normalizedRow = [];
                    for ($column = 0; $column <= $maxColumn; $column++) {
                        $normalizedRow[$column] = $rowValues[$column] ?? '';
                    }

                    if (collect($normalizedRow)->contains(fn ($value) => trim((string) $value) !== '')) {
                        $sheetRows[] = $normalizedRow;
                    }
                }
            }
        }

        $zip->close();

        return $sheetRows;
    }

    private function extractCsvRows(string $path): array
    {
        $handle = @fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $line = collect($row)
                ->map(fn ($value) => trim((string) $value))
                ->all();

            if (collect($line)->contains(fn ($value) => $value !== '')) {
                $rows[] = $line;
            }
        }
        fclose($handle);

        return $rows;
    }

    private function extractXlsxRowsWithPowerShell(string $path): array
    {
        $sharedStrings = $this->extractXlsxSharedStringsWithPowerShell($path);
        $worksheets = $this->extractXlsxWorksheetXmlFilesWithPowerShell($path);
        $sheetRows = [];

        foreach ($worksheets as $xml) {
            preg_match_all('/<(?:\w+:)?row[^>]*>(.*?)<\/(?:\w+:)?row>/su', $xml, $rows);
            foreach ($rows[1] ?? [] as $rowXml) {
                preg_match_all('/<(?:\w+:)?c\b([^>]*)>(.*?)<\/(?:\w+:)?c>/su', $rowXml, $cells, PREG_SET_ORDER);

                $rowValues = [];
                foreach ($cells as $cell) {
                    $attributes = $cell[1] ?? '';
                    $column = 0;
                    if (preg_match('/\br="([A-Z]+)\d+"/', $attributes, $referenceMatch)) {
                        $column = $this->spreadsheetColumnIndex($referenceMatch[1]);
                    }

                    $rowValues[$column] = trim($this->readXlsxCellValue($attributes, $cell[2] ?? '', $sharedStrings));
                }

                if ($rowValues) {
                    ksort($rowValues);
                    $maxColumn = max(array_keys($rowValues));
                    $normalizedRow = [];
                    for ($column = 0; $column <= $maxColumn; $column++) {
                        $normalizedRow[$column] = $rowValues[$column] ?? '';
                    }

                    if (collect($normalizedRow)->contains(fn ($value) => trim((string) $value) !== '')) {
                        $sheetRows[] = $normalizedRow;
                    }
                }
            }
        }

        return $sheetRows;
    }

    private function extractXlsxSharedStringsWithPowerShell(string $path): array
    {
        $expandedPath = $this->expandXlsxWithPowerShell($path);
        if (!$expandedPath) {
            return [];
        }

        $sharedStringsPath = $expandedPath.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'sharedStrings.xml';
        if (!is_file($sharedStringsPath)) {
            $this->deleteDirectory($expandedPath);
            return [];
        }

        $sharedStringsXml = (string) file_get_contents($sharedStringsPath);
        $this->deleteDirectory($expandedPath);

        preg_match_all('/<(?:\w+:)?si[^>]*>(.*?)<\/(?:\w+:)?si>/su', $sharedStringsXml, $matches);

        return collect($matches[1] ?? [])
            ->map(function ($sharedStringXml) {
                preg_match_all('/<(?:\w+:)?t[^>]*>(.*?)<\/(?:\w+:)?t>/su', $sharedStringXml, $textMatches);

                return html_entity_decode(
                    collect($textMatches[1] ?? [])->implode(''),
                    ENT_QUOTES | ENT_XML1,
                    'UTF-8'
                );
            })
            ->values()
            ->all();
    }

    private function extractXlsxWorksheetXmlFilesWithPowerShell(string $path): array
    {
        $expandedPath = $this->expandXlsxWithPowerShell($path);
        if (!$expandedPath) {
            return [];
        }

        $worksheetDir = $expandedPath.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets';
        if (!is_dir($worksheetDir)) {
            $this->deleteDirectory($expandedPath);
            return [];
        }

        $worksheets = [];
        foreach (glob($worksheetDir.DIRECTORY_SEPARATOR.'sheet*.xml') ?: [] as $worksheetPath) {
            $worksheets[] = (string) file_get_contents($worksheetPath);
        }

        $this->deleteDirectory($expandedPath);

        return $worksheets;
    }

    private function expandXlsxWithPowerShell(string $path): ?string
    {
        $baseDir = storage_path('app'.DIRECTORY_SEPARATOR.'pds-xlsx');
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $destination = $baseDir.DIRECTORY_SEPARATOR.(string) Str::uuid();
        mkdir($destination, 0775, true);
        $archivePath = $destination.'.zip';
        if (!copy($path, $archivePath)) {
            $this->deleteDirectory($destination);
            return null;
        }

        $command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "Expand-Archive -LiteralPath '
            .$this->quotePowerShellString($archivePath)
            .' -DestinationPath '
            .$this->quotePowerShellString($destination)
            .' -Force"';

        @exec($command, $output, $exitCode);
        @unlink($archivePath);
        if ($exitCode !== 0) {
            $this->deleteDirectory($destination);
            return null;
        }

        return $destination;
    }

    private function quotePowerShellString(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $path.DIRECTORY_SEPARATOR.$item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($path);
    }

    private function extractOfficialPdsCoordinateData(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            $comData = $this->extractOfficialPdsDataWithExcelCom($path);
            if (collect($comData)->filter()->isNotEmpty()) {
                return $comData;
            }

            $sharedStrings = $this->extractXlsxSharedStringsWithPowerShell($path);
            $worksheets = $this->extractXlsxWorksheetXmlFilesWithPowerShell($path);
            return $this->extractBestOfficialPdsDataFromWorksheets($worksheets, $sharedStrings);
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $worksheets = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (preg_match('/^xl\/worksheets\/sheet\d+\.xml$/', $name)) {
                $worksheets[] = $zip->getFromName($name) ?: '';
            }
        }
        $zip->close();

        return $this->extractBestOfficialPdsDataFromWorksheets($worksheets, $sharedStrings);
    }

    private function extractOfficialPdsCheckboxData(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return $this->extractOfficialPdsCheckboxDataWithPowerShell($path);
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $data = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $sheetName = $zip->getNameIndex($index);
            if (!preg_match('/^xl\/worksheets\/sheet\d+\.xml$/', $sheetName)) {
                continue;
            }

            $sheetXml = $zip->getFromName($sheetName) ?: '';
            $relsName = 'xl/worksheets/_rels/'.basename($sheetName).'.rels';
            $relsXml = $zip->getFromName($relsName) ?: '';
            if ($sheetXml === '' || $relsXml === '') {
                continue;
            }

            $data = array_merge($data, $this->extractCheckedPdsOptionsFromSheetControls(
                $sheetXml,
                $relsXml,
                fn (string $target) => $zip->getFromName($this->resolveSpreadsheetPackagePath('xl/worksheets', $target)) ?: ''
            ));

            preg_match_all('/<(?:\w+:)?Relationship\b([^>]*)\/?>/su', $relsXml, $relationships, PREG_SET_ORDER);
            foreach ($relationships as $relationship) {
                $attributes = $relationship[1] ?? '';
                if (!str_contains($attributes, '/vmlDrawing')) {
                    continue;
                }

                $target = $this->readXmlAttribute($attributes, 'Target');
                if (!$target) {
                    continue;
                }

                $vmlPath = $this->resolveSpreadsheetPackagePath('xl/worksheets', $target);
                $vmlXml = $zip->getFromName($vmlPath) ?: '';
                if ($vmlXml === '') {
                    continue;
                }

                $data = array_merge($data, $this->extractCheckedPdsOptionsFromVml($vmlXml));
                if (filled($data['sex'] ?? null) && filled($data['civil_status'] ?? null)) {
                    $zip->close();
                    return $data;
                }
            }
        }

        $zip->close();

        return $data;
    }

    private function extractOfficialPdsCheckboxDataWithPowerShell(string $path): array
    {
        $expandedPath = $this->expandXlsxWithPowerShell($path);
        if (!$expandedPath) {
            return [];
        }

        try {
            $worksheetRelsDir = $expandedPath.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets'.DIRECTORY_SEPARATOR.'_rels';
            if (!is_dir($worksheetRelsDir)) {
                return [];
            }

            $data = [];
            foreach (glob($worksheetRelsDir.DIRECTORY_SEPARATOR.'sheet*.xml.rels') ?: [] as $relsPath) {
                $relsXml = (string) file_get_contents($relsPath);
                $sheetPath = dirname($worksheetRelsDir).DIRECTORY_SEPARATOR
                    .preg_replace('/\.rels$/', '', basename($relsPath));
                $sheetXml = is_file($sheetPath) ? (string) file_get_contents($sheetPath) : '';

                if ($sheetXml !== '') {
                    $data = array_merge($data, $this->extractCheckedPdsOptionsFromSheetControls(
                        $sheetXml,
                        $relsXml,
                        function (string $target) use ($expandedPath) {
                            $relativePath = $this->resolveSpreadsheetPackagePath('xl/worksheets', $target);
                            $path = $expandedPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

                            return is_file($path) ? (string) file_get_contents($path) : '';
                        }
                    ));
                }

                preg_match_all('/<(?:\w+:)?Relationship\b([^>]*)\/?>/su', $relsXml, $relationships, PREG_SET_ORDER);

                foreach ($relationships as $relationship) {
                    $attributes = $relationship[1] ?? '';
                    if (!str_contains($attributes, '/vmlDrawing')) {
                        continue;
                    }

                    $target = $this->readXmlAttribute($attributes, 'Target');
                    if (!$target) {
                        continue;
                    }

                    $relativeVmlPath = $this->resolveSpreadsheetPackagePath('xl/worksheets', $target);
                    $vmlPath = $expandedPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeVmlPath);
                    if (!is_file($vmlPath)) {
                        continue;
                    }

                    $data = array_merge($data, $this->extractCheckedPdsOptionsFromVml((string) file_get_contents($vmlPath)));
                    if (filled($data['sex'] ?? null) && filled($data['civil_status'] ?? null)) {
                        return $data;
                    }
                }
            }

            return $data;
        } finally {
            $this->deleteDirectory($expandedPath);
        }
    }

    private function extractCheckedPdsOptionsFromVml(string $vmlXml): array
    {
        preg_match_all('/<(?:\w+:)?shape\b[^>]*>.*?<\/(?:\w+:)?shape>/isu', $vmlXml, $matches);

        $data = [];
        foreach ($matches[0] ?? [] as $shapeXml) {
            if (!preg_match('/<(?:\w+:)?ClientData\b[^>]*ObjectType=["\']Checkbox["\'][^>]*>(.*?)<\/(?:\w+:)?ClientData>/isu', $shapeXml, $clientDataMatch)) {
                continue;
            }

            $clientDataXml = $clientDataMatch[1] ?? '';
            if (!$this->vmlCheckboxIsChecked($clientDataXml)) {
                continue;
            }

            $choice = $this->pdsCheckboxChoiceFromVmlText($shapeXml);
            if ($choice) {
                $data[$choice[0]] = $choice[1];
                continue;
            }

            if (!preg_match('/<(?:\w+:)?Anchor[^>]*>(.*?)<\/(?:\w+:)?Anchor>/isu', $clientDataXml, $anchorMatch)) {
                continue;
            }

            $anchor = array_map('intval', preg_split('/\s*,\s*/', trim($anchorMatch[1])) ?: []);
            if (count($anchor) < 3) {
                continue;
            }

            $startColumn = ($anchor[0] ?? 0) + 1;
            $endColumn = ($anchor[4] ?? $anchor[0] ?? 0) + 1;
            $startRow = ($anchor[2] ?? 0) + 1;
            $endRow = ($anchor[6] ?? $anchor[2] ?? 0) + 1;
            $column = (int) round(($startColumn + $endColumn) / 2);
            $row = (int) round(($startRow + $endRow) / 2);
            $choice = $this->pdsCheckboxChoiceFromPosition($row, $column);
            if ($choice) {
                $data[$choice[0]] = $choice[1];
            }
        }

        return $data;
    }

    private function pdsCheckboxChoiceFromVmlText(string $clientDataXml): ?array
    {
        $text = $this->normalizePdsLabel(html_entity_decode(strip_tags($clientDataXml), ENT_QUOTES | ENT_XML1, 'UTF-8'));

        foreach ([
            'sex' => ['Male', 'Female'],
            'civil_status' => ['Single', 'Married', 'Widowed', 'Separated'],
        ] as $field => $choices) {
            foreach ($choices as $choice) {
                if (preg_match('/\b'.preg_quote($this->normalizePdsLabel($choice), '/').'\b/u', $text)) {
                    return [$field, $choice];
                }
            }
        }

        return null;
    }

    private function extractCheckedPdsOptionsFromSheetControls(string $sheetXml, string $relsXml, callable $readPackageTarget): array
    {
        $relationships = $this->extractSpreadsheetRelationships($relsXml);
        $shapePositions = [];

        foreach ($relationships as $relationship) {
            if (!str_contains($relationship['type'], '/drawing')) {
                continue;
            }

            $drawingXml = $readPackageTarget($relationship['target']);
            if ($drawingXml !== '') {
                $shapePositions += $this->extractDrawingShapePositions($drawingXml);
            }
        }

        preg_match_all('/<(?:\w+:)?control\b([^>]*)\/?>/su', $sheetXml, $controls, PREG_SET_ORDER);

        $data = [];
        foreach ($controls as $control) {
            $attributes = $control[1] ?? '';
            $shapeId = (int) ($this->readXmlAttribute($attributes, 'shapeId') ?? 0);
            $relationshipId = $this->readXmlAttribute($attributes, 'r:id');
            if (!$shapeId || !$relationshipId || !isset($relationships[$relationshipId])) {
                continue;
            }

            $controlXml = $readPackageTarget($relationships[$relationshipId]['target']);
            if (!$this->controlPropertyIsChecked($controlXml)) {
                continue;
            }

            $position = $shapePositions[$shapeId] ?? null;
            if (!$position) {
                continue;
            }

            $choice = $this->pdsCheckboxChoiceFromPosition($position['row'], $position['column']);
            if ($choice) {
                $data[$choice[0]] = $choice[1];
            }
        }

        return $data;
    }

    private function extractSpreadsheetRelationships(string $relsXml): array
    {
        preg_match_all('/<(?:\w+:)?Relationship\b([^>]*)\/?>/su', $relsXml, $matches, PREG_SET_ORDER);

        $relationships = [];
        foreach ($matches as $match) {
            $attributes = $match[1] ?? '';
            $id = $this->readXmlAttribute($attributes, 'Id');
            $target = $this->readXmlAttribute($attributes, 'Target');
            if (!$id || !$target) {
                continue;
            }

            $relationships[$id] = [
                'target' => $target,
                'type' => (string) $this->readXmlAttribute($attributes, 'Type'),
            ];
        }

        return $relationships;
    }

    private function extractDrawingShapePositions(string $drawingXml): array
    {
        preg_match_all('/<(?:\w+:)?(?:oneCellAnchor|twoCellAnchor)\b[^>]*>(.*?)<\/(?:\w+:)?(?:oneCellAnchor|twoCellAnchor)>/su', $drawingXml, $anchors);

        $positions = [];
        foreach ($anchors[1] ?? [] as $anchorXml) {
            if (!preg_match('/<(?:\w+:)?from>\s*<(?:\w+:)?col>(\d+)<\/(?:\w+:)?col>.*?<(?:\w+:)?row>(\d+)<\/(?:\w+:)?row>/su', $anchorXml, $fromMatch)) {
                continue;
            }

            if (!preg_match('/<(?:\w+:)?cNvPr\b([^>]*)/su', $anchorXml, $shapeMatch)) {
                continue;
            }

            $shapeId = (int) ($this->readXmlAttribute($shapeMatch[1], 'id') ?? 0);
            if (!$shapeId) {
                continue;
            }

            $positions[$shapeId] = [
                'column' => ((int) $fromMatch[1]) + 1,
                'row' => ((int) $fromMatch[2]) + 1,
            ];
        }

        return $positions;
    }

    private function controlPropertyIsChecked(string $controlXml): bool
    {
        if ($controlXml === '') {
            return false;
        }

        if (preg_match('/\bchecked=(["\'])(.*?)\1/isu', $controlXml, $match)) {
            return in_array(Str::lower(trim($match[2])), ['checked', 'true', '1', 'yes'], true);
        }

        if (preg_match('/<(?:\w+:)?checked\b[^>]*>(.*?)<\/(?:\w+:)?checked>/isu', $controlXml, $match)) {
            return in_array(Str::lower(trim(strip_tags($match[1] ?? ''))), ['checked', 'true', '1', 'yes'], true);
        }

        return false;
    }

    private function vmlCheckboxIsChecked(string $clientDataXml): bool
    {
        if (preg_match('/<(?:\w+:)?Checked\b[^>]*\/>/iu', $clientDataXml)) {
            return true;
        }

        if (preg_match('/<(?:\w+:)?Checked\b[^>]*>(.*?)<\/(?:\w+:)?Checked>/isu', $clientDataXml, $match)) {
            $value = Str::lower(trim(strip_tags($match[1] ?? '')));

            return $value === '' || in_array($value, ['1', 'true', 'checked', 'yes'], true);
        }

        return false;
    }

    private function pdsCheckboxChoiceFromPosition(int $row, int $column, bool $includeCivilStatus = true): ?array
    {
        if ($row === 16) {
            return ['sex', $column <= 5 ? 'Male' : 'Female'];
        }

        if (!$includeCivilStatus) {
            return null;
        }

        if ($row === 17) {
            return ['civil_status', $column <= 5 ? 'Single' : 'Married'];
        }

        if ($row === 18) {
            return ['civil_status', $column <= 5 ? 'Widowed' : 'Separated'];
        }

        return null;
    }

    private function readXmlAttribute(string $attributes, string $name): ?string
    {
        if (!preg_match('/\b'.preg_quote($name, '/').'=(["\'])(.*?)\1/su', $attributes, $match)) {
            return null;
        }

        return html_entity_decode($match[2], ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function resolveSpreadsheetPackagePath(string $baseDirectory, string $target): string
    {
        $parts = explode('/', trim(str_replace('\\', '/', $baseDirectory.'/'.$target), '/'));
        $resolved = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($resolved);
                continue;
            }

            $resolved[] = $part;
        }

        return implode('/', $resolved);
    }

    private function extractBestOfficialPdsDataFromWorksheets(array $worksheets, array $sharedStrings): array
    {
        $bestData = [];
        $bestScore = 0;
        foreach ($worksheets as $sheetXml) {
            if ($sheetXml === '') {
                continue;
            }

            $cells = $this->readXlsxCellsByReference($sheetXml, $sharedStrings);
            $data = $this->extractOfficialPdsDataFromCells($cells);
            $score = collect($data)->reject(fn ($value) => blank($value))->count();

            if ($score > $bestScore) {
                $bestData = $data;
                $bestScore = $score;
            }
        }

        return $bestData;
    }

    private function extractOfficialPdsDataWithExcelCom(string $path): array
    {
        $scriptDir = storage_path('app'.DIRECTORY_SEPARATOR.'pds-xlsx');
        if (!is_dir($scriptDir)) {
            mkdir($scriptDir, 0775, true);
        }

        $scriptPath = $scriptDir.DIRECTORY_SEPARATOR.(string) Str::uuid().'.ps1';
        $escapedPath = str_replace("'", "''", $path);
        $script = <<<'POWERSHELL'
$ErrorActionPreference = 'Stop'
$workbookPath = '__WORKBOOK_PATH__'
$excel = $null
$workbook = $null
try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    $workbook = $excel.Workbooks.Open($workbookPath, $null, $true)
    $sheet = $workbook.Worksheets.Item(1)

    function Get-RangeJoin($address) {
        $values = @()
        foreach ($cell in $sheet.Range($address).Cells) {
            $text = ([string] $cell.Text).Trim()
            if ($text -and $text -notmatch '^(NAME EXTENSION|Jr\.|Sr\.|N/A)$') {
                $values += $text
            }
        }
        return ($values -join ' ').Trim()
    }

    function Get-CheckedOption($row, $leftLabel, $rightLabel) {
        try {
            foreach ($box in $sheet.CheckBoxes()) {
                if ([int] $box.Value -ne 1) { continue }
                $cell = $box.TopLeftCell
                if ([int] $cell.Row -eq $row) {
                    if ([int] $cell.Column -le 4) { return $leftLabel }
                    return $rightLabel
                }
            }
        } catch {}
        return $null
    }

    function Get-CivilStatusOption() {
        try {
            foreach ($box in $sheet.CheckBoxes()) {
                if ([int] $box.Value -ne 1) { continue }
                $cell = $box.TopLeftCell
                $row = [int] $cell.Row
                $column = [int] $cell.Column
                if ($row -eq 17) {
                    if ($column -le 4) { return 'Single' }
                    return 'Married'
                }
                if ($row -eq 18) {
                    if ($column -le 4) { return 'Widowed' }
                    return 'Separated'
                }
                if ($row -eq 19 -and $column -le 4) {
                    return 'Other/s'
                }
            }
        } catch {}
        return $null
    }

    $data = [ordered]@{
        surname = Get-RangeJoin 'C10:N10'
        first_name = Get-RangeJoin 'C11:I11'
        middle_name = Get-RangeJoin 'C12:N12'
        name_extension = Get-RangeJoin 'J11:N11'
        date_of_birth = Get-RangeJoin 'C13:E13'
        place_of_birth = Get-RangeJoin 'C15:E15'
        sex = (Get-CheckedOption 16 'Male' 'Female')
        civil_status = (Get-CivilStatusOption)
        gsis_id_no = Get-RangeJoin 'C27:E27'
        gsis_no = Get-RangeJoin 'C27:E27'
        pag_ibig_id_no = Get-RangeJoin 'C29:E29'
        philhealth_no = Get-RangeJoin 'C31:E31'
        sss_no = Get-RangeJoin 'C32:E32'
        tin_no = Get-RangeJoin 'C33:E33'
        permanent_address = ((Get-RangeJoin 'I25:N25'), (Get-RangeJoin 'I27:N27'), (Get-RangeJoin 'I29:N29') | Where-Object { $_ }) -join ' '
        zip_code = $(if ((Get-RangeJoin 'I31:N31')) { Get-RangeJoin 'I31:N31' } else { Get-RangeJoin 'I30:N30' })
        telephone_no = Get-RangeJoin 'I32:N32'
        mobile_no = Get-RangeJoin 'I33:N33'
        email_address = Get-RangeJoin 'I34:N34'
        elementary = ((Get-RangeJoin 'D54:K54'), (Get-RangeJoin 'L54:N54') | Where-Object { $_ }) -join ' - '
        secondary = ((Get-RangeJoin 'D55:K55'), (Get-RangeJoin 'L55:N55') | Where-Object { $_ }) -join ' - '
        vocational_trade_course = ((Get-RangeJoin 'D56:K56'), (Get-RangeJoin 'L56:N56') | Where-Object { $_ }) -join ' - '
        graduate_studies = ((Get-RangeJoin 'D58:K58'), (Get-RangeJoin 'L58:N58') | Where-Object { $_ }) -join ' - '
    }

    $data | ConvertTo-Json -Compress
} finally {
    if ($workbook -ne $null) { $workbook.Close($false) | Out-Null }
    if ($excel -ne $null) { $excel.Quit() | Out-Null }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
POWERSHELL;

        file_put_contents($scriptPath, str_replace('__WORKBOOK_PATH__', $escapedPath, $script));

        $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '.escapeshellarg($scriptPath);
        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);
        @unlink($scriptPath);

        if ($exitCode !== 0 || !$output) {
            return [];
        }

        $decoded = json_decode(implode("\n", $output), true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn ($value, $key) => $key === 'date_of_birth'
                ? $this->normalizePdsDate(is_string($value) ? trim($value) : $value)
                : (is_string($value) ? trim($value) : $value)
            )
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    private function extractOfficialPdsDataFromCells(array $cells): array
    {
        $valueFrom = function (array $references) use ($cells) {
            foreach ($references as $reference) {
                $value = trim((string) ($cells[$reference] ?? ''));
                if ($value !== '') {
                    return $this->cleanPdsValue($value);
                }
            }

            return null;
        };
        $valueFromRange = function (string $startColumn, string $endColumn, int $row, ?callable $filter = null) use ($cells) {
            $values = [];
            $start = $this->spreadsheetColumnIndex($startColumn);
            $end = $this->spreadsheetColumnIndex($endColumn);

            for ($column = $start; $column <= $end; $column++) {
                $reference = $this->spreadsheetColumnName($column) . $row;
                $value = trim((string) ($cells[$reference] ?? ''));
                if ($value !== '') {
                    if ($filter && !$filter($value, $reference)) {
                        continue;
                    }

                    $values[] = $value;
                }
            }

            return $this->cleanPdsValue(implode(' ', $values));
        };
        $ignoreOfficialPdsNoise = function (string $value): bool {
            $normalized = $this->normalizePdsLabel($value);
            if ($normalized === '') {
                return false;
            }

            $noise = [
                'single',
                'married',
                'widow er',
                'widowed',
                'separated',
                'other s',
                'others',
                'house block lot no',
                'street',
                'subdivision village',
                'barangay',
                'city municipality',
                'province',
                'zip code',
                'telephone no',
                'mobile no',
                'e mail address if any',
                'if holder of dual citizenship',
                'please indicate the details',
                'pls indicate country',
            ];

            if (in_array($normalized, $noise, true)) {
                return false;
            }

            $countryNoise = [
                'afghanistan',
                'albania',
                'algeria',
                'andorra',
                'angola',
                'bahamas the',
                'bahrain',
                'bangladesh',
                'barbados',
                'belarus',
                'belgium',
                'belize',
                'benin',
                'bhutan',
                'bolivia',
            ];

            return !in_array($normalized, $countryNoise, true);
        };

        return [
            'surname' => $valueFromRange('C', 'E', 10, $ignoreOfficialPdsNoise) ?: $valueFromRange('B', 'E', 10, $ignoreOfficialPdsNoise),
            'first_name' => $valueFromRange('C', 'E', 11, $ignoreOfficialPdsNoise) ?: $valueFromRange('B', 'E', 11, $ignoreOfficialPdsNoise),
            'middle_name' => $valueFromRange('C', 'D', 12, $ignoreOfficialPdsNoise) ?: $valueFromRange('B', 'D', 12, $ignoreOfficialPdsNoise),
            'name_extension' => $valueFromRange('J', 'N', 11),
            'date_of_birth' => $this->normalizePdsDate($valueFromRange('C', 'E', 13) ?: $valueFromRange('B', 'E', 13)),
            'place_of_birth' => $valueFromRange('C', 'D', 15, $ignoreOfficialPdsNoise) ?: $valueFromRange('B', 'D', 15, $ignoreOfficialPdsNoise),
            'sex' => $this->extractCheckedPdsChoiceFromCells($cells, [
                    'Male' => [16],
                    'Female' => [16],
                ])
                ?: $this->extractCheckedPdsOption($cells, ['B16', 'C16', 'D16', 'E16'], 'Male')
                ?: $this->extractCheckedPdsOption($cells, ['F16', 'G16', 'H16'], 'Female'),
            'civil_status' => $this->extractCheckedPdsChoiceFromCells($cells, [
                    'Single' => [17],
                    'Married' => [17],
                    'Widowed' => [18],
                    'Separated' => [18],
                ])
                ?: $this->extractCheckedPdsOption($cells, ['B17', 'C17', 'D17', 'E17'], 'Single')
                ?: $this->extractCheckedPdsOption($cells, ['F17', 'G17', 'H17'], 'Married')
                ?: $this->extractCheckedPdsOption($cells, ['B18', 'C18', 'D18', 'E18'], 'Widowed')
                ?: $this->extractCheckedPdsOption($cells, ['F18', 'G18', 'H18'], 'Separated'),
            'gsis_id_no' => $valueFromRange('C', 'D', 27, $ignoreOfficialPdsNoise),
            'gsis_no' => $valueFromRange('C', 'D', 27, $ignoreOfficialPdsNoise),
            'pag_ibig_id_no' => $valueFromRange('C', 'D', 29, $ignoreOfficialPdsNoise),
            'philhealth_no' => $valueFromRange('C', 'D', 31, $ignoreOfficialPdsNoise),
            'sss_no' => $valueFromRange('C', 'D', 32, $ignoreOfficialPdsNoise),
            'tin_no' => $valueFromRange('B', 'D', 33, $ignoreOfficialPdsNoise),
            'permanent_address' => $this->cleanPdsPermanentAddress(collect([
                $valueFromRange('I', 'N', 25),
                $valueFromRange('I', 'N', 26),
                $valueFromRange('I', 'N', 27),
                $valueFromRange('I', 'N', 28),
                $valueFromRange('I', 'N', 29),
            ])->filter()->implode(' ')),
            'zip_code' => $valueFromRange('I', 'N', 30),
            'telephone_no' => $valueFromRange('I', 'N', 32),
            'mobile_no' => $valueFromRange('I', 'N', 33),
            'email_address' => $valueFromRange('I', 'N', 34),
            'elementary' => $this->cleanPdsValue(collect([$valueFrom(['D54', 'E54', 'F54']), $valueFrom(['L54', 'M54'])])->filter()->implode(' - ')),
            'secondary' => $this->cleanPdsValue(collect([$valueFrom(['D55', 'E55', 'F55']), $valueFrom(['L55', 'M55'])])->filter()->implode(' - ')),
            'vocational_trade_course' => $this->cleanPdsValue(collect([$valueFrom(['D56', 'E56', 'F56']), $valueFrom(['L56', 'M56'])])->filter()->implode(' - ')),
            'graduate_studies' => $this->cleanPdsValue(collect([$valueFrom(['D58', 'E58', 'F58']), $valueFrom(['L58', 'M58'])])->filter()->implode(' - ')),
        ];
    }

    private function readXlsxSharedStrings(\ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml') ?: '';
        if ($sharedStringsXml === '') {
            return [];
        }

        preg_match_all('/<(?:\w+:)?si[^>]*>(.*?)<\/(?:\w+:)?si>/su', $sharedStringsXml, $matches);

        return collect($matches[1] ?? [])
            ->map(function ($sharedStringXml) {
                preg_match_all('/<(?:\w+:)?t[^>]*>(.*?)<\/(?:\w+:)?t>/su', $sharedStringXml, $textMatches);

                return html_entity_decode(
                    collect($textMatches[1] ?? [])->implode(''),
                    ENT_QUOTES | ENT_XML1,
                    'UTF-8'
                );
            })
            ->values()
            ->all();
    }

    private function readXlsxCellsByReference(string $sheetXml, array $sharedStrings): array
    {
        preg_match_all('/<(?:\w+:)?c\b([^>]*)>(.*?)<\/(?:\w+:)?c>/su', $sheetXml, $matches, PREG_SET_ORDER);

        $cells = [];
        foreach ($matches as $cell) {
            $attributes = $cell[1] ?? '';
            $body = $cell[2] ?? '';

            if (!preg_match('/\br="([A-Z]+\d+)"/', $attributes, $referenceMatch)) {
                continue;
            }

            $cells[$referenceMatch[1]] = $this->readXlsxCellValue($attributes, $body, $sharedStrings);
        }

        return $cells;
    }

    private function readXlsxCellValue(string $attributes, string $body, array $sharedStrings): string
    {
        if ((str_contains($attributes, 't="s"') || str_contains($attributes, "t='s'")) && preg_match('/<(?:\w+:)?v>(.*?)<\/(?:\w+:)?v>/su', $body, $valueMatch)) {
            return (string) ($sharedStrings[(int) $valueMatch[1]] ?? '');
        }

        if (preg_match_all('/<(?:\w+:)?t[^>]*>(.*?)<\/(?:\w+:)?t>/su', $body, $inlineMatches)) {
            return html_entity_decode(
                collect($inlineMatches[1] ?? [])->implode(''),
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );
        }

        if (preg_match('/<(?:\w+:)?v>(.*?)<\/(?:\w+:)?v>/su', $body, $valueMatch)) {
            $rawValue = html_entity_decode(strip_tags($valueMatch[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
            if (preg_match('/^\d+$/', $rawValue) && array_key_exists((int) $rawValue, $sharedStrings)) {
                return (string) $sharedStrings[(int) $rawValue];
            }

            return $rawValue;
        }

        return '';
    }

    private function spreadsheetColumnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private function spreadsheetColumnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $name = chr(65 + $remainder) . $name;
            $index = intdiv($index - 1, 26);
        }

        return $name;
    }

    private function extractCheckedPdsOption(array $cells, array $references, string $label): ?string
    {
        foreach ($references as $reference) {
            $value = trim((string) ($cells[$reference] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($this->containsCheckedMarker($value)) {
                return $label;
            }
        }

        return null;
    }

    private function extractCheckedPdsChoiceFromCells(array $cells, array $choiceRows): ?string
    {
        $cellsByPosition = [];
        foreach ($cells as $reference => $value) {
            if (!preg_match('/^([A-Z]+)(\d+)$/', (string) $reference, $match)) {
                continue;
            }

            $row = (int) $match[2];
            $column = $this->spreadsheetColumnIndex($match[1]);
            $cellsByPosition[$row][$column] = trim((string) $value);
        }

        foreach ($choiceRows as $choice => $rows) {
            foreach ($rows as $row) {
                foreach (($cellsByPosition[$row] ?? []) as $column => $value) {
                    if (!preg_match('/\b'.preg_quote((string) $choice, '/').'\b/iu', $value)) {
                        continue;
                    }

                    $nearby = [$value];
                    for ($offset = 1; $offset <= 3; $offset++) {
                        $nearby[] = (string) ($cellsByPosition[$row][$column - $offset] ?? '');
                    }

                    if ($this->containsCheckedMarker(implode(' ', $nearby))) {
                        return (string) $choice;
                    }
                }
            }
        }

        foreach ($choiceRows as $choice => $rows) {
            foreach ($rows as $row) {
                $rowText = collect($cellsByPosition[$row] ?? [])->implode(' ');
                if (preg_match('/(?:☑|☒|✓|✔|■|[xX]\b|true|yes|checked)\s*'.preg_quote((string) $choice, '/').'\b/iu', $rowText)) {
                    return (string) $choice;
                }
            }
        }

        return null;
    }

    private function extractBinarySpreadsheetText(string $path): string
    {
        $content = @file_get_contents($path);
        if (!is_string($content) || $content === '') {
            return '';
        }

        $decodedContent = @mb_convert_encoding($content, 'UTF-8', 'UTF-16LE,UTF-16BE,Windows-1252,UTF-8');
        if (!is_string($decodedContent) || $decodedContent === '') {
            $decodedContent = $content;
        }

        preg_match_all('/[\p{L}\p{N}@._,:\-\/#\s☑☒☐□✓✔■]{2,}/u', $decodedContent, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->implode("\n");
    }

    private function parsePdsRows(array $rows): array
    {
        $fieldLabels = [
            'surname' => ['surname', 'last name'],
            'first_name' => ['first name', 'firstname'],
            'middle_name' => ['middle name', 'middlename'],
            'name_extension' => ['name extension', 'extension name', 'ext'],
            'date_of_birth' => ['date of birth', 'birth date', 'dob'],
            'place_of_birth' => ['place of birth'],
            'sex' => ['sex'],
            'civil_status' => ['civil status'],
            'gsis_id_no' => ['gsis id no', 'gsis id number'],
            'gsis_no' => ['gsis no', 'gsis number'],
            'pag_ibig_id_no' => ['pag-ibig id no', 'pag ibig id no', 'pag-ibig no'],
            'philhealth_no' => ['philhealth no', 'philhealth number'],
            'sss_no' => ['sss no', 'sss number'],
            'tin_no' => ['tin no', 'tin number'],
            'permanent_address' => ['permanent address/zip code', 'permanent address'],
            'zip_code' => ['zip code', 'postal code'],
            'telephone_no' => ['telephone no', 'telephone number'],
            'mobile_no' => ['mobile no', 'mobile number', 'cellphone no'],
            'email_address' => ['e-mail address', 'email address', 'email'],
            'elementary' => ['elementary'],
            'secondary' => ['secondary'],
            'vocational_trade_course' => ['vocational / trade course', 'vocational', 'trade course'],
            'graduate_studies' => ['graduate studies', 'graduates studies'],
        ];

        $fields = [];
        foreach ($fieldLabels as $field => $labels) {
            $fields[$field] = $this->findValueNearPdsLabel($rows, $labels);
        }

        $fields['sex'] = $this->extractPdsChoiceFromRows($rows, ['sex'], ['Male', 'Female']) ?: $fields['sex'];
        $fields['civil_status'] = $this->extractPdsChoiceFromRows($rows, ['civil status'], ['Single', 'Married', 'Widowed', 'Separated'], 3) ?: $fields['civil_status'];
        $fields['date_of_birth'] = $this->normalizePdsDate($fields['date_of_birth'] ?? null);

        return collect($fields)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    private function findValueNearPdsLabel(array $rows, array $labels): ?string
    {
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $cell) {
                $cellText = trim((string) $cell);
                if ($cellText === '') {
                    continue;
                }

                $normalizedCell = $this->normalizePdsLabel($cellText);
                foreach ($labels as $label) {
                    $normalizedLabel = $this->normalizePdsLabel($label);
                    if (!str_contains($normalizedCell, $normalizedLabel)) {
                        continue;
                    }

                    $inlineValue = trim(preg_replace('/^.*?' . preg_quote($label, '/') . '\s*[:\-]?\s*/iu', '', $cellText) ?? '');
                    if ($inlineValue !== '' && $this->normalizePdsLabel($inlineValue) !== $normalizedLabel) {
                        return $this->cleanPdsValue($inlineValue);
                    }

                    $sameRowValue = $this->firstNonEmptyCells($row, (int) $columnIndex + 1, (int) $columnIndex + 20);
                    if ($sameRowValue) {
                        return $sameRowValue;
                    }

                    for ($down = 1; $down <= 3; $down++) {
                        $nextRow = $rows[$rowIndex + $down] ?? [];
                        $belowValue = $this->firstNonEmptyCells($nextRow, max(0, (int) $columnIndex - 1), (int) $columnIndex + 20);
                        if ($belowValue) {
                            return $belowValue;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function extractPdsChoiceFromRows(array $rows, array $labels, array $choices, int $extraRows = 0): ?string
    {
        foreach ($rows as $rowIndex => $row) {
            $rowText = $this->normalizePdsLabel(implode(' ', array_map('strval', $row)));
            if (!collect($labels)->contains(fn ($label) => str_contains($rowText, $this->normalizePdsLabel($label)))) {
                continue;
            }

            $candidateRows = [$row];
            for ($offset = 1; $offset <= $extraRows; $offset++) {
                if (isset($rows[$rowIndex + $offset])) {
                    $candidateRows[] = $rows[$rowIndex + $offset];
                }
            }

            $choice = $this->extractCheckedChoiceFromCandidateRows($candidateRows, $choices);
            if ($choice) {
                return $choice;
            }
        }

        return null;
    }

    private function extractCheckedChoiceFromCandidateRows(array $rows, array $choices): ?string
    {
        $tokens = [];
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $cell = trim((string) $cell);
                if ($cell !== '') {
                    $tokens[] = $cell;
                }
            }
        }

        foreach ($tokens as $index => $token) {
            foreach ($choices as $choice) {
                if (!preg_match('/\b'.preg_quote($choice, '/').'\b/iu', $token)) {
                    continue;
                }

                $window = collect(array_slice($tokens, max(0, $index - 3), 7))->implode(' ');
                $choiceHasTrailingCheckedValue = preg_match(
                    '/\b'.preg_quote($choice, '/').'\b(?:\s+\S+){0,4}\s+\b(?:1|true|yes|checked)\b/iu',
                    $window
                );

                if ($this->containsCheckedMarker($window) || $choiceHasTrailingCheckedValue) {
                    return $choice;
                }
            }
        }

        $text = implode(' ', $tokens);
        foreach ($choices as $choice) {
            if (preg_match('/(?:☑|☒|✓|✔|■|[xX]\b|true|yes|checked)\s*'.preg_quote($choice, '/').'\b/iu', $text)) {
                return $choice;
            }
        }

        return null;
    }

    private function inferOfficialPdsCivilStatus(array $rows, string $text, array $pdsData): ?string
    {
        $choice = $this->extractOfficialPdsCivilStatusFromRowNumbers($rows);
        if ($choice) {
            return $choice;
        }

        $choice = $this->extractPdsChoiceFromRows($rows, ['civil status'], ['Single', 'Married', 'Widowed', 'Separated'], 4);
        if ($choice) {
            return $choice;
        }

        $normalizedText = $this->normalizePdsLabel($text);
        $looksLikeOfficialPds = str_contains($normalizedText, 'personal data sheet')
            && str_contains($normalizedText, 'civil status')
            && str_contains($normalizedText, 'single')
            && str_contains($normalizedText, 'married');

        if (!$looksLikeOfficialPds) {
            return null;
        }

        return null;
    }

    private function extractOfficialPdsCivilStatusFromRowNumbers(array $rows): ?string
    {
        $officialRows = [
            17 => ['Single', 'Married'],
            18 => ['Widowed', 'Separated'],
        ];

        foreach ($officialRows as $excelRowNumber => $choices) {
            foreach ($rows as $row) {
                if (!$this->rowLooksLikeExcelRow($row, $excelRowNumber)) {
                    continue;
                }

                $choice = $this->extractCheckedChoiceFromCandidateRows([$row], $choices);
                if ($choice) {
                    return $choice;
                }
            }
        }

        return null;
    }

    private function rowLooksLikeExcelRow(array $row, int $excelRowNumber): bool
    {
        $firstValue = trim((string) ($row[0] ?? ''));
        if ($firstValue !== '' && (int) $firstValue === $excelRowNumber) {
            return true;
        }

        $rowText = $this->normalizePdsLabel(implode(' ', array_map('strval', $row)));

        return match ($excelRowNumber) {
            17 => str_contains($rowText, 'single') && str_contains($rowText, 'married'),
            18 => str_contains($rowText, 'widowed') && str_contains($rowText, 'separated'),
            default => false,
        };
    }

    private function containsCheckedMarker(string $value): bool
    {
        $value = str_replace(
            ['â˜‘', 'â˜’', 'âœ“', 'âœ”', 'â– '],
            ['☑', '☒', '✓', '✔', '■'],
            $value
        );

        return (bool) preg_match('/☑|☒|✓|✔|■|(?:^|\s)[xX](?:\s|$)|\b(?:true|yes|checked)\b/iu', $value);
    }

    private function firstNonEmptyCells(array $row, int $start, int $end): ?string
    {
        $values = [];
        for ($index = $start; $index <= $end; $index++) {
            $value = trim((string) ($row[$index] ?? ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values ? $this->cleanPdsValue(implode(' ', $values)) : null;
    }

    private function normalizePdsLabel(string $value): string
    {
        $value = Str::lower($value);
        $value = str_replace(['-', '/', '.', ':', '#'], ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function parsePdsText(string $text): array
    {
        $normalized = preg_replace('/[ \t]+/', ' ', str_replace(["\r\n", "\r"], "\n", $text)) ?? '';

        $fields = [
            'surname' => $this->extractLabeledValue($normalized, ['surname', 'last name']),
            'first_name' => $this->extractLabeledValue($normalized, ['first name', 'firstname']),
            'middle_name' => $this->extractLabeledValue($normalized, ['middle name', 'middlename']),
            'name_extension' => $this->extractLabeledValue($normalized, ['name extension', 'extension name', 'ext']),
            'date_of_birth' => $this->normalizePdsDate($this->extractLabeledValue($normalized, ['date of birth', 'birth date', 'dob'])),
            'place_of_birth' => $this->extractLabeledValue($normalized, ['place of birth']),
            'sex' => $this->extractLabeledValue($normalized, ['sex']),
            'civil_status' => $this->extractLabeledValue($normalized, ['civil status']),
            'gsis_id_no' => $this->extractLabeledValue($normalized, ['gsis id no', 'gsis id number']),
            'gsis_no' => $this->extractLabeledValue($normalized, ['gsis no', 'gsis number']),
            'pag_ibig_id_no' => $this->extractLabeledValue($normalized, ['pag-ibig id no', 'pag ibig id no', 'pag-ibig no']),
            'philhealth_no' => $this->extractLabeledValue($normalized, ['philhealth no', 'philhealth number']),
            'sss_no' => $this->extractLabeledValue($normalized, ['sss no', 'sss number']),
            'tin_no' => $this->extractLabeledValue($normalized, ['tin no', 'tin number']),
            'permanent_address' => $this->extractLabeledValue($normalized, ['permanent address/zip code', 'permanent address']),
            'zip_code' => $this->extractLabeledValue($normalized, ['zip code', 'postal code']),
            'telephone_no' => $this->extractLabeledValue($normalized, ['telephone no', 'telephone number']),
            'mobile_no' => $this->extractLabeledValue($normalized, ['mobile no', 'mobile number', 'cellphone no']),
            'email_address' => $this->extractLabeledValue($normalized, ['e-mail address', 'email address', 'email']),
            'elementary' => $this->extractEducationValue($normalized, 'elementary'),
            'secondary' => $this->extractEducationValue($normalized, 'secondary'),
            'vocational_trade_course' => $this->extractEducationValue($normalized, 'vocational'),
            'graduate_studies' => $this->extractEducationValue($normalized, 'graduate studies'),
        ];

        return collect($fields)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    private function extractLabeledValue(string $text, array $labels): ?string
    {
        $stopLabels = [
            'surname',
            'last name',
            'first name',
            'middle name',
            'name extension',
            'date of birth',
            'place of birth',
            'sex',
            'civil status',
            'gsis id no',
            'gsis no',
            'pag-ibig id no',
            'pag ibig id no',
            'philhealth no',
            'sss no',
            'tin no',
            'permanent address',
            'zip code',
            'postal code',
            'telephone no',
            'mobile no',
            'e-mail address',
            'email address',
            'elementary',
            'secondary',
            'vocational',
            'graduate studies',
        ];

        foreach ($labels as $label) {
            $quotedLabel = preg_quote($label, '/');
            $quotedStops = collect($stopLabels)
                ->reject(fn ($stop) => $stop === $label)
                ->map(fn ($stop) => preg_quote($stop, '/'))
                ->implode('|');

            if (preg_match('/(?:^|\n|\b)' . $quotedLabel . '\s*(?:[:\-]|\n)?\s*(.+?)(?=\n\s*(?:' . $quotedStops . ')\b|\s{2,}(?:' . $quotedStops . ')\b|$)/isu', $text, $match)) {
                return $this->cleanPdsValue($match[1] ?? '');
            }
        }

        return null;
    }

    private function extractEducationValue(string $text, string $level): ?string
    {
        $label = preg_quote($level, '/');
        if (preg_match('/(?:^|\n|\b)' . $label . '\s*(?:[:\-]|\n)?\s*(.+?)(?=\n\s*(?:secondary|vocational|college|graduate studies|work experience)\b|$)/isu', $text, $match)) {
            return $this->cleanPdsValue($match[1] ?? '');
        }

        return null;
    }

    private function normalizePdsChoice(mixed $value, array $choices): ?string
    {
        $rawValue = trim((string) $value);
        $normalized = $this->normalizePdsLabel($rawValue);
        if ($normalized === '') {
            return null;
        }

        foreach ($choices as $choice) {
            if ($normalized === $this->normalizePdsLabel($choice)) {
                return $choice;
            }
        }

        foreach ($choices as $choice) {
            if (preg_match('/(?:☑|✓|✔|checked|true|yes|\bx\b)\s*'.preg_quote($choice, '/').'\b/iu', $rawValue)) {
                return $choice;
            }
        }

        $matches = collect($choices)
            ->filter(fn ($choice) => preg_match('/\b'.preg_quote($choice, '/').'\b/iu', $rawValue))
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function cleanPdsValue(string $value): ?string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        $value = trim($value, " \t\n\r\0\x0B:-");

        return $value === '' ? null : Str::limit($value, 255, '');
    }

    private function cleanPdsPermanentAddress(string $value): ?string
    {
        $labels = [
            'House/Block/Lot No.',
            'House Block Lot No.',
            'No. Street',
            'Street',
            'Subdivision/Village',
            'Subdivision Village',
            'Barangay',
            'City/Municipality',
            'City Municipality',
            'Province',
        ];

        foreach ($labels as $label) {
            $value = preg_replace('/(?<!\w)'.preg_quote($label, '/').'(?!\w)/iu', ' ', $value) ?? $value;
        }

        return $this->cleanPdsValue($value);
    }

    private function cleanPdsNameExtension(string $value): ?string
    {
        $value = $this->cleanPdsValue($value) ?? '';
        $normalized = $this->normalizePdsLabel(str_replace(['.', ',', '(', ')'], ' ', $value));

        if ($normalized === '' || in_array($normalized, [
            'name extension',
            'name extension jr sr',
            'jr sr',
            'na',
            'n a',
            'none',
            'not applicable',
        ], true)) {
            return null;
        }

        if (! preg_match('/[\pL\pN]/u', $value)) {
            return null;
        }

        return $value;
    }

    private function educationUserIdAllowsNull(): bool
    {
        if (! Schema::hasColumn('education', 'user_id')) {
            return true;
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            return true;
        }

        foreach (DB::select('PRAGMA table_info(education)') as $column) {
            if (($column->name ?? null) === 'user_id') {
                return (int) ($column->notnull ?? 0) === 0;
            }
        }

        return true;
    }

    private function cleanPdsEducationCell(string $value): ?string
    {
        $value = $this->cleanPdsValue($value) ?? '';
        $normalized = $this->normalizePdsLabel(str_replace(['(', ')'], ' ', $value));

        if ($this->looksLikePdsCountryListValue($value)) {
            return null;
        }

        if ($normalized === '' || in_array($normalized, [
            'elementary',
            'secondary',
            'vocational',
            'trade course',
            'vocational trade course',
            'college',
            'graduate studies',
            'name of school',
            'write in full',
            'basic education degree course',
            'period of attendance',
            'from',
            'to',
            'highest level units earned',
            'if not graduated',
            'year graduated',
            'scholarship academic honors received',
            'continue on separate sheet if necessary',
            'na',
            'n a',
            'none',
            'not applicable',
        ], true)) {
            return null;
        }

        if (! preg_match('/[\pL\pN]/u', $value)) {
            return null;
        }

        return $value;
    }

    private function looksLikePdsCitizenshipCountry(string $value): bool
    {
        $normalized = $this->normalizePdsLabel(str_replace(',', ' ', $value));
        if ($normalized === '') {
            return false;
        }

        $countriesAndCitizenshipValues = [
            'afghanistan',
            'albania',
            'algeria',
            'andorra',
            'angola',
            'argentina',
            'armenia',
            'australia',
            'austria',
            'bahamas',
            'bahamas the',
            'bahrain',
            'bangladesh',
            'belgium',
            'brazil',
            'canada',
            'china',
            'denmark',
            'dual citizenship',
            'filipino',
            'france',
            'germany',
            'india',
            'indonesia',
            'italy',
            'japan',
            'malaysia',
            'philippines',
            'singapore',
            'spain',
            'thailand',
            'united states',
            'united states of america',
        ];

        return in_array($normalized, $countriesAndCitizenshipValues, true);
    }

    private function normalizePdsDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim((string) $value);

        if (is_numeric($value) && (float) $value > 20000) {
            try {
                return \Carbon\Carbon::create(1899, 12, 30)
                    ->addDays((int) $value)
                    ->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function store_rating(Request $request)
    {
        $attrs = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $applicantEmail = session('applicant_email');
        if (!$applicantEmail) {
            return redirect()->route('guest.index')
                ->with('popup_error', 'Unable to save rating. Please submit an application first.');
        }

        $applicant = Applicant::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($applicantEmail)])
            ->latest('id')
            ->first();

        if (!$applicant) {
            return redirect()->route('guest.index')
                ->with('popup_error', 'Unable to save rating. Applicant record was not found.');
        }

        $applicant->update([
            'starRatings' => (string) $attrs['rating'],
        ]);

        return redirect()->route('guest.index')
            ->with('success', 'Thank you for rating the system.');
    }

    public function display_application(Request $request){
        $attrs = $request->validate([
            'application_lookup' => 'required|string|max:255',
        ]);

        $lookup = trim((string) ($attrs['application_lookup'] ?? ''));
        if ($lookup === '') {
            return back()->withErrors(['application_lookup' => 'Please enter your tracking number.']);
        }

        $applicantsQuery = $this->applicationStatusQuery($lookup);

        if (!(clone $applicantsQuery)->exists()) {
            return view('guest.application', [
                'applicants' => collect(),
                'searchedEmail' => $lookup,
                'applicationStatusSignature' => $this->applicationStatusSignature(collect()),
            ]);
        }

        $applicants = $this->applicationStatusApplicants($lookup);


        return view('guest.application', [
            'applicants' => $applicants,
            'searchedEmail' => $lookup,
            'applicationStatusSignature' => $this->applicationStatusSignature($applicants),
        ]);
    }

    public function application_status_check(Request $request)
    {
        $attrs = $request->validate([
            'application_lookup' => 'required|string|max:255',
            'signature' => 'nullable|string',
        ]);

        $lookup = trim((string) ($attrs['application_lookup'] ?? ''));
        $applicants = $lookup === ''
            ? collect()
            : $this->applicationStatusApplicants($lookup);
        $signature = $this->applicationStatusSignature($applicants);
        $clientSignature = (string) ($attrs['signature'] ?? '');

        if ($clientSignature !== '' && hash_equals($signature, $clientSignature)) {
            return response()
                ->json([
                    'changed' => false,
                    'signature' => $signature,
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()
            ->json([
                'changed' => true,
                'signature' => $signature,
                'html' => view('guest.partials.application-status-board', [
                    'applicants' => $applicants,
                    'searchedEmail' => $lookup,
                ])->render(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function applicationStatusQuery(string $lookup)
    {
        return Applicant::with([
            'position',
            'degrees' => function ($query) {
                $query->orderBy('degree_level')->orderBy('sort_order');
            },
            'documents' => function ($query) {
                $query->orderByDesc('created_at');
            },
        ])->whereRaw('UPPER(TRIM(tracking_number)) = ?', [Str::upper($lookup)]);
    }

    private function applicationStatusApplicants(string $lookup)
    {
        return $this->applicationStatusQuery($lookup)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Applicant $applicant) {
                $applicant->setAttribute('is_email_history_match', false);

                return $applicant;
            });
    }

    private function applicationStatusSignature($applicants): string
    {
        return md5(json_encode(collect($applicants)->map(fn (Applicant $applicant) => [
            'id' => $applicant->id,
            'application_status' => $applicant->application_status,
            'date_hired' => optional($applicant->date_hired)->toDateString(),
            'updated_at' => optional($applicant->updated_at)->toDateTimeString(),
            'documents' => collect($applicant->documents ?? [])->map(fn ($document) => [
                'id' => $document->id,
                'filename' => $document->filename,
                'type' => $document->type,
                'updated_at' => optional($document->updated_at)->toDateTimeString(),
            ])->values(),
        ])->values()));
    }

    private function findLatestResignedEmployeeByEmail(string $email): ?User
    {
        if ($email === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('resignations')
                    ->whereColumn('resignations.user_id', 'users.id')
                    ->whereRaw("LOWER(TRIM(COALESCE(resignations.status, ''))) IN (?, ?)", ['approved', 'completed']);
            })
            ->orderByDesc('id')
            ->first();
    }

    private function latestApprovedResignationDateForUser(int $userId)
    {
        if ($userId <= 0) {
            return null;
        }

        $resignation = Resignation::query()
            ->where('user_id', $userId)
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN (?, ?)", ['approved', 'completed'])
            ->orderByDesc(DB::raw('COALESCE(effective_date, processed_at, submitted_at, created_at)'))
            ->orderByDesc('id')
            ->first();

        if (!$resignation) {
            return null;
        }

        return $resignation->effective_date
            ?? $resignation->processed_at
            ?? $resignation->submitted_at
            ?? $resignation->created_at;
    }

    private function releaseApplicantEmailForRehire(string $email): void
    {
        if ($email === '') {
            return;
        }

        Applicant::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->orderByDesc('id')
            ->get()
            ->each(function (Applicant $applicant) {
                $archivedEmail = $this->buildArchivedApplicantEmail(
                    (string) ($applicant->email ?? ''),
                    (int) $applicant->id
                );

                if ($archivedEmail !== '' && $archivedEmail !== $applicant->email) {
                    $applicant->forceFill([
                        'email' => $archivedEmail,
                    ])->save();
                }
            });
    }

    private function buildArchivedApplicantEmail(string $email, int $applicantId): string
    {
        $trimmedEmail = trim($email);
        if ($trimmedEmail === '' || $applicantId <= 0) {
            return $trimmedEmail;
        }

        $parts = explode('@', $trimmedEmail, 2);
        $local = trim((string) ($parts[0] ?? ''));
        $domain = trim((string) ($parts[1] ?? 'archived.local'));
        if ($local === '') {
            $local = 'archived-applicant';
        }
        if ($domain === '') {
            $domain = 'archived.local';
        }

        $suffix = '.archived.'.$applicantId;
        $maxLocalLength = max(1, 255 - strlen($domain) - 1 - strlen($suffix));
        $safeLocal = substr($local, 0, $maxLocalLength);

        return $safeLocal.$suffix.'@'.$domain;
    }

    private function applyApplicantEmailHistoryFilter($query, string $email): void
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            $query->whereRaw('1 = 0');
            return;
        }

        $parts = explode('@', $normalizedEmail, 2);
        $local = trim((string) ($parts[0] ?? ''));
        $domain = trim((string) ($parts[1] ?? ''));

        $query->where(function ($innerQuery) use ($normalizedEmail, $local, $domain) {
            $innerQuery->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail]);

            if ($local !== '' && $domain !== '') {
                $innerQuery->orWhereRaw('LOWER(TRIM(email)) LIKE ?', [$local.'.archived.%@'.$domain]);
            }
        });
    }
}
