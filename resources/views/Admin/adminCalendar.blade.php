<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Calendar</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .admin-display {
      font-family: "Arial Black", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      letter-spacing: -0.03em;
    }
    .admin-kicker {
      letter-spacing: 0.22em;
    }
    .calendar-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .calendar-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .calendar-reveal.is-visible {
      animation: calendar-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--calendar-delay, 0ms);
    }
    .calendar-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .calendar-card-motion:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1);
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-action {
      background: #162238 !important;
      border-color: #34455c !important;
      color: #cbd5e1 !important;
      box-shadow: none !important;
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-action:hover:not(:disabled) {
      background: #1b2a42 !important;
      border-color: #4a6079 !important;
      filter: none !important;
      transform: translateY(-1px) !important;
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-action:disabled {
      background: #121d30 !important;
      color: #718096 !important;
      opacity: .72 !important;
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-day {
      background: #111c30 !important;
      border-color: #304158 !important;
      color: #cbd5e1 !important;
      box-shadow: none !important;
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-day:hover {
      background: #17263c !important;
      border-color: #465b73 !important;
      filter: none !important;
      transform: translateY(-1px) !important;
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-day.is-today {
      background: #172b45 !important;
      border-color: #3b82f6 !important;
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-day.is-selected {
      background: #1b3150 !important;
      border-color: #60a5fa !important;
      box-shadow: inset 0 0 0 1px #60a5fa !important;
    }
    html[data-theme="dark"] #admin-calendar-page .calendar-event-label {
      background: #1e2d43 !important;
      border-left: 3px solid currentColor;
    }
    .calendar-icon-pop {
      animation: calendar-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--calendar-delay, 0ms);
    }
    @media (max-width: 767px) {
      #admin-calendar-page {
        padding: 4.75rem 0.75rem 1.5rem !important;
        gap: 1rem;
      }
      #admin-calendar-page .calendar-panel {
        padding: 0.875rem !important;
        border-radius: 1.25rem !important;
      }
      #admin-calendar-page > section:first-of-type {
        padding: 1rem !important;
        border-radius: 1.25rem !important;
      }
      #admin-calendar-page > section:first-of-type > div:last-child,
      #admin-calendar-page > section:first-of-type > div:last-child > div,
      #admin-calendar-page .calendar-spotlight,
      #admin-calendar-page .calendar-spotlight * {
        min-width: 0;
      }
      #admin-calendar-page .calendar-spotlight {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        padding: 1rem !important;
        border-radius: 1rem !important;
      }
      #admin-calendar-page .calendar-spotlight h2 {
        margin-top: 0.25rem;
        font-size: 1.35rem;
        line-height: 1.6rem;
      }
      #admin-calendar-page .calendar-spotlight > div:first-child > div:last-child {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateLabel {
        margin-top: 0.75rem;
        font-size: 0.75rem;
        line-height: 1.25rem;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateLabel + div {
        margin-top: 0.75rem;
        gap: 0.5rem;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateLabel + div > div {
        padding: 0.65rem 0.75rem;
        border-radius: 0.75rem;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateLabel + div p:first-child {
        font-size: 0.55rem;
        letter-spacing: 0.12em;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateLabel + div p:last-child {
        margin-top: 0.25rem;
        font-size: 1.15rem;
      }
      #admin-calendar-page .calendar-spotlight > div:last-child {
        margin-top: 0.875rem;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateItems > div {
        gap: 0.65rem;
        padding: 0.65rem;
        border-radius: 0.75rem;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateItems > div > span:first-child {
        width: 2.25rem;
        height: 2.25rem;
        flex: 0 0 2.25rem;
        border-radius: 0.65rem;
      }
      #admin-calendar-page .calendar-spotlight #selectedDateItems > div > span:last-child {
        overflow: visible;
        white-space: normal;
        text-overflow: clip;
        overflow-wrap: anywhere;
        line-height: 1.25rem;
      }
      #admin-calendar-page .calendar-toolbar {
        gap: 0.75rem;
        padding-bottom: 0.875rem;
      }
      #admin-calendar-page .calendar-month-controls {
        width: 100%;
        justify-content: space-between;
      }
      #admin-calendar-page .calendar-month-controls button {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
      }
      #admin-calendar-page .calendar-month-heading {
        flex: 1;
        text-align: center;
      }
      #admin-calendar-page .calendar-month-heading .admin-kicker {
        font-size: 0.625rem;
        letter-spacing: 0.16em;
      }
      #admin-calendar-page #calendarMonthLabel {
        margin-top: 0.125rem;
        font-size: 1.25rem;
        line-height: 1.5rem;
      }
      #admin-calendar-page .calendar-legend {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        width: 100%;
        gap: 0.375rem;
      }
      #admin-calendar-page .calendar-legend > span {
        min-width: 0;
        justify-content: center;
        gap: 0.3rem;
        padding: 0.4rem 0.25rem;
        font-size: 0.625rem;
        white-space: nowrap;
      }
      #admin-calendar-page .calendar-legend > span > span {
        width: 0.4rem;
        height: 0.4rem;
        flex: 0 0 auto;
      }
      #admin-calendar-page .calendar-weekdays {
        margin-top: 0.875rem;
        gap: 0.25rem;
        font-size: 0.55rem;
        letter-spacing: 0.08em;
      }
      #admin-calendar-page .calendar-weekdays > div {
        padding: 0.45rem 0;
        border-radius: 0.5rem;
      }
      #admin-calendar-page #calendarGrid {
        margin-top: 0.35rem;
        gap: 0.25rem;
        grid-auto-rows: 3.35rem;
      }
      #admin-calendar-page .calendar-day,
      #admin-calendar-page .calendar-outside-day {
        height: 3.35rem !important;
        min-height: 0 !important;
        padding: 0.3rem !important;
        border-radius: 0.65rem !important;
        align-self: stretch;
      }
      #admin-calendar-page .calendar-outside-day {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      #admin-calendar-page .calendar-outside-day > div {
        font-size: 0.7rem;
      }
      #admin-calendar-page .calendar-day > div:first-child {
        align-items: center;
        justify-content: center;
      }
      #admin-calendar-page .calendar-day > div:first-child > div {
        position: absolute;
        display: flex;
        gap: 0.15rem;
        margin-top: 2.25rem;
        padding: 0;
        background: transparent;
      }
      #admin-calendar-page .calendar-day > div:first-child > div > span {
        width: 0.3rem;
        height: 0.3rem;
      }
      #admin-calendar-page .calendar-day > div:first-child > span {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 0.55rem;
        font-size: 0.7rem;
      }
      #admin-calendar-page .calendar-day > div:nth-child(2) {
        display: none;
      }
      #admin-calendar-page .calendar-status {
        margin-top: 0.875rem;
        padding: 0.75rem;
        border-radius: 0.875rem;
      }
      #admin-calendar-page .calendar-status > div:last-child {
        display: none;
      }
      #admin-calendar-page .calendar-actions-sidebar {
        gap: 0.75rem;
      }
      #admin-calendar-page .calendar-action-panel {
        padding: 0.875rem !important;
        border-radius: 1rem !important;
        box-shadow: none !important;
      }
      #admin-calendar-page .calendar-action-panel > div:first-child {
        align-items: center;
      }
      #admin-calendar-page .calendar-action-panel > div:first-child .admin-kicker {
        font-size: 0.55rem;
        letter-spacing: 0.16em;
      }
      #admin-calendar-page .calendar-action-panel > div:first-child h3 {
        margin-top: 0.1rem;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0;
      }
      #admin-calendar-page .calendar-action-panel > div:first-child > div:last-child {
        width: 2rem;
        height: 2rem;
        border-radius: 0.6rem;
        font-size: 0.8rem;
      }
      #admin-calendar-page .calendar-action-list {
        margin-top: 0.75rem;
        gap: 0.45rem;
      }
      #admin-calendar-page .calendar-action {
        min-height: 2.9rem;
        padding: 0.45rem 0.65rem !important;
        border-color: #e2e8f0 !important;
        border-radius: 0.75rem !important;
        background: #f8fafc !important;
        color: #334155 !important;
        font-size: 0.72rem;
        box-shadow: none !important;
      }
      #admin-calendar-page .calendar-action > span:first-child {
        min-width: 0;
        gap: 0.55rem;
      }
      #admin-calendar-page .calendar-action > span:first-child > span {
        width: 2rem;
        height: 2rem;
        flex: 0 0 2rem;
        border-radius: 0.6rem;
        font-size: 0.72rem;
      }
      #admin-calendar-page .calendar-action > i:last-child {
        color: #94a3b8 !important;
        font-size: 0.65rem;
      }
      #admin-calendar-page .calendar-action:disabled {
        background: #f8fafc !important;
        color: #94a3b8 !important;
        opacity: 0.62 !important;
      }
    }
    @keyframes calendar-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes calendar-pop-in {
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
      .calendar-reveal,
      .calendar-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .calendar-card-motion {
        transition: none;
      }
      .calendar-card-motion:hover {
        transform: none;
      }
    }
  </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#f8fafc,_#eef2ff_40%,_#f8fafc_100%)] text-slate-900">

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    @include('components.adminHeader.calenderHeader')

    <div id="admin-calendar-page" class="space-y-8 p-4 pt-20 md:p-8">
      <section class="calendar-reveal relative overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 px-6 py-7 text-white shadow-[0_30px_80px_rgba(15,23,42,0.22)] md:px-8" style="--calendar-delay: 0ms;">
        <div class="absolute -left-12 top-0 h-32 w-32 rounded-full bg-sky-400/15 blur-3xl"></div>
        <div class="absolute right-10 top-2 h-28 w-28 rounded-full bg-emerald-400/15 blur-3xl"></div>

        <div class="relative grid gap-8 xl:grid-cols-[1.4fr_0.9fr] xl:items-end">
          <div class="space-y-5">
            <div class="admin-kicker inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold uppercase text-sky-100">
              Calendar Control Center
            </div>
            <div>
              <h1 class="admin-display max-w-3xl text-3xl leading-tight text-white md:text-5xl">Organize holidays, school events, and exam days from one beautiful calendar.</h1>
              <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-200 md:text-base">
                Keep the academic rhythm visible at a glance, then jump straight into the dates that need action.
              </p>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
              <div class="calendar-card-motion calendar-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--calendar-delay: 50ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">Holidays</p>
                <p id="holidayCount" class="admin-display mt-2 text-3xl text-white">0</p>
                <p class="mt-1 text-xs text-rose-200">Official and custom</p>
              </div>
              <div class="calendar-card-motion calendar-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--calendar-delay: 80ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">No Classes</p>
                <p id="noClassCount" class="admin-display mt-2 text-3xl text-white">0</p>
                <p class="mt-1 text-xs text-orange-200">Sunday and holiday dates</p>
              </div>
              <div class="calendar-card-motion calendar-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--calendar-delay: 110ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">Events</p>
                <p id="eventCount" class="admin-display mt-2 text-3xl text-white">0</p>
                <p class="mt-1 text-xs text-amber-200">Special and school activities</p>
              </div>
              <div class="calendar-card-motion calendar-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--calendar-delay: 140ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">Exam Days</p>
                <p id="examCount" class="admin-display mt-2 text-3xl text-white">0</p>
                <p class="mt-1 text-xs text-emerald-200">Recurring assessment dates</p>
              </div>
            </div>
          </div>

          <div class="calendar-spotlight calendar-card-motion calendar-reveal rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur-sm" style="--calendar-delay: 120ms;">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="admin-kicker text-xs font-semibold uppercase text-sky-100">Selected Date</p>
                <h2 class="admin-display mt-2 text-2xl text-white">Date Spotlight</h2>
              </div>
              <div class="calendar-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-sky-100" style="--calendar-delay: 170ms;">
                <i class="fa-solid fa-calendar-days text-2xl"></i>
              </div>
            </div>

            <p id="selectedDateLabel" class="mt-5 text-sm leading-6 text-slate-200">Select a date to add a custom event or holiday.</p>

            <div class="mt-5 grid grid-cols-2 gap-3">
              <div class="rounded-2xl bg-white/10 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300">Holiday</p>
                <p id="selectedHolidayCount" class="mt-2 text-2xl font-black text-white">0</p>
              </div>
              <div class="rounded-2xl bg-white/10 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300">Events</p>
                <p id="selectedEventCount" class="mt-2 text-2xl font-black text-white">0</p>
              </div>
              <div class="rounded-2xl bg-white/10 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300">Special</p>
                <p id="selectedSpecialCount" class="mt-2 text-2xl font-black text-white">0</p>
              </div>
              <div class="rounded-2xl bg-white/10 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300">Exams</p>
                <p id="selectedExamCount" class="mt-2 text-2xl font-black text-white">0</p>
              </div>
            </div>

            <div class="mt-5">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">What&rsquo;s happening</p>
              <div id="selectedDateItems" class="mt-3 space-y-2 text-sm text-slate-100">
                <div class="rounded-2xl bg-white/10 px-4 py-3 text-slate-300">No date selected yet.</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.45fr_0.75fr]">
        <div class="calendar-panel calendar-reveal rounded-[2rem] border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur-sm md:p-6" style="--calendar-delay: 180ms;">
          <div class="calendar-toolbar flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center md:justify-between">
            <div class="calendar-month-controls flex items-center gap-3">
              <button id="calendarPrevBtn" type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:-translate-y-0.5 hover:border-sky-200 hover:text-sky-700 hover:shadow-sm">
                <i class="fa-solid fa-chevron-left"></i>
              </button>
              <div class="calendar-month-heading">
                <p class="admin-kicker text-xs font-semibold uppercase text-sky-700">Monthly View</p>
                <h3 id="calendarMonthLabel" class="admin-display mt-1 text-2xl text-slate-900"></h3>
              </div>
              <button id="calendarNextBtn" type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:-translate-y-0.5 hover:border-sky-200 hover:text-sky-700 hover:shadow-sm">
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>

            <div class="calendar-legend flex flex-wrap items-center gap-2 text-xs font-semibold">
              <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-2 text-rose-700">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                Holiday
              </span>
              <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-2 text-orange-700">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                No Class
              </span>
              <span class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-2 text-sky-700">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                Special
              </span>
              <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-2 text-amber-700">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                School Event
              </span>
              <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-emerald-700">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                Exam Day
              </span>
            </div>
          </div>

          <div class="calendar-weekdays mt-5 grid grid-cols-7 gap-2 text-center text-[11px] font-bold uppercase tracking-[0.25em] text-slate-400 md:text-xs">
            <div class="rounded-2xl bg-slate-50 py-3">Sun</div>
            <div class="rounded-2xl bg-slate-50 py-3">Mon</div>
            <div class="rounded-2xl bg-slate-50 py-3">Tue</div>
            <div class="rounded-2xl bg-slate-50 py-3">Wed</div>
            <div class="rounded-2xl bg-slate-50 py-3">Thu</div>
            <div class="rounded-2xl bg-slate-50 py-3">Fri</div>
            <div class="rounded-2xl bg-slate-50 py-3">Sat</div>
          </div>

          <div id="calendarGrid" class="mt-3 grid grid-cols-7 gap-3"></div>

          <div class="calendar-status mt-5 flex flex-col gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50/80 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-700">Holiday sync status</p>
              <p id="holidayStatus" class="text-xs text-slate-500">Ready to load holidays</p>
            </div>
            <div class="text-xs text-slate-500">Tip: click a date card to unlock the action panel.</div>
          </div>
        </div>

        <aside class="calendar-actions-sidebar space-y-6">
          <section class="calendar-action-panel calendar-card-motion calendar-reveal rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm" style="--calendar-delay: 220ms;">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="admin-kicker text-xs font-semibold uppercase text-sky-700">Quick Actions</p>
                <h3 class="admin-display mt-2 text-2xl text-slate-900">Create</h3>
              </div>
              <div class="calendar-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700" style="--calendar-delay: 260ms;">
                <i class="fa-solid fa-wand-magic-sparkles text-xl"></i>
              </div>
            </div>

            <div class="calendar-action-list mt-5 grid gap-3">
              <button
                id="addCustomEventBtn"
                type="button"
                class="calendar-action inline-flex items-center justify-between rounded-[1.25rem] border border-amber-100 bg-amber-50 px-4 py-3 text-left text-sm font-semibold text-amber-800 transition hover:-translate-y-0.5 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                disabled
              >
                <span class="inline-flex items-center gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-500 text-white">
                    <i class="fa-solid fa-plus"></i>
                  </span>
                  Add School Event
                </span>
                <i class="fa-solid fa-arrow-right text-amber-500"></i>
              </button>

              <button
                id="addExamDayBtn"
                type="button"
                class="calendar-action inline-flex items-center justify-between rounded-[1.25rem] border border-emerald-100 bg-emerald-50 px-4 py-3 text-left text-sm font-semibold text-emerald-800 transition hover:-translate-y-0.5 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                disabled
              >
                <span class="inline-flex items-center gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-500 text-white">
                    <i class="fa-solid fa-graduation-cap"></i>
                  </span>
                  Add Exam Day
                </span>
                <i class="fa-solid fa-arrow-right text-emerald-500"></i>
              </button>

              <button
                id="addCustomHolidayBtn"
                type="button"
                class="calendar-action inline-flex items-center justify-between rounded-[1.25rem] border border-rose-100 bg-rose-50 px-4 py-3 text-left text-sm font-semibold text-rose-800 transition hover:-translate-y-0.5 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                disabled
              >
                <span class="inline-flex items-center gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-500 text-white">
                    <i class="fa-solid fa-calendar-plus"></i>
                  </span>
                  Add Holiday
                </span>
                <i class="fa-solid fa-arrow-right text-rose-500"></i>
              </button>
            </div>
          </section>

          <section class="calendar-action-panel calendar-card-motion calendar-reveal rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm" style="--calendar-delay: 260ms;">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="admin-kicker text-xs font-semibold uppercase text-amber-700">Management</p>
                <h3 class="admin-display mt-2 text-2xl text-slate-900">Refine</h3>
              </div>
              <div class="calendar-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700" style="--calendar-delay: 300ms;">
                <i class="fa-solid fa-sliders text-xl"></i>
              </div>
            </div>

            <div class="calendar-action-list mt-5 grid gap-3">
              <button
                id="convertEventToHolidayBtn"
                type="button"
                class="calendar-action inline-flex items-center justify-between rounded-[1.25rem] border border-sky-100 bg-sky-50 px-4 py-3 text-left text-sm font-semibold text-sky-800 transition hover:-translate-y-0.5 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                disabled
              >
                <span class="inline-flex items-center gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-500 text-white">
                    <i class="fa-solid fa-arrows-rotate"></i>
                  </span>
                  Event to Holiday
                </span>
                <i class="fa-solid fa-arrow-right text-sky-500"></i>
              </button>

              <button
                id="removeCustomEventBtn"
                type="button"
                class="calendar-action inline-flex items-center justify-between rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                disabled
              >
                <span class="inline-flex items-center gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-700 text-white">
                    <i class="fa-solid fa-trash"></i>
                  </span>
                  Remove Event
                </span>
                <i class="fa-solid fa-arrow-right text-slate-500"></i>
              </button>

              <button
                id="removeCustomHolidayBtn"
                type="button"
                class="calendar-action inline-flex items-center justify-between rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50"
                disabled
              >
                <span class="inline-flex items-center gap-3">
                  <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-white">
                    <i class="fa-solid fa-calendar-xmark"></i>
                  </span>
                  Remove Holiday
                </span>
                <i class="fa-solid fa-arrow-right text-slate-500"></i>
              </button>
            </div>
          </section>
        </aside>
      </section>
    </div>
  </main>
