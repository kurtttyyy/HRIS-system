<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Interview Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    body {
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
      transition: margin-left 0.3s ease;
    }

    main {
      transition: margin-left 0.3s ease;
    }

    aside ~ main {
      margin-left: 16rem;
    }
    .interview-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .interview-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .interview-reveal.is-visible {
      animation: interview-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--interview-delay, 0ms);
    }
    .interview-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .interview-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .interview-icon-pop {
      animation: interview-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--interview-delay, 0ms);
    }
    @keyframes interview-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes interview-pop-in {
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
      .interview-reveal,
      .interview-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .interview-card-motion {
        transition: none;
      }
      .interview-card-motion:hover {
        transform: none;
      }
    }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#f1f5f9_48%,#eefbf6_100%)] text-slate-800">

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    <div id="admin-interview-page" class="space-y-4 px-4 pb-4 pt-3 md:px-8 md:pb-8 md:pt-4">
      <section class="interview-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/60 bg-[linear-gradient(135deg,rgba(2,6,23,0.98)_0%,rgba(2,6,23,0.98)_42%,rgba(17,24,39,0.96)_68%,rgba(6,78,59,0.94)_100%)] px-6 py-6 text-white shadow-[0_25px_70px_rgba(3,19,29,0.2)] md:px-8" style="--interview-delay: 0ms;">
        <div class="absolute -right-14 -top-16 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute bottom-0 right-24 h-28 w-28 rounded-full bg-emerald-300/20 blur-2xl"></div>

        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-100">
              Interview Command Center
            </div>
            <h1 class="mt-3 text-3xl font-black tracking-tight md:text-4xl">Keep every candidate conversation organized, visible, and on time.</h1>
            <p class="mt-2 max-w-2xl text-sm text-emerald-50/85 md:text-base">
              Review today's activity, follow the next interview in line, and reschedule sessions from one focused dashboard.
            </p>
          </div>

          <div class="flex flex-col items-start gap-3 xl:items-stretch">
            <div class="interview-card-motion interview-reveal rounded-[1.5rem] border border-white/15 bg-white/10 px-5 py-4 backdrop-blur" style="--interview-delay: 70ms;">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100">Today</p>
              <p class="mt-2 text-lg font-bold">{{ now()->format('F j, Y') }}</p>
              <p class="text-sm text-emerald-50/80">{{ now()->format('l') }}</p>
            </div>

            <button
              type="button"
              onclick="openEmptyScheduleModal()"
              class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-slate-950/10 transition hover:-translate-y-0.5 hover:bg-slate-100"
            >
              <i class="fa-solid fa-calendar-plus text-emerald-600"></i>
              Schedule Interview
            </button>
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="interview-card-motion interview-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--interview-delay: 110ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Today</p>
              <p id="todayCountValue" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $count_daily }}</p>
              <p class="mt-1 text-sm text-slate-500">Interviews scheduled for today</p>
            </div>
            <div class="text-right">
              <div class="interview-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600" style="--interview-delay: 140ms;">
                <i class="fa-regular fa-calendar-days"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Daily</span>
            </div>
          </div>
        </article>

        <article class="interview-card-motion interview-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--interview-delay: 140ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">This Month</p>
              <p id="monthCountValue" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $count_month }}</p>
              <p class="mt-1 text-sm text-slate-500">Completed interviews this month</p>
            </div>
            <div class="text-right">
              <div class="interview-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600" style="--interview-delay: 170ms;">
                <i class="fa-solid fa-circle-check"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Completed</span>
            </div>
          </div>
        </article>

        <article class="interview-card-motion interview-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--interview-delay: 170ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">This Year</p>
              <p id="yearCountValue" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $count_year }}</p>
              <p class="mt-1 text-sm text-slate-500">Completed interviews this year</p>
            </div>
            <div class="text-right">
              <div class="interview-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600" style="--interview-delay: 200ms;">
                <i class="fa-solid fa-chart-line"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">Yearly</span>
            </div>
          </div>
        </article>

        <article class="interview-card-motion interview-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--interview-delay: 200ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Upcoming</p>
              <p id="upcomingCountValue" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $count_upcoming }}</p>
              <p class="mt-1 text-sm text-slate-500">Sessions waiting in the queue</p>
            </div>
            <div class="text-right">
              <div class="interview-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600" style="--interview-delay: 230ms;">
                <i class="fa-regular fa-clock"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Scheduled</span>
            </div>
          </div>
        </article>
      </section>

      <section class="interview-wrapper interview-reveal rounded-[2rem] border border-white/80 bg-white/92 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur md:p-6" style="--interview-delay: 240ms;">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-700">
              Interview Schedule
            </div>
            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">Daily interview board</h2>
            <p class="mt-1 text-sm text-slate-500">Monitor upcoming sessions and completed conversations in one streamlined timeline.</p>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Focus</p>
              <p class="mt-1 text-sm font-semibold text-slate-700">Next session is highlighted automatically.</p>
            </div>
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Live Status</p>
              <p class="mt-1 text-sm font-semibold text-slate-700">Cards move to completed after their schedule ends.</p>
            </div>
          </div>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
          <section class="interview-reveal rounded-[1.75rem] border border-indigo-100 bg-[linear-gradient(180deg,rgba(238,242,255,0.9),rgba(255,255,255,0.96))] p-5 md:p-6" style="--interview-delay: 280ms;">
            <div class="mb-5 flex items-center justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500">Queue</p>
                <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900">Upcoming Interviews</h3>
              </div>
              <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700 shadow-sm">
                {{ $count_upcoming }} scheduled
              </span>
            </div>

            <div id="upcomingInterviewList" class="space-y-4">
              @forelse($upcomingInterviews as $inter)
                @php
                  $firstName = trim((string) ($inter->applicant->first_name ?? ''));
                  $lastName = trim((string) ($inter->applicant->last_name ?? ''));
                  $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                  $positionTitle = $inter->applicant->position->title ?? $inter->applicant->applied_position ?? '-';
                @endphp
                <article
                  class="interview-card interview-card-motion group relative overflow-hidden rounded-[1.75rem] border border-indigo-100 bg-white/95 p-5 shadow-[0_12px_30px_rgba(79,70,229,0.08)]"
                  data-scheduled-date="{{ $inter->date->format('Y-m-d') }}"
                  data-scheduled-time="{{ \Carbon\Carbon::parse($inter->time)->format('H:i:s') }}"
                  data-duration-minutes="{{ (int) filter_var($inter->duration, FILTER_SANITIZE_NUMBER_INT) }}"
                >
                  <div class="absolute inset-y-5 left-0 w-1 rounded-r-full bg-[linear-gradient(180deg,#6366f1,#0ea5e9)]"></div>

                  <div class="flex flex-col gap-5 pl-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex gap-4">
                      <div class="flex min-w-[82px] flex-col items-center justify-center rounded-[1.25rem] bg-indigo-50 px-3 py-4 text-center">
                        <span class="text-2xl font-black leading-none text-indigo-700">{{ \Carbon\Carbon::parse($inter->time)->format('h:i') }}</span>
                        <span class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500">{{ \Carbon\Carbon::parse($inter->time)->format('A') }}</span>
                      </div>

                      <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                          <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-indigo-700">
                            {{ $inter->interview_type }}
                          </span>
                          <span class="next-interview-label hidden rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700">
                            Next Interview
                          </span>
                        </div>

                        <div class="mt-4 flex items-start gap-3">
                          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sm font-bold text-white">
                            {{ $initials !== '' ? $initials : 'NA' }}
                          </div>
                          <div class="min-w-0">
                            <h4 class="text-lg font-black tracking-tight text-slate-900">{{ $firstName }} {{ $lastName }}</h4>
                            <p class="text-sm text-slate-500">{{ $positionTitle }}</p>
                          </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                          <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
                            <i class="fa-regular fa-hourglass-half text-slate-400"></i>
                            {{ $inter->duration }}
                          </span>
                          <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
                            <i class="fa-solid fa-user-group text-slate-400"></i>
                            {{ $inter->interviewers }}
                          </span>
                          <span class="inline-flex items-center gap-2 rounded-full {{ $inter->date->isToday() ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' }} px-3 py-1.5 text-xs font-semibold">
                            <i class="fa-regular fa-calendar"></i>
                            {{ $inter->date->isToday() ? 'Today' : $inter->date->format('M j, Y') }}
                          </span>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-indigo-600" data-role="time-remaining"></p>

                        <div class="mt-4 flex flex-wrap gap-3">
                          <button
                            class="inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                            onclick="scheduleInterview({{ $inter->applicant_id }})"
                          >
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                            Reschedule
                          </button>
                          <form action="{{ route('admin.interviewCancel', $inter->applicant_id) }}" method="POST">
                            @csrf
                            <button class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-rose-200 hover:text-rose-600" type="submit">
                              <i class="fa-solid fa-ban text-xs"></i>
                              Cancel
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>

                    <div class="flex flex-col items-start gap-2 lg:items-end">
                      <span class="rounded-full bg-indigo-100 px-4 py-1.5 text-sm font-semibold text-indigo-700">
                        {{ $inter->date->isToday() ? 'Today' : 'Upcoming' }}
                      </span>
                    </div>
                  </div>
                </article>
              @empty
                <div id="upcomingEmptyState" class="rounded-[1.5rem] border border-dashed border-indigo-200 bg-white/80 p-8 text-center text-sm text-slate-500">
                  <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-500">
                    <i class="fa-regular fa-calendar-xmark text-lg"></i>
                  </div>
                  <p class="mt-4 font-medium text-slate-700">No upcoming interviews.</p>
                </div>
              @endforelse
            </div>
          </section>

          <section class="interview-reveal rounded-[1.75rem] border border-emerald-100 bg-[linear-gradient(180deg,rgba(236,253,245,0.9),rgba(255,255,255,0.96))] p-5 md:p-6" style="--interview-delay: 320ms;">
            <div class="mb-5 flex items-center justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">Archive</p>
                <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900">Completed Interviews</h3>
              </div>
              <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700 shadow-sm">
                Finished sessions
              </span>
            </div>

            <div id="completedInterviewList" class="space-y-4">
              @forelse($completedInterviews as $inter)
                @php
                  $firstName = trim((string) ($inter->applicant->first_name ?? ''));
                  $lastName = trim((string) ($inter->applicant->last_name ?? ''));
                  $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                  $positionTitle = $inter->applicant->position->title ?? $inter->applicant->applied_position ?? '-';
                @endphp
                <article
                  class="completed-card interview-card-motion relative overflow-hidden rounded-[1.75rem] border border-emerald-100 bg-white/95 p-5 opacity-85 shadow-[0_12px_30px_rgba(16,185,129,0.08)]"
                  data-scheduled-date="{{ $inter->date->format('Y-m-d') }}"
                  data-scheduled-time="{{ \Carbon\Carbon::parse($inter->time)->format('H:i:s') }}"
                  data-duration-minutes="{{ (int) filter_var($inter->duration, FILTER_SANITIZE_NUMBER_INT) }}"
                >
                  <div class="absolute inset-y-5 left-0 w-1 rounded-r-full bg-[linear-gradient(180deg,#10b981,#34d399)]"></div>

                  <div class="flex flex-col gap-4 pl-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex gap-4">
                      <div class="flex min-w-[82px] flex-col items-center justify-center rounded-[1.25rem] bg-emerald-50 px-3 py-4 text-center">
                        <span class="text-2xl font-black leading-none text-emerald-700">{{ \Carbon\Carbon::parse($inter->time)->format('h:i') }}</span>
                        <span class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">{{ \Carbon\Carbon::parse($inter->time)->format('A') }}</span>
                      </div>

                      <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                          <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700">
                            {{ $inter->interview_type }}
                          </span>
                          <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">
                            Completed
                          </span>
                        </div>

                        <div class="mt-4 flex items-start gap-3">
                          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-sm font-bold text-white">
                            {{ $initials !== '' ? $initials : 'NA' }}
                          </div>
                          <div class="min-w-0">
                            <h4 class="text-lg font-black tracking-tight text-slate-900">{{ $firstName }} {{ $lastName }}</h4>
                            <p class="text-sm text-slate-500">{{ $positionTitle }}</p>
                          </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                          <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
                            <i class="fa-regular fa-hourglass-half text-slate-400"></i>
                            {{ $inter->duration }}
                          </span>
                          <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
                            <i class="fa-solid fa-user-group text-slate-400"></i>
                            {{ $inter->interviewers }}
                          </span>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-emerald-700">Completed</p>
                      </div>
                    </div>

                    <span class="h-fit rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-semibold text-emerald-700">
                      Completed
                    </span>
                  </div>
                </article>
              @empty
                <div id="completedEmptyState" class="rounded-[1.5rem] border border-dashed border-emerald-200 bg-white/80 p-8 text-center text-sm text-slate-500">
                  <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                    <i class="fa-regular fa-circle-check text-lg"></i>
                  </div>
                  <p class="mt-4 font-medium text-slate-700">No completed interviews yet.</p>
                </div>
              @endforelse
            </div>
          </section>
        </div>
      </section>

      <div id="scheduleInterviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm">
        <div class="w-full max-w-3xl overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.28)]">
          <div class="bg-[linear-gradient(135deg,#312e81,#4f46e5,#0ea5e9)] px-6 py-5 text-white md:px-8">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-100">Interview Planner</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight">Schedule Interview</h2>
                <p class="mt-1 text-sm text-slate-100">Update the session details and keep applicants informed with accurate timing.</p>
              </div>
              <button onclick="closeScheduleModal()" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-lg text-white transition hover:bg-white/20">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>
          <div class="grid gap-6 p-6 md:grid-cols-[0.9fr_1.1fr] md:p-8">
            <div class="rounded-[1.75rem] border border-slate-200 bg-[linear-gradient(180deg,#f8fafc,#eef2ff)] p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Applicant</p>
              <div class="mt-4 flex items-center gap-4">
                <div id="applicantInitials" class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-lg font-bold text-white">
                  NA
                </div>
                <div>
                  <p class="text-lg font-black tracking-tight text-slate-900" id="name">Select an interview</p>
                  <p class="text-sm text-slate-500" id="title">Applicant role appears here</p>
                </div>
              </div>

              <div class="mt-5 space-y-3">
                <div class="rounded-2xl border border-white/80 bg-white/80 px-4 py-3">
                  <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Reminder</p>
                  <p class="mt-1 text-sm font-medium text-slate-700">Choose a clear time, interviewer list, and optional meeting link for remote interviews.</p>
                </div>
                <div class="rounded-2xl border border-white/80 bg-white/80 px-4 py-3">
                  <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Tip</p>
                  <p class="mt-1 text-sm font-medium text-slate-700">Use notes to record room instructions, test tasks, or interview flow guidance.</p>
                </div>
              </div>
            </div>

            <form class="space-y-4" action="{{ route('admin.storeUpdatedInterview') }}" method="POST" id="form">
              @csrf
              <input type="hidden" id="interview_id" name="interviewId">
              <input type="hidden" id="applicants_id" name="applicantId">

              <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Interview Type</label>
                <select
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                  id="interview_type"
                  name="interview_type"
                >
                  <option value="HR Interview">HR Interview</option>
                  <option value="Final Interview">Final Interview</option>
                </select>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-semibold text-slate-700">Date</label>
                  <input
                    type="date"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                    name="date"
                    id="date"
                  >
                </div>
                <div>
                  <label class="mb-1 block text-sm font-semibold text-slate-700">Time</label>
                  <input
                    type="time"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                    name="time"
                    id="time"
                  >
                </div>
              </div>

              <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Duration</label>
                <select
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                  name="duration"
                  id="duration"
                >
                  <option value="5 minutes">5 minutes</option>
                  <option value="30 minutes">30 minutes</option>
                  <option value="45 minutes">45 minutes</option>
                  <option value="60 minutes">60 minutes</option>
                  <option value="90 minutes">90 minutes</option>
                </select>
              </div>

              <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Interviewer(s)</label>
                <input
                  type="text"
                  placeholder="Enter interviewer name(s)"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                  name="interviewers"
                  id="interviewers"
                >
              </div>

              <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Email Link</label>
                <input
                  type="email"
                  placeholder="Enter email address"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                  name="email_link"
                  id="email_link"
                >
              </div>

              <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Meeting Link (Optional)</label>
                <input
                  type="url"
                  placeholder="https://meet.google.com/..."
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                  name="url"
                  id="url"
                >
              </div>

              <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Notes (Optional)</label>
                <textarea
                  placeholder="Add any additional notes or instructions..."
                  class="h-28 w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                  name="notes"
                  id="notes"
                ></textarea>
              </div>

              <div class="flex flex-wrap justify-end gap-3 pt-2">
                <button type="button" onclick="closeScheduleModal()" class="rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                  Cancel
                </button>
                <button type="submit" class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                  Save Interview
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
  (function () {
    const initInterviewPageAnimation = () => {
      const page = document.getElementById('admin-interview-page');
      if (!page) return;

      const revealItems = Array.from(page.querySelectorAll('.interview-reveal'));
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

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initInterviewPageAnimation, { once: true });
    } else {
      initInterviewPageAnimation();
    }
  })();

  function setApplicantInitials(name) {
    const initialsTarget = document.getElementById('applicantInitials');
    if (!initialsTarget) return;

    const parts = (name || '')
      .trim()
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2);

    const initials = parts.map((part) => part.charAt(0).toUpperCase()).join('');
    initialsTarget.textContent = initials || 'NA';
  }

  function openEmptyScheduleModal() {
    document.getElementById('form').reset();
    document.getElementById('interview_id').value = '';
    document.getElementById('applicants_id').value = '';
    document.getElementById('name').innerText = 'Select an interview';
    document.getElementById('title').innerText = 'Applicant role appears here';
    setApplicantInitials('');
    document.getElementById('scheduleInterviewModal').classList.remove('hidden');
    document.getElementById('scheduleInterviewModal').classList.add('flex');
  }

  function scheduleInterview(appId) {
    if (!appId) {
      alert('Please select an applicant first.');
      return;
    }

    fetch(`/system/interviewers/ID/${appId}`)
      .then(res => res.json())
      .then(data => {
        document.getElementById('interview_id').value = data.id;
        document.getElementById('applicants_id').value = data.applicant_id;
        document.getElementById('name').innerText = data.name;
        document.getElementById('title').innerText = data.title;
        document.getElementById('interview_type').value = data.interview_type;
        document.getElementById('date').value = data.date;
        document.getElementById('interviewers').value = data.interviewers;
        document.getElementById('duration').value = data.duration;
        document.getElementById('time').value = data.time;
        document.getElementById('email_link').value = data.email_link;
        document.getElementById('url').value = data.url;
        document.getElementById('notes').value = data.notes;
        setApplicantInitials(data.name);
      });

    document.getElementById('scheduleInterviewModal').classList.remove('hidden');
    document.getElementById('scheduleInterviewModal').classList.add('flex');
  }

  function closeScheduleModal() {
    document.getElementById('scheduleInterviewModal').classList.add('hidden');
    document.getElementById('scheduleInterviewModal').classList.remove('flex');
  }
