<!-- Personal Details -->
<div
  x-show="tab === 'personal'"
  x-transition
  class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6"
>
  <style>
    .missing-info-exclamation {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1rem;
      height: 1rem;
      border-radius: 9999px;
      background: #ef4444;
      color: #fff;
      font-size: 0.7rem;
      font-weight: 800;
      line-height: 1;
      box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45);
      animation: missingInfoPulse 1.2s ease-in-out infinite;
    }

    @keyframes missingInfoPulse {
      0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45);
      }
      50% {
        transform: scale(1.08);
        box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
      }
    }
  </style>

  <!-- Personal Information -->
  <div class="bg-slate-50 p-6 rounded-xl shadow-sm space-y-5">
    <div class="flex items-center gap-2 text-indigo-600 font-semibold text-lg">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M5.121 17.804A9.003 9.003 0 1118.879 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      Personal Information
    </div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4.5 20.25a7.5 7.5 0 0115 0" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold" style="font-size: 10px;">Full Name</span>
        <span x-text="`${selectedEmployee?.first_name ?? ''} ${selectedEmployee?.middle_name ?? ''} ${selectedEmployee?.last_name ?? ''}`.trim()"></span>
      </div>
    </div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M8 7V3m8 4V3m-11 8h14m-15 9.75A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V7.5A2.25 2.25 0 0018.75 5.25H5.25A2.25 2.25 0 003 7.5v11.25z" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Date of Birth</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.formatted_birthday) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.formatted_birthday)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.formatted_birthday)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Age</span>
        <span class="inline-flex items-center gap-2">
          <span
            :class="isMissingDisplayValue((() => {
              const rawBirthday = selectedEmployee?.employee?.birthday;
              if (!rawBirthday) return '';

              const birthday = new Date(rawBirthday);
              if (Number.isNaN(birthday.getTime())) return '';

              const today = new Date();
              if (birthday > today) return '';

              let years = today.getFullYear() - birthday.getFullYear();
              const monthDiff = today.getMonth() - birthday.getMonth();
              const dayDiff = today.getDate() - birthday.getDate();

              if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                years -= 1;
              }

              return years >= 0 ? String(years) : '';
            })()) ? 'text-rose-600' : 'text-slate-700'"
            x-text="displayValueOrNoInfo((() => {
              const rawBirthday = selectedEmployee?.employee?.birthday;
              if (!rawBirthday) return '';

              const birthday = new Date(rawBirthday);
              if (Number.isNaN(birthday.getTime())) return '';

              const today = new Date();
              if (birthday > today) return '';

              let years = today.getFullYear() - birthday.getFullYear();
              const monthDiff = today.getMonth() - birthday.getMonth();
              const dayDiff = today.getDate() - birthday.getDate();

              if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                years -= 1;
              }

              return years >= 0 ? String(years) : '';
            })())"
          ></span>
          <span
            x-show="isMissingDisplayValue((() => {
              const rawBirthday = selectedEmployee?.employee?.birthday;
              if (!rawBirthday) return '';

              const birthday = new Date(rawBirthday);
              if (Number.isNaN(birthday.getTime())) return '';

              const today = new Date();
              if (birthday > today) return '';

              let years = today.getFullYear() - birthday.getFullYear();
              const monthDiff = today.getMonth() - birthday.getMonth();
              const dayDiff = today.getDate() - birthday.getDate();

              if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                years -= 1;
              }

              return years >= 0 ? String(years) : '';
            })())"
            class="missing-info-exclamation"
            aria-hidden="true"
          >!</span>
        </span>
      </div>
    </div>

<div class="flex gap-3">
  <svg
    class="w-5 h-5 text-gray-400 mt-1"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    viewBox="0 0 24 24"
  >
    <!-- Female -->
    <circle cx="9" cy="8" r="3" />
    <path stroke-linecap="round" d="M9 11v4m-2 0h4" />

    <!-- Male -->
    <circle cx="15" cy="14" r="3" />
    <path stroke-linecap="round" d="M17 12l3-3m0 0h-3m3 0v3" />
  </svg>

  <div>
    <span class="block uppercase text-gray-400 font-semibold">Gender</span>
    <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.sex) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.sex)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.sex)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
  </div>
