



<!-- ================= PERSONAL DETAILS ================= -->
<style>
  @media (max-width: 767px) {
    .employee-service-record {
      padding: 0.85rem !important;
    }
    .employee-service-record > div {
      row-gap: 0.85rem !important;
    }
    .employee-service-info {
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
      gap: 0.6rem !important;
    }
    .employee-service-info > div {
      min-width: 0;
      min-height: 5.25rem;
      padding: 0.8rem !important;
      border: 1px solid #e2e8f0;
      border-radius: 0.9rem !important;
      box-shadow: 0 6px 18px rgba(15, 23, 42, 0.045) !important;
    }
    .employee-service-info > div > p:first-child {
      font-size: 0.58rem !important;
      font-weight: 700;
      letter-spacing: 0.08em;
    }
    .employee-service-info > div > p:nth-child(2),
    .employee-service-info > div > span {
      margin-top: 0.45rem !important;
      overflow-wrap: anywhere;
      font-size: 0.78rem !important;
      line-height: 1.3;
    }
    .employee-service-stats {
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
      gap: 0.6rem !important;
    }
    .employee-service-stats > div {
      min-width: 0;
      min-height: 6rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 0.8rem !important;
      border: 1px solid transparent;
      border-radius: 0.9rem !important;
    }
    .employee-service-stats > div:first-child {
      grid-column: 1 / -1;
      min-height: 5rem;
      border-color: #c7d2fe;
      background: linear-gradient(135deg, #eef2ff, #e0e7ff) !important;
    }
    .employee-service-stats > div:nth-child(2) {
      border-color: #bbf7d0;
      background: linear-gradient(135deg, #f0fdf4, #dcfce7) !important;
    }
    .employee-service-stats > div:nth-child(3) {
      border-color: #ddd6fe;
      background: linear-gradient(135deg, #f5f3ff, #ede9fe) !important;
    }
    .employee-service-stats > div > p:first-child {
      font-size: 1.25rem !important;
      line-height: 1.15;
      overflow-wrap: anywhere;
    }
    .employee-service-stats > div:first-child > p:first-child {
      font-size: 1.55rem !important;
    }
    .employee-service-stats > div > p:last-child {
      margin-top: 0.25rem;
      font-size: 0.65rem !important;
    }
    .employee-service-content {
      gap: 0.85rem !important;
    }
    .employee-service-content > div,
    .employee-service-content > div > div {
      border-radius: 0.9rem !important;
    }
    .employee-service-timeline {
      padding: 1rem !important;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05) !important;
    }
    .employee-service-timeline > h2 {
      margin-bottom: 0.75rem !important;
      font-size: 0.9rem;
    }
    .employee-service-timeline-scroll {
      max-height: 20rem !important;
      padding-right: 0 !important;
    }
    .employee-service-timeline-item {
      padding: 0.25rem 0 0.25rem 2rem !important;
    }
    .employee-service-timeline-item > span {
      left: 0.1rem !important;
      top: 1rem !important;
      width: 0.75rem !important;
      height: 0.75rem !important;
    }
    .employee-service-timeline-card {
      padding: 0.75rem !important;
      border-radius: 0.75rem !important;
      box-shadow: none !important;
    }
    .employee-service-timeline-card > div:first-child {
      gap: 0.35rem !important;
    }
    .employee-service-timeline-card > div:first-child span {
      padding: 0.2rem 0.4rem !important;
      font-size: 0.55rem !important;
    }
    .employee-service-timeline-card h3 {
      margin-top: 0.5rem !important;
      font-size: 0.78rem !important;
    }
    .employee-service-timeline-card p {
      margin-top: 0.25rem !important;
      font-size: 0.65rem !important;
      line-height: 1.45 !important;
    }
    .employee-service-sidebar {
      row-gap: 0.65rem !important;
    }
    .employee-service-sidebar > div {
      padding: 0.9rem !important;
      border: 1px solid #e2e8f0;
      box-shadow: 0 6px 18px rgba(15, 23, 42, 0.045) !important;
    }
    .employee-service-sidebar h2 {
      margin-bottom: 0.65rem !important;
      font-size: 0.85rem;
    }
    .employee-service-sidebar .space-y-3 {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.4rem !important;
    }
    .employee-service-sidebar .space-y-3 > * {
      margin-top: 0 !important;
      min-width: 0;
      padding: 0.5rem 0.25rem !important;
      font-size: 0.6rem !important;
    }
  }
</style>
<div x-show="tab==='record'" x-transition class="employee-service-record w-full p-6">

  <!-- Main Container -->
  <div class="max-w-7xl mx-auto space-y-6">

    <!-- Top Info Cards -->
    <div class="employee-service-info grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-xs text-gray-400">EMPLOYEE ID</p>
        <p class="font-semibold mt-1" x-text="selectedEmployee?.employee?.employee_id ?? '-'"></p>
      </div>

      <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-xs text-gray-400">DEPARTMENT</p>
        <p class="font-semibold mt-1" x-text="selectedEmployee?.department ?? selectedEmployee?.employee?.department ?? selectedEmployee?.applicant?.position?.department ?? '-'"></p>
      </div>

      <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-xs text-gray-400">DATE HIRED</p>
        <p
          class="font-semibold mt-1"
          x-text="(() => {
            const raw = currentServiceStartRaw();
            if (!raw) return '-';
            const datePart = raw.toString().split('T')[0];
            const [year, month, day] = datePart.split('-').map(Number);
            if (!year || !month || !day) return '-';
            const date = new Date(year, month - 1, day);
            if (Number.isNaN(date.getTime())) return '-';
            return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
          })()"
        ></p>
      </div>

      <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-xs text-gray-400">STATUS</p>
        <span
          class="inline-block px-3 py-1 rounded-full text-sm mt-1"
          :class="effectiveAccountStatusClass()"
          x-text="effectiveAccountStatus()">
        </span>
      </div>
    </div>

    <!-- Stats -->
    <div class="employee-service-stats grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-indigo-50 text-indigo-600 rounded-xl p-6 text-center">
        <p
          class="text-3xl font-bold"
          x-html="(() => {
            const raw = currentServiceStartRaw();
            if (!raw) return `0<span class='text-sm align-baseline ml-1'>Y</span> 0<span class='text-sm align-baseline ml-1'>M</span> 0<span class='text-sm align-baseline ml-1'>D</span>`;
            const datePart = raw.toString().split('T')[0];
            const [year, month, day] = datePart.split('-').map(Number);
            if (!year || !month || !day) return `0<span class='text-sm align-baseline ml-1'>Y</span> 0<span class='text-sm align-baseline ml-1'>M</span> 0<span class='text-sm align-baseline ml-1'>D</span>`;
            const start = new Date(year, month - 1, day);
            if (Number.isNaN(start.getTime())) return `0<span class='text-sm align-baseline ml-1'>Y</span> 0<span class='text-sm align-baseline ml-1'>M</span> 0<span class='text-sm align-baseline ml-1'>D</span>`;
            const today = new Date();
            let years = today.getFullYear() - start.getFullYear();
            let months = today.getMonth() - start.getMonth();
            let days = today.getDate() - start.getDate();
            if (days < 0) {
              days += new Date(today.getFullYear(), today.getMonth(), 0).getDate();
              months -= 1;
            }
            if (months < 0) {
              months += 12;
              years -= 1;
            }
            if (years < 0) {
              years = 0;
              months = 0;
              days = 0;
            }
            return `${years}<span class='text-sm align-baseline ml-1'>Y</span> ${months}<span class='text-sm align-baseline ml-1'>M</span> ${days}<span class='text-sm align-baseline ml-1'>D</span>`;
          })()"
        ></p>
        <p class="text-sm">Service Length</p>
      </div>

      <div class="bg-green-50 text-green-600 rounded-xl p-6 text-center">
        <p
          class="text-3xl font-bold"
          x-text="countPromotionEvents()"
        ></p>
        <p class="text-sm">Promotions</p>
      </div>

      <div class="bg-purple-50 text-purple-600 rounded-xl p-6 text-center">
        <p
          class="text-2xl font-bold"
          x-text="employmentClassificationLabel()"
        ></p>
        <p class="text-sm">Classification</p>
      </div>
    </div>

    <!-- Timeline + Sidebar -->
    <div class="employee-service-content grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Service Timeline -->
      <div class="employee-service-timeline lg:col-span-2 bg-white rounded-xl p-6 shadow">
        <h2 class="font-semibold mb-6">Service Timeline</h2>

        <div class="employee-service-timeline-scroll relative max-h-[30rem] overflow-y-auto pr-2">
          <span class="absolute left-3 top-2 bottom-2 w-px bg-indigo-200"></span>

          <template x-for="(timelineItem, index) in buildServiceTimeline()" :key="`${timelineItem.type}-${index}`">
            <article class="employee-service-timeline-item relative pl-10 py-1 pr-1">
              <span class="absolute left-[6px] top-6 w-4 h-4 rounded-full border-2 border-white shadow-sm" :class="timelineItem.dotClass"></span>
              <div class="employee-service-timeline-card rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <span class="text-xs px-2.5 py-1 rounded-md font-medium" :class="timelineItem.badgeClass" x-text="timelineItem.badge"></span>
                  <span class="text-xs font-medium text-slate-500" x-text="timelineItem.dateLabel"></span>
                </div>
                <h3 class="mt-2 text-base font-semibold text-slate-900" x-text="timelineItem.title"></h3>
                <p class="mt-1 text-sm leading-6 text-slate-600" x-text="timelineItem.description"></p>
              </div>
            </article>
          </template>

          <p
            x-show="buildServiceTimeline().length === 0"
            class="text-sm text-gray-500 pl-10 py-4"
          >
            No service timeline data available.
          </p>

        </div>
      </div>

      <!-- Right Panel -->
      <div class="employee-service-sidebar space-y-6">

        <!-- Leave Balance -->
        <div class="bg-white rounded-xl p-6 shadow">
          <h2 class="font-semibold mb-4">Leave Balance</h2>

          <div class="mb-4">
            <div class="flex justify-between text-sm mb-1">
              <span>Vacation</span>
              <span x-text="formatLeaveBalance(leaveVacationAvailable(), leaveVacationLimit())"></span>
            </div>
            <div class="bg-gray-200 h-2 rounded-full">
              <div
                class="bg-indigo-500 h-2 rounded-full"
                :style="`width: ${leaveBalancePercent(leaveVacationAvailable(), leaveVacationLimit())}%`"
              ></div>
            </div>
          </div>

          <div class="mb-4">
            <div class="flex justify-between text-sm mb-1">
              <span>Sick</span>
              <span x-text="formatLeaveBalance(leaveSickAvailable(), leaveSickLimit())"></span>
            </div>
            <div class="bg-gray-200 h-2 rounded-full">
              <div
                class="bg-green-500 h-2 rounded-full"
                :style="`width: ${leaveBalancePercent(leaveSickAvailable(), leaveSickLimit())}%`"
              ></div>
            </div>
          </div>

        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl p-6 shadow">
          <h2 class="font-semibold mb-4">Quick Actions</h2>
          <div class="space-y-3">

            <button
              @click="emailOpen = true"
              class="w-full border rounded-lg py-2 hover:bg-gray-50">
              Send Email
            </button>


            <a
              :href="selectedEmployee?.id ? '{{ route('admin.PersonalDetail.serviceRecordEdit') }}?user_id=' + selectedEmployee.id : '#'"
              class="block w-full border rounded-lg py-2 text-center hover:bg-gray-50">
              Edit Record
            </a>

            <a
              :href="selectedEmployee?.id ? '{{ route('admin.PersonalDetail.serviceRecordEdit.downloadWord') }}?user_id=' + selectedEmployee.id : '#'"
              class="block w-full border rounded-lg py-2 text-center hover:bg-gray-50">
              Download Word
            </a>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<!-- SEND EMAIL MODAL -->
