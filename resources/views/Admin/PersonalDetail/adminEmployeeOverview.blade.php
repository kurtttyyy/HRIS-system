<!-- Overview -->
<div
  x-show="tab === 'overview'"
  x-transition
  class="w-full grid grid-cols-1 md:grid-cols-2 gap-6 p-6"
>
  <!-- Contact Information -->
  <div class="bg-slate-50 p-4 rounded-xl text-sm">
    <h2 class="font-semibold mb-5">Contact Information</h2>

    <!-- Email -->
    <div class="flex items-start gap-3 mb-5">
      <a
        :href="selectedEmployee?.email ? `mailto:${selectedEmployee.email}` : null"
        class="text-gray-400 hover:text-gray-600 mt-0.5"
      >
        <svg
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75m19.5 0-9.75 6.75L2.25 6.75"
          />
        </svg>
      </a>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Email
        </span>

        <a
          x-show="selectedEmployee?.email"
          :href="`mailto:${selectedEmployee.email}`"
          class="text-gray-700 hover:text-indigo-600"
          x-text="selectedEmployee.email"
        ></a>

        <span
          x-show="!selectedEmployee?.email"
          x-html="'No information <span class=&quot;inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-xs font-black text-white shadow-sm&quot; aria-hidden=&quot;true&quot;>!</span>'"
          class="inline-flex items-center gap-1.5 font-medium text-rose-500"
        >Ã¢â‚¬â€</span>
      </div>
    </div>

    <!-- Phone -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372a1.125 1.125 0 0 0-.852-1.091l-4.423-1.106a1.125 1.125 0 0 0-1.173.417l-.97 1.293a12.035 12.035 0 0 1-5.292-5.292l1.293-.97a1.125 1.125 0 0 0 .417-1.173L6.963 4.102A1.125 1.125 0 0 0 5.872 3.25H4.5A2.25 2.25 0 0 0 2.25 5.5v1.25z"
        />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Phone
        </span>
        <span
          class="text-gray-700"
          x-text="selectedEmployee?.applicant?.phone ?? selectedEmployee?.employee?.contact_number ?? 'Ã¢â‚¬â€'"
        ></span>
      </div>
    </div>

    <!-- Location -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 21s6-5.686 6-10a6 6 0 1 0-12 0c0 4.314 6 10 6 10z"
        />
        <circle cx="12" cy="11" r="2.5" />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Location
        </span>
        <span
          class="text-gray-700"
          x-text="selectedEmployee?.applicant?.address ?? selectedEmployee?.employee?.address ?? 'Ã¢â‚¬â€'"
        ></span>
      </div>
    </div>

    <!-- Age -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 6v6l4 2"
        />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Age
        </span>
        <span
          class="text-gray-700"
          x-text="(() => {
            const rawBirthday = selectedEmployee?.employee?.birthday;
            if (!rawBirthday) return '—';

            const birthday = new Date(rawBirthday);
            if (Number.isNaN(birthday.getTime())) return '—';

            const today = new Date();
            if (birthday > today) return '—';

            let years = today.getFullYear() - birthday.getFullYear();
            const monthDiff = today.getMonth() - birthday.getMonth();
            const dayDiff = today.getDate() - birthday.getDate();

            if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
              years -= 1;
            }

            return years >= 0 ? String(years) : '—';
          })()"
        ></span>
      </div>
    </div>
  </div>


  <!-- Employment Details -->
  <div class="bg-slate-50 p-4 rounded-xl text-sm">
    <h2 class="font-semibold mb-5">Employment Details</h2>

    <!-- Employee ID -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z"
        />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M4.5 20.25a7.5 7.5 0 0 1 15 0"
        />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Employee ID
        </span>
        <span class="text-gray-700"
            x-text="selectedEmployee?.employee.employee_id ?? 'Ã¢â‚¬â€'"
        ></span>
      </div>
    </div>

    <!-- Join Date -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-11 8h14m-15 9.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V7.5A2.25 2.25 0 0 0 18.75 5.25H5.25A2.25 2.25 0 0 0 3 7.5v11.25z" />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Join Date
        </span>
        <span
          class="text-gray-700"
          x-text="(() => {
            const formatted = selectedEmployee?.applicant?.formatted_date_hired
              || selectedEmployee?.employee?.formatted_employement_date;
            if (formatted) return formatted;

            const raw = selectedEmployee?.employee?.employement_date;
            if (!raw) return 'Ã¢â‚¬â€';

            const parsed = new Date(raw);
            return Number.isNaN(parsed.getTime())
              ? raw
              : parsed.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
          })()"
        ></span>
      </div>
    </div>


    <!-- Position -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M17 20.25v-2.625a4.125 4.125 0 0 0-4.125-4.125h-1.75A4.125 4.125 0 0 0 7 17.625V20.25"
        />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z"
        />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Position
        </span>
        <span
          class="text-gray-700"
          x-text="selectedEmployee?.job_role ?? selectedEmployee?.employee?.position ?? selectedEmployee?.applicant?.position?.title ?? 'Ã¢â‚¬â€'"
        ></span>
      </div>
    </div>
    <!-- Department -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4.75A1.75 1.75 0 0 1 11 7.75V18a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 18V7.5zM13 6h6a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-6V6z"
        />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Department
        </span>
        <span
          class="text-gray-700"
          x-text="selectedEmployee?.employee?.department ?? selectedEmployee?.applicant?.position?.department ?? 'Ã¢â‚¬â€'"
        ></span>
      </div>
    </div>

    <!-- Classification -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Classification
        </span>
        <span
          class="text-gray-700"
          x-text="selectedEmployee?.employee?.classification ?? selectedEmployee?.applicant?.position?.employment ?? 'Ã¢â‚¬â€'"
        ></span>
      </div>
    </div>
    <!-- Contract Type -->
    <div class="flex items-start gap-3 mb-5">
      <svg
        class="w-5 h-5 text-gray-400 mt-0.5"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M16.5 9.4V6.75A2.25 2.25 0 0 0 14.25 4.5h-7.5A2.25 2.25 0 0 0 4.5 6.75v10.5A2.25 2.25 0 0 0 6.75 19.5h7.5a2.25 2.25 0 0 0 2.25-2.25V14.6M9 12h12m0 0-3-3m3 3-3 3"
        />
      </svg>

      <div>
        <span class="block font-semibold text-xs uppercase text-gray-400">
          Contract Type
        </span>
        <span
          class="text-gray-700"
          x-text="(() => {
            const jobTypeRaw = selectedEmployee?.employee?.job_type ?? selectedEmployee?.applicant?.position?.job_type ?? '';
            const jobType = jobTypeRaw.toString().trim().toLowerCase();
            const rawJoinDate = selectedEmployee?.applicant?.date_hired ?? selectedEmployee?.employee?.employement_date;
            if (jobType !== 'non-teaching' && jobType !== 'teaching') return 'N/A';
            if (!rawJoinDate) return 'Probationary';

            const joinDate = new Date(rawJoinDate);
            if (Number.isNaN(joinDate.getTime())) return 'Probationary';

            const today = new Date();
            if (jobType === 'non-teaching') {
              const sixMonthsAfterJoin = new Date(joinDate);
              sixMonthsAfterJoin.setMonth(sixMonthsAfterJoin.getMonth() + 6);
              return today < sixMonthsAfterJoin ? 'Probationary' : 'Permanent';
            }

            const threeYearsAfterJoin = new Date(joinDate);
            threeYearsAfterJoin.setFullYear(threeYearsAfterJoin.getFullYear() + 3);
            return today < threeYearsAfterJoin ? 'Probationary' : 'Permanent';
          })()"
        ></span>
      </div>
    </div>
  </div>
  <!-- Skills -->