</div>



    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0115 0"/>
    <!-- Person 2 -->
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14a7.5 7.5 0 00-7.5 6.75"/>
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Marital Status</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.civil_status) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.civil_status)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.civil_status)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5H4.5A2.25 2.25 0 002.25 6.75m19.5 0l-9.75 6.75L2.25 6.75" />
      </svg>

      <div>
        <span class="block uppercase text-gray-400 font-semibold">
          Email Address
        </span>
      <span class="inline-flex items-center gap-2 text-gray-600"><span :class="isMissingDisplayValue(firstCleanDisplayValue(selectedEmployee?.email, selectedEmployee?.employee?.email, selectedEmployee?.applicant?.email)) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(firstCleanDisplayValue(selectedEmployee?.email, selectedEmployee?.employee?.email, selectedEmployee?.applicant?.email))"></span><span x-show="isMissingDisplayValue(firstCleanDisplayValue(selectedEmployee?.email, selectedEmployee?.employee?.email, selectedEmployee?.applicant?.email))" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>


        <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372a1.125 1.125 0 00-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293a12.035 12.035 0 01-5.292-5.292l1.293-.97a1.125 1.125 0 00.417-1.173L6.963 4.102A1.125 1.125 0 005.872 3.25H4.5A2.25 2.25 0 002.25 5.5v1.25z"/>
      </svg>
      <div>
        <span class="block text-xs uppercase text-gray-400 font-semibold">Phone No.</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(firstCleanDisplayValue(selectedEmployee?.applicant?.phone, selectedEmployee?.employee?.contact_number)) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(firstCleanDisplayValue(selectedEmployee?.applicant?.phone, selectedEmployee?.employee?.contact_number))"></span><span x-show="isMissingDisplayValue(firstCleanDisplayValue(selectedEmployee?.applicant?.phone, selectedEmployee?.employee?.contact_number))" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>
  </div>

  <!-- Address -->
  <div class="bg-slate-50 p-6 rounded-xl shadow-sm space-y-5">
    <div class="flex items-center gap-2 text-indigo-600 font-semibold text-lg">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a7 7 0 017 7c0 4.418-7 13-7 13S5 13.418 5 9a7 7 0 017-7z" />
    <circle cx="12" cy="9" r="2.5" />
      </svg>
      Address
    </div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V5a2 2 0 012-2h9a2 2 0 012 2v16M9 7h4M9 11h4M9 15h4M2 21h20" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Street / Sitio / Subdivision</span>
        <span class="inline-flex items-center gap-2">
          <span
            :class="isMissingDisplayValue(addressFormParts().addressLine) ? 'text-rose-600' : 'text-slate-700'"
            x-text="displayValueOrNoInfo(addressFormParts().addressLine)"
          ></span>
          <span x-show="isMissingDisplayValue(addressFormParts().addressLine)" class="missing-info-exclamation" aria-hidden="true">!</span>
        </span>
      </div>
    </div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9v9a3 3 0 01-3 3H6a3 3 0 01-3-3v-9z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9" />
    </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Barangay</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(employeeAddressParts()[0]) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(employeeAddressParts()[0])"></span><span x-show="isMissingDisplayValue(employeeAddressParts()[0])" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a7 7 0 017 7c0 4.418-7 13-7 13S5 13.418 5 9a7 7 0 017-7z" />
    <circle cx="12" cy="9" r="2.5" />
    </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Municipality / City</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(employeeAddressParts()[1]) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(employeeAddressParts()[1])"></span><span x-show="isMissingDisplayValue(employeeAddressParts()[1])" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a7 7 0 017 7c0 4.418-7 13-7 13S5 13.418 5 9a7 7 0 017-7z" />
    <circle cx="12" cy="9" r="2.5" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Province</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(employeeAddressParts()[2]) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(employeeAddressParts()[2])"></span><span x-show="isMissingDisplayValue(employeeAddressParts()[2])" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

  </div>

  <!-- Emergency Contact -->
  <div class="bg-slate-50 p-6 rounded-xl shadow-sm space-y-5">
    <div class="flex items-center gap-2 text-red-600 font-semibold text-lg">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M18.364 5.636a9 9 0 11-12.728 0" />
      </svg>
      Emergency Contact
    </div>