</script>

<script>
  function ensureEmptyState(containerId, emptyId, message) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const hasCards = container.querySelectorAll('.interview-card, .completed-card').length > 0;
    let emptyState = document.getElementById(emptyId);

    if (!hasCards && !emptyState) {
      emptyState = document.createElement('div');
      emptyState.id = emptyId;
      emptyState.className = 'rounded-[1.5rem] border border-dashed border-slate-300 bg-white/80 p-8 text-center text-sm text-slate-500';
      emptyState.textContent = message;
      container.appendChild(emptyState);
    }

    if (hasCards && emptyState) {
      emptyState.remove();
    }
  }

  function moveCardToCompleted(card) {
    const completedList = document.getElementById('completedInterviewList');
    if (!completedList || card.dataset.movedToCompleted === '1') return;

    card.dataset.movedToCompleted = '1';
    card.classList.remove('interview-card', 'border-indigo-100', 'shadow-[0_12px_30px_rgba(79,70,229,0.08)]');
    card.classList.add('completed-card', 'border-emerald-100', 'opacity-85', 'shadow-[0_12px_30px_rgba(16,185,129,0.08)]');

    const accentBar = card.querySelector('.absolute.inset-y-5.left-0');
    if (accentBar) {
      accentBar.classList.remove('bg-[linear-gradient(180deg,#6366f1,#0ea5e9)]');
      accentBar.classList.add('bg-[linear-gradient(180deg,#10b981,#34d399)]');
    }

    const timeBox = card.querySelector('.bg-indigo-50');
    if (timeBox) {
      timeBox.classList.remove('bg-indigo-50');
      timeBox.classList.add('bg-emerald-50');
    }

    const timeText = card.querySelector('.text-indigo-700');
    if (timeText) {
      timeText.classList.remove('text-indigo-700');
      timeText.classList.add('text-emerald-700');
    }

    const periodText = card.querySelector('.text-indigo-500');
    if (periodText) {
      periodText.classList.remove('text-indigo-500');
      periodText.classList.add('text-emerald-500');
    }

    const remainingEl = card.querySelector('[data-role="time-remaining"]');
    if (remainingEl) {
      remainingEl.textContent = 'Completed';
      remainingEl.classList.remove('text-indigo-600');
      remainingEl.classList.add('text-emerald-700');
    }

    const nextLabel = card.querySelector('.next-interview-label');
    if (nextLabel) nextLabel.classList.add('hidden');

    const actionWrap = card.querySelector('.mt-4.flex.flex-wrap.gap-3');
    if (actionWrap) actionWrap.remove();

    const avatar = card.querySelector('.bg-slate-900');
    if (avatar) {
      avatar.classList.remove('bg-slate-900');
      avatar.classList.add('bg-emerald-600');
    }

    const typeBadge = card.querySelector('.bg-indigo-100.text-indigo-700');
    if (typeBadge) {
      typeBadge.classList.remove('bg-indigo-100', 'text-indigo-700');
      typeBadge.classList.add('bg-emerald-100', 'text-emerald-700');
    }

    const dateBadge = card.querySelector('.bg-amber-100, .bg-sky-100');
    if (dateBadge) {
      dateBadge.classList.remove('bg-amber-100', 'text-amber-700', 'bg-sky-100', 'text-sky-700');
      dateBadge.classList.add('bg-emerald-100', 'text-emerald-700');
      dateBadge.innerHTML = '<i class="fa-regular fa-circle-check"></i> Completed';
    }

    const statusWrap = card.querySelector('.lg\\:items-end');
    if (statusWrap) {
      statusWrap.innerHTML = '<span class="h-fit rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-semibold text-emerald-700">Completed</span>';
    }

    completedList.prepend(card);
  }

  function refreshUpcomingCount() {
    const upcomingCountEl = document.getElementById('upcomingCountValue');
    const upcomingList = document.getElementById('upcomingInterviewList');
    if (!upcomingCountEl || !upcomingList) return;
    const count = upcomingList.querySelectorAll('.interview-card').length;
    upcomingCountEl.textContent = String(count);
  }

  function refreshCompletedStats() {
    const todayCountEl = document.getElementById('todayCountValue');
    const monthCountEl = document.getElementById('monthCountValue');
    const yearCountEl = document.getElementById('yearCountValue');
    if (!todayCountEl || !monthCountEl || !yearCountEl) return;

    const now = new Date();
    const nowMs = now.getTime();
    const completedCards = Array.from(document.querySelectorAll('[data-scheduled-date][data-scheduled-time][data-duration-minutes]'))
      .filter((card) => {
        const scheduledDate = card.dataset.scheduledDate;
        const scheduledTime = card.dataset.scheduledTime;
        const durationMinutes = parseInt(card.dataset.durationMinutes || '0', 10);
        if (!scheduledDate || !scheduledTime || Number.isNaN(durationMinutes)) return false;
        const startMs = new Date(`${scheduledDate}T${scheduledTime}`).getTime();
        if (Number.isNaN(startMs)) return false;
        const endMs = startMs + (durationMinutes * 60 * 1000);
        return nowMs >= endMs;
      });

    let todayCount = 0;
    let monthCount = 0;
    let yearCount = 0;

    completedCards.forEach((card) => {
      const scheduledDate = card.dataset.scheduledDate;
      const scheduledTime = card.dataset.scheduledTime;
      const durationMinutes = parseInt(card.dataset.durationMinutes || '0', 10);
      const startMs = new Date(`${scheduledDate}T${scheduledTime}`).getTime();
      const endDate = new Date(startMs + (durationMinutes * 60 * 1000));

      if (
        endDate.getFullYear() === now.getFullYear() &&
        endDate.getMonth() === now.getMonth() &&
        endDate.getDate() === now.getDate()
      ) {
        todayCount += 1;
      }

      if (
        endDate.getFullYear() === now.getFullYear() &&
        endDate.getMonth() === now.getMonth()
      ) {
        monthCount += 1;
      }

      if (endDate.getFullYear() === now.getFullYear()) {
        yearCount += 1;
      }
    });

    todayCountEl.textContent = String(todayCount);
    monthCountEl.textContent = String(monthCount);
    yearCountEl.textContent = String(yearCount);
  }

  function formatRemainingTime(ms) {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours > 0) return `${hours}h ${minutes}m ${seconds}s`;
    if (minutes > 0) return `${minutes}m ${seconds}s`;
    return `${seconds}s`;
  }

  function updateInterviewCards() {
    const nowMs = Date.now();
    const cards = Array.from(document.querySelectorAll('.interview-card'));
    let nextCard = null;
    let nextStartMs = Number.POSITIVE_INFINITY;

    cards.forEach((card) => {
      const scheduledDate = card.dataset.scheduledDate;
      const scheduledTime = card.dataset.scheduledTime;
      const durationMinutes = parseInt(card.dataset.durationMinutes || '0', 10);
      const remainingEl = card.querySelector('[data-role="time-remaining"]');
      const nextLabel = card.querySelector('.next-interview-label');

      if (nextLabel) nextLabel.classList.add('hidden');
      if (!scheduledDate || !scheduledTime) return;

      const scheduledMs = new Date(`${scheduledDate}T${scheduledTime}`).getTime();
      if (Number.isNaN(scheduledMs)) return;
      if (Number.isNaN(durationMinutes)) return;

      const endMs = scheduledMs + (durationMinutes * 60 * 1000);

      if (nowMs >= endMs) {
        moveCardToCompleted(card);
      } else {
        card.classList.remove('hidden');
        card.classList.remove('opacity-70');

        if (nowMs < scheduledMs) {
          if (remainingEl) {
            remainingEl.textContent = `Starts in ${formatRemainingTime(scheduledMs - nowMs)}`;
          }

          if (scheduledMs < nextStartMs) {
            nextStartMs = scheduledMs;
            nextCard = card;
          }
        } else if (remainingEl) {
          remainingEl.textContent = 'In progress';
        }
      }

      const wrapper = card.closest('.interview-wrapper');
      if (wrapper) {
        wrapper.classList.remove('hidden');
      }
    });

    if (nextCard) {
      const nextLabel = nextCard.querySelector('.next-interview-label');
      if (nextLabel) nextLabel.classList.remove('hidden');
    }

    refreshUpcomingCount();
    refreshCompletedStats();
    ensureEmptyState('upcomingInterviewList', 'upcomingEmptyState', 'No upcoming interviews.');
    ensureEmptyState('completedInterviewList', 'completedEmptyState', 'No completed interviews yet.');
  }

  updateInterviewCards();
  setInterval(updateInterviewCards, 1000);

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
