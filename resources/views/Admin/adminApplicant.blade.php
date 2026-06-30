<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>PeopleHub - HR Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .applicant-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .applicant-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .applicant-reveal.is-visible {
      animation: applicant-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--applicant-delay, 0ms);
    }
    .applicant-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .applicant-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .applicant-icon-pop {
      animation: applicant-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--applicant-delay, 0ms);
    }
    .applicant-table-row {
      transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .applicant-table-row:hover {
      transform: translateX(4px);
      box-shadow: inset 3px 0 0 rgba(14, 165, 233, 0.55);
    }
    .applicant-modal-shell {
      max-height: min(88vh, 860px);
    }
    .applicant-modal-body {
      max-height: calc(min(88vh, 860px) - 82px);
    }
    .applicant-doc-list {
      max-height: 360px;
      overflow-y: auto;
      padding-right: 0.25rem;
    }
    .applicant-doc-list::-webkit-scrollbar {
      width: 6px;
    }
    .applicant-doc-list::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 999px;
    }
    .applicant-doc-card {
      transition: border-color 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
    }
    .applicant-doc-card:hover {
      transform: translateY(-1px);
      border-color: #7dd3fc;
      background: #f8fafc;
    }
    .applicant-doc-card.is-downloaded {
      border-color: #86efac;
      background: #f0fdf4;
    }
    .interview-conflict-popup {
      border-radius: 1.75rem !important;
      animation: interview-conflict-pop 0.48s cubic-bezier(0.22, 0.9, 0.2, 1) both;
    }
    .interview-conflict-popup .swal2-icon {
      animation: interview-conflict-shake 0.65s ease-in-out 0.18s both;
    }
    .applicant-doc-check {
      opacity: 0;
      transform: scale(0.88);
      transition: opacity 0.18s ease, transform 0.18s ease;
    }
    @keyframes interview-conflict-pop {
      0% { opacity: 0; transform: translateY(24px) scale(0.9); }
      65% { opacity: 1; transform: translateY(-5px) scale(1.025); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes interview-conflict-shake {
      0%, 100% { transform: rotate(0); }
      25% { transform: rotate(-8deg); }
      50% { transform: rotate(7deg); }
      75% { transform: rotate(-4deg); }
    }
    .applicant-doc-card.is-downloaded .applicant-doc-check {
      opacity: 1;
      transform: scale(1);
    }
    @keyframes applicant-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes applicant-pop-in {
      0% {
        opacity: 0;
        transform: scale(0.82) rotate(-4deg);
      }
      100% {
        opacity: 1;
        transform: scale(1) rotate(0);
      }
    }
    @media (prefers-reduced-motion: reduce) {
      .applicant-reveal,
      .applicant-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .applicant-card-motion,
      .applicant-table-row {
        transition: none;
      }
      .applicant-card-motion:hover,
      .applicant-table-row:hover {
        transform: none;
      }
    }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#f1f5f9_45%,#eefbf6_100%)]">

@php
  $positionOptions = collect($applicant ?? [])
    ->map(fn($app) => trim((string) optional($app->position)->title))
    ->filter(fn($value) => $value !== '')
    ->unique()
    ->sort()
    ->values();

  $statusOptions = collect($applicant ?? [])
    ->map(fn($app) => trim((string) ($app->application_status ?? '')))
    ->filter(fn($value) => $value !== '')
    ->unique()
    ->sort()
    ->values();
@endphp

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    @include('components.adminHeader.applicantHeader')

    <div id="admin-applicant-page" class="p-4 md:p-8 space-y-6 pt-20">
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
        <div role="button" tabindex="0" data-applicant-dashboard-filter="all" class="applicant-stat-card applicant-card-motion applicant-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-[0_24px_55px_rgba(15,23,42,0.1)] focus:outline-none focus:ring-2 focus:ring-sky-300" style="--applicant-delay: 30ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Total Applicants</p>
              <p class="mt-3 text-4xl font-black tracking-tight text-slate-900" data-applicant-stat-count="total">{{ $count_applicant }}</p>
              <p class="mt-1 text-sm text-slate-500">All candidate submissions</p>
            </div>
            <div class="text-right">
              <div class="applicant-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600" style="--applicant-delay: 70ms;">
                <i class="fa-solid fa-users"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-600">+12%</span>
            </div>
          </div>
        </div>

        <div role="button" tabindex="0" data-applicant-dashboard-filter="pending" class="applicant-stat-card applicant-card-motion applicant-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-[0_24px_55px_rgba(15,23,42,0.1)] focus:outline-none focus:ring-2 focus:ring-sky-300" style="--applicant-delay: 60ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pending</p>
              <p class="mt-3 text-4xl font-black tracking-tight text-slate-900" data-applicant-stat-count="pending">{{ $count_pending }}</p>
              <p class="mt-1 text-sm text-slate-500">Applicants awaiting review</p>
            </div>
            <div class="text-right">
              <div class="applicant-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-yellow-600" style="--applicant-delay: 100ms;">
                <i class="fa-regular fa-clock"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-600">Pending</span>
            </div>
          </div>
        </div>

        <div role="button" tabindex="0" data-applicant-dashboard-filter="interview" class="applicant-stat-card applicant-card-motion applicant-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-[0_24px_55px_rgba(15,23,42,0.1)] focus:outline-none focus:ring-2 focus:ring-sky-300" style="--applicant-delay: 90ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Interviews Scheduled</p>
              <p class="mt-3 text-4xl font-black tracking-tight text-slate-900" data-applicant-stat-count="interview">{{ $count_final_interview }}</p>
              <p class="mt-1 text-sm text-slate-500">Candidates moved into interview stage</p>
            </div>
            <div class="text-right">
              <div class="applicant-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-green-100 text-green-600" style="--applicant-delay: 130ms;">
                <i class="fa-regular fa-calendar"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-600">This Week</span>
            </div>
          </div>
        </div>

        <div role="button" tabindex="0" data-applicant-dashboard-filter="hired_month" class="applicant-stat-card applicant-card-motion applicant-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-[0_24px_55px_rgba(15,23,42,0.1)] focus:outline-none focus:ring-2 focus:ring-sky-300" style="--applicant-delay: 120ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Hired This Month</p>
              <p class="mt-3 text-4xl font-black tracking-tight text-slate-900" data-applicant-stat-count="hired_month">{{ $hired }}</p>
              <p class="mt-1 text-sm text-slate-500">Successful hires completed</p>
            </div>
            <div class="text-right">
              <div class="applicant-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-100 text-purple-600" style="--applicant-delay: 160ms;">
                <i class="fa-solid fa-check"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-600">+8%</span>
            </div>
          </div>
        </div>
      </div>

      <div class="applicant-reveal rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur" style="--applicant-delay: 170ms;">
        <div class="flex flex-col gap-4 mb-6 xl:flex-row xl:items-end xl:justify-between">
          <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">
              Recruitment Pipeline
            </div>
            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">Recent Applicants</h2>
            <p class="mt-1 text-sm text-slate-500">Review candidate details, filter the pipeline, and open actions directly from the list.</p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[720px]">
            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
              <i class="fa-solid fa-layer-group text-slate-400"></i>
              <select id="applicantPositionFilter" class="w-full bg-transparent outline-none text-slate-700">
                <option value="">All Positions</option>
                @foreach ($positionOptions as $positionOption)
                  <option value="{{ strtolower($positionOption) }}">{{ $positionOption }}</option>
                @endforeach
              </select>
            </label>
            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
              <i class="fa-solid fa-signal text-slate-400"></i>
              <select id="applicantStatusFilter" class="w-full bg-transparent outline-none text-slate-700">
                <option value="">All Status</option>
                @foreach ($statusOptions as $statusOption)
                  <option value="{{ strtolower($statusOption) }}">{{ $statusOption }}</option>
                @endforeach
              </select>
            </label>
            <button id="clearApplicantFilters" type="button" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
              <i class="fa-solid fa-rotate-left text-xs"></i>
              Reset Filters
            </button>
          </div>
        </div>

        <div class="overflow-x-auto rounded-[1.5rem] border border-slate-200 bg-slate-50/60">
          <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-white/80 text-left text-slate-400">
              <tr>
                <th class="px-5 py-4">APPLICANT</th>
                <th class="px-3 py-4">POSITION</th>
                <th class="px-3 py-4">APPLIED DATE</th>
                <th class="px-3 py-4">STATUS</th>
                <th class="px-3 py-4">RATING</th>
                <th class="px-5 py-4">ACTIONS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200" id="applicantsTableBody"></tbody>
          </table>
        </div>

        <div id="applicantEmptyState" class="hidden mt-4 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50/70 p-6 text-center text-sm text-slate-500">
          No applicants matched the current search or filters.
        </div>

        <div class="mt-4 flex justify-end items-center gap-2" id="paginationControls"></div>
      </div>
    </div>
  </main>
</div>

<div id="applicantModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-4 py-6 backdrop-blur-sm">
  <div class="applicant-modal-shell relative w-full max-w-7xl overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.18)]">
    <div class="border-b border-slate-200 bg-[linear-gradient(135deg,rgba(14,165,233,0.08),rgba(16,185,129,0.08))] px-6 py-4">
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Applicant Profile</p>
          <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Candidate Review Desk</h2>
        </div>
        <button type="button" onclick="closeApplicantModal()" class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:text-slate-900">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>

    <div class="applicant-modal-body overflow-y-auto bg-slate-50/50 p-5">
      <div id="applicantReviewPanel" class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,0.85fr)]">
        <div class="space-y-6">
          <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
              <div class="flex items-start gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-[1.35rem] bg-[linear-gradient(135deg,#0ea5e9,#2563eb)] text-xl font-bold text-white" id="applicantInitials">
                  AP
                </div>
                <div>
                  <h3 class="text-2xl font-black tracking-tight text-slate-900" id="name"></h3>
                  <p class="mt-1 text-sm text-slate-500" id="email"></p>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700" id="title"></span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" id="status"></span>
                  </div>
                  <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-2"><i class="fa-regular fa-calendar text-sky-500"></i><span id="one"></span></span>
                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-location-dot text-emerald-500"></i><span id="location"></span></span>
                  </div>
                </div>
              </div>

              <div class="grid w-full gap-3 sm:grid-cols-2 lg:w-[210px] lg:grid-cols-1">
                <button type="button" onclick="scheduleInterview()" class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                  <i class="fa-regular fa-calendar"></i>
                  Schedule Interview
                </button>

                <form action="{{ route('admin.updateStatus') }}" id="updateStatus" method="POST">
                  @csrf
                  <input type="hidden" name="reviewId" id="statusId">
                  <input type="hidden" name="status" id="statusAutoValue" value="Under Review">
                  <input type="hidden" name="date_hired" id="statusDateHired">
                </form>

                <button type="button" id="nextApplicantButton" onclick="showApplicantInterviewPanel()" disabled class="inline-flex items-center justify-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-5 py-3 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-400">
                  Proceed
                </button>
                <p id="documentReviewProgress" class="text-center text-xs font-semibold text-slate-400">Review all documents to continue</p>
              </div>
            </div>
          </div>

          <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm">
              <h4 class="flex items-center gap-2 text-base font-bold text-slate-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                  <i class="fa-solid fa-briefcase"></i>
                </span>
                Work Experience
              </h4>
              <div class="mt-4 space-y-2 text-sm leading-6 text-slate-600" id="work_info"></div>
            </div>

            <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm">
              <h4 class="flex items-center gap-2 text-base font-bold text-slate-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                  <i class="fa-solid fa-graduation-cap"></i>
                </span>
                Education
              </h4>
              <div class="mt-4 space-y-2 text-sm leading-6 text-slate-600" id="university_info"></div>
            </div>
          </div>
        </div>

        <div class="space-y-5 xl:sticky xl:top-0 xl:self-start">
          <div class="rounded-[1.35rem] border border-emerald-200 bg-emerald-50/70 p-5">
            <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Skills</h4>
            <div class="mt-3 flex flex-wrap gap-2" id="skills"></div>
          </div>

          <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm">
            <h4 class="text-base font-bold text-slate-900">Contact Information</h4>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
              <div class="flex items-start gap-3">
                <span class="mt-0.5 text-sky-500"><i class="fa-regular fa-envelope"></i></span>
                <p id="contact_email"></p>
              </div>
              <div class="flex items-start gap-3">
                <span class="mt-0.5 text-emerald-500"><i class="fa-solid fa-phone"></i></span>
                <p id="number"></p>
              </div>
            </div>
          </div>

          <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
              <h4 class="text-base font-bold text-slate-900">Documents</h4>
              <span id="documentsCount" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">0 files</span>
            </div>
            <div id="rehireSummary" class="mt-3 hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-800"></div>
            <div id="documents" class="applicant-doc-list mt-4 space-y-4"></div>
          </div>

          <form action="{{ route('admin.adminStarStore') }}" method="POST" id="starRatings">
            @csrf
            <input type="hidden" name="ratingId" id="ratingStarId">
            <input type="hidden" name="rating" id="ratingValue">
          </form>

          <div class="rounded-[1.35rem] border border-amber-200 bg-amber-50/70 p-5">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Applicant Rating</p>
                <div class="mt-3 flex gap-1 text-xl text-amber-400" id="ratingStars">
                  <i class="fa-regular fa-star star cursor-pointer" data-value="1"></i>
                  <i class="fa-regular fa-star star cursor-pointer" data-value="2"></i>
                  <i class="fa-regular fa-star star cursor-pointer" data-value="3"></i>
                  <i class="fa-regular fa-star star cursor-pointer" data-value="4"></i>
                  <i class="fa-regular fa-star star cursor-pointer" data-value="5"></i>
                </div>
              </div>
              <div class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-600" id="ratingText">0 / 5</div>
            </div>
          </div>
        </div>
      </div>

      <div id="applicantInterviewPanel" class="hidden">
        <div class="grid gap-5 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
          <div class="flex min-h-[520px] flex-col rounded-[1.35rem] border border-sky-200 bg-sky-50/80 p-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Interview Setup</p>
              <h3 id="interviewSetupTitle" class="mt-3 text-2xl font-black text-slate-900">Set Initial Interview</h3>
              <div class="mt-5 rounded-[1.1rem] border border-sky-100 bg-white/75 p-4">
                <div class="flex items-center gap-4">
                  <div class="flex h-[3.25rem] w-[3.25rem] shrink-0 items-center justify-center rounded-[1rem] bg-[linear-gradient(135deg,#0ea5e9,#2563eb)] text-base font-bold text-white" id="interviewPanelInitials">AP</div>
                  <div class="min-w-0">
                    <p class="truncate font-bold text-slate-900" id="interviewPanelName">Applicant</p>
                    <p class="mt-1 text-sm text-slate-500" id="interviewPanelPosition">Position not specified</p>
                  </div>
                </div>
                <p class="mt-3 text-xs font-semibold text-emerald-700">Documents verified and ready for scheduling.</p>
              </div>

              <div id="interviewScheduleList" class="mt-4 hidden space-y-3"></div>

              <div id="interviewDecisionActions" class="mt-4 hidden rounded-2xl border border-emerald-200 bg-white/80 p-3">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-emerald-700">Interview Decision</p>
                <div class="mt-3 flex flex-wrap gap-2">
                  <button type="button" onclick="rejectApplicantAfterInterview()" class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-50">
                    <i class="fa-solid fa-xmark"></i>
                    Reject
                  </button>
                  <button type="button" id="interviewDecisionProceedButton" onclick="proceedApplicantAfterInterview()" disabled class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span id="interviewDecisionProceedLabel">Proceed</span>
                  </button>
                </div>
                <p id="interviewDecisionHint" class="mt-2 text-xs font-semibold text-slate-500">Finish the required stage before proceeding.</p>
              </div>
            </div>
            <div class="mt-auto flex flex-wrap items-center justify-start gap-3 pt-6">
              <button type="button" onclick="showApplicantReviewPanel()" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">Back</button>
            </div>
          </div>

          <form class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm" action="{{ route('admin.storeNewInterview') }}" method="POST" id="applicantInterviewPanelForm">
            @csrf
            <input type="hidden" id="panel_applicants_id" name="applicants_id">
            <input type="hidden" id="panel_next_interview_confirmed" name="next_interview_confirmed" value="0">

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Interview Type</label>
                <select name="interview_type" id="panel_interview_type" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300">
                  <option value="Initial Interview">Initial Interview</option>
                  <option value="Final Interview">Final Interview</option>
                  <option value="Demo Teaching">Demo Teaching</option>
                </select>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                <input type="date" name="date" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300">
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Time</label>
                <input type="time" name="time" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300">
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Duration</label>
                <select name="duration" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300">
                  <option value="5 minutes">5 minutes</option>
                  <option value="30 minutes">30 minutes</option>
                  <option value="45 minutes">45 minutes</option>
                  <option value="60 minutes">60 minutes</option>
                  <option value="90 minutes">90 minutes</option>
                </select>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Interviewer(s)</label>
                <input type="text" name="interviewers" placeholder="Enter interviewer name(s)" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300">
              </div>

              <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Email Address</label>
                <input type="email" name="email_link" id="panel_email_link" placeholder="Enter email address" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300">
              </div>

              <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Meeting Link (Optional)</label>
                <input type="url" name="url" placeholder="https://meet.google.com/..." class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300">
              </div>

              <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Notes (Optional)</label>
                <textarea name="notes" placeholder="Add any additional notes or instructions..." class="h-24 w-full resize-none rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-sky-300"></textarea>
              </div>
            </div>

            <div class="mt-5 flex justify-end">
              <button type="submit" id="saveInterviewButton" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">Save Interview</button>
            </div>
          </form>
        </div>
      </div>

      <div id="applicantPassingDocumentPanel" class="hidden">
        <div class="grid gap-5 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
          <div class="flex min-h-[520px] flex-col rounded-[1.35rem] border border-emerald-200 bg-emerald-50/80 p-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Passing Document</p>
              <h3 class="mt-3 text-2xl font-black text-slate-900">Final Document Check</h3>
              <div class="mt-5 rounded-[1.1rem] border border-emerald-100 bg-white/80 p-4">
                <div class="flex items-center gap-4">
                  <div class="flex h-[3.25rem] w-[3.25rem] shrink-0 items-center justify-center rounded-[1rem] bg-[linear-gradient(135deg,#10b981,#059669)] text-base font-bold text-white" id="passingPanelInitials">AP</div>
                  <div class="min-w-0">
                    <p class="truncate font-bold text-slate-900" id="passingPanelName">Applicant</p>
                    <p class="mt-1 text-sm text-slate-500" id="passingPanelPosition">Position not specified</p>
                  </div>
                </div>
                <p class="mt-3 text-xs font-semibold text-emerald-700">Applicant passed the interview stage. Review the submitted final documents before completion.</p>
              </div>

              <div class="mt-4 rounded-[1.1rem] border border-emerald-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Review Progress</p>
                    <p id="passingDocumentProgress" class="mt-2 text-xl font-black text-slate-900">0/0 reviewed</p>
                  </div>
                  <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                    <i class="fa-solid fa-file-circle-check"></i>
                  </span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                  <div id="passingDocumentProgressBar" class="h-full w-0 rounded-full bg-emerald-500 transition-all"></div>
                </div>
                <p id="passingDocumentHint" class="mt-3 text-xs font-semibold text-slate-500">View or download every document to enable completion.</p>
              </div>

              <div id="hireApplicantPanel" class="mt-4 hidden rounded-[1.1rem] border border-sky-200 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                  <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                    <i class="fa-solid fa-user-check"></i>
                  </span>
                  <div>
                    <p class="text-sm font-black text-slate-900">Ready for Hiring</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Documents are complete. Confirm hiring to move this applicant into Hired status.</p>
                  </div>
                </div>
                <label class="mt-4 block">
                  <span class="mb-1 block text-xs font-bold uppercase tracking-[0.14em] text-sky-700">Report / Start Date</span>
                  <input type="date" id="hireApplicantStartDate" class="w-full rounded-2xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-sky-300 focus:bg-white">
                </label>
              </div>
            </div>

            <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-6">
              <button type="button" onclick="showApplicantReviewPanel()" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">Back</button>
              <div class="flex flex-wrap gap-2">
                <button type="button" onclick="rejectApplicantFromFinalReview()" class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                  <i class="fa-solid fa-xmark"></i>
                  Reject
                </button>
                <button type="button" id="markPassingDocumentCompleteButton" onclick="markApplicantDocumentsComplete()" disabled class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">Mark Complete</button>
                <button type="button" id="hireApplicantButton" onclick="hireApplicantFromCompleted()" class="hidden rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">Hire Applicant</button>
              </div>
            </div>
          </div>

          <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Submitted Documents</p>
                <h4 class="mt-2 text-xl font-black text-slate-900">Document Checklist</h4>
              </div>
              <span id="passingDocumentsCount" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">0 files</span>
            </div>
            <div id="passingDocumentsList" class="applicant-doc-list mt-5 space-y-4"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<form id="cancelApplicantInterviewForm" method="POST" class="hidden">
  @csrf