<div class="flex gap-3">
  <svg
    class="w-5 h-5 text-gray-400 mt-1"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    viewBox="0 0 24 24"
  >
    <!-- Head -->
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
    />
    <!-- Body -->
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M4.5 20.25a7.5 7.5 0 0115 0"
    />
  </svg>

  <div>
    <span class="block uppercase text-gray-400 font-semibold">
      Contact Name
    </span>
    <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.emergency_contact_name) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.emergency_contact_name)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.emergency_contact_name)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
  </div>
</div>


    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0115 0" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14a7.5 7.5 0 00-7.5 6.75" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Relationship</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.emergency_contact_relationship) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.emergency_contact_relationship)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.emergency_contact_relationship)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

<div class="flex gap-3">
  <svg
    class="w-5 h-5 text-gray-400 mt-1"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    viewBox="0 0 24 24"
  >
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372a1.125 1.125 0 00-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293a12.035 12.035 0 01-5.292-5.292l1.293-.97a1.125 1.125 0 00.417-1.173L6.963 4.102A1.125 1.125 0 005.872 3.25H4.5A2.25 2.25 0 002.25 5.5v1.25z"
    />
  </svg>

  <div>
    <span class="block uppercase text-gray-400 font-semibold">Phone Number</span>
    <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.emergency_contact_number) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.emergency_contact_number)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.emergency_contact_number)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
  </div>
</div>

  </div>

  <!-- Bank Details -->
  <div class="bg-slate-50 p-6 rounded-xl shadow-sm space-y-5">
    <div class="flex items-center gap-2 text-indigo-600 font-semibold text-lg">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3 10h18M12 2v20M7 18h10" />
      </svg>
      Bank Details
    </div>

<div class="flex gap-3">
  <svg
    class="w-5 h-5 text-gray-400 mt-1"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    viewBox="0 0 24 24"
  >
    <!-- Bank / building structure -->
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M3 10l9-7 9 7M4.5 10.5v9h15v-9"
    />
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M9 14h1.5M13.5 14H15M9 17h1.5M13.5 17H15"
    />
  </svg>

  <div>
    <span class="block uppercase text-gray-400 font-semibold">
      PNB
    </span>
    <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.account_number) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.account_number)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.account_number)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
  </div>

</div>

    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4 6h16M4 12h16M4 18h16" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">PAG IBIG MID:</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.government?.MID) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.government?.MID)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.government?.MID)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

        <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4 6h16M4 12h16M4 18h16" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">PAG IBIG RTN:</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.government?.RTN) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.government?.RTN)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.government?.RTN)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

        <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4 6h16M4 12h16M4 18h16" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">SSS</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.government?.SSS) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.government?.SSS)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.government?.SSS)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

        <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4 6h16M4 12h16M4 18h16" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">PhilHealth</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.government?.PhilHealth) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.government?.PhilHealth)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.government?.PhilHealth)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>
        <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4 6h16M4 12h16M4 18h16" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">TIN</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.government?.TIN) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.government?.TIN)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.government?.TIN)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>


    <div class="flex gap-3">
      <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M7 7h10v10H7z" />
      </svg>
      <div>
        <span class="block uppercase text-gray-400 font-semibold">Account Number</span>
        <span class="inline-flex items-center gap-2"><span :class="isMissingDisplayValue(selectedEmployee?.employee?.employee_id) ? 'text-rose-600' : 'text-slate-700'" x-text="displayValueOrNoInfo(selectedEmployee?.employee?.employee_id)"></span><span x-show="isMissingDisplayValue(selectedEmployee?.employee?.employee_id)" class="missing-info-exclamation" aria-hidden="true">!</span></span>
      </div>
    </div>

  </div>

</div>