<div class="bg-slate-50 p-4 rounded-xl text-sm">
  <h3 class="font-semibold mb-5">Skills</h3>
  <div class="flex flex-wrap gap-2">
    <template x-for="skill in (selectedEmployee?.applicant?.position?.skills ?? '').split(',')" :key="skill">
      <span class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-full" x-text="skill.trim()"></span>
    </template>
  </div>
</div>

  <!-- Subject Loads -->
  <div class="bg-slate-50 p-4 rounded-xl text-sm">
    <h3 class="font-semibold mb-5">Subjects and Units</h3>

    <div
      x-show="Array.isArray(selectedEmployee?.subject_loads) && selectedEmployee.subject_loads.length"
      class="space-y-3"
    >
      <template x-for="(load, index) in (selectedEmployee?.subject_loads ?? [])" :key="`${load?.subject_name ?? load?.code ?? 'load'}-${index}`">
        <div class="flex items-start justify-between gap-4 rounded-lg border border-slate-200 bg-white px-3 py-2">
          <div class="min-w-0">
            <p
              class="font-medium text-slate-800"
              x-text="load?.subject_name || [load?.code, load?.course_no].filter(Boolean).join(' ') || 'Untitled subject'"
            ></p>
            <p
              class="text-xs text-slate-500"
              x-show="load?.schedule"
              x-text="load?.schedule"
            ></p>
          </div>
          <span
            class="shrink-0 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700"
            x-text="(() => {
              const rawUnits = (load?.units ?? '').toString().trim();
              return rawUnits ? `${rawUnits} unit${rawUnits === '1' ? '' : 's'}` : 'No units';
            })()"
          ></span>
        </div>
      </template>
    </div>

    <p
      x-show="!Array.isArray(selectedEmployee?.subject_loads) || !selectedEmployee.subject_loads.length"
      class="text-slate-500"
    >
      No existing subject loads found.
    </p>
  </div>


  <!-- Recent Activity -->
  <div class="bg-slate-50 p-4 rounded-xl text-sm">
    <h3 class="font-semibold mb-5">Recent Activity</h3>

    <ul class="space-y-3">
      <li class="flex items-start gap-3">
        <span class="w-2 h-2 rounded-full bg-green-500 mt-2"></span>
        <div>
          <p class="text-gray-700">Completed project milestone</p>
          <p class="text-xs text-gray-400">2 days ago</p>
        </div>
      </li>

      <li class="flex items-start gap-3">
        <span class="w-2 h-2 rounded-full bg-blue-500 mt-2"></span>
        <div>
          <p class="text-gray-700">Attended team meeting</p>
          <p class="text-xs text-gray-400">5 days ago</p>
        </div>
      </li>

      <li class="flex items-start gap-3">
        <span class="w-2 h-2 rounded-full bg-purple-500 mt-2"></span>
        <div>
          <p class="text-gray-700">Updated profile</p>
          <p class="text-xs text-gray-400">1 week ago</p>
        </div>
      </li>
    </ul>
  </div>
</div>