<div
  x-show="emailOpen"
  x-transition
  x-cloak
  @keydown.escape.window="emailOpen = false"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
>

  <!-- Modal Card -->
  <div
    @click.outside="emailOpen = false"
    class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden"
  >

    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3 text-white">
        <div class="bg-white/20 p-2 rounded-lg">
          ✉️
        </div>
        <div>
          <h2 class="text-lg font-semibold">Send Email</h2>
          <p class="text-sm opacity-90">To: Jennifer Williams</p>
        </div>
      </div>

      <button
        @click="emailOpen = false"
        class="text-white/80 hover:text-white text-xl"
      >
        ✕
      </button>
    </div>

    <!-- Body -->
    <div class="p-6 space-y-5">

      <!-- To -->
      <div>
        <label class="text-sm text-gray-600">To</label>
        <input
          type="email"
          value="jennifer.williams@techcorp.com"
          class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
        >
      </div>

      <!-- Subject -->
      <div>
        <label class="text-sm text-gray-600">Subject</label>
        <input
          type="text"
          placeholder="Enter email subject"
          class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
        >
      </div>

      <!-- Message -->
      <div>
        <label class="text-sm text-gray-600">Message</label>
        <textarea
          rows="5"
          placeholder="Type your message here..."
          class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
        ></textarea>
      </div>

    </div>

    <!-- Footer -->
    <div class="px-6 py-4 border-t flex justify-between items-center">
      <button
        class="flex-1 mr-3 bg-indigo-600 text-white py-2.5 rounded-lg font-medium hover:bg-indigo-700 flex items-center justify-center gap-2"
      >
        ⚠️ Send Email
      </button>

      <button
        @click="emailOpen = false"
        class="px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
      >
        Cancel
      </button>
    </div>

  </div>