</div>

<script>
  (function () {
    const initCalendarPageAnimation = () => {
      const page = document.getElementById('admin-calendar-page');
      if (!page) return;

      const revealItems = Array.from(page.querySelectorAll('.calendar-reveal'));
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
      document.addEventListener('DOMContentLoaded', initCalendarPageAnimation, { once: true });
    } else {
      initCalendarPageAnimation();
    }
  })();

  const sidebar = document.querySelector('aside');
  const main = document.querySelector('main');
  if (sidebar && main) {
    sidebar.addEventListener('mouseenter', function () {
      main.classList.remove('ml-16');
      main.classList.add('ml-64');
    });
    sidebar.addEventListener('mouseleave', function () {
      main.classList.remove('ml-64');
      main.classList.add('ml-16');
    });
  }

  const monthLabel = document.getElementById('calendarMonthLabel');
  const grid = document.getElementById('calendarGrid');
  const prevBtn = document.getElementById('calendarPrevBtn');
  const nextBtn = document.getElementById('calendarNextBtn');
  const holidayStatus = document.getElementById('holidayStatus');
  const selectedDateLabel = document.getElementById('selectedDateLabel');
  const addCustomEventBtn = document.getElementById('addCustomEventBtn');
  const removeCustomEventBtn = document.getElementById('removeCustomEventBtn');
  const addExamDayBtn = document.getElementById('addExamDayBtn');
  const addCustomHolidayBtn = document.getElementById('addCustomHolidayBtn');
  const convertEventToHolidayBtn = document.getElementById('convertEventToHolidayBtn');
  const removeCustomHolidayBtn = document.getElementById('removeCustomHolidayBtn');
  const holidayCountEl = document.getElementById('holidayCount');
  const noClassCountEl = document.getElementById('noClassCount');
  const eventCountEl = document.getElementById('eventCount');
  const examCountEl = document.getElementById('examCount');
  const selectedHolidayCountEl = document.getElementById('selectedHolidayCount');
  const selectedEventCountEl = document.getElementById('selectedEventCount');
  const selectedSpecialCountEl = document.getElementById('selectedSpecialCount');
  const selectedExamCountEl = document.getElementById('selectedExamCount');
  const selectedDateItemsEl = document.getElementById('selectedDateItems');

  const HOLIDAY_COUNTRY = 'US';
  const CUSTOM_EVENT_STORAGE_KEY = 'school_custom_events_v1';
  const CUSTOM_HOLIDAY_STORAGE_KEY = 'school_custom_holidays_v1';
  const RECURRING_EVENT_STORAGE_KEY = 'school_recurring_events_v1';
  const RECURRING_EXAM_STORAGE_KEY = 'school_recurring_exams_v1';
  const RECURRING_HOLIDAY_STORAGE_KEY = 'school_recurring_holidays_v1';
  const HIDDEN_OFFICIAL_HOLIDAY_STORAGE_KEY = 'hidden_official_holidays_v1';
  const HIDDEN_SPECIAL_EVENT_STORAGE_KEY = 'hidden_special_events_v1';
  const calendarHolidaySyncUrl = @json(route('admin.syncHiddenOfficialHolidays'));
  const csrfToken = @json(csrf_token());
  const holidayCache = {};
  const specialEventCache = {};
  let customEvents = loadCustomEvents();
  let customHolidays = loadCustomHolidays();
  let recurringEvents = loadRecurringEvents();
  let recurringExamDays = loadRecurringExams();
  let recurringHolidays = loadRecurringHolidays();
  let hiddenOfficialHolidays = loadHiddenOfficialHolidays();
  let hiddenSpecialEvents = loadHiddenSpecialEvents();
  let currentEventEntriesByDate = {};
  let currentHolidayEntriesByDate = {};
  let currentSpecialEventEntriesByDate = {};
  let currentExamEntriesByDate = {};
  let selectedDateForCustomEvent = null;
  let calendarDate = new Date();

  function loadCustomEvents() {
    try {
      const raw = localStorage.getItem(CUSTOM_EVENT_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function saveCustomEvents() {
    localStorage.setItem(CUSTOM_EVENT_STORAGE_KEY, JSON.stringify(customEvents));
  }

  function loadRecurringEvents() {
    try {
      const raw = localStorage.getItem(RECURRING_EVENT_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function saveRecurringEvents() {
    localStorage.setItem(RECURRING_EVENT_STORAGE_KEY, JSON.stringify(recurringEvents));
  }

  function loadRecurringExams() {
    try {
      const raw = localStorage.getItem(RECURRING_EXAM_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function saveRecurringExams() {
    localStorage.setItem(RECURRING_EXAM_STORAGE_KEY, JSON.stringify(recurringExamDays));
  }

  function loadCustomHolidays() {
    try {
      const raw = localStorage.getItem(CUSTOM_HOLIDAY_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function saveCustomHolidays() {
    localStorage.setItem(CUSTOM_HOLIDAY_STORAGE_KEY, JSON.stringify(customHolidays));
    syncCalendarHolidaysWithServer();
  }

  function loadRecurringHolidays() {
    try {
      const raw = localStorage.getItem(RECURRING_HOLIDAY_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function saveRecurringHolidays() {
    localStorage.setItem(RECURRING_HOLIDAY_STORAGE_KEY, JSON.stringify(recurringHolidays));
    syncCalendarHolidaysWithServer();
  }

  function loadHiddenOfficialHolidays() {
    try {
      const raw = localStorage.getItem(HIDDEN_OFFICIAL_HOLIDAY_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function saveHiddenOfficialHolidays() {
    localStorage.setItem(HIDDEN_OFFICIAL_HOLIDAY_STORAGE_KEY, JSON.stringify(hiddenOfficialHolidays));
    syncCalendarHolidaysWithServer();
  }

  function loadHiddenSpecialEvents() {
    try {
      const raw = localStorage.getItem(HIDDEN_SPECIAL_EVENT_STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function saveHiddenSpecialEvents() {
    localStorage.setItem(HIDDEN_SPECIAL_EVENT_STORAGE_KEY, JSON.stringify(hiddenSpecialEvents));
  }

  function syncCalendarHolidaysWithServer() {
    if (!calendarHolidaySyncUrl) return;

    fetch(calendarHolidaySyncUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        hidden_official_holidays: hiddenOfficialHolidays,
        custom_holidays: customHolidays,
        recurring_holidays: recurringHolidays,
      }),
    }).catch((error) => {
      console.error('Unable to sync calendar holidays.', error);
    });
  }

  function getCustomEventNamesForDate(isoDate) {
    const events = customEvents[isoDate];
    return Array.isArray(events) ? events : [];
  }

  function getMonthDayKey(isoDate) {
    return isoDate.slice(5);
  }

  function getRecurringEventNamesForDate(isoDate) {
    const events = recurringEvents[getMonthDayKey(isoDate)];
    return Array.isArray(events) ? events : [];
  }

  function getRecurringExamNamesForDate(isoDate) {
    const exams = recurringExamDays[getMonthDayKey(isoDate)];
    return Array.isArray(exams) ? exams : [];
  }

  function getCustomHolidayNamesForDate(isoDate) {
    const holidays = customHolidays[isoDate];
    return Array.isArray(holidays) ? holidays : [];
  }

  function getRecurringHolidayNamesForDate(isoDate) {
    const holidays = recurringHolidays[getMonthDayKey(isoDate)];
    return Array.isArray(holidays) ? holidays : [];
  }

  function createSelectedDateItem(label, colorClass, iconClass) {
    const item = document.createElement('div');
    item.className = 'flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3';
    item.innerHTML = `
      <span class="flex h-10 w-10 items-center justify-center rounded-2xl ${colorClass} text-white">
        <i class="${iconClass}"></i>
      </span>
      <span class="min-w-0 truncate text-sm font-medium text-white">${label}</span>
    `;
    return item;
  }

  function renderSelectedDateSummary(isoDate) {
    const holidays = currentHolidayEntriesByDate[isoDate] || [];
    const events = currentEventEntriesByDate[isoDate] || [];
    const specials = currentSpecialEventEntriesByDate[isoDate] || [];
    const exams = currentExamEntriesByDate[isoDate] || [];

    selectedHolidayCountEl.textContent = String(holidays.length);
    selectedEventCountEl.textContent = String(events.length);
    selectedSpecialCountEl.textContent = String(specials.length);
    selectedExamCountEl.textContent = String(exams.length);

    selectedDateItemsEl.innerHTML = '';

    if (!holidays.length && !events.length && !specials.length && !exams.length) {
      selectedDateItemsEl.innerHTML = '<div class="rounded-2xl bg-white/10 px-4 py-3 text-slate-300">No saved items for this date yet.</div>';
      return;
    }

    holidays.forEach((entry) => {
      selectedDateItemsEl.appendChild(createSelectedDateItem(entry.name, 'bg-rose-500', 'fa-solid fa-umbrella-beach'));
    });
    events.forEach((entry) => {
      selectedDateItemsEl.appendChild(createSelectedDateItem(entry.name, 'bg-amber-500', 'fa-solid fa-bullhorn'));
    });
    specials.forEach((entry) => {
      selectedDateItemsEl.appendChild(createSelectedDateItem(entry, 'bg-sky-500', 'fa-solid fa-star'));
    });
    exams.forEach((entry) => {
      selectedDateItemsEl.appendChild(createSelectedDateItem(entry, 'bg-emerald-500', 'fa-solid fa-graduation-cap'));
    });
  }

  function setSelectedDate(isoDate) {
    selectedDateForCustomEvent = isoDate;
    const parsedDate = new Date(`${isoDate}T00:00:00`);
    const readable = parsedDate.toLocaleDateString('en-US', {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    });
    selectedDateLabel.textContent = `Selected: ${readable}`;
    addCustomEventBtn.disabled = false;
    removeCustomEventBtn.disabled = (currentEventEntriesByDate[isoDate] || []).length === 0;
    addExamDayBtn.disabled = false;
    addCustomHolidayBtn.disabled = false;
    convertEventToHolidayBtn.disabled = (currentSpecialEventEntriesByDate[isoDate] || []).length === 0;
    removeCustomHolidayBtn.disabled = (currentHolidayEntriesByDate[isoDate] || []).length === 0;
    renderSelectedDateSummary(isoDate);
  }

  async function getHolidaysForYear(year) {
    if (holidayCache[year]) {
      return holidayCache[year];
    }

    if (holidayStatus) {
      holidayStatus.textContent = 'Loading holidays...';
    }

    try {
      const response = await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/${HOLIDAY_COUNTRY}`);
      if (!response.ok) {
        throw new Error('Failed to load holidays');
      }

      const holidays = await response.json();
      const normalized = {};
      holidays.forEach((holiday) => {
        if (!holiday?.date) return;
        normalized[holiday.date] = holiday.localName || holiday.name || 'Holiday';
      });

      holidayCache[year] = normalized;
      if (holidayStatus) {
        holidayStatus.textContent = `${Object.keys(normalized).length} holidays loaded`;
      }
      return normalized;
    } catch (error) {
      holidayCache[year] = {};
      if (holidayStatus) {
        holidayStatus.textContent = 'Unable to load holidays';
      }
      return {};
    }
  }

  async function renderCalendar() {
    const year = calendarDate.getFullYear();
    const month = calendarDate.getMonth();
    const holidays = await getHolidaysForYear(year);
    const specialEvents = getSpecialEventsForYear(year);
    currentEventEntriesByDate = {};
    currentHolidayEntriesByDate = {};
    currentSpecialEventEntriesByDate = {};
    currentExamEntriesByDate = {};

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    const today = new Date();
    let totalHolidayCount = 0;
    let totalNoClassCount = 0;
    let totalEventCount = 0;
    let totalExamCount = 0;

    monthLabel.textContent = calendarDate.toLocaleDateString('en-US', {
      month: 'long',
      year: 'numeric',
    });

    grid.innerHTML = '';

    for (let i = firstDay - 1; i >= 0; i--) {
      const cell = document.createElement('div');
      cell.className = 'calendar-outside-day min-h-[132px] rounded-[1.4rem] border border-slate-100 bg-slate-50 px-3 py-3 text-xs text-slate-300';
      cell.innerHTML = `<div class="text-right text-sm font-semibold">${String(daysInPrevMonth - i)}</div>`;
      grid.appendChild(cell);
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const isToday =
        day === today.getDate() &&
        month === today.getMonth() &&
        year === today.getFullYear();
      const isoDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const holidayEntries = [];
      const officialHolidayName = holidays[isoDate] || null;
      const hiddenOfficialForDate = Array.isArray(hiddenOfficialHolidays[isoDate]) ? hiddenOfficialHolidays[isoDate] : [];
      if (officialHolidayName && !hiddenOfficialForDate.includes(officialHolidayName)) {
        holidayEntries.push({ name: officialHolidayName, type: 'official' });
      }
      getCustomHolidayNamesForDate(isoDate).forEach((name) => {
        holidayEntries.push({ name, type: 'custom_exact' });
      });
      getRecurringHolidayNamesForDate(isoDate).forEach((name) => {
        holidayEntries.push({ name, type: 'custom_recurring' });
      });

      const dayOfWeek = new Date(year, month, day).getDay();
      const isHoliday = holidayEntries.length > 0;
      const isNoClassSunday = dayOfWeek === 0;
      const noClassLabel = isHoliday ? 'No Classes (Holiday)' : (isNoClassSunday ? 'No Classes (Sunday)' : null);
      const hiddenEventsForDate = Array.isArray(hiddenSpecialEvents[isoDate]) ? hiddenSpecialEvents[isoDate] : [];
      const events = (specialEvents[isoDate] || []).filter((eventName) => !hiddenEventsForDate.includes(eventName));
      const hasSpecialEvent = events.length > 0;
      const customEventEntries = [
        ...getCustomEventNamesForDate(isoDate).map((name) => ({ name, type: 'custom_exact' })),
        ...getRecurringEventNamesForDate(isoDate).map((name) => ({ name, type: 'custom_recurring' })),
      ];
      const examEntries = getRecurringExamNamesForDate(isoDate);
      const hasCustomEvent = customEventEntries.length > 0;
      const hasExamDay = examEntries.length > 0;
      const isSelected = selectedDateForCustomEvent === isoDate;

      totalHolidayCount += holidayEntries.length;
      totalEventCount += customEventEntries.length + events.length;
      totalExamCount += examEntries.length;
      if (noClassLabel) {
        totalNoClassCount += 1;
      }

      const cell = document.createElement('button');
      cell.type = 'button';
      cell.className = [
        'calendar-day group min-h-[132px] rounded-[1.4rem] border px-3 py-3 text-left text-xs md:text-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md',
        isToday ? 'is-today' : '',
        isSelected ? 'is-selected' : '',
        isToday
          ? 'border-blue-200 bg-blue-50 text-blue-900'
          : 'border-slate-200 bg-white text-slate-700',
        isHoliday ? 'shadow-[inset_0_0_0_1px_rgba(244,63,94,0.25)] bg-rose-50/70' : '',
        !isHoliday && isNoClassSunday ? 'shadow-[inset_0_0_0_1px_rgba(249,115,22,0.25)] bg-orange-50/70' : '',
        !isHoliday && !isNoClassSunday && hasSpecialEvent ? 'shadow-[inset_0_0_0_1px_rgba(14,165,233,0.25)] bg-sky-50/70' : '',
        hasCustomEvent ? 'ring-1 ring-amber-300' : '',
        hasExamDay ? 'ring-2 ring-emerald-300' : '',
        isSelected ? 'ring-2 ring-slate-900 shadow-lg' : '',
      ].join(' ');

      const markers = [];
      if (holidayEntries.length) markers.push('<span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>');
      if (noClassLabel) markers.push('<span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>');
      if (events.length) markers.push('<span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>');
      if (customEventEntries.length) markers.push('<span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>');
      if (examEntries.length) markers.push('<span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>');

      const visibleLabels = [];
      holidayEntries.slice(0, 1).forEach((entry) => {
        visibleLabels.push(`<div class="calendar-event-label truncate rounded-xl bg-rose-100 px-2 py-1 text-[10px] font-semibold text-rose-700">${entry.name}</div>`);
      });
      if (noClassLabel) {
        visibleLabels.push(`<div class="calendar-event-label truncate rounded-xl bg-orange-100 px-2 py-1 text-[10px] font-semibold text-orange-700">${noClassLabel}</div>`);
      }
      events.slice(0, visibleLabels.length < 2 ? 2 - visibleLabels.length : 0).forEach((entry) => {
        visibleLabels.push(`<div class="calendar-event-label truncate rounded-xl bg-sky-100 px-2 py-1 text-[10px] font-semibold text-sky-700">${entry}</div>`);
      });
      customEventEntries.slice(0, visibleLabels.length < 2 ? 2 - visibleLabels.length : 0).forEach((entry) => {
        visibleLabels.push(`<div class="calendar-event-label truncate rounded-xl bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700">${entry.name}</div>`);
      });
      examEntries.slice(0, visibleLabels.length < 2 ? 2 - visibleLabels.length : 0).forEach((entry) => {
        visibleLabels.push(`<div class="calendar-event-label truncate rounded-xl bg-emerald-100 px-2 py-1 text-[10px] font-semibold text-emerald-700">${entry}</div>`);
      });

      const totalEntries = holidayEntries.length + (noClassLabel ? 1 : 0) + events.length + customEventEntries.length + examEntries.length;
      const moreCount = Math.max(totalEntries - visibleLabels.length, 0);

      cell.innerHTML = `
        <div class="flex items-start justify-between gap-2">
          <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100/80 px-2 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">
            ${markers.join('')}
          </div>
          <span class="flex h-9 w-9 items-center justify-center rounded-2xl ${isToday ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'} text-sm font-bold">${day}</span>
        </div>
        <div class="mt-3 space-y-2">
          ${visibleLabels.join('')}
          ${moreCount > 0 ? `<div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">+${moreCount} more</div>` : ''}
        </div>
      `;
      cell.addEventListener('click', function () {
        setSelectedDate(isoDate);
        renderCalendar();
      });

      grid.appendChild(cell);
      currentEventEntriesByDate[isoDate] = customEventEntries;
      currentHolidayEntriesByDate[isoDate] = holidayEntries;
      currentSpecialEventEntriesByDate[isoDate] = events;
      currentExamEntriesByDate[isoDate] = examEntries;
    }

    const totalCells = firstDay + daysInMonth;
    const trailing = (7 - (totalCells % 7)) % 7;
    for (let day = 1; day <= trailing; day++) {
      const cell = document.createElement('div');
      cell.className = 'calendar-outside-day min-h-[132px] rounded-[1.4rem] border border-slate-100 bg-slate-50 px-3 py-3 text-xs text-slate-300';
      cell.innerHTML = `<div class="text-right text-sm font-semibold">${String(day)}</div>`;
      grid.appendChild(cell);
    }

    holidayCountEl.textContent = String(totalHolidayCount);
    noClassCountEl.textContent = String(totalNoClassCount);
    eventCountEl.textContent = String(totalEventCount);
    examCountEl.textContent = String(totalExamCount);

    if (selectedDateForCustomEvent) {
      renderSelectedDateSummary(selectedDateForCustomEvent);
    }
  }

  prevBtn?.addEventListener('click', function () {
    calendarDate.setMonth(calendarDate.getMonth() - 1);
    renderCalendar();
  });

  nextBtn?.addEventListener('click', function () {
    calendarDate.setMonth(calendarDate.getMonth() + 1);
    renderCalendar();
  });

  addCustomEventBtn?.addEventListener('click', function () {
    if (!selectedDateForCustomEvent) return;

    const eventTitle = window.prompt('Enter custom school event title:');
    if (eventTitle === null) return;

    const trimmed = eventTitle.trim();
    if (!trimmed) return;

    const monthDay = getMonthDayKey(selectedDateForCustomEvent);
    const current = Array.isArray(recurringEvents[monthDay]) ? recurringEvents[monthDay] : [];
    recurringEvents[monthDay] = [...current, trimmed];
    saveRecurringEvents();
    renderCalendar();
    removeCustomEventBtn.disabled = false;
  });

  removeCustomEventBtn?.addEventListener('click', function () {
    if (!selectedDateForCustomEvent) return;

    const eventEntries = currentEventEntriesByDate[selectedDateForCustomEvent] || [];
    if (!eventEntries.length) return;

    const optionsText = eventEntries
      .map((eventEntry, index) => `${index + 1}. ${eventEntry.name} (${eventEntry.type === 'custom_recurring' ? 'Recurring' : 'One-time'})`)
      .join('\n');
    const selected = window.prompt(`Select event number to remove:\n${optionsText}`);
    if (selected === null) return;

    const selectedIndex = parseInt(selected, 10) - 1;
    if (Number.isNaN(selectedIndex) || selectedIndex < 0 || selectedIndex >= eventEntries.length) return;

    const target = eventEntries[selectedIndex];
    if (target.type === 'custom_recurring') {
      const monthDay = getMonthDayKey(selectedDateForCustomEvent);
      const recurringList = Array.isArray(recurringEvents[monthDay]) ? recurringEvents[monthDay] : [];
      const nextRecurring = recurringList.filter((eventName) => eventName !== target.name);
      if (nextRecurring.length > 0) {
        recurringEvents[monthDay] = nextRecurring;
      } else {
        delete recurringEvents[monthDay];
      }
      saveRecurringEvents();
    } else {
      const exactList = getCustomEventNamesForDate(selectedDateForCustomEvent);
      const nextExact = exactList.filter((eventName) => eventName !== target.name);
      if (nextExact.length > 0) {
        customEvents[selectedDateForCustomEvent] = nextExact;
      } else {
        delete customEvents[selectedDateForCustomEvent];
      }
      saveCustomEvents();
    }

    renderCalendar();
    removeCustomEventBtn.disabled = (currentEventEntriesByDate[selectedDateForCustomEvent] || []).length === 0;
  });

  addCustomHolidayBtn?.addEventListener('click', function () {
    if (!selectedDateForCustomEvent) return;

    const holidayTitle = window.prompt('Enter custom holiday title:');
    if (holidayTitle === null) return;

    const trimmed = holidayTitle.trim();
    if (!trimmed) return;

    const monthDay = getMonthDayKey(selectedDateForCustomEvent);
    const current = Array.isArray(recurringHolidays[monthDay]) ? recurringHolidays[monthDay] : [];
    recurringHolidays[monthDay] = [...current, trimmed];
    saveRecurringHolidays();
    renderCalendar();
    removeCustomHolidayBtn.disabled = false;
  });

  convertEventToHolidayBtn?.addEventListener('click', function () {
    if (!selectedDateForCustomEvent) return;

    const eventEntries = currentSpecialEventEntriesByDate[selectedDateForCustomEvent] || [];
    if (!eventEntries.length) {
      window.alert('No special events available on the selected date.');
      return;
    }

    const optionsText = eventEntries
      .map((eventName, index) => `${index + 1}. ${eventName}`)
      .join('\n');
    const selected = window.prompt(`Select special event number to convert into holiday:\n${optionsText}`);
    if (selected === null) return;

    const selectedIndex = parseInt(selected, 10) - 1;
    if (Number.isNaN(selectedIndex) || selectedIndex < 0 || selectedIndex >= eventEntries.length) return;

    const targetEvent = eventEntries[selectedIndex];
    const monthDay = getMonthDayKey(selectedDateForCustomEvent);
    const holidayList = Array.isArray(recurringHolidays[monthDay]) ? recurringHolidays[monthDay] : [];

    if (!holidayList.includes(targetEvent)) {
      recurringHolidays[monthDay] = [...holidayList, targetEvent];
      saveRecurringHolidays();
    }

    const hiddenList = Array.isArray(hiddenSpecialEvents[selectedDateForCustomEvent])
      ? hiddenSpecialEvents[selectedDateForCustomEvent]
      : [];
    hiddenSpecialEvents[selectedDateForCustomEvent] = [...new Set([...hiddenList, targetEvent])];
    saveHiddenSpecialEvents();

    renderCalendar();
    removeCustomHolidayBtn.disabled = false;
  });

  addExamDayBtn?.addEventListener('click', function () {
    if (!selectedDateForCustomEvent) return;

    const examTitle = window.prompt('Enter exam day title (e.g., Midterm Exam):');
    if (examTitle === null) return;

    const trimmed = examTitle.trim();
    if (!trimmed) return;

    const monthDay = getMonthDayKey(selectedDateForCustomEvent);
    const current = Array.isArray(recurringExamDays[monthDay]) ? recurringExamDays[monthDay] : [];
    recurringExamDays[monthDay] = [...current, trimmed];
    saveRecurringExams();
    renderCalendar();
  });

  removeCustomHolidayBtn?.addEventListener('click', function () {
    if (!selectedDateForCustomEvent) return;

    const holidayEntries = currentHolidayEntriesByDate[selectedDateForCustomEvent] || [];
    if (!holidayEntries.length) return;

    const optionsText = holidayEntries
      .map((holidayEntry, index) => {
        const label = holidayEntry.type === 'official'
          ? 'Official'
          : (holidayEntry.type === 'custom_recurring' ? 'Recurring Custom' : 'One-time Custom');
        return `${index + 1}. ${holidayEntry.name} (${label})`;
      })
      .join('\n');
    const selected = window.prompt(`Select holiday number to remove:\n${optionsText}`);
    if (selected === null) return;

    const selectedIndex = parseInt(selected, 10) - 1;
    if (Number.isNaN(selectedIndex) || selectedIndex < 0 || selectedIndex >= holidayEntries.length) return;

    const target = holidayEntries[selectedIndex];
    if (target.type === 'custom_recurring') {
      const monthDay = getMonthDayKey(selectedDateForCustomEvent);
      const recurringList = Array.isArray(recurringHolidays[monthDay]) ? recurringHolidays[monthDay] : [];
      const nextRecurring = recurringList.filter((holidayName) => holidayName !== target.name);
      if (nextRecurring.length > 0) {
        recurringHolidays[monthDay] = nextRecurring;
      } else {
        delete recurringHolidays[monthDay];
      }
      saveRecurringHolidays();
    } else if (target.type === 'custom_exact') {
      const customList = getCustomHolidayNamesForDate(selectedDateForCustomEvent);
      const nextHolidays = customList.filter((holidayName) => holidayName !== target.name);
      if (nextHolidays.length > 0) {
        customHolidays[selectedDateForCustomEvent] = nextHolidays;
      } else {
        delete customHolidays[selectedDateForCustomEvent];
      }
      saveCustomHolidays();
    } else {
      const hiddenList = Array.isArray(hiddenOfficialHolidays[selectedDateForCustomEvent])
        ? hiddenOfficialHolidays[selectedDateForCustomEvent]
        : [];
      hiddenOfficialHolidays[selectedDateForCustomEvent] = [...new Set([...hiddenList, target.name])];
      saveHiddenOfficialHolidays();
    }

    renderCalendar();
    removeCustomHolidayBtn.disabled = (currentHolidayEntriesByDate[selectedDateForCustomEvent] || []).length === 0;
  });

  function getSpecialEventsForYear(year) {
    if (specialEventCache[year]) {
      return specialEventCache[year];
    }

    const events = {};
    const add = (date, label) => {
      if (!events[date]) events[date] = [];
      events[date].push(label);
    };

    add(`${year}-02-14`, "Valentine's Day");
    add(`${year}-03-08`, "International Women's Day");
    add(`${year}-04-22`, "Earth Day");
    add(`${year}-10-31`, "Halloween");
    add(`${year}-11-01`, "All Saints' Day");
    add(`${year}-11-02`, "All Souls' Day");
    add(`${year}-12-24`, "Christmas Eve");
    add(`${year}-12-31`, "New Year's Eve");

    const chineseNewYearByYear = {
      2024: '2024-02-10',
      2025: '2025-01-29',
      2026: '2026-02-17',
      2027: '2027-02-06',
      2028: '2028-01-26',
      2029: '2029-02-13',
      2030: '2030-02-03',
      2031: '2031-01-23',
      2032: '2032-02-11',
      2033: '2033-01-31',
      2034: '2034-02-19',
      2035: '2035-02-08',
    };
    if (chineseNewYearByYear[year]) {
      add(chineseNewYearByYear[year], 'Chinese New Year');
    }

    specialEventCache[year] = events;
    return events;
  }

  syncCalendarHolidaysWithServer();
  renderCalendar();
</script>

</body>
</html>