</form>

<script>
  let applicants = @json($applicant);
  let currentApplicantMonth = @json(now()->format('Y-m'));
  const applicantSnapshotUrl = @json(route('admin.adminApplicant.snapshot'));
  let applicantSnapshotSignature = @json($applicantSnapshotSignature ?? null);
  const recentlyScheduledApplicantId = @json(session('scheduled_applicant_id'));
  const recentlyUpdatedApplicantId = @json(session('updated_applicant_id'));
  const recentlyUpdatedApplicantStatus = @json(session('updated_applicant_status'));
  const interviewScheduleConflictMessage = @json(session('interview_schedule_conflict'));
  const rowsPerPage = 5;
  let currentPage = 1;
  let currentApplicantId = null;
  let activeDashboardFilter = 'all';
  let currentApplicantDocumentKeys = new Set();
  let downloadedApplicantDocuments = new Set();
  let currentApplicantModalData = null;
  let interviewCountdownTimer = null;
  let autoUnderReviewSubmitted = false;
  let applicantRefreshInFlight = false;
  let shouldWarnOnInterviewPanelOpen = false;

  const initApplicantPageAnimation = () => {
    const page = document.getElementById('admin-applicant-page');
    if (!page) return;

    const revealItems = Array.from(page.querySelectorAll('.applicant-reveal'));
    if (!revealItems.length) return;

    if (!('IntersectionObserver' in window)) {
      revealItems.forEach((item) => item.classList.add('is-visible'));
      return;
    }

    let lastScrollY = window.scrollY;
    let scrollDirection = 'down';

    window.addEventListener('scroll', () => {
      const currentScrollY = window.scrollY;
      scrollDirection = currentScrollY < lastScrollY ? 'up' : 'down';
      lastScrollY = currentScrollY;
    }, { passive: true });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.toggle('reveal-from-top', scrollDirection === 'up');
          entry.target.classList.add('is-visible');
          return;
        }

        entry.target.classList.remove('is-visible');
      });
    }, {
      root: null,
      threshold: 0.12,
      rootMargin: '-8% 0px -8% 0px',
    });

    revealItems.forEach((item) => observer.observe(item));
  };

  const statusClasses = {
    pending: 'bg-amber-100 text-amber-700',
    'under review': 'bg-sky-100 text-sky-700',
    'initial interview': 'bg-blue-100 text-blue-700',
    'final interview': 'bg-violet-100 text-violet-700',
    'demo teaching': 'bg-cyan-100 text-cyan-700',
    'passing document': 'bg-orange-100 text-orange-700',
    completed: 'bg-emerald-100 text-emerald-700',
    hired: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-rose-100 text-rose-700',
    default: 'bg-slate-100 text-slate-700'
  };

  function normalizeText(value) {
    return (value ?? '').toString().trim().toLowerCase();
  }

  function getInitials(firstName, lastName) {
    const source = `${firstName ?? ''} ${lastName ?? ''}`.trim();
    if (!source) {
      return 'AP';
    }

    return source
      .split(/\s+/)
      .slice(0, 2)
      .map(part => part.charAt(0).toUpperCase())
      .join('');
  }

  function formatDate(dateValue) {
    if (!dateValue) {
      return 'N/A';
    }

    const parsed = new Date(dateValue);
    if (Number.isNaN(parsed.getTime())) {
      return dateValue;
    }

    return parsed.toLocaleDateString(undefined, {
      month: 'numeric',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function dateInputValue(dateValue) {
    if (!dateValue) {
      return '';
    }

    const raw = dateValue.toString();
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
      return raw.slice(0, 10);
    }

    const parsed = new Date(raw);
    return Number.isNaN(parsed.getTime()) ? '' : parsed.toISOString().slice(0, 10);
  }

  function formatInterviewTime(timeValue) {
    if (!timeValue) {
      return 'N/A';
    }

    const [hours, minutes] = timeValue.toString().split(':');
    const parsed = new Date();
    parsed.setHours(Number(hours || 0), Number(minutes || 0), 0, 0);

    return parsed.toLocaleTimeString(undefined, {
      hour: 'numeric',
      minute: '2-digit'
    });
  }

  function getInterviewTimeParts(timeValue) {
    if (!timeValue) {
      return { time: '--:--', meridiem: '--' };
    }

    const formatted = formatInterviewTime(timeValue);
    const match = formatted.match(/^(.+?)\s*([AP]M)$/i);
    return {
      time: match ? match[1] : formatted,
      meridiem: match ? match[2].toUpperCase() : ''
    };
  }

  function formatInterviewDateBadge(dateValue) {
    if (!dateValue) {
      return 'Not set';
    }

    const today = new Date();
    const parsed = new Date(dateValue);
    if (Number.isNaN(parsed.getTime())) {
      return dateValue;
    }

    const todayKey = today.toISOString().slice(0, 10);
    const dateKey = parsed.toISOString().slice(0, 10);
    if (todayKey === dateKey) {
      return 'Today';
    }

    return parsed.toLocaleDateString(undefined, {
      month: 'short',
      day: 'numeric'
    });
  }

  function formatCountdownDistance(milliseconds) {
    const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (days > 0) {
      return `${days}d ${hours}h ${minutes}m`;
    }

    return `${hours}h ${minutes}m ${seconds}s`;
  }

  function buildRatingStars(rating = 0) {
    return Array.from({ length: 5 }, (_, index) => {
      const filled = index < Number(rating || 0);
      return `<i class="${filled ? 'fa-solid text-amber-400' : 'fa-regular text-slate-300'} fa-star"></i>`;
    }).join('');
  }

  function getFilteredApplicants() {
    const searchTerm = normalizeText(document.getElementById('headerApplicantSearch')?.value);
    const positionFilter = normalizeText(document.getElementById('applicantPositionFilter')?.value);
    const statusFilter = normalizeText(document.getElementById('applicantStatusFilter')?.value);

    return applicants.filter(app => {
      const fullName = `${app.first_name ?? ''} ${app.last_name ?? ''}`.trim();
      const position = app.position?.title ?? '';
      const status = app.application_status ?? '';
      const email = app.email ?? '';
      const dateText = formatDate(app.created_at);

      const matchesSearch = !searchTerm || [
        fullName,
        email,
        position,
        status,
        dateText
      ].some(value => normalizeText(value).includes(searchTerm));

      const matchesPosition = !positionFilter || normalizeText(position) === positionFilter;
      const matchesStatus = !statusFilter || normalizeText(status) === statusFilter;
      const normalizedStatus = normalizeText(status);
      const applicationMonth = (app.date_hired || app.created_at || '').toString().slice(0, 7);
      const matchesDashboardFilter = (() => {
        if (activeDashboardFilter === 'pending') {
          return normalizedStatus === 'pending';
        }

        if (activeDashboardFilter === 'interview') {
          return ['initial interview', 'final interview', 'demo teaching'].includes(normalizedStatus);
        }

        if (activeDashboardFilter === 'hired_month') {
          return normalizedStatus === 'hired' && applicationMonth === currentApplicantMonth;
        }

        return true;
      })();

      return matchesSearch && matchesPosition && matchesStatus && matchesDashboardFilter;
    });
  }

  function updateDashboardCardState() {
    document.querySelectorAll('[data-applicant-dashboard-filter]').forEach(card => {
      const isActive = card.dataset.applicantDashboardFilter === activeDashboardFilter;
      card.classList.toggle('border-sky-300', isActive);
      card.classList.toggle('ring-2', isActive);
      card.classList.toggle('ring-sky-200', isActive);
      card.classList.toggle('bg-sky-50/70', isActive);
    });
  }

  function setSelectValueIfPresent(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const normalizedValue = normalizeText(value);
    const option = Array.from(select.options).find(item => normalizeText(item.value) === normalizedValue);
    select.value = option ? option.value : '';
  }

  function updateApplicantStatCounts(counts = {}) {
    const countMap = {
      total: counts.total,
      pending: counts.pending,
      interview: counts.interview,
      hired_month: counts.hired_month,
    };

    Object.entries(countMap).forEach(([key, value]) => {
      const target = document.querySelector(`[data-applicant-stat-count="${key}"]`);
      if (target && value !== undefined && value !== null) {
        target.textContent = value;
      }
    });
  }

  function syncApplicantSelectOptions(selectId, label, options = []) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const previousValue = select.value;
    select.innerHTML = `<option value="">${label}</option>`;

    options.forEach(optionLabel => {
      const option = document.createElement('option');
      option.value = normalizeText(optionLabel);
      option.textContent = optionLabel;
      select.appendChild(option);
    });

    setSelectValueIfPresent(selectId, previousValue);
  }

  async function refreshApplicantSnapshot(force = false) {
    if (applicantRefreshInFlight || !applicantSnapshotUrl) return;
    if (!force && document.hidden) return;

    applicantRefreshInFlight = true;

    try {
      const url = new URL(applicantSnapshotUrl, window.location.origin);
      if (applicantSnapshotSignature) {
        url.searchParams.set('signature', applicantSnapshotSignature);
      }

      const response = await fetch(url.toString(), {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      if (!response.ok) return;

      const payload = await response.json();
      applicantSnapshotSignature = payload.signature || applicantSnapshotSignature;

      if (payload.changed === false) return;

      applicants = Array.isArray(payload.applicants) ? payload.applicants : applicants;
      currentApplicantMonth = payload.current_month || currentApplicantMonth;
      updateApplicantStatCounts(payload.counts || {});
      syncApplicantSelectOptions('applicantPositionFilter', 'All Positions', payload.position_options || []);
      syncApplicantSelectOptions('applicantStatusFilter', 'All Status', payload.status_options || []);
      updateDashboardCardState();
      renderTable(currentPage);
    } catch (error) {
      console.warn('Applicant refresh failed.', error);
    } finally {
      applicantRefreshInFlight = false;
    }
  }

  function applyDashboardFilter(filter) {
    activeDashboardFilter = filter || 'all';

    const search = document.getElementById('headerApplicantSearch');
    const position = document.getElementById('applicantPositionFilter');
    if (search) search.value = '';
    if (position) position.value = '';

    if (activeDashboardFilter === 'pending') {
      setSelectValueIfPresent('applicantStatusFilter', 'pending');
    } else if (activeDashboardFilter === 'hired_month') {
      setSelectValueIfPresent('applicantStatusFilter', 'hired');
    } else {
      setSelectValueIfPresent('applicantStatusFilter', '');
    }

    updateDashboardCardState();
    renderTable(1);
    document.getElementById('applicantsTableBody')?.closest('div')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function renderTable(page = 1) {
    const tbody = document.getElementById('applicantsTableBody');
    const emptyState = document.getElementById('applicantEmptyState');
    const filteredApplicants = getFilteredApplicants();
    const totalPages = Math.max(1, Math.ceil(filteredApplicants.length / rowsPerPage));

    currentPage = Math.min(page, totalPages);
    const start = (currentPage - 1) * rowsPerPage;
    const paginatedItems = filteredApplicants.slice(start, start + rowsPerPage);

    tbody.innerHTML = paginatedItems.map(app => {
      const statusKey = normalizeText(app.application_status);
      const badgeClass = statusClasses[statusKey] || statusClasses.default;
      const initials = getInitials(app.first_name, app.last_name);
      const position = app.position?.title ?? 'Unassigned Position';
      const appliedDate = formatDate(app.created_at);
      const fullName = `${app.first_name ?? ''} ${app.last_name ?? ''}`.trim();

      return `
        <tr class="applicant-table-row transition hover:bg-white">
          <td class="px-5 py-4">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-full bg-sky-500 font-semibold text-white">${initials}</div>
              <div>
                <p class="font-semibold text-slate-900">${fullName}</p>
                <p class="text-xs text-slate-400">${app.email ?? ''}</p>
              </div>
            </div>
          </td>
          <td class="px-3 py-4">
            <p class="font-semibold text-slate-800">${position}</p>
          </td>
          <td class="px-3 py-4 text-slate-600">${appliedDate}</td>
          <td class="px-3 py-4">
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${badgeClass}">${app.application_status ?? 'Pending'}</span>
          </td>
          <td class="px-3 py-4">
            <div class="flex items-center gap-1 text-sm">${buildRatingStars(app.starRatings || 0)}</div>
          </td>
          <td class="px-5 py-4">
            <div class="flex items-center gap-2 text-slate-400">
              <button type="button" onclick="openApplicantModal(${app.id})" class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white transition hover:border-sky-200 hover:text-sky-600" title="View applicant">
                <i class="fa-regular fa-eye"></i>
              </button>
              <button type="button" onclick="openApplicantInterviewFromTable(${app.id})" class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white transition hover:border-indigo-200 hover:text-indigo-600" title="Schedule interview">
                <i class="fa-regular fa-calendar"></i>
              </button>
              <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white transition hover:border-rose-200 hover:text-rose-500" title="Remove action">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    emptyState.classList.toggle('hidden', filteredApplicants.length !== 0);
    renderPagination(filteredApplicants.length);
  }

  function renderPagination(totalItems) {
    const pagination = document.getElementById('paginationControls');
    pagination.innerHTML = '';

    const pageCount = Math.ceil(totalItems / rowsPerPage);
    if (pageCount <= 1) {
      return;
    }

    for (let i = 1; i <= pageCount; i++) {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = i;
      button.className = `flex h-10 w-10 items-center justify-center rounded-full border text-sm font-semibold transition ${
        i === currentPage
          ? 'border-slate-900 bg-slate-900 text-white'
          : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'
      }`;
      button.addEventListener('click', () => renderTable(i));
      pagination.appendChild(button);
    }
  }

  function showFlexModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function hideFlexModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function scheduleInterview() {
    shouldWarnOnInterviewPanelOpen = true;
    showApplicantInterviewPanel(true);
  }

  function activeApplicantInterviewSchedule() {
    const interviews = Array.isArray(currentApplicantModalData?.interviews)
      ? currentApplicantModalData.interviews
      : [];

    return interviews.find(interview => !Boolean(interview?.is_finished)) || null;
  }

  function showInterviewScheduleConflict(message, activeInterview = null) {
    const type = activeInterview?.interview_type || 'interview';
    const date = activeInterview?.date ? formatDate(activeInterview.date) : '';
    const scheduleDetails = date
      ? `<p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">Active schedule: ${escapeApplicantHtml(type)} on ${escapeApplicantHtml(date)}</p>`
      : '';

    Swal.fire({
      icon: 'warning',
      title: 'Only one schedule is allowed',
      html: `
        <p class="text-sm leading-6 text-slate-600">${escapeApplicantHtml(message || 'This applicant already has an active interview schedule.')}</p>
        ${scheduleDetails}
        <p class="mt-3 text-xs font-semibold text-slate-500">Please reschedule, finish, or cancel the existing interview before creating another one.</p>
      `,
      confirmButtonText: 'View Existing Schedule',
      confirmButtonColor: '#d97706',
      customClass: {
        popup: 'interview-conflict-popup',
      },
    }).then(() => {
      document.getElementById('interviewScheduleList')?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      });
    });
  }

  function warnAboutApplicantInterviewState() {
    const activeInterview = activeApplicantInterviewSchedule();
    if (activeInterview) {
      window.setTimeout(() => {
        showInterviewScheduleConflict(
          `This applicant is still assigned to an active ${activeInterview.interview_type || 'interview'}.`,
          activeInterview
        );
      }, 180);
      return;
    }

    const status = normalizeText(currentApplicantModalData?.status);
    const interviews = Array.isArray(currentApplicantModalData?.interviews)
      ? currentApplicantModalData.interviews
      : [];
    const nextInterviewUnlocked = Boolean(currentApplicantModalData?.pending_next_interview_type);

    if (['hired', 'rejected', 'completed', 'passing document'].includes(status)) {
      window.setTimeout(() => {
        Swal.fire({
          icon: 'info',
          title: status === 'hired' ? 'Applicant already hired' : 'Interview scheduling is closed',
          text: `This applicant is already in the ${currentApplicantModalData?.status || status} stage. Another interview cannot be scheduled.`,
          confirmButtonText: 'OK',
          confirmButtonColor: '#0f172a',
          customClass: { popup: 'interview-conflict-popup' },
        });
      }, 180);
      return;
    }

    if (
      interviews.length > 0
      && ['initial interview', 'final interview', 'demo teaching'].includes(status)
      && !nextInterviewUnlocked
    ) {
      window.setTimeout(() => {
        Swal.fire({
          icon: 'warning',
          title: 'Interview decision required',
          text: 'The previous interview has ended, but this applicant is still in the interview stage. Choose Proceed or Reject before creating another schedule.',
          confirmButtonText: 'Review Interview',
          confirmButtonColor: '#d97706',
          customClass: { popup: 'interview-conflict-popup' },
        });
      }, 180);
    }
  }

  function openApplicantInterviewFromTable(appId) {
    if (!appId) return;

    shouldWarnOnInterviewPanelOpen = true;
    openApplicantModal(appId, true);
  }

  function renderSkills(skillsValue) {
    const skillsContainer = document.getElementById('skills');
    skillsContainer.innerHTML = '';

    const skills = (skillsValue || '')
      .split(',')
      .map(skill => skill.trim())
      .filter(Boolean);

    if (!skills.length) {
      skillsContainer.innerHTML = '<span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500">No skills listed</span>';
      return;
    }

    skillsContainer.innerHTML = skills.map(skill =>
      `<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700">${skill}</span>`
    ).join('');
  }

  function applicantDocumentStorageKey() {
    return `admin_applicant_downloaded_documents:${currentApplicantId || 'none'}`;
  }

  function saveDownloadedApplicantDocuments() {
    try {
      localStorage.setItem(applicantDocumentStorageKey(), JSON.stringify(Array.from(downloadedApplicantDocuments)));
    } catch (_) {
      // Ignore storage errors.
    }
  }

  function loadDownloadedApplicantDocuments() {
    try {
      const key = applicantDocumentStorageKey();
      const savedDocuments = localStorage.getItem(key) || sessionStorage.getItem(key) || '[]';
      downloadedApplicantDocuments = new Set(JSON.parse(savedDocuments));

      if (!localStorage.getItem(key) && downloadedApplicantDocuments.size > 0) {
        saveDownloadedApplicantDocuments();
      }
    } catch (_) {
      downloadedApplicantDocuments = new Set();
    }
  }

  function getApplicantDocumentKey(doc) {
    return btoa(unescape(encodeURIComponent(`${doc?.url ?? ''}|${doc?.name ?? ''}`)));
  }

  function applicantCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
      || document.querySelector('input[name="_token"]')?.value
      || '';
  }

  function updateDocumentReviewProgress() {
    const nextButton = document.getElementById('nextApplicantButton');
    const progress = document.getElementById('documentReviewProgress');
    if (!nextButton || !currentApplicantId) return;

    const total = currentApplicantDocumentKeys.size;
    const reviewed = Array.from(currentApplicantDocumentKeys)
      .filter(key => downloadedApplicantDocuments.has(key))
      .length;
    const isComplete = total > 0 && reviewed >= total;

    nextButton.disabled = !isComplete;
    nextButton.title = isComplete ? 'Set initial interview' : 'Review all documents first';
    if (progress) {
      progress.textContent = total
        ? `${reviewed}/${total} documents reviewed`
        : 'No documents available to review';
      progress.className = `text-center text-xs font-semibold ${isComplete ? 'text-emerald-600' : 'text-slate-400'}`;
    }

    document.querySelectorAll('[data-document-key]').forEach(row => {
      row.classList.toggle('is-downloaded', downloadedApplicantDocuments.has(row.dataset.documentKey));
    });
    updatePassingDocumentProgress();
    autoMovePendingApplicantToUnderReview(isComplete);
  }

  function markApplicantDocumentDownloaded(key, documentId = null) {
    if (!key) return;

    downloadedApplicantDocuments.add(key);
    saveDownloadedApplicantDocuments();
    updateDocumentReviewProgress();

    if (!documentId) return;

    fetch(`/system/applicant/document/${encodeURIComponent(documentId)}/reviewed`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': applicantCsrfToken(),
      },
    }).catch(() => {
      // Local progress is kept so the admin can continue reviewing even if the request is interrupted.
    });
  }

  function autoMovePendingApplicantToUnderReview(isComplete) {
    if (
      !isComplete
      || autoUnderReviewSubmitted
      || !currentApplicantId
      || normalizeText(currentApplicantModalData?.status) !== 'pending'
    ) {
      return;
    }

    autoUnderReviewSubmitted = true;
    document.getElementById('statusId').value = currentApplicantId;
    document.getElementById('statusAutoValue').value = 'Under Review';
    document.getElementById('updateStatus').submit();
  }

  function showApplicantReviewPanel() {
    document.getElementById('applicantInterviewPanel')?.classList.add('hidden');
    document.getElementById('applicantPassingDocumentPanel')?.classList.add('hidden');
    document.getElementById('applicantReviewPanel')?.classList.remove('hidden');
  }

  function showApplicantInterviewPanel(skipDocumentGate = false) {
    updateDocumentReviewProgress();
    const nextButton = document.getElementById('nextApplicantButton');
    if (!skipDocumentGate && nextButton?.disabled) return;

    document.getElementById('panel_applicants_id').value = currentApplicantId || '';
    document.getElementById('panel_email_link').value = currentApplicantModalData?.email || '';
    document.getElementById('interviewPanelInitials').innerText = getInitials(currentApplicantModalData?.name, '');
    document.getElementById('interviewPanelName').innerText = currentApplicantModalData?.name || 'Applicant';
    document.getElementById('interviewPanelPosition').innerText = currentApplicantModalData?.title || 'Position not specified';
    configureInterviewTypeOptions();
    renderInterviewScheduleSummary(currentApplicantModalData?.latest_interview);
    updateInterviewProceedButton();

    document.getElementById('applicantReviewPanel')?.classList.add('hidden');
    document.getElementById('applicantPassingDocumentPanel')?.classList.add('hidden');
    document.getElementById('applicantInterviewPanel')?.classList.remove('hidden');
    if (shouldWarnOnInterviewPanelOpen) {
      shouldWarnOnInterviewPanelOpen = false;
      warnAboutApplicantInterviewState();
    }
  }

  function showApplicantPassingDocumentPanel() {
    document.getElementById('passingPanelInitials').innerText = getInitials(currentApplicantModalData?.name, '');
    document.getElementById('passingPanelName').innerText = currentApplicantModalData?.name || 'Applicant';
    document.getElementById('passingPanelPosition').innerText = currentApplicantModalData?.title || 'Position not specified';
    renderPassingDocumentChecklist();
    updatePassingDocumentProgress();

    document.getElementById('applicantReviewPanel')?.classList.add('hidden');
    document.getElementById('applicantInterviewPanel')?.classList.add('hidden');
    document.getElementById('applicantPassingDocumentPanel')?.classList.remove('hidden');
  }

  function focusInterviewForm() {
    const form = document.getElementById('applicantInterviewPanelForm');
    form?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    form?.querySelector('input[name="date"]')?.focus({ preventScroll: true });
  }

  function configureInterviewTypeOptions() {
    const select = document.getElementById('panel_interview_type');
    if (!select) {
      return;
    }

    const progress = currentApplicantModalData?.interview_progress || {};
    const isTeaching = Boolean(progress.is_teaching);
    const status = normalizeText(currentApplicantModalData?.status);
    const pendingNextInterviewType = currentApplicantModalData?.pending_next_interview_type || '';
    const title = document.getElementById('interviewSetupTitle');
    const saveButton = document.getElementById('saveInterviewButton');
    const options = Array.from(select.options);
    const initialOption = options.find(option => option.value === 'Initial Interview');
    const finalOption = Array.from(select.options).find(option => option.value === 'Final Interview');
    const demoOption = Array.from(select.options).find(option => option.value === 'Demo Teaching');
    const completedStages = {
      'Initial Interview': Boolean(progress.completed_initial),
      'Final Interview': Boolean(progress.completed_final),
      'Demo Teaching': Boolean(progress.completed_demo_teaching),
    };
    let allowedType = 'Initial Interview';

    if (isTeaching && status === 'demo teaching') {
      allowedType = 'Demo Teaching';
    } else if (!isTeaching && status === 'final interview') {
      allowedType = 'Final Interview';
    } else if (isTeaching && pendingNextInterviewType === 'Demo Teaching') {
      allowedType = 'Demo Teaching';
    } else if (!isTeaching && pendingNextInterviewType === 'Final Interview') {
      allowedType = 'Final Interview';
    }

    if (completedStages[allowedType]) {
      allowedType = '';
    }

    if (initialOption) {
      initialOption.hidden = allowedType !== 'Initial Interview' || completedStages['Initial Interview'];
      initialOption.disabled = allowedType !== 'Initial Interview' || completedStages['Initial Interview'];
    }
    if (finalOption) {
      finalOption.hidden = isTeaching || allowedType !== 'Final Interview' || completedStages['Final Interview'];
      finalOption.disabled = isTeaching || allowedType !== 'Final Interview' || completedStages['Final Interview'];
    }
    if (demoOption) {
      demoOption.hidden = !isTeaching || allowedType !== 'Demo Teaching' || completedStages['Demo Teaching'];
      demoOption.disabled = !isTeaching || allowedType !== 'Demo Teaching' || completedStages['Demo Teaching'];
    }

    select.disabled = allowedType === '';
    if (allowedType !== '') {
      select.value = allowedType;
    }
    if (saveButton) {
      saveButton.disabled = allowedType === '';
      saveButton.title = allowedType === ''
        ? 'Click Proceed before scheduling the next interview stage.'
        : `Save ${allowedType}`;
    }
    if (title) {
      title.textContent = allowedType === '' ? 'Proceed Required' : `Set ${allowedType}`;
    }

    const confirmedInput = document.getElementById('panel_next_interview_confirmed');
    if (confirmedInput) {
      confirmedInput.value = pendingNextInterviewType === allowedType ? '1' : '0';
    }
  }

  function renderPassingDocumentChecklist() {
    const list = document.getElementById('passingDocumentsList');
    const count = document.getElementById('passingDocumentsCount');
    const documents = Array.isArray(currentApplicantModalData?.documents) ? currentApplicantModalData.documents : [];
    if (count) {
      count.textContent = `${documents.length} ${documents.length === 1 ? 'file' : 'files'}`;
    }

    if (!list) return;

    if (!documents.length) {
      list.innerHTML = '<p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm font-semibold text-slate-400">No documents submitted yet.</p>';
      return;
    }

    const groupedDocs = documents.reduce((groups, doc) => {
      const type = doc.type || 'Other Documents';
      groups[type] = groups[type] || [];
      groups[type].push(doc);
      return groups;
    }, {});

    list.innerHTML = Object.entries(groupedDocs).map(([type, docs]) => `
      <section class="space-y-2">
        <div class="flex items-center justify-between gap-3">
          <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">${type}</p>
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">${docs.length}</span>
        </div>
        <div class="space-y-2">
          ${docs.map(doc => `
            <div class="applicant-doc-card flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5" data-document-key="${getApplicantDocumentKey(doc)}">
              <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                  <i class="fa-regular fa-file-lines"></i>
                </div>
                <div class="min-w-0">
                  <p class="max-w-[280px] truncate text-sm font-semibold text-slate-800" title="${doc.name}">${doc.name}</p>
                  <p class="text-xs font-medium text-slate-400">View or download to mark reviewed</p>
                </div>
              </div>
              <div class="flex shrink-0 items-center gap-1.5">
                <span class="applicant-doc-check flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-600" title="Reviewed">
                  <i class="fa-solid fa-check text-xs"></i>
                </span>
                <a href="${doc.preview_url || doc.url}" target="_blank" onclick="markApplicantDocumentDownloaded('${getApplicantDocumentKey(doc)}', ${Number(doc.id) || 'null'})" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-sky-100 hover:text-sky-600" title="View ${doc.name}">
                  <i class="fa-regular fa-eye"></i>
                </a>
                <a href="${doc.download_url || doc.url}" target="_blank" onclick="markApplicantDocumentDownloaded('${getApplicantDocumentKey(doc)}', ${Number(doc.id) || 'null'})" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-emerald-100 hover:text-emerald-600" title="Download ${doc.name}">
                  <i class="fa-solid fa-download"></i>
                </a>
              </div>
            </div>
          `).join('')}
        </div>
      </section>
    `).join('');
  }

  function updatePassingDocumentProgress() {
    const progress = document.getElementById('passingDocumentProgress');
    const progressBar = document.getElementById('passingDocumentProgressBar');
    const hint = document.getElementById('passingDocumentHint');
    const completeButton = document.getElementById('markPassingDocumentCompleteButton');
    const hirePanel = document.getElementById('hireApplicantPanel');
    const hireButton = document.getElementById('hireApplicantButton');
    const isCompletedStatus = normalizeText(currentApplicantModalData?.status) === 'completed';
    const isHiredStatus = normalizeText(currentApplicantModalData?.status) === 'hired';
    const total = currentApplicantDocumentKeys.size;
    const reviewed = Array.from(currentApplicantDocumentKeys)
      .filter(key => downloadedApplicantDocuments.has(key))
      .length;
    const isComplete = total > 0 && reviewed >= total;
    const percent = total ? Math.round((reviewed / total) * 100) : 0;

    if (progress) progress.textContent = `${reviewed}/${total} reviewed`;
    if (progressBar) progressBar.style.width = `${percent}%`;
    if (hint) {
      hint.textContent = isComplete
        ? 'All documents reviewed. You can mark this step complete.'
        : 'View or download every document to enable completion.';
      hint.className = `mt-3 text-xs font-semibold ${isComplete ? 'text-emerald-700' : 'text-slate-500'}`;
    }
    if (completeButton) {
      completeButton.classList.toggle('hidden', isCompletedStatus || isHiredStatus);
      completeButton.disabled = !isComplete;
      completeButton.title = isComplete ? 'Mark applicant document step as completed' : 'Review all documents first';
    }
    hirePanel?.classList.toggle('hidden', !isCompletedStatus);
    hireButton?.classList.toggle('hidden', !isCompletedStatus);
    document.querySelectorAll('#passingDocumentsList [data-document-key]').forEach(row => {
      row.classList.toggle('is-downloaded', downloadedApplicantDocuments.has(row.dataset.documentKey));
    });
  }

  function markApplicantDocumentsComplete() {
    const total = currentApplicantDocumentKeys.size;
    const reviewed = Array.from(currentApplicantDocumentKeys)
      .filter(key => downloadedApplicantDocuments.has(key))
      .length;
    if (!currentApplicantId || total === 0 || reviewed < total) return;

    updateApplicantStatusFromModal('Completed', {
      title: 'Mark documents complete?',
      text: 'This applicant has completed the passing document step.',
      icon: 'question',
      confirmButtonText: 'Mark Complete'
    });
  }

  function hireApplicantFromCompleted() {
    if (!currentApplicantId || normalizeText(currentApplicantModalData?.status) !== 'completed') return;
    const startDateInput = document.getElementById('hireApplicantStartDate');
    const startDate = startDateInput?.value || '';

    if (!startDate) {
      startDateInput?.focus();
      Swal.fire({
        icon: 'info',
        title: 'Set report date',
        text: 'Please choose the date the applicant should report or start work.'
      });
      return;
    }

    Swal.fire({
      title: 'Hire applicant?',
      html: `<p class="text-sm text-slate-600">This applicant will report/start work on <strong>${formatDate(startDate)}</strong>.</p>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#059669',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Hire Applicant',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (!result.isConfirmed) {
        return;
      }

      document.getElementById('statusId').value = currentApplicantId;
      document.getElementById('statusAutoValue').value = 'Hired';
      document.getElementById('statusDateHired').value = startDate;
      document.getElementById('updateStatus').submit();
    });
  }

  function updateInterviewProceedButton() {
    const decisionButton = document.getElementById('interviewDecisionProceedButton');
    const decisionLabel = document.getElementById('interviewDecisionProceedLabel');
    const decisionHint = document.getElementById('interviewDecisionHint');

    const progress = currentApplicantModalData?.interview_progress || {};
    const pendingNextInterviewType = currentApplicantModalData?.pending_next_interview_type || '';
    const canProceed = Boolean(progress.can_proceed_passing_document);
    const proceedTarget = progress.proceed_target || 'Next Step';
    const proceedLabel = `Proceed to ${proceedTarget}`;

    if (decisionButton) {
      decisionButton.disabled = !canProceed || Boolean(pendingNextInterviewType);
      decisionButton.title = pendingNextInterviewType
        ? `${pendingNextInterviewType} schedule is already unlocked.`
        : canProceed
        ? proceedLabel
        : progress.is_teaching
          ? 'Finish Initial Interview first, then Demo Teaching'
          : 'Finish Initial Interview first, then Final Interview';
    }

    if (decisionLabel) {
      decisionLabel.textContent = pendingNextInterviewType
        ? `${pendingNextInterviewType} Unlocked`
        : proceedLabel;
    }

    if (decisionHint) {
      decisionHint.textContent = pendingNextInterviewType
        ? `You can now schedule ${pendingNextInterviewType} in the form.`
        : canProceed
        ? `Applicant passed this stage. Next step: ${proceedTarget}.`
        : progress.is_teaching
          ? 'Finish Initial Interview first, then Demo Teaching.'
          : 'Finish Initial Interview first, then Final Interview.';
    }
  }

  function updateInterviewDecisionActions(isFinished) {
    const decisionActions = document.getElementById('interviewDecisionActions');
    const canProceed = Boolean(currentApplicantModalData?.interview_progress?.proceed_target);
    const hasUnlockedNextSchedule = Boolean(currentApplicantModalData?.pending_next_interview_type);
    decisionActions?.classList.toggle('hidden', !isFinished || (!canProceed && !hasUnlockedNextSchedule));
    updateInterviewProceedButton();
  }

  function updateApplicantStatusFromModal(status, options = {}) {
    if (!currentApplicantId) {
      return;
    }

    const title = options.title || `Move applicant to ${status}?`;
    const text = options.text || `This will update the applicant status to ${status}.`;
    const confirmButtonText = options.confirmButtonText || 'Confirm';
    const confirmButtonColor = options.confirmButtonColor || '#0f172a';

    Swal.fire({
      title,
      text,
      icon: options.icon || 'question',
      showCancelButton: true,
      confirmButtonColor,
      cancelButtonColor: '#64748b',
      confirmButtonText,
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (!result.isConfirmed) {
        return;
      }

      document.getElementById('statusId').value = currentApplicantId;
      document.getElementById('statusAutoValue').value = status;
      document.getElementById('statusDateHired').value = options.date_hired || '';
      document.getElementById('updateStatus').submit();
    });
  }

  function rejectApplicantAfterInterview() {
    updateApplicantStatusFromModal('Rejected', {
      title: 'Reject applicant?',
      text: 'This applicant did not pass the interview stage.',
      icon: 'warning',
      confirmButtonColor: '#e11d48',
      confirmButtonText: 'Reject'
    });
  }

  function rejectApplicantFromFinalReview() {
    updateApplicantStatusFromModal('Rejected', {
      title: 'Reject applicant?',
      text: 'This applicant will be rejected during the final document review. This action will update the application status immediately.',
      icon: 'warning',
      confirmButtonColor: '#e11d48',
      confirmButtonText: 'Reject Applicant'
    });
  }

  function proceedApplicantAfterInterview() {
    const progress = currentApplicantModalData?.interview_progress || {};
    const canProceed = Boolean(progress.can_proceed_passing_document);
    const proceedTarget = progress.proceed_target || 'Next Step';
    if (!canProceed) {
      Swal.fire({
        icon: 'info',
        title: 'Interview not complete yet',
        text: progress.is_teaching
          ? 'Teaching applicants need Initial Interview and Demo Teaching.'
          : 'Non-teaching applicants need Initial Interview and Final Interview.'
      });
      return;
    }

    if (['Demo Teaching', 'Final Interview'].includes(proceedTarget)) {
      Swal.fire({
        title: `Prepare ${proceedTarget} schedule?`,
        text: proceedTarget === 'Demo Teaching'
          ? 'This will enable the Demo Teaching schedule form. The applicant status will update only after you save the schedule.'
          : 'This will enable the Final Interview schedule form. The applicant status will update only after you save the schedule.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0f172a',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Proceed',
        cancelButtonText: 'Cancel'
      }).then(result => {
        if (!result.isConfirmed) {
          return;
        }

        currentApplicantModalData.pending_next_interview_type = proceedTarget;
        configureInterviewTypeOptions();
        updateInterviewDecisionActions(true);
        document.getElementById('applicantInterviewPanelForm')?.classList.remove('hidden');
        focusInterviewForm();
      });
      return;
    }

    updateApplicantStatusFromModal(proceedTarget, {
      title: `Proceed to ${proceedTarget}?`,
      text: 'This applicant passed the required interview stages and will move to Passing Document.',
      confirmButtonText: 'Proceed'
    });
  }

  function markCurrentInterviewFinished(interview) {
    if (!currentApplicantModalData || !interview?.interview_type) {
      return;
    }

    currentApplicantModalData.interview_progress = currentApplicantModalData.interview_progress || {};
    const type = interview.interview_type.toString().trim().toLowerCase();

    if (type === 'initial interview') {
      currentApplicantModalData.interview_progress.completed_initial = true;
    }

    if (type === 'final interview') {
      currentApplicantModalData.interview_progress.completed_final = true;
    }

    if (type === 'demo teaching') {
      currentApplicantModalData.interview_progress.completed_demo_teaching = true;
    }

    const progress = currentApplicantModalData.interview_progress;
    const status = normalizeText(currentApplicantModalData.status);
    if (progress.is_teaching) {
      if (status === 'initial interview' && progress.completed_initial && !progress.completed_demo_teaching) {
        progress.proceed_target = 'Demo Teaching';
      } else if (status === 'demo teaching' && progress.completed_initial && progress.completed_demo_teaching) {
        progress.proceed_target = 'Passing Document';
      } else {
        progress.proceed_target = null;
      }
    } else if (status === 'initial interview' && progress.completed_initial && !progress.completed_final) {
      progress.proceed_target = 'Final Interview';
    } else if (status === 'final interview' && progress.completed_initial && progress.completed_final) {
      progress.proceed_target = 'Passing Document';
    } else {
      progress.proceed_target = null;
    }

    progress.can_proceed_passing_document = progress.proceed_target !== null;

    updateInterviewProceedButton();
    configureInterviewTypeOptions();
  }

  function unmarkCurrentInterviewFinished(interview) {
    if (!currentApplicantModalData || !interview?.interview_type) {
      return;
    }

    currentApplicantModalData.interview_progress = currentApplicantModalData.interview_progress || {};
    const progress = currentApplicantModalData.interview_progress;
    const type = interview.interview_type.toString().trim().toLowerCase();

    if (type === 'initial interview') {
      progress.completed_initial = false;
    }

    if (type === 'final interview') {
      progress.completed_final = false;
    }

    if (type === 'demo teaching') {
      progress.completed_demo_teaching = false;
    }

    progress.proceed_target = null;
    progress.can_proceed_passing_document = false;
    updateInterviewProceedButton();
    configureInterviewTypeOptions();
  }

  function cancelApplicantInterview() {
    if (!currentApplicantId) {
      return;
    }

    Swal.fire({
      title: 'Cancel interview?',
      text: 'This will remove the scheduled interview for this applicant.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e11d48',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Yes, cancel it',
      cancelButtonText: 'Keep schedule'
    }).then(result => {
      if (!result.isConfirmed) {
        return;
      }

      const form = document.getElementById('cancelApplicantInterviewForm');
      form.action = `/system/delete/interview/${currentApplicantId}`;
      form.submit();
    });
  }

  function renderInterviewScheduleSummary(interview) {
    const list = document.getElementById('interviewScheduleList');
    if (interviewCountdownTimer) {
      clearInterval(interviewCountdownTimer);
      interviewCountdownTimer = null;
    }

    const interviews = Array.isArray(currentApplicantModalData?.interviews) && currentApplicantModalData.interviews.length
      ? currentApplicantModalData.interviews
      : (interview ? [interview] : []);

    if (!list || interviews.length === 0) {
      list?.classList.add('hidden');
      updateInterviewDecisionActions(false);
      return;
    }

    list.innerHTML = interviews.map((item, index) => {
      const timeParts = getInterviewTimeParts(item.time);
      const isFinished = Boolean(item.is_finished);

      return `
        <div class="interview-schedule-card overflow-hidden rounded-[1.25rem] border ${isFinished ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white'} shadow-sm transition" data-index="${index}" data-interview-id="${item.id || ''}" data-starts-at="${item.starts_at || `${item.date}T${item.time || '00:00'}`}" data-ends-at="${item.ended_at || item.ends_at || item.starts_at || `${item.date}T${item.time || '00:00'}`}" data-type="${item.interview_type || ''}">
          <div class="flex">
            <div class="interview-time-strip flex w-20 shrink-0 flex-col items-center justify-center ${isFinished ? 'bg-emerald-100' : 'bg-indigo-50'} px-2 py-5 text-center">
              <p class="text-[1.35rem] font-black leading-none text-indigo-600">${timeParts.time}</p>
              <p class="mt-1 text-xs font-bold uppercase text-indigo-500">${timeParts.meridiem}</p>
            </div>
            <div class="min-w-0 flex-1 p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-base font-black text-slate-900">${item.interview_type || 'Interview'}</p>
                  <p class="mt-0.5 text-sm text-slate-500">${currentApplicantModalData?.title || 'Position not specified'}</p>
                </div>
                <span class="interview-state-badge ${isFinished ? '' : 'hidden'} shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-emerald-700">Finished</span>
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                  <i class="fa-regular fa-hourglass-half text-slate-400"></i>
                  ${item.duration || 'N/A'}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                  <i class="fa-solid fa-user-group text-slate-400"></i>
                  ${item.interviewers || 'Not set'}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                  <i class="fa-regular fa-calendar text-amber-600"></i>
                  ${formatInterviewDateBadge(item.date)}
                </span>
              </div>
              <p class="interview-countdown mt-3 text-xs font-semibold ${isFinished ? 'text-emerald-700' : 'text-indigo-600'}">Waiting for schedule</p>
              <div class="interview-schedule-actions mt-4 ${isFinished ? 'hidden' : 'flex'} flex-wrap gap-2">
                <button type="button" onclick="focusInterviewForm()" class="interview-reschedule-button inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-700">
                  <i class="fa-regular fa-pen-to-square"></i>
                  Reschedule
                </button>
                <button type="button" onclick="endApplicantInterviewNow(${index})" class="interview-end-now-button hidden items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">
                  <i class="fa-solid fa-flag-checkered"></i>
                  End Now
                </button>
                <button type="button" onclick="extendApplicantInterview(${index}, 15)" class="interview-extend-button hidden items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100">
                  <i class="fa-regular fa-clock"></i>
                  +15 min
                </button>
                <button type="button" onclick="cancelApplicantInterview()" class="interview-cancel-button inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:text-rose-600">
                  <i class="fa-solid fa-ban"></i>
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');

    list.classList.remove('hidden');

    const updateCountdown = () => {
      const now = Date.now();
      let latestIsFinished = false;

      list.querySelectorAll('.interview-schedule-card').forEach((card, index) => {
        const startTime = new Date(card.dataset.startsAt);
        const endTime = new Date(card.dataset.endsAt);
        const countdown = card.querySelector('.interview-countdown');
        const stateBadge = card.querySelector('.interview-state-badge');
        const actions = card.querySelector('.interview-schedule-actions');
        const timeStrip = card.querySelector('.interview-time-strip');
        const rescheduleButton = card.querySelector('.interview-reschedule-button');
        const cancelButton = card.querySelector('.interview-cancel-button');
        const endButton = card.querySelector('.interview-end-now-button');
        const extendButton = card.querySelector('.interview-extend-button');
        if (!countdown || Number.isNaN(startTime.getTime()) || Number.isNaN(endTime.getTime())) return;

        const hasStarted = now >= startTime.getTime();
        const isManuallyEnded = Boolean(interviews[index]?.ended_at);
        const isFinished = isManuallyEnded || now >= endTime.getTime();
        const remainingToStart = startTime.getTime() - now;
        const remainingToEnd = endTime.getTime() - now;

        countdown.textContent = isFinished
          ? (isManuallyEnded ? 'Interview ended early. Ready for the next review step.' : 'Interview finished. Ready for the next review step.')
          : hasStarted
            ? `In progress, ends in ${formatCountdownDistance(remainingToEnd)}`
            : `Starts in ${formatCountdownDistance(remainingToStart)}`;
        countdown.classList.toggle('text-emerald-700', isFinished);
        countdown.classList.toggle('text-indigo-600', !isFinished);

        card.classList.toggle('border-emerald-200', isFinished);
        card.classList.toggle('bg-emerald-50', isFinished);
        card.classList.toggle('border-slate-200', !isFinished);
        card.classList.toggle('bg-white', !isFinished);
        timeStrip?.classList.toggle('bg-emerald-100', isFinished);
        timeStrip?.classList.toggle('bg-indigo-50', !isFinished);
        stateBadge?.classList.toggle('hidden', !isFinished);
        actions?.classList.toggle('hidden', isManuallyEnded);
        actions?.classList.toggle('flex', !isManuallyEnded);
        rescheduleButton?.classList.toggle('hidden', isFinished);
        rescheduleButton?.classList.toggle('inline-flex', !isFinished);
        cancelButton?.classList.toggle('hidden', isFinished);
        cancelButton?.classList.toggle('inline-flex', !isFinished);
        endButton?.classList.toggle('hidden', !hasStarted || isFinished);
        endButton?.classList.toggle('inline-flex', hasStarted && !isFinished);
        extendButton?.classList.toggle('hidden', !hasStarted || isManuallyEnded);
        extendButton?.classList.toggle('inline-flex', hasStarted && !isManuallyEnded);

        if (isFinished) {
          markCurrentInterviewFinished(interviews[index]);
        }

        if (index === interviews.length - 1) {
          latestIsFinished = isFinished;
        }
      });

      updateInterviewDecisionActions(latestIsFinished);
    };

    updateCountdown();
    interviewCountdownTimer = setInterval(updateCountdown, 1000);
  }

  function syncInterviewCardState(index, updates = {}) {
    if (!Array.isArray(currentApplicantModalData?.interviews) || !currentApplicantModalData.interviews[index]) {
      return;
    }

    currentApplicantModalData.interviews[index] = {
      ...currentApplicantModalData.interviews[index],
      ...updates,
    };

    renderInterviewScheduleSummary(currentApplicantModalData.interviews[index]);
  }

  function postInterviewTimingAction(interviewId, url, payload = {}) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': applicantCsrfToken(),
      },
      body: JSON.stringify(payload),
    }).then(async response => {
      const data = await response.json().catch(() => ({}));
      if (!response.ok || data.ok === false) {
        throw new Error(data.message || 'Unable to update interview timing.');
      }

      return data;
    });
  }

  function endApplicantInterviewNow(index) {
    const interview = currentApplicantModalData?.interviews?.[index];
    if (!interview?.id) return;

    Swal.fire({
      title: 'End interview now?',
      text: 'Use this when the interview finished earlier than the scheduled duration.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#059669',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'End Now',
      cancelButtonText: 'Keep running'
    }).then(result => {
      if (!result.isConfirmed) return;

      postInterviewTimingAction(interview.id, `/system/interview/${encodeURIComponent(interview.id)}/end-now`)
        .then(data => {
          syncInterviewCardState(index, {
            ended_at: data.ended_at || new Date().toISOString(),
            is_finished: true,
          });
          Swal.fire({
            icon: 'success',
            title: 'Interview ended',
            text: 'You can now choose the next review step.',
            timer: 1600,
            showConfirmButton: false
          });
        })
        .catch(error => {
          Swal.fire({ icon: 'error', title: 'Unable to end interview', text: error.message });
        });
    });
  }

  function extendApplicantInterview(index, minutes = 15) {
    const interview = currentApplicantModalData?.interviews?.[index];
    if (!interview?.id) return;

    postInterviewTimingAction(interview.id, `/system/interview/${encodeURIComponent(interview.id)}/extend`, { minutes })
      .then(data => {
        const nextEndsAt = data.ends_at || interview.ends_at;
        if (nextEndsAt && new Date(nextEndsAt).getTime() > Date.now()) {
          unmarkCurrentInterviewFinished(interview);
        }

        syncInterviewCardState(index, {
          duration: data.duration || interview.duration,
          ends_at: nextEndsAt,
          ended_at: null,
          is_finished: nextEndsAt ? new Date(nextEndsAt).getTime() <= Date.now() : Boolean(interview.is_finished),
        });
      })
      .catch(error => {
        Swal.fire({ icon: 'error', title: 'Unable to extend interview', text: error.message });
      });
  }

  function escapeApplicantHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function applicantValueIsBlank(value) {
    const normalized = String(value ?? '').trim().toLowerCase();
    return !normalized || ['n/a', 'na', 'none', '-', 'null', 'undefined'].includes(normalized);
  }

  function renderWorkExperience(data) {
    const container = document.getElementById('work_info');
    if (!container) return;

    const rows = [
      ['Position', data?.work_position],
      ['Employer', data?.work_employer],
      ['Location', data?.work_location],
      ['Duration', data?.work_duration],
    ].filter(([, value]) => !applicantValueIsBlank(value));

    if (!rows.length) {
      container.innerHTML = '<p>No work experience information provided.</p>';
      return;
    }

    container.innerHTML = rows.map(([label, value]) => `
      <div class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2">
        <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">${label}</span>
        <span class="mt-1 block font-semibold text-slate-700">${escapeApplicantHtml(value)}</span>
      </div>
    `).join('');
  }

  function renderEducationBackground(educationRows) {
    const container = document.getElementById('university_info');
    if (!container) return;

    const levelLabels = {
      elementary: 'Elementary',
      secondary: 'Secondary',
      vocational_trade: 'Vocational / Trade Course',
      bachelor: 'Bachelor Degree',
      master: 'Master Degree',
      doctorate: 'Doctorate Degree',
    };

    const rows = Array.isArray(educationRows)
      ? educationRows.filter(row => (
          !applicantValueIsBlank(row?.degree_name)
          || !applicantValueIsBlank(row?.school_name)
          || !applicantValueIsBlank(row?.year_finished)
        ))
      : [];

    if (!rows.length) {
      container.innerHTML = '<p>No education information provided.</p>';
      return;
    }

    container.innerHTML = rows.map(row => {
      const level = String(row?.level ?? '').trim();
      const title = levelLabels[level] || level || 'Education';
      const degreeName = applicantValueIsBlank(row?.degree_name) ? '' : String(row.degree_name).trim();
      const schoolName = applicantValueIsBlank(row?.school_name) ? '' : String(row.school_name).trim();
      const yearFinished = applicantValueIsBlank(row?.year_finished) ? '' : String(row.year_finished).trim();
      const details = [
        schoolName,
        yearFinished,
      ].filter(Boolean).join(' - ');

      return `
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 px-3 py-2">
          <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-700">${escapeApplicantHtml(title)}</span>
          ${degreeName ? `<span class="mt-1 block font-semibold text-slate-800">${escapeApplicantHtml(degreeName)}</span>` : ''}
          ${details ? `<span class="mt-1 block text-slate-600">${escapeApplicantHtml(details)}</span>` : ''}
        </div>
      `;
    }).join('');
  }

  function openApplicantModal(applicantId, openInterviewPanel = false, openPassingDocumentPanel = false) {
    currentApplicantId = applicantId;
    currentApplicantModalData = null;
    autoUnderReviewSubmitted = false;
    document.getElementById('statusId').value = applicantId;
    document.getElementById('ratingStarId').value = applicantId;
    loadDownloadedApplicantDocuments();
    currentApplicantDocumentKeys = new Set();

    fetch(`/system/applicants/ID/${applicantId}`)
      .then(res => res.json())
      .then(data => {
        currentApplicantModalData = data;
        showApplicantReviewPanel();
        document.getElementById('name').innerText = data.name;
        document.getElementById('email').innerText = data.email;
        document.getElementById('contact_email').innerText = data.email;
        document.getElementById('title').innerText = data.title;
        document.getElementById('status').innerText = data.status;
        document.getElementById('location').innerText = data.location;
        document.getElementById('one').innerText = data.one;
        document.getElementById('number').innerText = data.number;
        document.getElementById('applicantInitials').innerText = getInitials(data.name, '');
        const hireStartDate = document.getElementById('hireApplicantStartDate');
        if (hireStartDate) {
          hireStartDate.value = dateInputValue(data.date_hired) || new Date().toISOString().slice(0, 10);
        }

        renderWorkExperience(data);
        renderEducationBackground(data.education_background);

        renderSkills(data.skills);

        const docsContainer = document.getElementById('documents');
        const docsCount = document.getElementById('documentsCount');
        const rehireSummary = document.getElementById('rehireSummary');
        docsContainer.innerHTML = '';
        if (docsCount) {
          const count = Array.isArray(data.documents) ? data.documents.length : 0;
          docsCount.innerText = `${count} ${count === 1 ? 'file' : 'files'}`;
        }
        if (rehireSummary) {
          const changedFields = Array.isArray(data?.comparison?.changed_fields) ? data.comparison.changed_fields : [];
          const changedDegrees = Array.isArray(data?.comparison?.changed_degree_levels) ? data.comparison.changed_degree_levels : [];
          if (data?.comparison?.is_rehire) {
            const count = changedFields.length + changedDegrees.length;
            rehireSummary.classList.remove('hidden');
            rehireSummary.innerText = count > 0
              ? `Rehire application detected. ${count} updated field${count === 1 ? '' : 's'} marked as new, and uploaded documents are labeled New.`
              : 'Rehire application detected. Uploaded documents are labeled New for this returning employee.';
          } else {
            rehireSummary.classList.add('hidden');
            rehireSummary.innerText = '';
          }
        }

        if (data.documents && data.documents.length > 0) {
          currentApplicantDocumentKeys = new Set(data.documents.map(doc => getApplicantDocumentKey(doc)));
          data.documents
            .filter(doc => doc.is_reviewed)
            .forEach(doc => downloadedApplicantDocuments.add(getApplicantDocumentKey(doc)));
          saveDownloadedApplicantDocuments();
          const groupedDocs = data.documents.reduce((groups, doc) => {
            const type = doc.type || 'Other Documents';
            groups[type] = groups[type] || [];
            groups[type].push(doc);
            return groups;
          }, {});

          docsContainer.innerHTML = Object.entries(groupedDocs).map(([type, docs]) => `
            <section class="space-y-2">
              <div class="flex items-center justify-between gap-3">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">${type}</p>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">${docs.length}</span>
              </div>
              <div class="space-y-2">
                ${docs.map(doc => `
                  <div class="applicant-doc-card flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5" data-document-key="${getApplicantDocumentKey(doc)}">
                    <div class="flex min-w-0 items-center gap-3">
                      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                        <i class="fa-regular fa-file-lines"></i>
                      </div>
                      <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                          <p class="max-w-[210px] truncate text-sm font-semibold text-slate-800" title="${doc.name}">${doc.name}</p>
                          ${doc.is_new ? '<span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700">New</span>' : ''}
                        </div>
                      </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                      <span class="applicant-doc-check flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-600" title="Downloaded">
                        <i class="fa-solid fa-check text-xs"></i>
                      </span>
                      <a href="${doc.preview_url || doc.url}" target="_blank" onclick="markApplicantDocumentDownloaded('${getApplicantDocumentKey(doc)}', ${Number(doc.id) || 'null'})" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-sky-100 hover:text-sky-600" title="View ${doc.name}">
                        <i class="fa-regular fa-eye"></i>
                      </a>
                      <a href="${doc.download_url || doc.url}" target="_blank" onclick="markApplicantDocumentDownloaded('${getApplicantDocumentKey(doc)}', ${Number(doc.id) || 'null'})" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-sky-100 hover:text-sky-600" title="Download ${doc.name}">
                        <i class="fa-solid fa-download"></i>
                      </a>
                    </div>
                  </div>
                `).join('')}
              </div>
            </section>
          `).join('');
        } else {
          currentApplicantDocumentKeys = new Set();
          docsContainer.innerHTML = '<p class="text-sm text-slate-400">No documents uploaded.</p>';
        }

        setRating(data.star || 0);
        updateDocumentReviewProgress();
        if (
          openPassingDocumentPanel
          || ['passing document', 'completed'].includes(normalizeText(data.status))
        ) {
          showApplicantPassingDocumentPanel();
        } else if (openInterviewPanel) {
          showApplicantInterviewPanel(true);
        }
        showFlexModal('applicantModal');
      });
  }

  function closeApplicantModal() {
    if (interviewCountdownTimer) {
      clearInterval(interviewCountdownTimer);
      interviewCountdownTimer = null;
    }
    hideFlexModal('applicantModal');
  }

  document.getElementById('applicantInterviewPanelForm')?.addEventListener('submit', (event) => {
    const status = normalizeText(currentApplicantModalData?.status);
    if (['hired', 'rejected', 'completed', 'passing document'].includes(status)) {
      event.preventDefault();
      warnAboutApplicantInterviewState();
      return;
    }

    const activeInterview = activeApplicantInterviewSchedule();
    if (!activeInterview) {
      const saveButton = document.getElementById('saveInterviewButton');
      if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving Interview…';
      }
      return;
    }

    event.preventDefault();
    showInterviewScheduleConflict(
      `This applicant already has an active ${activeInterview.interview_type || 'interview'}.`,
      activeInterview
    );
  });

  const stars = document.querySelectorAll('.star');
  const ratingText = document.getElementById('ratingText');
  const ratingInput = document.getElementById('ratingValue');

  stars.forEach(star => {
    star.addEventListener('click', function () {
      const rating = parseInt(this.dataset.value, 10);

      let starsHtml = '';
      for (let i = 1; i <= 5; i++) {
        starsHtml += i <= rating
          ? '<i class="fa-solid fa-star text-amber-400 text-2xl mx-1"></i>'
          : '<i class="fa-regular fa-star text-slate-300 text-2xl mx-1"></i>';
      }

      Swal.fire({
        title: 'Confirm Rating',
        html: `
          <div class="mb-2 flex justify-center">
            ${starsHtml}
          </div>
          <p class="text-sm text-slate-600">Rate this applicant ${rating} / 5</p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, rate',
        cancelButtonText: 'Cancel'
      }).then(result => {
        if (result.isConfirmed) {
          setRating(rating);
          document.getElementById('starRatings').submit();
        }
      });
    });
  });

  function setRating(rating) {
    ratingInput.value = rating;

    stars.forEach(star => {
      if (Number(star.dataset.value) <= Number(rating)) {
        star.classList.remove('fa-regular');
        star.classList.add('fa-solid');
      } else {
        star.classList.remove('fa-solid');
        star.classList.add('fa-regular');
      }
    });

    ratingText.textContent = `${rating} / 5`;
  }

  function confirmStatusChange(select) {
    const newStatus = select.value;
    if (newStatus === '-- Choose Option --') {
      return;
    }

    Swal.fire({
      title: 'Are you sure?',
      text: `Change applicant status to "${newStatus}"?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (!result.isConfirmed) {
        select.value = '-- Choose Option --';
        return;
      }

      document.getElementById('updateStatus').submit();
    });
  }

  document.getElementById('headerApplicantSearch')?.addEventListener('input', () => {
    activeDashboardFilter = 'all';
    updateDashboardCardState();
    renderTable(1);
  });
  document.getElementById('applicantPositionFilter')?.addEventListener('change', () => {
    activeDashboardFilter = 'all';
    updateDashboardCardState();
    renderTable(1);
  });
  document.getElementById('applicantStatusFilter')?.addEventListener('change', () => {
    activeDashboardFilter = 'all';
    updateDashboardCardState();
    renderTable(1);
  });
  document.getElementById('clearApplicantFilters')?.addEventListener('click', () => {
    activeDashboardFilter = 'all';
    const search = document.getElementById('headerApplicantSearch');
    const position = document.getElementById('applicantPositionFilter');
    const status = document.getElementById('applicantStatusFilter');
    if (search) search.value = '';
    if (position) position.value = '';
    if (status) status.value = '';
    updateDashboardCardState();
    renderTable(1);
  });

  document.querySelectorAll('[data-applicant-dashboard-filter]').forEach(card => {
    const handleClick = () => applyDashboardFilter(card.dataset.applicantDashboardFilter || 'all');
    card.addEventListener('click', handleClick);
    card.addEventListener('keydown', event => {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      handleClick();
    });
  });

  document.querySelectorAll('#applicantModal').forEach(modal => {
    modal.addEventListener('click', event => {
      if (event.target === modal) {
        hideFlexModal(modal.id);
      }
    });
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApplicantPageAnimation, { once: true });
  } else {
    initApplicantPageAnimation();
  }

  updateDashboardCardState();
  renderTable(1);

  setInterval(() => refreshApplicantSnapshot(), 15000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      refreshApplicantSnapshot(true);
    }
  });

  if (recentlyScheduledApplicantId) {
    shouldWarnOnInterviewPanelOpen = Boolean(interviewScheduleConflictMessage);
    openApplicantModal(recentlyScheduledApplicantId, true);
  } else if (recentlyUpdatedApplicantId) {
    openApplicantModal(
      recentlyUpdatedApplicantId,
      false,
      ['passing document', 'completed'].includes(normalizeText(recentlyUpdatedApplicantStatus))
    );
  }

</script>

<script>
  const sidebar = document.querySelector('aside');
  const main = document.querySelector('main');
  if (sidebar && main) {
    sidebar.addEventListener('mouseenter', function() {
      main.classList.remove('ml-16');
      main.classList.add('ml-64');
    });
    sidebar.addEventListener('mouseleave', function() {
      main.classList.remove('ml-64');
      main.classList.add('ml-16');
    });
  }
</script>

</body>
</html>