</div>


<!-- ================= EDIT MODAL ================= -->
<div
  x-show="editOpen"
  x-transition
  x-cloak
  @keydown.escape.window="editOpen = false"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
>

  <div
    @click.outside="editOpen = false"
    class="w-full max-w-3xl bg-white rounded-2xl shadow-xl overflow-hidden"
  >

    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex justify-between items-center text-white">
      <div>
        <h2 class="text-lg font-semibold">Edit Employee Record</h2>
        <p class="text-sm opacity-90">Sarah Rodriguez</p>
      </div>
      <button @click="editOpen = false" class="text-xl">✕</button>
    </div>

    <!-- Body -->
    <div class="p-6 space-y-6">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <input class="rounded-lg border p-2" value="Sarah Rodriguez">
        <input class="rounded-lg border p-2" value="Senior Software Engineer">

        <select class="rounded-lg border p-2">
          <option>Engineering</option>
        </select>

        <select class="rounded-lg border p-2">
          <option>Active</option>
          <option>Inactive</option>
        </select>

        <input class="rounded-lg border p-2" value="Mar 15, 2019">
        <input disabled class="rounded-lg border p-2 bg-gray-100" value="EMP-2019-0847">

      </div>

      <div class="bg-indigo-50 rounded-xl p-4">
        <h3 class="font-semibold text-indigo-700 mb-4">
          Performance Metrics
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <input class="rounded-lg border text-center" value="5.8">
          <input class="rounded-lg border text-center" value="3">
          <input class="rounded-lg border text-center" value="248">
          <input class="rounded-lg border text-center" value="5">
        </div>
      </div>

    </div>

    <!-- Footer -->
    <div class="px-6 py-4 border-t flex justify-between">
      <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700">
        ✔ Save Changes
      </button>
      <button
        @click="editOpen = false"
        class="bg-gray-100 px-5 py-2 rounded-lg hover:bg-gray-200">
        Cancel
      </button>
    </div>

  </div>
</div>


