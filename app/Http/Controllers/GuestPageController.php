<?php

namespace App\Http\Controllers;

use App\Events\GuestLog;
use App\Models\Applicant;
use App\Models\OpenPosition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuestPageController extends Controller
{
    public function display_application(Request $request){
        $lookup = trim((string) $request->query('application_lookup', ''));

        if ($lookup !== '') {
            $applicants = Applicant::with([
                'position',
                'degrees' => function ($query) {
                    $query->orderBy('degree_level')->orderBy('sort_order');
                },
                'documents' => function ($query) {
                    $query->orderByDesc('created_at');
                },
            ])
                ->whereRaw('UPPER(TRIM(tracking_number)) = ?', [Str::upper($lookup)])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->map(function (Applicant $applicant) {
                    $applicant->setAttribute('is_email_history_match', false);

                    return $applicant;
                });

            return view('guest.application', [
                'applicants' => $applicants,
                'searchedEmail' => $lookup,
                'applicationStatusSignature' => $this->applicationStatusSignature($applicants),
            ]);
        }

        return view('guest.application', [
            'applicants' => collect(), // avoid undefined variable
        ]);
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

    public function display_non_teaching($id){
        $openPosition = OpenPosition::publicVacancies()->findOrFail($id);
        return view('guest.applicationNonTeachingSteps', compact('openPosition'));
    }

    public function display_teaching(){
        return view('guest.applicationTeachingSteps');
    }

    public function display_index(){
        $applicantEmail = session('applicant_email');
        $appliedPositionIds = $this->getBlockedPositionIds($applicantEmail);
        $newCutoff = now()->subDays(3);

        $open_position = $this->availablePositionsQuery($appliedPositionIds)
            ->orderByRaw('CASE WHEN created_at >= ? THEN 0 ELSE 1 END', [$newCutoff->toDateTimeString()])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $featuredOpenPositions = $open_position->take(6);
        $vacancySignature = $this->vacancySignature($appliedPositionIds);
        $openCount = $open_position->count();
        $hasMoreOpenPositions = $openCount > $featuredOpenPositions->count();
        $department = $open_position->groupBy('department')->count();
        $employee = User::where('role', 'Employee')->count();
        $ratingStats = Applicant::query()
            ->whereNotNull('starRatings')
            ->get(['starRatings'])
            ->map(function ($applicant) {
                $value = (int) $applicant->starRatings;
                return ($value >= 1 && $value <= 5) ? $value : null;
            })
            ->filter();
        $companyRating = $ratingStats->count() ? round((float) $ratingStats->avg(), 1) : null;
        $ratingCount = $ratingStats->count();
        event(new GuestLog('Viewed'));
        return view('guest.index', compact(
            'open_position',
            'openCount',
            'department',
            'employee',
            'companyRating',
            'ratingCount',
            'vacancySignature',
            'featuredOpenPositions',
            'hasMoreOpenPositions'
        ));
    }

    public function display_about(){
        $openCount = OpenPosition::publicVacancies()->count();
        $department = OpenPosition::publicVacancies()->distinct('department')->count('department');
        $employee = User::where('role', 'Employee')->count();

        return view('guest.about', compact(
            'openCount',
            'department',
            'employee'
        ));
    }

    public function display_policy(){
        return view('guest.policy');
    }

    public function display_terms(){
        return view('guest.terms');
    }

    public function display_cookie(){
        return view('guest.cookie');
    }

    public function chat_reply(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $message = trim((string) $validated['message']);
        $response = $this->buildChatbotReply($message);

        return response()->json($response);
    }

    public function job_open_landing(){
        $applicantEmail = session('applicant_email');
        $appliedPositionIds = $this->getBlockedPositionIds($applicantEmail);

        $firstAvailableJob = $this->availablePositionsQuery($appliedPositionIds)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (!$firstAvailableJob) {
            return redirect()->route('guest.index')
                ->with('error', 'No available job positions at the moment.');
        }

        return redirect()->route('guest.jobOpen', ['id' => $firstAvailableJob->id]);
    }

    public function job_vacancies_check(Request $request)
    {
        $appliedPositionIds = $this->getBlockedPositionIds(session('applicant_email'));
        $signature = $this->vacancySignature($appliedPositionIds);
        $clientSignature = (string) $request->query('signature', '');

        if ($clientSignature === '' || hash_equals($signature, $clientSignature)) {
            return response()
                ->json([
                    'changed' => false,
                    'signature' => $signature,
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $newCutoff = now()->subDays(3);
        $openPosition = $this->availablePositionsQuery($appliedPositionIds)
            ->orderByRaw('CASE WHEN created_at >= ? THEN 0 ELSE 1 END', [$newCutoff->toDateTimeString()])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $featuredOpenPositions = $openPosition->take(6);

        return response()
            ->json([
                'changed' => true,
                'signature' => $signature,
                'html' => view('guest.partials.job-vacancies-list', [
                    'open_position' => $featuredOpenPositions,
                ])->render(),
                'count' => $openPosition->count(),
                'hasMore' => $openPosition->count() > $featuredOpenPositions->count(),
                'seeMoreUrl' => route('guest.jobOpenLanding'),
                'departments' => $openPosition->pluck('department')->filter()->unique()->values(),
                'employments' => $openPosition->pluck('employment')->filter()->unique()->values(),
                'locations' => $openPosition->pluck('location')->filter()->unique()->values(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function display_job($id){
        $job = OpenPosition::publicVacancies()->findOrFail($id);

        $applicantEmail = session('applicant_email');
        $appliedPositionIds = $this->getBlockedPositionIds($applicantEmail);

        if ($applicantEmail) {
            $latestApplication = Applicant::where('email', $applicantEmail)
                ->where('open_position_id', $job->id)
                ->latest('id')
                ->first();

            if ($latestApplication) {
                $status = Str::lower(trim((string) $latestApplication->application_status));

                if ($status === 'rejected') {
                    $baseDate = $latestApplication->updated_at ?? $latestApplication->created_at;
                    $nextEligibleDate = $baseDate->copy()->addMonths(3);

                    if (now()->lt($nextEligibleDate)) {
                        return redirect()->route('guest.index')
                            ->with('popup_error', 'Your last application was rejected. You can apply again on '.$nextEligibleDate->format('F j, Y').'.');
                    }
                } else {
                    return redirect()->route('guest.index')
                        ->with('popup_error', 'You already applied for that position.');
                }
            }
        }

        $other = OpenPosition::publicVacancies()
            ->where('id', '!=', $job->id)
            ->when($appliedPositionIds->isNotEmpty(), function ($query) use ($appliedPositionIds) {
                $query->whereNotIn('id', $appliedPositionIds);
            })
            ->latest('created_at')
            ->latest('id')
            ->get();

        $jobOpen = $this->availablePositionsQuery($appliedPositionIds)
            ->latest('created_at')
            ->latest('id')
            ->get();

        return view('guest.jobOpen', compact('jobOpen','job','other'));
    }

    private function availablePositionsQuery($appliedPositionIds)
    {
        return OpenPosition::publicVacancies()
            ->when($appliedPositionIds->isNotEmpty(), function ($query) use ($appliedPositionIds) {
                $query->whereNotIn('id', $appliedPositionIds);
            });
    }

    private function vacancySignature($appliedPositionIds): string
    {
        $query = OpenPosition::withTrashed()
            ->publicVacancies()
            ->when($appliedPositionIds->isNotEmpty(), function ($query) use ($appliedPositionIds) {
                $query->whereNotIn('id', $appliedPositionIds);
            });

        return implode(':', [
            (clone $query)->count(),
            (clone $query)->max('id') ?: 0,
            (string) ((clone $query)->max('updated_at') ?: ''),
            (string) ((clone $query)->max('deleted_at') ?: ''),
        ]);
    }

    private function getBlockedPositionIds(?string $applicantEmail)
    {
        if (!$applicantEmail) {
            return collect();
        }

        return Applicant::where('email', $applicantEmail)
            ->orderByDesc('id')
            ->get()
            ->unique('open_position_id')
            ->filter(function ($application) {
                $status = Str::lower(trim((string) $application->application_status));

                if ($status !== 'rejected') {
                    return true;
                }

                $baseDate = $application->updated_at ?? $application->created_at;
                return $baseDate->gt(now()->subMonths(3));
            })
            ->pluck('open_position_id');
    }

    private function buildChatbotReply(string $message): array
    {
        $aiReply = $this->askOpenAi($message);
        if ($aiReply) {
            return [
                'reply' => $aiReply,
                'used_ai' => true,
                'suggestions' => [
                    'Show available jobs',
                    'How to apply',
                    'Application requirements',
                    'Explain this website',
                    'Where are policy pages?',
                    'How to create an account',
                ],
            ];
        }

        return [
            'reply' => $this->buildRuleBasedReply($message),
            'used_ai' => false,
            'suggestions' => $this->fallbackSuggestions($message),
        ];
    }

    private function askOpenAi(string $message): ?string
    {
        $apiKey = (string) (config('services.openai.key') ?: env('OPENAI_API_KEY'));
        if ($apiKey === '') {
            return null;
        }

        $openJobs = OpenPosition::publicVacancies()->latest('id')->take(10)->get([
            'title', 'department', 'employment', 'work_mode', 'experience_level',
            'location', 'requirements', 'skills', 'benifits',
        ]);
        $jobSummary = $openJobs->isEmpty()
            ? 'No open positions currently.'
            : $openJobs->map(function ($job) {
                return collect([
                    "Title: {$job->title}",
                    $job->department ? "department: {$job->department}" : null,
                    $job->employment ? "employment: {$job->employment}" : null,
                    $job->work_mode ? "work mode: {$job->work_mode}" : null,
                    $job->experience_level ? "experience: {$job->experience_level}" : null,
                    $job->location ? "location: {$job->location}" : null,
                    $job->requirements ? "requirements: {$job->requirements}" : null,
                    $job->skills ? "skills: {$job->skills}" : null,
                    $job->benifits ? "benefits: {$job->benifits}" : null,
                ])->filter()->implode('; ');
            })->implode(' | ');

        $systemPrompt = "You are NC Careers Assistant for Northeastern College HR recruitment website. ".
            "Answer clearly in a friendly tone and give enough detail to be useful. ".
            "You can explain the whole website experience including: Home page, Job Vacancies, About, Application flow, Login/Register, ".
            "Privacy Policy, Terms of Service, Cookie Policy, and footer contact links. ".
            "You may also answer general career questions such as resumes, interviews, qualifications, skills, and job-search advice. ".
            "When the user asks where to find something, give direct navigation steps using the page/section names used on this site. ".
            "Use the vacancy context below for questions about current positions and never invent missing vacancy details. ".
            "Never fabricate employee-private data or internal admin-only information. ".
            "Current quick context: Open jobs snapshot: {$jobSummary}";

        try {
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => (string) (config('services.openai.model') ?: env('OPENAI_MODEL', 'gpt-4o-mini')),
                    'temperature' => 0.5,
                    'max_tokens' => 500,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('OpenAI chatbot request failed', ['status' => $response->status()]);
                return null;
            }

            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
            $content = trim($content);

            return $content !== '' ? $content : null;
        } catch (\Throwable $e) {
            Log::warning('OpenAI chatbot exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildRuleBasedReply(string $message): string
    {
        $text = Str::lower(trim($message));
        $jobsCount = OpenPosition::publicVacancies()->count();

        if ($text === '' || Str::contains($text, ['hello', 'hi', 'good morning', 'good afternoon'])) {
            return "Hello. I can guide you across the full website: home, vacancies, application process, account/login, policies, and contact links. ".
                "What would you like to check first?";
        }

        if (Str::contains($text, ['track', 'tracking number', 'application status', 'screening', 'shortlist', 'rejected', 'accepted'])) {
            return "Use the Application Status button and enter the tracking number issued after you submitted. The same number is also sent to your application email. ".
                "The status page shows your submitted role and recruitment updates. If you were rejected, this site allows another application for that position after the three-month waiting period.";
        }

        if (Str::contains($text, ['apply', 'application', 'how to apply'])) {
            return "To apply: open a vacancy, click 'View Details & Apply', complete the application form, and submit your required details/documents. ".
                "Save the tracking number shown after submission and sent to your email. Use the Application Status button to check screening, interview, or decision updates.";
        }

        if (Str::contains($text, ['requirement', 'document', 'resume', 'cv'])) {
            return "The application asks for personal and contact details, education, experience, key skills, and the required supporting documents. ".
                "Uploaded documents must be PDF, DOC, or DOCX and each file can be up to 5 MB. Read the selected vacancy's Requirements section because role-specific documents and qualifications may differ.";
        }

        if (Str::contains($text, ['interview', 'interviewer', 'schedule'])) {
            return "If HR schedules an interview, review the date, time, and instructions in your application update or notification. Prepare examples that show how your education, skills, and experience match the vacancy, and keep your contact details available for follow-up.";
        }

        if (Str::contains($text, ['resume', 'cv', 'cover letter'])) {
            return "Tailor your resume to the position title and its listed requirements. Put your most relevant education, experience, and skills first, use clear achievement-focused descriptions, check your contact details, and upload the requested file format. Never claim experience or credentials you do not have.";
        }

        if (Str::contains($text, ['qualification', 'qualified', 'eligible', 'experience', 'skill'])) {
            return "Open the vacancy and compare your background with its Requirements, Experience Level, and Skills sections. You may still apply as a fresh graduate where appropriate, but the information you submit must be complete and accurate. HR makes the final screening decision.";
        }

        if (Str::contains($text, ['website', 'site', 'explain', 'guide', 'navigation', 'how this works'])) {
            return "Website guide: Home shows highlights and quick navigation. Job Vacancies lets you browse open positions and apply. ".
                "About shares institutional background. Application pages handle submission flow. ".
                "Footer links open Privacy Policy, Terms of Service, and Cookie Policy. ".
                "You can also use Applicant Login or Create Account from quick links.";
        }

        if (Str::contains($text, ['account', 'register', 'sign up', 'login', 'log in', 'applicant login'])) {
            return "To access applicant features, use 'Create Account' to register first, then use 'Applicant Login' from quick links. ".
                "If you already have credentials, go directly to login and continue your application process.";
        }

        if (Str::contains($text, ['salary', 'pay', 'compensation'])) {
            return "Salary is not published in the current public vacancy details. Please review the selected posting and ask HR during the recruitment process rather than relying on an estimate.";
        }

        if (Str::contains($text, ['benefit', 'employment type', 'full-time', 'part-time', 'work mode', 'remote', 'onsite', 'on-site'])) {
            return "Employment type, work mode, and benefits vary by position. Open Job Vacancies, select a role, and review its details. If a detail is not listed, confirm it with HR during screening or interview.";
        }

        if (Str::contains($text, ['about', 'department', 'filter', 'home'])) {
            return "From Home, you can search and filter vacancies by department, employment type, and location. ".
                "The About page gives an overview of the institution, while Job Vacancies focuses on open roles and application actions.";
        }

        if (Str::contains($text, ['privacy', 'policy'])) {
            return "You can review the Privacy Policy from the footer link. It explains what data is collected, why it is needed, and how records are protected.";
        }

        if (Str::contains($text, ['terms', 'service'])) {
            return "The Terms of Service page is available in the footer. It covers acceptable use, responsibilities, and updates to website terms.";
        }

        if (Str::contains($text, ['cookie'])) {
            return "The Cookie Policy page in the footer explains what cookies are used on public pages and how they support site functionality and analytics.";
        }

        if (Str::contains($text, ['contact', 'facebook', 'address', 'location', 'sias'])) {
            return "You can reach Northeastern College through the footer contact links: Villasis, Santiago City, Isabela 3311, the NC Facebook page, and SIAS Online.";
        }

        if (Str::contains($text, ['job', 'vacancy', 'opening', 'position', 'hiring', 'available'])) {
            if ($jobsCount === 0) {
                return "There are no open positions right now. Please check back soon, as new listings are posted on the home page.";
            }

            $titles = OpenPosition::publicVacancies()->latest('id')->pluck('title')->filter()->take(8)->implode(', ');
            return "We currently have {$jobsCount} open position(s): {$titles}. Open Job Vacancies to search by position title and select a role for its department, description, requirements, skills, work setup, and application action.";
        }

        return "I can help with current vacancies, role requirements, application steps, documents, tracking and status, accounts, interviews, resumes, career preparation, policies, and contact information. Please add a little more detail to your question so I can give the most relevant answer.";
    }

    private function fallbackSuggestions(string $message): array
    {
        $text = Str::lower(trim($message));

        if (Str::contains($text, ['job', 'vacancy', 'opening'])) {
            return ['How to apply', 'Required documents', 'Open Job Vacancies', 'Explain this website'];
        }

        if (Str::contains($text, ['policy', 'privacy', 'terms', 'cookie'])) {
            return ['Privacy Policy', 'Terms of Service', 'Cookie Policy', 'How this website uses my data'];
        }

        if (Str::contains($text, ['account', 'register', 'login'])) {
            return ['How to create an account', 'Applicant Login help', 'How to apply', 'Explain this website'];
        }

        return [
            'Explain this website',
            'Show available jobs',
            'How to apply',
            'Application requirements',
            'How to create an account',
            'Where are policy pages?',
        ];
    }
}
