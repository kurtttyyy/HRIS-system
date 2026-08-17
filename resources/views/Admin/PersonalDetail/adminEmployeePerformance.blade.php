<!-- Performance Tab -->
<style>
  @media (max-width: 767px) {
    .employee-performance-tab {
      padding: 0.85rem !important;
      row-gap: 0.85rem !important;
    }
    .employee-performance-metrics {
      display: grid !important;
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
      gap: 0.6rem !important;
    }
    .employee-performance-metrics > div {
      min-width: 0;
      min-height: 6.75rem;
      justify-content: space-between;
      gap: 0.5rem !important;
      padding: 0.85rem !important;
      border: 1px solid transparent;
      border-radius: 1rem !important;
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    }
    .employee-performance-metrics > div:nth-child(1) {
      border-color: #bfdbfe;
      background: linear-gradient(135deg, #eff6ff, #dbeafe) !important;
      color: #1d4ed8 !important;
    }
    .employee-performance-metrics > div:nth-child(2) {
      border-color: #bbf7d0;
      background: linear-gradient(135deg, #f0fdf4, #dcfce7) !important;
      color: #15803d !important;
    }
    .employee-performance-metrics > div:nth-child(3) {
      border-color: #ddd6fe;
      background: linear-gradient(135deg, #f5f3ff, #ede9fe) !important;
      color: #7e22ce !important;
    }
    .employee-performance-metrics > div:nth-child(4) {
      border-color: #fed7aa;
      background: linear-gradient(135deg, #fff7ed, #ffedd5) !important;
      color: #c2410c !important;
    }
    .employee-performance-metrics svg {
      width: 1.15rem !important;
      height: 1.15rem !important;
      flex: 0 0 auto;
    }
    .employee-performance-metrics span {
      font-size: 0.68rem !important;
      line-height: 1.25;
    }
    .employee-performance-metrics > div > div:last-child {
      font-size: 1.65rem !important;
      line-height: 1;
    }
    .employee-performance-section {
      padding: 1rem !important;
      row-gap: 0.75rem !important;
      border: 1px solid #e2e8f0;
      border-radius: 1rem !important;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05) !important;
    }
    .employee-performance-section > h3 {
      margin-bottom: 0.5rem !important;
      font-size: 1rem !important;
    }
    .employee-performance-section > h3 svg {
      width: 1.2rem !important;
      height: 1.2rem !important;
    }
    .employee-performance-section article {
      padding: 0.75rem !important;
      border-radius: 0.75rem !important;
    }
    .employee-performance-section article header {
      gap: 0.5rem;
    }
    .employee-performance-section article h4 {
      font-size: 0.78rem;
    }
    .employee-performance-section article p,
    .employee-performance-section article time,
    .employee-performance-section article > div:last-child {
      font-size: 0.62rem !important;
      line-height: 1.4;
    }
    .employee-performance-section article > div:last-child {
      flex-wrap: wrap;
      gap: 0.4rem 0.75rem !important;
    }
  }
</style>
<div x-show="tab === 'performance'" x-transition class="employee-performance-tab p-6 space-y-6">

  <!-- Metric Cards -->
  <div class="employee-performance-metrics grid grid-cols-1 sm:grid-cols-4 gap-4">

    <!-- Overall Rating -->
    <div class="bg-blue-600 text-white rounded-xl p-5 flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
        </svg>
        <span class="text-sm font-semibold">Overall Rating</span>
      </div>
      <div class="text-3xl font-bold">4.5</div>
    </div>

    <!-- Projects Completed -->
    <div class="bg-green-600 text-white rounded-xl p-5 flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m3 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-sm font-semibold">Projects Completed</span>
      </div>
      <div class="text-3xl font-bold">24</div>
    </div>

    <!-- Attendance Rate -->
    <div class="bg-purple-600 text-white rounded-xl p-5 flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-sm font-semibold">Attendance Rate</span>
      </div>
      <div class="text-3xl font-bold">98%</div>
    </div>

    <!-- Achievements -->
    <div class="bg-orange-600 text-white rounded-xl p-5 flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l2.122 6.517a1 1 0 00.95.69h6.853c.969 0 1.371 1.24.588 1.81l-5.54 4.034a1 1 0 00-.364 1.118l2.122 6.517c.3.921-.755 1.688-1.538 1.118l-5.54-4.034a1 1 0 00-1.176 0l-5.54 4.034c-.783.57-1.838-.197-1.538-1.118l2.122-6.517a1 1 0 00-.364-1.118L2.44 11.944c-.783-.57-.38-1.81.588-1.81h6.853a1 1 0 00.95-.69l2.122-6.517z" />
        </svg>
        <span class="text-sm font-semibold">Achievements</span>
      </div>
      <div class="text-3xl font-bold">15</div>
    </div>

  </div>

  <!-- Performance Reviews Section -->
  <section class="employee-performance-section bg-white rounded-xl p-6 shadow-sm space-y-6">

    <h3 class="font-semibold text-lg mb-4 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2-8v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2h3l2-2 2 2h3a2 2 0 012 2z" />
      </svg>
      Performance Reviews
    </h3>

    <!-- Review 1 -->
    <article class="border border-gray-200 rounded-lg p-4 space-y-2">
      <header class="flex justify-between items-center">
        <div>
          <h4 class="font-semibold">Q4 2024 Review</h4>
          <time class="text-xs text-gray-500">December 15, 2024</time>
        </div>
        <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold">Excellent</span>
      </header>
      <p class="text-gray-700 text-sm">
        Consistently exceeded expectations. Strong leadership in project delivery and excellent collaboration with team members.
      </p>
      <div class="text-xs text-gray-500 flex gap-4">
        <span>Technical Skills: <strong>5/5</strong></span>
        <span>Communication: <strong>4.5/5</strong></span>
        <span>Teamwork: <strong>5/5</strong></span>
      </div>
    </article>

    <!-- Review 2 -->
    <article class="border border-gray-200 rounded-lg p-4 space-y-2">
      <header class="flex justify-between items-center">
        <div>
          <h4 class="font-semibold">Q3 2024 Review</h4>
          <time class="text-xs text-gray-500">September 15, 2024</time>
        </div>
        <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold">Good</span>
      </header>
      <p class="text-gray-700 text-sm">
        Met all objectives and demonstrated strong problem-solving abilities. Continues to develop leadership skills.
      </p>
      <div class="text-xs text-gray-500 flex gap-4">
        <span>Technical Skills: <strong>4.5/5</strong></span>
        <span>Communication: <strong>4/5</strong></span>
        <span>Teamwork: <strong>4.5/5</strong></span>
      </div>
    </article>

    <!-- Review 3 -->
    <article class="border border-gray-200 rounded-lg p-4 space-y-2">
      <header class="flex justify-between items-center">
        <div>
          <h4 class="font-semibold">Q2 2024 Review</h4>
          <time class="text-xs text-gray-500">June 15, 2024</time>
        </div>
        <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold">Excellent</span>
      </header>
      <p class="text-gray-700 text-sm">
        Outstanding performance across all metrics. Successfully led multiple high-priority projects to completion.
      </p>
      <div class="text-xs text-gray-500 flex gap-4">
        <span>Technical Skills: <strong>5/5</strong></span>
        <span>Communication: <strong>4.5/5</strong></span>
        <span>Teamwork: <strong>5/5</strong></span>
      </div>
    </article>

  </section>

  <!-- Goals & Objectives Section -->
  <section class="employee-performance-section bg-white rounded-xl p-6 shadow-sm space-y-5">

    <h3 class="font-semibold text-lg mb-4 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2-8v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2h3l2-2 2 2h3a2 2 0 012 2z" />
      </svg>
      Goals & Objectives (2024)
    </h3>

    <!-- Goal 1 -->
    <div>
      <div class="flex justify-between mb-1">
        <span class="text-sm font-medium text-gray-700">Lead 3 major projects to completion</span>
        <span class="text-sm font-medium text-gray-700">100%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-3">
        <div class="bg-green-600 h-3 rounded-full" style="width: 100%"></div>
      </div>
    </div>

    <!-- Goal 2 -->
    <div>
      <div class="flex justify-between mb-1">
        <span class="text-sm font-medium text-gray-700">Mentor junior developers</span>
        <span class="text-sm font-medium text-gray-700">75%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-3">
        <div class="bg-blue-600 h-3 rounded-full" style="width: 75%"></div>
      </div>
    </div>

    <!-- Goal 3 -->
    <div>
      <div class="flex justify-between mb-1">
        <span class="text-sm font-medium text-gray-700">Complete AWS certification</span>
        <span class="text-sm font-medium text-gray-700">60%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-3">
        <div class="bg-orange-500 h-3 rounded-full" style="width: 60%"></div>
      </div>
    </div>

  </section>

</div>
