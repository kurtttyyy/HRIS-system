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
        $openPosition = OpenPosition::findOrFail($id);
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
        $openCount = OpenPosition::count();
        $department = OpenPosition::query()->distinct('department')->count('department');
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
        $job = OpenPosition::findOrFail($id);

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

        $other = OpenPosition::where('id', '!=', $job->id)
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
        return OpenPosition::query()
            ->when($appliedPositionIds->isNotEmpty(), function ($query) use ($appliedPositionIds) {
                $query->whereNotIn('id', $appliedPositionIds);
            });
    }

    private function vacancySignature($appliedPositionIds): string
    {
        $query = OpenPosition::withTrashed()
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

        $openJobs = OpenPosition::query()->latest('id')->take(4)->get(['title', 'department']);
        $jobSummary = $openJobs->isEmpty()
            ? 'No open positions currently.'
            : $openJobs->map(fn ($job) => "{$job->title} ({$job->department})")->implode(', ');

        $systemPrompt = "You are NC Careers Assistant for Northeastern College HR recruitment website. ".
            "Answer briefly and clearly in a friendly tone. ".
            "You can explain the whole website experience including: Home page, Job Vacancies, About, Application flow, Login/Register, ".
            "Privacy Policy, Terms of Service, Cookie Policy, and footer contact links. ".
            "When the user asks where to find something, give direct navigation steps using the page/section names used on this site. ".
            "If asked about jobs, application, policy, terms, cookie policy, or contact, respond with practical steps. ".
            "Never fabricate employee-private data or internal admin-only information. ".
            "Current quick context: Open jobs snapshot: {$jobSummary}";

        try {
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => (string) (config('services.openai.model') ?: env('OPENAI_MODEL', 'gpt-4o-mini')),
                    'temperature' => 0.5,
                    'max_tokens' => 240,
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
        $jobsCount = OpenPosition::count();

        if ($text === '' || Str::contains($text, ['hello', 'hi', 'good morning', 'good afternoon'])) {
            return "Hello. I can guide you across the full website: home, vacancies, application process, account/login, policies, and contact links. ".
                "What would you like to check first?";
        }

        if (Str::contains($text, ['job', 'vacancy', 'opening', 'position', 'hiring'])) {
            return $jobsCount > 0
                ? "We currently have {$jobsCount} open position(s). You can open Job Vacancies from the home page and filter by department, employment type, and location."
                : "There are no open positions right now. Please check back soon, as new listings are posted on the home page.";
        }

        if (Str::contains($text, ['apply', 'application', 'how to apply'])) {
            return "To apply: open a vacancy, click 'View Details & Apply', complete the application form, and submit your required details/documents. ".
                "After submission, wait for HR updates regarding screening and interview.";
        }

        if (Str::contains($text, ['requirement', 'document', 'resume', 'cv'])) {
            return "Common requirements include personal details, contact information, and supporting documents like resume/CV. ".
                "Specific requirements may vary by position, so please read the selected job post carefully before submitting.";
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

        return "I can explain the whole site, including vacancies, application flow, accounts/login, policies, and contact links. ".
            "Try asking: 'Explain this website' or 'How do I apply?'.";
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
