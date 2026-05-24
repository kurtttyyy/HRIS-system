<script>
window.displayEmployeeId = function displayEmployeeId(employee, emptyValue = '-') {
  const value = String(employee?.employee?.employee_id ?? '').trim();
  if (!value) {
    return emptyValue;
  }

  const userId = employee?.id;
  if (userId) {
    const generatedId = `EMP-${String(userId).padStart(5, '0')}`;
    if (value.toUpperCase() === generatedId) {
      return emptyValue;
    }
  }

  return value;
};
</script>

<div x-show="tab === 'biometric'" x-transition class="w-full p-6 space-y-6">
    <div class="p-8 space-y-6">
      <div>

<div
  class="max-w-5xl mx-auto bg-transparent px-5 py-8 border border-gray-400 text-[13px] text-black"
  style="background-image: linear-gradient(rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.85)), url('{{ asset('images/logo1.png') }}'); background-repeat: no-repeat; background-position: center top; background-size: cover;"
>

<!-- EDIT & DOWNLOAD BUTTONS -->
<div id="action-buttons" class="flex justify-between items-center mb-4 space-x-2">

  <!-- Edit Icon -->
  <div class="relative group">
    <button @click="ensureEmployeeClassification(); openEditProfile = true; modalTarget = 'biometric'; resetBiometricPhotoEditor(profilePhotoUrl())" class="p-2 bg-green-600 text-white rounded hover:bg-green-700">
      <!-- Pencil/Edit Icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M16.5 3.5l4 4-8 8H8v-4l8.5-8.5z" />
      </svg>
    </button>

    <!-- Bubble Chat Tooltip -->
    <div
      class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
      <div class="relative bg-green-600 text-white text-xs rounded-lg px-3 py-1 shadow-lg whitespace-nowrap">
        Edit Profile
        <!-- Tail using pseudo-circle -->
        <div class="absolute left-1/2 -bottom-1 w-2 h-2 bg-green-600 rotate-45 -translate-x-1/2"></div>
      </div>
    </div>
  </div>

  <!-- Download Icon -->
  <div class="relative group">
    <button onclick="downloadProfileDOCX()"
      class="p-2 bg-blue-600 text-white rounded hover:bg-blue-700">
      <!-- Download Icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
        viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v8m0 0l-4-4m4 4l4-4M12 4v8" />
      </svg>
    </button>


    <!-- Bubble Chat Tooltip -->
    <div
      class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
      <div class="relative bg-blue-600 text-white text-xs rounded-lg px-3 py-1 shadow-lg whitespace-nowrap">
        Download Profile
        <!-- Tail -->
        <div class="absolute left-1/2 -bottom-1 w-2 h-2 bg-blue-600 rotate-45 -translate-x-1/2"></div>
      </div>
    </div>
  </div>

</div>


<div id="profile-form">

  <!-- HEADER -->
  <div class="text-center mb-6 leading-tight">
    <img
      src="{{ asset('images/logo.png') }}"
      alt="Northeastern College Logo"
      class="mx-auto mb-2 h-24 w-auto object-contain"
    >

    <p class="mt-4 font-semibold uppercase">Human Resources Department</p>
    <p class="font-semibold uppercase">Employees Profile Form</p>
  </div>

  <!-- ROW STYLES -->
  <style>
    .row {
      padding: 4px 8px;
      border-bottom: 1px solid #000000;
      font-size: 0.875rem;
    }
    .label {
      font-weight: 500;
      display: inline;
    }
    .value {
      display: inline;
      margin-left: 4px;
    }
    .empty-data-flag {
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      color: #b91c1c;
      font-weight: 600;
    }
    .empty-data-flag::after {
      content: "!";
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.05rem;
      height: 1.05rem;
      border-radius: 999px;
      background: #ef4444;
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45);
      animation: emptyDataPulse 1.2s ease-in-out infinite;
    }
    @keyframes emptyDataPulse {
      0%,
      100% {
        transform: translateY(0) scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45);
      }
      50% {
        transform: translateY(-1px) scale(1.08);
        box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
      }
    }
  </style>

  <!-- TOP + EMPLOYMENT SECTION -->
  <div class="grid grid-cols-2 gap-5 items-start">

    <div class="space-y-3">
      <div class="border border-gray-500">
        <div class="row row-split">
          <span class="label split-label">ID Number:</span>
          <span class="value split-value" x-text="displayEmployeeId(selectedEmployee)"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Last Name:</span>
          <span class="value split-value" x-text="selectedEmployee?.last_name ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">First Name:</span>
          <span class="value split-value" x-text="selectedEmployee?.first_name ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Middle Name:</span>
          <span class="value split-value" x-text="selectedEmployee?.middle_name ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Account No.:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.account_number ?? '-'"></span>
        </div>
      </div>

      <div class="border border-gray-500">
        <div class="row row-split">
          <span class="label split-label">Sex:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.sex ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Civil Status:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.civil_status ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Contact No.:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.contact_number ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Date of Birth:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.formatted_birthday ?? '-'"></span>
        </div>
        <div class="row row-split row-merge-first-col">
          <span class="label split-label">Address:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.address ?? '-'"></span>
        </div>
      </div>

      <div class="border border-gray-500">
        <div class="row row-split">
          <span class="label split-label edu-label">Bachelor's Degree:</span>
          <span class="value split-value edu-value">
            <template x-if="hasDegreeRows('bachelor')">
              <span class="block space-y-1">
                <template x-for="(row, idx) in degreeRows('bachelor')" :key="`bachelor-${row?.id ?? idx}`">
                  <span class="block">
                    <span class="block edu-title">
                      <span x-text="formatGraduateDegreeTitle(row?.degree_name)"></span>
                      <span x-show="degreeLevelIsNew('bachelor')" class="ml-2 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700">New</span>
                    </span>
                    <span class="block text-[11px] text-gray-700 edu-meta" x-text="`${row?.school_name ?? 'School N/A'}, ${row?.year_finished ?? 'Year N/A'}`"></span>
                  </span>
                </template>
              </span>
            </template>
            <template x-if="!hasDegreeRows('bachelor')">
              <span class="block">
                <span class="block edu-title">
                  <span x-text="selectedEmployee?.education?.bachelor ?? '-'"></span>
                  <span x-show="degreeLevelIsNew('bachelor')" class="ml-2 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700">New</span>
                </span>
                <span
                  class="block text-[11px] text-gray-700 edu-meta"
                  x-text="`${selectedEmployee?.applicant?.bachelor_school_name ?? 'School N/A'}, ${selectedEmployee?.applicant?.bachelor_year_finished ?? 'Year N/A'}`"
                ></span>
              </span>
            </template>
          </span>
        </div>
        <div class="row row-split">
          <span class="label split-label edu-label">Master's Degree:</span>
          <span class="value split-value edu-value">
            <template x-if="hasDegreeRows('master')">
              <span class="block space-y-1">
                <template x-for="(row, idx) in degreeRows('master')" :key="`master-${row?.id ?? idx}`">
                  <span class="block">
                    <span class="block edu-title">
                      <span x-text="formatGraduateDegreeTitle(row?.degree_name)"></span>
                      <span x-show="degreeLevelIsNew('master')" class="ml-2 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700">New</span>
                    </span>
                    <span class="block text-[11px] text-gray-700 edu-meta" x-text="`${row?.school_name ?? 'School N/A'}, ${row?.year_finished ?? 'Year N/A'}`"></span>
                  </span>
                </template>
              </span>
            </template>
            <template x-if="!hasDegreeRows('master')">
              <span class="block">
                <span class="block edu-title">
                  <span x-text="formatGraduateDegreeTitle(selectedEmployee?.education?.master)"></span>
                  <span x-show="degreeLevelIsNew('master')" class="ml-2 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700">New</span>
                </span>
                <span
                  class="block text-[11px] text-gray-700 edu-meta"
                  x-text="`${selectedEmployee?.applicant?.master_school_name ?? 'School N/A'}, ${selectedEmployee?.applicant?.master_year_finished ?? 'Year N/A'}`"
                ></span>
              </span>
            </template>
          </span>
        </div>
        <div class="row row-split">
          <span class="label split-label edu-label">Doctorate Degree:</span>
          <span class="value split-value edu-value">
            <template x-if="hasDegreeRows('doctorate')">
              <span class="block space-y-1">
                <template x-for="(row, idx) in degreeRows('doctorate')" :key="`doctorate-${row?.id ?? idx}`">
                  <span class="block">
                    <span class="block edu-title">
                      <span x-text="formatGraduateDegreeTitle(row?.degree_name)"></span>
                      <span x-show="degreeLevelIsNew('doctorate')" class="ml-2 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700">New</span>
                    </span>
                    <span class="block text-[11px] text-gray-700 edu-meta" x-text="`${row?.school_name ?? 'School N/A'}, ${row?.year_finished ?? 'Year N/A'}`"></span>
                  </span>
                </template>
              </span>
            </template>
            <template x-if="!hasDegreeRows('doctorate')">
              <span class="block">
                <span class="block edu-title">
                  <span x-text="formatGraduateDegreeTitle(selectedEmployee?.education?.doctorate)"></span>
                  <span x-show="degreeLevelIsNew('doctorate')" class="ml-2 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-700">New</span>
                </span>
                <span
                  class="block text-[11px] text-gray-700 edu-meta"
                  x-text="`${selectedEmployee?.applicant?.doctoral_school_name ?? 'School N/A'}, ${selectedEmployee?.applicant?.doctoral_year_finished ?? 'Year N/A'}`"
                ></span>
              </span>
            </template>
          </span>
        </div>
      </div>

    </div>

    <div class="space-y-7">
      <div class="p-2 flex justify-center">
        <div class="employee-photo-box">
          <img
            id="biometric-photo-img"
            class="employee-photo-image cursor-zoom-in"
            :src="(() => {
              const documents = selectedEmployee?.applicant?.documents ?? [];
              const profilePhotoDoc = documents.find((doc) => (doc?.type ?? '').toUpperCase() === 'PROFILE_PHOTO' && doc?.filepath);
              const imageDoc = documents.find((doc) => {
                const mime = (doc?.mime_type ?? '').toLowerCase();
                const filename = (doc?.filename ?? '').toLowerCase();
                const isImageByMime = mime.startsWith('image/');
                const isImageByName = /\.(png|jpe?g|gif|webp)$/i.test(filename);
                return (isImageByMime || isImageByName) && doc?.filepath;
              });
              const photo = profilePhotoDoc || imageDoc;
              const placeholderSvg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect width='200' height='200' fill='#e5e7eb'/><circle cx='100' cy='74' r='40' fill='#9ca3af'/><path d='M16 200c8-46 42-78 84-78s76 32 84 78' fill='#9ca3af'/></svg>`;
              const placeholder = `data:image/svg+xml;utf8,${encodeURIComponent(placeholderSvg)}`;
              return photo ? `/storage/${photo.filepath}` : placeholder;
            })()"
            :data-default-src="`data:image/svg+xml;utf8,${encodeURIComponent(`<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect width='200' height='200' fill='#e5e7eb'/><circle cx='100' cy='74' r='40' fill='#9ca3af'/><path d='M16 200c8-46 42-78 84-78s76 32 84 78' fill='#9ca3af'/></svg>`)}`"
            alt="Employee Photo"
            @click.stop="openImagePreview($event.currentTarget.src)"
            onerror="this.onerror=null;const s=`<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect width='200' height='200' fill='#e5e7eb'/><circle cx='100' cy='74' r='40' fill='#9ca3af'/><path d='M16 200c8-46 42-78 84-78s76 32 84 78' fill='#9ca3af'/></svg>`;this.src='data:image/svg+xml;utf8,'+encodeURIComponent(s);"
          >
        </div>
      </div>

      <div class="border border-gray-500 -mt-2  ">
        <div class="row row-split">
          <span class="label split-label">Employment Date:</span>
          <span
            class="value split-value"
            x-text="(() => {
              const raw = selectedEmployee?.employee?.employement_date;
              if (!raw) return '-';
              const parsed = new Date(raw);
              return Number.isNaN(parsed.getTime())
                ? raw
                : parsed.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            })()"
          ></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Position:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.position ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Department:</span>
          <span class="value split-value" x-text="selectedEmployee?.employee?.department ?? '-'"></span>
        </div>
        <div class="row">
          <span class="label">Classification:</span>
          <span class="value">
            <label class="mr-2">
              <input type="checkbox" disabled
                :checked="isBiometricClassification('full-time')"> Full-time
            </label>
            <label class="mr-2">
              <input type="checkbox" disabled
                :checked="isBiometricClassification('part-time')"> Part-time
            </label>
            <label>
              <input type="checkbox" disabled
                :checked="isBiometricClassification('nt')"> NT
            </label>
          </span>
        </div>
      </div>

      <div class="border border-gray-500">
        <div class="row row-split">
          <span class="label split-label">License:</span>
          <span class="value split-value" x-text="selectedEmployee?.license?.license ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Registration No.:</span>
          <span class="value split-value" x-text="selectedEmployee?.license?.registration_number ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Registration Date:</span>
          <span class="value split-value" x-text="selectedEmployee?.license?.registration_date ?? '-'"></span>
        </div>
        <div class="row row-split">
          <span class="label split-label">Valid Until:</span>
          <span class="value split-value" x-text="selectedEmployee?.license?.valid_until ?? '-'"></span>
        </div>
      </div>

      <div class="border border-gray-500">
        <div class="row">
          <span class="label">SSS:</span>
          <span class="value" x-text="selectedEmployee?.government?.SSS ?? '-'"></span>
        </div>
        <div class="row">
          <span class="label">TIN:</span>
          <span class="value" x-text="selectedEmployee?.government?.TIN ?? '-'"></span>
        </div>
        <div class="row">
          <span class="label">PhilHealth:</span>
          <span class="value" x-text="selectedEmployee?.government?.PhilHealth ?? '-'"></span>
        </div>
        <div class="row">
          <span class="label">Pag-IBIG MID:</span>
          <span class="value" x-text="selectedEmployee?.government?.MID ?? '-'"></span>
        </div>
        <div class="row">
          <span class="label">Pag-IBIG RTN:</span>
          <span class="value" x-text="selectedEmployee?.government?.RTN ?? '-'"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- SALARY -->
  <div
    class="border border-gray-500 w-[49%]"
    :style="(() => {
      const totalDegreeRows = degreeRows('bachelor').length + degreeRows('master').length + degreeRows('doctorate').length;
      return totalDegreeRows > 3 ? 'margin-top: 0.75rem;' : 'margin-top: -102px;';
    })()"
  >
    <div class="row row-split">
      <span class="label split-label">Basic Salary:</span>
      <span class="value split-value" x-text="selectedEmployee?.salary?.salary ?? '-'"></span>
    </div>
    <div class="row row-split">
      <span class="label split-label">Rate per Hour:</span>
      <span class="value split-value" x-text="selectedEmployee?.salary?.rate_per_hour ?? '-'"></span>
    </div>
    <div class="row row-split">
      <span class="label split-label">COLA:</span>
      <span class="value split-value" x-text="selectedEmployee?.salary?.cola ?? '-'"></span>
    </div>
  </div>
  <p class="mt-8 text-[13px] text-black italic">
    Disclaimer: This form contains confidential employee information intended solely for authorized administrative and human resource purposes. Unauthorized copying, disclosure, or distribution of any information contained herein is strictly prohibited.
  </p>

    <!-- FOOTER -->
  <div class="mt-6 text-xl text-black">
    NC HR Form No. 16a – Employees Profile Rev. 01
  </div>

  <div class="border-t border-dashed border-black my-3"></div>

  <!-- EMPLOYEE DETAILS -->
  <div class="row font-semibold bg-transparent mt-2">Employee ID Information – Office of EDP / NCIS. Official employee identification record.</div>

  <div class="row">
    <span class="label">Full Name:</span>
    <span class="value"
      x-text="`${selectedEmployee?.first_name ?? ''} ${selectedEmployee?.middle_name ?? ''} ${selectedEmployee?.last_name ?? ''}`">
    </span>
  </div>

  <div class="row">
    <span class="label">ID Number:</span>
    <span class="value" x-text="displayEmployeeId(selectedEmployee)"></span>
  </div>

  <div class="row">
    <span class="label">Department:</span>
    <span class="value" x-text="selectedEmployee?.employee?.department ?? '-'"></span>
  </div>

  <div class="row">
    <span class="label">Person to Contact in Emergency:</span>
    <span class="value" x-text="selectedEmployee?.employee?.emergency_contact_name ?? '-'"></span>
  </div>

  <div class="row">
    <span class="label">Address:</span>
    <span class="value" x-text="selectedEmployee?.employee?.address ?? '-'"></span>
  </div>

  <div class="row">
    <span class="label">Cellphone Number:</span>
    <span class="value" x-text="selectedEmployee?.employee?.emergency_contact_number ?? '-'"></span>
  </div>



</div>

</div>

<!-- MODAL -->
<!-- FULL FORM MODAL -->
<div
  x-show="openEditProfile && modalTarget === 'biometric'"
  x-transition
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 backdrop-blur-[1px] p-4">

  <div
    @click.outside="openEditProfile = false; modalTarget = ''"
    class="w-full max-w-6xl max-h-[92vh] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
      <h2 class="text-lg font-semibold text-slate-800">Edit Employee Profile</h2>
      <p class="text-xs text-slate-500 mt-1">Update biometric details and save changes to the employee record.</p>
    </div>

    <form action="{{ route('admin.updateBio')}}" method="POST" enctype="multipart/form-data" class="max-h-[calc(92vh-72px)] overflow-y-auto px-6 py-6 space-y-6">
      @csrf
      <input type="hidden" name="user_id" :value="selectedEmployee?.id">
      @if (request()->filled('tab_session'))
        <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
      @endif

      <section class="edit-section">
        <h3 class="edit-section-title">Personal Information</h3>
        <div class="edit-grid-3">
          <div class="edit-field"><label class="edit-label">Last Name</label><input class="edit-input" name="last" x-model="selectedEmployee.last_name"></div>
          <div class="edit-field"><label class="edit-label">First Name</label><input class="edit-input" name="first" x-model="selectedEmployee.first_name"></div>
          <div class="edit-field"><label class="edit-label">Middle Name</label><input class="edit-input" name="middle" x-model="selectedEmployee.middle_name"></div>
          <div class="edit-field">
            <label class="edit-label">ID Number</label>
            <input
              class="edit-input"
              name="employee_id"
              :value="displayEmployeeId(selectedEmployee, '')"
              @input="selectedEmployee.employee.employee_id = $event.target.value"
            >
          </div>
          <div class="edit-field"><label class="edit-label">Account Number</label><input class="edit-input" name="account_number" x-model="selectedEmployee.employee.account_number"></div>
          <div class="edit-field"><label class="edit-label">Sex</label><select name="gender" class="edit-input" x-model="selectedEmployee.employee.sex"><option value="">Select sex</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
          <div class="edit-field"><label class="edit-label">Civil Status</label><input class="edit-input" name="civil_status" x-model="selectedEmployee.employee.civil_status"></div>
          <div class="edit-field"><label class="edit-label">Contact Number</label><input class="edit-input" name="contact_number" x-model="selectedEmployee.employee.contact_number"></div>
          <div class="edit-field"><label class="edit-label">Date of Birth</label><input type="date" name="birthday" class="edit-input" :value="selectedEmployee?.employee?.birthday ? selectedEmployee.employee.birthday.split('T')[0] : ''"></div>
          <div class="edit-field md:col-span-3"><label class="edit-label">Address</label><input class="edit-input" name="address" x-model="selectedEmployee.employee.address"></div>
        </div>
      </section>

      <section class="edit-section">
        <h3 class="edit-section-title">Employment Information</h3>
        <div class="edit-grid-2">
          <div class="edit-field">
            <label class="edit-label">Employment Date</label>
            <input
              type="date"
              name="employment_date"
              class="edit-input"
              :value="selectedEmployee?.employee?.employement_date
                ? selectedEmployee.employee.employement_date.split('T')[0]
                : (selectedEmployee?.applicant?.date_hired
                  ? selectedEmployee.applicant.date_hired.split('T')[0]
                  : '')"
            >
          </div>
          <div class="edit-field"><label class="edit-label">Position</label><input class="edit-input" name="position" x-model="selectedEmployee.employee.position"></div>
          <div class="edit-field"><label class="edit-label">Department</label><input class="edit-input" name="department" x-model="selectedEmployee.employee.department"></div>
          <div class="edit-field"><label class="edit-label">Classification</label><select name="classification" class="edit-input" x-model="selectedEmployee.employee.classification"><option value="">Select classification</option><option value="Full-Time">Full-time</option><option value="Part-Time">Part-time</option><option value="NT">NT</option></select></div>
        </div>
      </section>

      <section class="edit-section">
        <h3 class="edit-section-title">Government IDs</h3>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-3 mb-3">
          <p class="text-[11px] text-emerald-800 font-medium">
            Enter IDs in standard format. The form auto-formats while typing.
          </p>
        </div>
        <div class="edit-grid-3">
          <div class="edit-field gov-field">
            <label class="edit-label">SSS</label>
            <input
              name="SSS"
              class="edit-input gov-input"
              x-model="selectedEmployee.government.SSS"
              placeholder="34-1234567-8"
              maxlength="11"
              inputmode="numeric"
              pattern="^\d{2}-\d{7}-\d{1}$"
              title="Format: 34-1234567-8"
              oninput="this.value=this.value.replace(/\D/g,'').slice(0,10).replace(/(\d{2})(\d{0,7})(\d{0,1})/,function(_,a,b,c){return a+(b?'-'+b:'')+(c?'-'+c:'');});"
            >
          </div>
          <div class="edit-field gov-field">
            <label class="edit-label">TIN</label>
            <input
              name="TIN"
              class="edit-input gov-input"
              x-model="selectedEmployee.government.TIN"
              placeholder="123-456-789-000"
              maxlength="15"
              inputmode="numeric"
              pattern="^\d{3}-\d{3}-\d{3}-\d{3}$"
              title="Format: 123-456-789-000"
              oninput="this.value=this.value.replace(/\D/g,'').slice(0,12).replace(/(\d{3})(\d{0,3})(\d{0,3})(\d{0,3})/,function(_,a,b,c,d){return a+(b?'-'+b:'')+(c?'-'+c:'')+(d?'-'+d:'');});"
            >
          </div>
          <div class="edit-field gov-field">
            <label class="edit-label">PhilHealth</label>
            <input
              name="PhilHealth"
              class="edit-input gov-input"
              x-model="selectedEmployee.government.PhilHealth"
              placeholder="12-123456789-0"
              maxlength="14"
              inputmode="numeric"
              pattern="^\d{2}-\d{9}-\d{1}$"
              title="Format: 12-123456789-0"
              oninput="this.value=this.value.replace(/\D/g,'').slice(0,12).replace(/(\d{2})(\d{0,9})(\d{0,1})/,function(_,a,b,c){return a+(b?'-'+b:'')+(c?'-'+c:'');});"
            >
          </div>
          <div class="edit-field gov-field">
            <label class="edit-label">Pag-IBIG MID</label>
            <input
              name="MID"
              class="edit-input gov-input"
              x-model="selectedEmployee.government.MID"
              placeholder="1234-5678-9012"
              maxlength="14"
              inputmode="numeric"
              pattern="^\d{4}-\d{4}-\d{4}$"
              title="Format: 1234-5678-9012"
              oninput="this.value=this.value.replace(/\D/g,'').slice(0,12).replace(/(\d{4})(\d{0,4})(\d{0,4})/,function(_,a,b,c){return a+(b?'-'+b:'')+(c?'-'+c:'');});"
            >
          </div>
          <div class="edit-field gov-field">
            <label class="edit-label">Pag-IBIG RTN</label>
            <input
              name="RTN"
              class="edit-input gov-input"
              x-model="selectedEmployee.government.RTN"
              placeholder="1234-5678-9012"
              maxlength="14"
              inputmode="numeric"
              pattern="^\d{4}-\d{4}-\d{4}$"
              title="Format: 1234-5678-9012"
              oninput="this.value=this.value.replace(/\D/g,'').slice(0,12).replace(/(\d{4})(\d{0,4})(\d{0,4})/,function(_,a,b,c){return a+(b?'-'+b:'')+(c?'-'+c:'');});"
            >
          </div>
        </div>
      </section>

      <section class="edit-section">
        <h3 class="edit-section-title">License Details</h3>
        <div class="edit-grid-2">
          <div class="edit-field"><label class="edit-label">License</label><input name="license" class="edit-input" x-model="selectedEmployee.license.license"></div>
          <div class="edit-field"><label class="edit-label">Registration Number</label><input name="registration_number" class="edit-input" x-model="selectedEmployee.license.registration_number"></div>
          <div class="edit-field"><label class="edit-label">Registration Date</label><input name="registration_date" type="date" class="edit-input" :value="selectedEmployee?.license?.registration_date ? selectedEmployee.license.registration_date.split('T')[0] : ''"></div>
          <div class="edit-field"><label class="edit-label">Valid Until</label><input name="valid_until" type="date" class="edit-input" :value="selectedEmployee?.license?.valid_until ? selectedEmployee.license.valid_until.split('T')[0] : ''"></div>
        </div>
      </section>

      <section class="edit-section">
        <h3 class="edit-section-title">Education</h3>
        <div class="space-y-4">
          <div class="space-y-3">
            <h4 class="text-sm font-semibold text-slate-700">Basic Education</h4>
            <div class="space-y-2 rounded-lg border border-slate-200 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Elementary</p>
              <div class="edit-grid-2">
                <div class="edit-field"><label class="edit-label">School Name</label><input class="edit-input" name="elementary_school_name" x-model="selectedEmployee.education.elementary_school_name"></div>
                <div class="edit-field"><label class="edit-label">Year Graduated</label><input class="edit-input" name="elementary_year_finished" x-model="selectedEmployee.education.elementary_year_finished"></div>
              </div>
            </div>
            <div class="space-y-2 rounded-lg border border-slate-200 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Secondary</p>
              <div class="edit-grid-2">
                <div class="edit-field"><label class="edit-label">School Name</label><input class="edit-input" name="secondary_school_name" x-model="selectedEmployee.education.secondary_school_name"></div>
                <div class="edit-field"><label class="edit-label">Year Graduated</label><input class="edit-input" name="secondary_year_finished" x-model="selectedEmployee.education.secondary_year_finished"></div>
              </div>
            </div>
            <div class="space-y-2 rounded-lg border border-slate-200 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Vocational / Trade Course</p>
              <div class="edit-grid-2">
                <div class="edit-field"><label class="edit-label">School Name</label><input class="edit-input" name="vocational_trade_school_name" x-model="selectedEmployee.education.vocational_trade_school_name"></div>
                <div class="edit-field"><label class="edit-label">Year Graduated</label><input class="edit-input" name="vocational_trade_year_finished" x-model="selectedEmployee.education.vocational_trade_year_finished"></div>
              </div>
            </div>
          </div>

          <input type="hidden" name="bachelor" :value="degreeEditRows?.bachelor?.[0]?.degree_name ?? ''">
          <input type="hidden" name="bachelor_school_name" :value="degreeEditRows?.bachelor?.[0]?.school_name ?? ''">
          <input type="hidden" name="bachelor_year_finished" :value="degreeEditRows?.bachelor?.[0]?.year_finished ?? ''">
          <input type="hidden" name="master" :value="degreeEditRows?.master?.[0]?.degree_name ?? ''">
          <input type="hidden" name="master_school_name" :value="degreeEditRows?.master?.[0]?.school_name ?? ''">
          <input type="hidden" name="master_year_finished" :value="degreeEditRows?.master?.[0]?.year_finished ?? ''">
          <input type="hidden" name="doctorate" :value="degreeEditRows?.doctorate?.[0]?.degree_name ?? ''">
          <input type="hidden" name="doctoral_school_name" :value="degreeEditRows?.doctorate?.[0]?.school_name ?? ''">
          <input type="hidden" name="doctoral_year_finished" :value="degreeEditRows?.doctorate?.[0]?.year_finished ?? ''">

          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-semibold text-slate-700">Bachelor's Degree</h4>
              <button type="button" @click="addDegreeRow('bachelor')" class="rounded-md bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-200">+ Add Bachelor</button>
            </div>
            <template x-for="(row, idx) in degreeEditRows.bachelor" :key="`edit-bachelor-${idx}`">
              <div class="space-y-2 rounded-lg border border-slate-200 p-3">
                <div class="flex justify-between items-center">
                  <p class="text-xs text-slate-500" x-text="`Entry ${idx + 1}`"></p>
                  <button type="button" @click="removeDegreeRow('bachelor', idx)" class="text-xs text-rose-600 hover:underline" x-show="degreeEditRows.bachelor.length > 1">Remove</button>
                </div>
                <div class="edit-grid-3">
                  <div class="edit-field"><label class="edit-label">Degree</label><input class="edit-input" :name="`degree_inputs[bachelor][${idx}][degree_name]`" x-model="row.degree_name"></div>
                  <div class="edit-field"><label class="edit-label">School Name</label><input class="edit-input" :name="`degree_inputs[bachelor][${idx}][school_name]`" x-model="row.school_name"></div>
                  <div class="edit-field"><label class="edit-label">Year Finished</label><input class="edit-input" :name="`degree_inputs[bachelor][${idx}][year_finished]`" x-model="row.year_finished"></div>
                </div>
              </div>
            </template>
          </div>

          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-semibold text-slate-700">Master's Degree</h4>
              <button type="button" @click="addDegreeRow('master')" class="rounded-md bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200">+ Add Master</button>
            </div>
            <template x-for="(row, idx) in degreeEditRows.master" :key="`edit-master-${idx}`">
              <div class="space-y-2 rounded-lg border border-slate-200 p-3">
                <div class="flex justify-between items-center">
                  <p class="text-xs text-slate-500" x-text="`Entry ${idx + 1}`"></p>
                  <button type="button" @click="removeDegreeRow('master', idx)" class="text-xs text-rose-600 hover:underline" x-show="degreeEditRows.master.length > 1">Remove</button>
                </div>
                <div class="edit-grid-3">
                  <div class="edit-field"><label class="edit-label">Degree</label><input class="edit-input" :name="`degree_inputs[master][${idx}][degree_name]`" x-model="row.degree_name"></div>
                  <div class="edit-field"><label class="edit-label">School Name</label><input class="edit-input" :name="`degree_inputs[master][${idx}][school_name]`" x-model="row.school_name"></div>
                  <div class="edit-field"><label class="edit-label">Year Finished</label><input class="edit-input" :name="`degree_inputs[master][${idx}][year_finished]`" x-model="row.year_finished"></div>
                </div>
              </div>
            </template>
          </div>

          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-semibold text-slate-700">Doctorate Degree</h4>
              <button type="button" @click="addDegreeRow('doctorate')" class="rounded-md bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700 hover:bg-purple-200">+ Add Doctorate</button>
            </div>
            <template x-for="(row, idx) in degreeEditRows.doctorate" :key="`edit-doctorate-${idx}`">
              <div class="space-y-2 rounded-lg border border-slate-200 p-3">
                <div class="flex justify-between items-center">
                  <p class="text-xs text-slate-500" x-text="`Entry ${idx + 1}`"></p>
                  <button type="button" @click="removeDegreeRow('doctorate', idx)" class="text-xs text-rose-600 hover:underline" x-show="degreeEditRows.doctorate.length > 1">Remove</button>
                </div>
                <div class="edit-grid-3">
                  <div class="edit-field"><label class="edit-label">Degree</label><input class="edit-input" :name="`degree_inputs[doctorate][${idx}][degree_name]`" x-model="row.degree_name"></div>
                  <div class="edit-field"><label class="edit-label">School Name</label><input class="edit-input" :name="`degree_inputs[doctorate][${idx}][school_name]`" x-model="row.school_name"></div>
                  <div class="edit-field"><label class="edit-label">Year Finished</label><input class="edit-input" :name="`degree_inputs[doctorate][${idx}][year_finished]`" x-model="row.year_finished"></div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </section>

      <section class="edit-section">
        <h3 class="edit-section-title">Salary</h3>
        <div class="edit-grid-3">
          <div class="edit-field"><label class="edit-label">Basic Salary</label><input name="salary" class="edit-input" x-model="selectedEmployee.salary.salary"></div>
          <div class="edit-field"><label class="edit-label">Rate per Hour</label><input name="rate_per_hour" class="edit-input" x-model="selectedEmployee.salary.rate_per_hour"></div>
          <div class="edit-field"><label class="edit-label">COLA</label><input name="cola" class="edit-input" x-model="selectedEmployee.salary.cola"></div>
        </div>
      </section>

      <section class="edit-section">
        <h3 class="edit-section-title">Profile Picture</h3>
        <input type="hidden" id="biometric-remove-photo-flag" name="remove_profile_picture" value="0">
        <div class="edit-field">
          <label class="edit-label">Current Picture</label>
          <div class="relative inline-flex w-fit">
            <img
              id="biometric-edit-photo-preview"
              :src="profilePhotoUrl() || `data:image/svg+xml;utf8,${encodeURIComponent(`<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect width='200' height='200' fill='#e5e7eb'/><circle cx='100' cy='74' r='40' fill='#9ca3af'/><path d='M16 200c8-46 42-78 84-78s76 32 84 78' fill='#9ca3af'/></svg>`)}`"
              :data-default-src="`data:image/svg+xml;utf8,${encodeURIComponent(`<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect width='200' height='200' fill='#e5e7eb'/><circle cx='100' cy='74' r='40' fill='#9ca3af'/><path d='M16 200c8-46 42-78 84-78s76 32 84 78' fill='#9ca3af'/></svg>`)}`"
              class="h-28 w-28 rounded-md border border-slate-300 bg-slate-100 object-cover"
              alt="Profile picture preview"
              onerror="this.onerror=null;this.src=this.dataset.defaultSrc || '';"
            >
            <button
              type="button"
              class="absolute -right-2 -top-2 inline-flex h-6 w-6 items-center justify-center rounded-full border border-rose-200 bg-white text-sm font-bold leading-none text-rose-600 shadow-sm hover:bg-rose-50"
              title="Remove photo"
              @click="removeBiometricPhoto()"
            >x</button>
          </div>
        </div>
        <div class="edit-field">
          <label class="edit-label">Upload Picture</label>
          <input
            id="biometric-photo-input"
            type="file"
            name="profile_picture"
            accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
            class="edit-input pt-2"
            @change="onBiometricPhotoInputChange($event.target)"
          >
        </div>
      </section>

      <section class="edit-section">
        <h3 class="edit-section-title">Emergency Contact</h3>
        <div class="edit-grid-3">
          <div class="edit-field"><label class="edit-label">Name</label><input name="emergency_contact_name" class="edit-input" x-model="selectedEmployee.employee.emergency_contact_name"></div>
          <div class="edit-field"><label class="edit-label">Contact Number</label><input name="emergency_contact_number" class="edit-input" x-model="selectedEmployee.employee.emergency_contact_number"></div>
          <div class="edit-field"><label class="edit-label">Relationship</label><input name="emergency_contact_relationship" class="edit-input" x-model="selectedEmployee.employee.emergency_contact_relationship"></div>
        </div>
      </section>

      <div class="sticky bottom-0 z-10 -mx-6 border-t border-slate-200 bg-white/95 px-6 py-4 backdrop-blur">
        <div class="flex justify-end gap-3">
          <button type="button" @click="openEditProfile = false; modalTarget = ''" class="rounded-lg border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50">Cancel</button>
          <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 font-medium text-white hover:bg-emerald-700">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>


<style>
  .edit-section {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    background: #ffffff;
  }
  .edit-section-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
  }
  .edit-grid-2,
  .edit-grid-3 {
    display: grid;
    gap: 12px;
  }
  .edit-grid-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  .edit-grid-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  @media (min-width: 768px) {
    .edit-grid-2 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .edit-grid-3 {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }
  .edit-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .edit-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
  }
  .edit-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #f8fafc;
    padding: 8px 10px;
    color: #0f172a;
    font-size: 13px;
    line-height: 1.35;
  }
  .edit-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    background: #ffffff;
  }
  .gov-field .edit-label {
    letter-spacing: 0.02em;
  }
  .gov-input {
    font-family: "Consolas", "Courier New", monospace;
    letter-spacing: 0.04em;
    background: #ffffff;
    border: 1px solid #b6c7dc;
  }
  .gov-input::placeholder {
    color: #94a3b8;
    letter-spacing: 0.03em;
  }

  .row {
    border-bottom: 1px solid #000000;
    padding: 8px 8px;
    min-height: 36px;
  }
  .row:last-child {
    border-bottom: none;
  }

  .row.row-split {
    display: grid;
    grid-template-columns: 39% 61%;
    padding: 0;
    height: auto;
  }

  .row.row-split .split-label,
  .row.row-split .split-value {
    display: block;
    padding: 6px 8px;
    margin-left: 0;
  }

  .row.row-split .split-label {
    border-right: 1px solid #000000;
    font-weight: 500;
  }

  .row.row-split .split-label.edu-label {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
  }

  .employee-photo-box {
    width: 192px;
    height: 192px;
    border: 1px solid #000000;
    background: #ffffff;
    overflow: hidden;
  }

  .employee-photo-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }

  .edu-value {
    padding: 0 !important;
  }

  .edu-value .edu-title,
  .edu-value .edu-meta {
    display: block;
    padding: 6px 8px;
  }

  .edu-value .edu-meta {
    border-top: 1px solid #000000;
  }

  .row.row-split.row-merge-first-col {
    grid-template-rows: auto auto;
  }

  .row.row-split.row-merge-first-col .split-label {
    grid-column: 1;
    grid-row: 1 / span 2;
    display: flex;
    align-items: center;
  }

  .row.row-split.row-merge-first-col .split-value {
    grid-column: 2;
    grid-row: 1 / span 2;
    white-space: normal;
    overflow-wrap: anywhere;
  }

  /* PRINT ONLY THE FORM */
  @media print {
    body * {
      visibility: hidden;
    }

    #profile-form,
    #profile-form * {
      visibility: visible;
    }

    #profile-form {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
    }

      .row {
        @apply flex justify-between px-2 py-1 border-b border-gray-300 text-sm;
      }
      .label {
        @apply font-medium;
      }
      .value {
        @apply text-right;
      }
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/docx@8.5.0/build/index.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>

<script>
const profileLogoUrl = @json(asset('images/logo.png'));
const profileFormDesignUrl = @json(asset('images/logo1.png'));

function biometricPlaceholderDataUri() {
  const placeholderSvg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect width='200' height='200' fill='#e5e7eb'/><circle cx='100' cy='74' r='40' fill='#9ca3af'/><path d='M16 200c8-46 42-78 84-78s76 32 84 78' fill='#9ca3af'/></svg>";
  return `data:image/svg+xml;utf8,${encodeURIComponent(placeholderSvg)}`;
}

function setBiometricPhotoPreview(src) {
  const nextSrc = (src || '').trim() || biometricPlaceholderDataUri();
  const profilePreview = document.getElementById('biometric-photo-img');
  const editPreview = document.getElementById('biometric-edit-photo-preview');
  if (profilePreview) profilePreview.src = nextSrc;
  if (editPreview) editPreview.src = nextSrc;
}

function onBiometricPhotoInputChange(inputEl) {
  const file = inputEl?.files?.[0];
  if (!file) return;
  const objectUrl = URL.createObjectURL(file);
  setBiometricPhotoPreview(objectUrl);
  const removeFlag = document.getElementById('biometric-remove-photo-flag');
  if (removeFlag) removeFlag.value = '0';
}

function removeBiometricPhoto() {
  setBiometricPhotoPreview(biometricPlaceholderDataUri());
  const fileInput = document.getElementById('biometric-photo-input');
  if (fileInput) fileInput.value = '';
  const removeFlag = document.getElementById('biometric-remove-photo-flag');
  if (removeFlag) removeFlag.value = '1';
}

function resetBiometricPhotoEditor(src) {
  const removeFlag = document.getElementById('biometric-remove-photo-flag');
  if (removeFlag) removeFlag.value = '0';
  const fileInput = document.getElementById('biometric-photo-input');
  if (fileInput) fileInput.value = '';
  setBiometricPhotoPreview((src || '').trim());
}

async function downloadProfileDOCX() {
  const docxLib = window.docx;
  if (!docxLib || typeof window.saveAs !== 'function') {
    alert('Word export library failed to load. Please refresh and try again.');
    return;
  }

  const {
    Document,
    Packer,
    Paragraph,
    Table,
    TableRow,
    TableCell,
    WidthType,
    TextRun,
    AlignmentType,
    BorderStyle,
    VerticalAlign,
    ImageRun,
    HeightRule,
    HorizontalPositionRelativeFrom,
    VerticalPositionRelativeFrom,
    TextWrappingType,
  } = docxLib;

  const normalize = (text) => (text || '')
    .toLowerCase()
    .replace(/[’']/g, '')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();

  const rows = Array.from(document.querySelectorAll('#profile-form .row'));
  const rowMap = {};
  rows.forEach((row) => {
    const labelEl = row.querySelector('.label');
    const valueEl = row.querySelector('.value');
    if (!labelEl || !valueEl) return;
    const key = normalize((labelEl.textContent || '').replace(/:\s*$/, ''));
    const value = ((valueEl.innerText || valueEl.textContent || '').replace(/\s+/g, ' ').trim()) || '-';
    rowMap[key] = { value, row };
  });

  const getValue = (label) => rowMap[normalize(label)]?.value || '-';
  const getRow = (label) => rowMap[normalize(label)]?.row || null;

  const classificationRow = getRow('Classification');
  const canonicalClassificationValue = (value) => {
    const normalized = normalize(value);
    if (!normalized) return '';
    if (normalized.includes('full')) return 'full time';
    if (normalized.includes('part')) return 'part time';
    if (normalized.includes('probationary') || normalized.includes('permanent') || normalized.includes('regular')) return 'full time';
    if (normalized === 'nt' || normalized === 'non teaching') return 'nt';
    if (normalized.includes('non teaching') || normalized.includes('non-teaching')) return 'nt';
    return '';
  };
  const classificationFromAlpine = (() => {
    const alpineRoot = document.querySelector('main[x-data]');
    const alpineData = alpineRoot?._x_dataStack?.[0] || alpineRoot?.__x?.$data || null;
    const selectedEmployee = alpineData?.selectedEmployee ?? null;
    const candidates = [
      selectedEmployee?.employee?.classification,
      selectedEmployee?.applicant?.position?.employment,
      selectedEmployee?.employee?.job_type,
      selectedEmployee?.applicant?.position?.job_type,
    ];

    for (const candidate of candidates) {
      const canonical = canonicalClassificationValue(candidate);
      if (canonical) return canonical;
    }
    return '';
  })();

  let classificationValue = '☐ Full-time   ☐ Part-time   ☐ NT';

  if (classificationFromAlpine) {
    classificationValue = `${classificationFromAlpine === 'full time' ? '☑' : '☐'} Full-time   ${classificationFromAlpine === 'part time' ? '☑' : '☐'} Part-time   ${classificationFromAlpine === 'nt' ? '☑' : '☐'} NT`;
  } else {
    const rawClassificationText = getValue('Classification');
    const canonicalFromText = canonicalClassificationValue(rawClassificationText);
    if (canonicalFromText) {
      classificationValue = `${canonicalFromText === 'full time' ? '☑' : '☐'} Full-time   ${canonicalFromText === 'part time' ? '☑' : '☐'} Part-time   ${canonicalFromText === 'nt' ? '☑' : '☐'} NT`;
    }
  }

  if (classificationRow) {
    const checkboxes = Array.from(classificationRow.querySelectorAll('input[type="checkbox"]'));
    if (checkboxes.length >= 3 && !classificationFromAlpine) {
      classificationValue = `${checkboxes[0].checked ? '☑' : '☐'} Full-time   ${checkboxes[1].checked ? '☑' : '☐'} Part-time   ${checkboxes[2].checked ? '☑' : '☐'} NT`;
    }
  }

  const border = {
    top: { style: BorderStyle.SINGLE, size: 4, color: '000000' },
    bottom: { style: BorderStyle.SINGLE, size: 4, color: '000000' },
    left: { style: BorderStyle.SINGLE, size: 4, color: '000000' },
    right: { style: BorderStyle.SINGLE, size: 4, color: '000000' },
  };

  const lineTable = (entries) => new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: entries.map(({ label, value }) => new TableRow({
      height: { value: 250, rule: HeightRule.ATLEAST },
      children: [
        new TableCell({
          width: { size: 34, type: WidthType.PERCENTAGE },
          borders: border,
          margins: { top: 60, bottom: 60, left: 80, right: 100 },
          children: [
            new Paragraph({
              children: [
                new TextRun({ text: `${label}:`, bold: true, font: 'Aptos Display' }),
              ],
            }),
          ],
        }),
        new TableCell({
          width: { size: 66, type: WidthType.PERCENTAGE },
          borders: border,
          margins: { top: 60, bottom: 60, left: 80, right: 100 },
          children: [
            new Paragraph({
              children: [
                new TextRun({ text: value || '-', font: 'Aptos Display' }),
              ],
            }),
          ],
        }),
      ],
    })),
  });

  const toTable = (entriesOrTable) => Array.isArray(entriesOrTable) ? lineTable(entriesOrTable) : entriesOrTable;
  const twoColumnSection = (leftEntries, rightEntries) => new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [
      new TableRow({
        children: [
          new TableCell({
            width: { size: 49, type: WidthType.PERCENTAGE },
            borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
            children: [toTable(leftEntries)],
            verticalAlign: VerticalAlign.TOP,
          }),
          new TableCell({
            width: { size: 2, type: WidthType.PERCENTAGE },
            borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
            children: [new Paragraph({ spacing: { before: 0, after: 0 } })],
          }),
          new TableCell({
            width: { size: 49, type: WidthType.PERCENTAGE },
            borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
            children: [toTable(rightEntries)],
            verticalAlign: VerticalAlign.TOP,
          }),
        ],
      }),
    ],
  });

  let logoParagraph = new Paragraph('');
  try {
    const logoResponse = await fetch(profileLogoUrl);
    const logoBuffer = await logoResponse.arrayBuffer();
    logoParagraph = new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 0, after: 60, line: 220 },
      children: [
        new ImageRun({
          data: new Uint8Array(logoBuffer),
          transformation: { width: 360, height: 70 },
        }),
      ],
    });
  } catch (e) {
    logoParagraph = new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 0, after: 60, line: 220 },
      children: [new TextRun({ text: 'NORTHEASTERN COLLEGE', bold: true, font: 'Aptos Display' })],
    });
  }

  let watermarkParagraph = new Paragraph('');
  try {
    const bgResponse = await fetch(profileFormDesignUrl);
    const bgBlob = await bgResponse.blob();

    let bgBuffer;
    try {
      const bgObjectUrl = URL.createObjectURL(bgBlob);
      const img = await new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = bgObjectUrl;
      });

      const canvas = document.createElement('canvas');
      canvas.width = 816;
      canvas.height = 1320;
      const ctx = canvas.getContext('2d');
      if (!ctx) throw new Error('Canvas context unavailable');

      // Draw full-page background then soften it for text readability.
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      ctx.fillStyle = 'rgba(255,255,255,0.88)';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      const softenedDataUrl = canvas.toDataURL('image/png');
      bgBuffer = await fetch(softenedDataUrl).then((res) => res.arrayBuffer());
      URL.revokeObjectURL(bgObjectUrl);
    } catch (e) {
      bgBuffer = await bgBlob.arrayBuffer();
    }

    watermarkParagraph = new Paragraph({
      children: [
        new ImageRun({
          data: new Uint8Array(bgBuffer),
          transformation: { width: 816, height: 1250 },
          floating: {
            behindDocument: true,
            horizontalPosition: {
              relative: HorizontalPositionRelativeFrom.PAGE,
              offset: 0,
            },
            verticalPosition: {
              relative: VerticalPositionRelativeFrom.PAGE,
              offset: 0,
            },
            wrap: {
              type: TextWrappingType.NONE,
            },
          },
        }),
      ],
    });
  } catch (e) {
    watermarkParagraph = new Paragraph('');
  }

  let employeePhotoBuffer = null;
  let employeePhotoFallbackBuffer = null;

  try {
    const canvas = document.createElement('canvas');
    canvas.width = 193;
    canvas.height = 193;
    const ctx = canvas.getContext('2d');
    if (ctx) {
      ctx.fillStyle = '#FFFFFF';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.strokeStyle = '#000000';
      ctx.lineWidth = 2;
      ctx.strokeRect(1, 1, canvas.width - 2, canvas.height - 2);
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = '#000000';
      ctx.font = 'bold 18px Arial';
      ctx.fillText('Insert here', canvas.width / 2, 80);
      ctx.fillText('your 2x2', canvas.width / 2, 106);
      const fallbackDataUrl = canvas.toDataURL('image/png');
      employeePhotoFallbackBuffer = await fetch(fallbackDataUrl).then((res) => res.arrayBuffer());
    }
  } catch (e) {
    employeePhotoFallbackBuffer = null;
  }

  try {
    const photoEl = document.getElementById('biometric-photo-img');
    const photoSrc = (photoEl?.getAttribute('src') || photoEl?.src || '').trim();
    if (photoSrc && !photoSrc.startsWith('data:image/svg+xml')) {
      const photoResponse = await fetch(photoSrc);
      if (photoResponse.ok) {
        employeePhotoBuffer = await photoResponse.arrayBuffer();
      }
    }
  } catch (e) {
    employeePhotoBuffer = null;
  }

  const photoTable = new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [
      new TableRow({
        children: [
          new TableCell({
            borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
            margins: { top: 0, bottom: 0, left: 40, right: 40 },
            children: [
              new Table({
                width: { size: 42, type: WidthType.PERCENTAGE },
                alignment: AlignmentType.CENTER,
                rows: [
                  new TableRow({
                    height: { value: 3000, rule: HeightRule.EXACT },
                    children: [
                      new TableCell({
                        borders: border,
                        margins: { top: 0, bottom: 0, left: 20, right: 20 },
                        verticalAlign: VerticalAlign.CENTER,
                        children: [
                          new Paragraph({
                            alignment: AlignmentType.CENTER,
                            spacing: { before: 0, after: 0, line: 200 },
                              children: (employeePhotoBuffer || employeePhotoFallbackBuffer)
                              ? [
                                  new ImageRun({
                                    data: new Uint8Array(employeePhotoBuffer || employeePhotoFallbackBuffer),
                                    transformation: { width: 193, height: 193 },
                                  }),
                                ]
                              : [
                                  new TextRun({ text: 'Insert here your 2x2', font: 'Aptos Display', bold: true, color: '000000' }),
                                ],
                          }),
                        ],
                      }),
                    ],
                  }),
                ],
              }),
            ],
          }),
        ],
      }),
    ],
  });

  const idRows = [
    { label: 'ID Number', value: getValue('ID Number') },
    { label: 'Last Name', value: getValue('Last Name') },
    { label: 'First Name', value: getValue('First Name') },
    { label: 'Middle Name', value: getValue('Middle Name') },
    { label: 'Account No.', value: getValue('Account No.') },
  ];

  const personalRows = [
    { label: 'Sex', value: getValue('Sex') },
    { label: 'Civil Status', value: getValue('Civil Status') },
    { label: 'Contact No.', value: getValue('Contact No.') },
    { label: 'Date of Birth', value: getValue('Date of Birth') },
    { label: 'Address', value: getValue('Address') },
  ];

  const employmentRows = [
    { label: 'Employment Date', value: getValue('Employment Date') },
    { label: 'Position', value: getValue('Position') },
    { label: 'Department', value: getValue('Department') },
    { label: 'Classification', value: classificationValue },
  ];

  const salaryRows = [
    { label: 'Basic Salary', value: getValue('Basic Salary') },
    { label: 'Rate per Hour', value: getValue('Rate per Hour') },
    { label: 'COLA', value: getValue('COLA') },
  ];

  const getEducationEntries = (label) => {
    const row = getRow(label);
    const titleNodes = row ? Array.from(row.querySelectorAll('.edu-title')) : [];
    const metaNodes = row ? Array.from(row.querySelectorAll('.edu-meta')) : [];

    if (!titleNodes.length) {
      return [{
        title: getValue(label) || '-',
        meta: 'School N/A, Year N/A',
      }];
    }

    return titleNodes.map((titleNode, idx) => ({
      title: titleNode?.textContent?.trim() || '-',
      meta: metaNodes[idx]?.textContent?.trim() || 'School N/A, Year N/A',
    }));
  };

  const degreeEntries = [
    { label: "Bachelor's Degree", items: getEducationEntries("Bachelor's Degree") },
    { label: "Master's Degree", items: getEducationEntries("Master's Degree") },
    { label: 'Doctorate Degree', items: getEducationEntries('Doctorate Degree') },
  ];

  const totalDegreeItems = degreeEntries.reduce((sum, entry) => sum + entry.items.length, 0);
  const hasExpandedDegrees = totalDegreeItems > 3;
  const extraDegreeItems = Math.max(totalDegreeItems - 3, 0);
  const exportTighten = Math.min(extraDegreeItems * 220, 1200);
  const postMainSpacer = Math.max(140, 1220 - exportTighten);
  const disclaimerToFooterGap = Math.max(80, 420 - Math.floor(exportTighten * 0.25));
  const formNoTopGap = Math.max(120, 500 - Math.floor(exportTighten * 0.2));
  const dashedLineTopGap = Math.max(200, 1600 - exportTighten);

  const degreeTable = new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: degreeEntries.flatMap(({ label, items }) => {
      const safeItems = items.length ? items : [{ title: '-', meta: 'School N/A, Year N/A' }];
      return safeItems.flatMap(({ title, meta }, idx) => ([
        new TableRow({
          height: { value: 250, rule: HeightRule.ATLEAST },
          children: [
            ...(idx === 0
              ? [new TableCell({
                  width: { size: 34, type: WidthType.PERCENTAGE },
                  rowSpan: safeItems.length * 2,
                  borders: border,
                  margins: { top: 60, bottom: 60, left: 100, right: 100 },
                  children: [
                    new Paragraph({
                      alignment: AlignmentType.CENTER,
                      children: [new TextRun({ text: `${label}:`, bold: true, font: 'Aptos Display' })],
                    }),
                  ],
                  verticalAlign: VerticalAlign.CENTER,
                })]
              : []),
            new TableCell({
              width: { size: 66, type: WidthType.PERCENTAGE },
              borders: border,
              margins: { top: 60, bottom: 60, left: 80, right: 100 },
              children: [
                new Paragraph({
                  children: [new TextRun({ text: title || '-', font: 'Aptos Display' })],
                }),
              ],
            }),
          ],
        }),
        new TableRow({
          height: { value: 220, rule: HeightRule.ATLEAST },
          children: [
            new TableCell({
              width: { size: 66, type: WidthType.PERCENTAGE },
              borders: border,
              margins: { top: 40, bottom: 40, left: 80, right: 100 },
              children: [
                new Paragraph({
                  children: [new TextRun({ text: meta, font: 'Aptos Display', size: 18, color: '000000' })],
                }),
              ],
            }),
          ],
        }),
      ]));
    }),
  });

  const governmentRows = [
    { label: 'SSS', value: getValue('SSS') },
    { label: 'TIN', value: getValue('TIN') },
    { label: 'PhilHealth', value: getValue('PhilHealth') },
    { label: 'Pag-IBIG MID', value: getValue('Pag-IBIG MID') },
    { label: 'Pag-IBIG RTN', value: getValue('Pag-IBIG RTN') },
  ];

  const licenseRows = [
    { label: 'License', value: getValue('License') },
    { label: 'Registration No.', value: getValue('Registration No.') },
    { label: 'Registration Date', value: getValue('Registration Date') },
    { label: 'Valid Until', value: getValue('Valid Until') },
  ];

  const stackTable = (blocks, gapAfterByIndex = []) => new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: blocks.flatMap((block, idx) => {
      const list = [
        new TableRow({
          children: [
            new TableCell({
              borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
              children: [toTable(block)],
            }),
          ],
        }),
      ];
      if (idx < blocks.length - 1) {
        const gapAfter = gapAfterByIndex[idx] ?? 10;
        list.push(
          new TableRow({
            children: [
              new TableCell({
                borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
                children: [new Paragraph({ spacing: { before: 0, after: gapAfter } })],
              }),
            ],
          })
        );
      }
      return list;
    }),
  });

  const withTopGap = (block, gap = 10) => new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [
      new TableRow({
        children: [
          new TableCell({
            borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
            children: [new Paragraph({ spacing: { before: 0, after: gap } })],
          }),
        ],
      }),
      new TableRow({
        children: [
          new TableCell({
            borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
            children: [toTable(block)],
          }),
        ],
      }),
    ],
  });

  const leftTopRows = stackTable(
    [idRows, personalRows, degreeTable, salaryRows],
    [0, 0, hasExpandedDegrees ? 10 : 1]
  );
  const rightTopRows = stackTable([photoTable, employmentRows, licenseRows, governmentRows], [0, 2, 0]);

  const employeeDetails = [
    { label: 'Full Name', value: getValue('Full Name') },
    { label: 'ID Number', value: getValue('ID Number') },
    { label: 'Department', value: getValue('Department') },
    { label: 'Person to Contact in Emergency', value: getValue('Person to Contact in Emergency') },
    { label: 'Address', value: getValue('Address') },
    { label: 'Cellphone Number', value: getValue('Cellphone Number') },
  ];

  const doc = new Document({
    sections: [
      {
        properties: {
          page: {
            size: {
              width: 12240,
              height: 18720,
            },
            margin: {
              top: 360,
              right: 720,
              bottom: 0,
              left: 720,
              footer: 0,
            },
          },
        },
        children: [
          watermarkParagraph,
          logoParagraph,
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 0, after: 20, line: 200 },
            children: [new TextRun({ text: 'Human  Resources Department', font: 'Aptos Display', size: 27 })],
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 0, after: 60, line: 200 },
            children: [new TextRun({ text: 'EMPLOYEES PROFILE FORM', bold: true, font: 'Aptos Display', size: 27 })],
          }),
          new Paragraph({ spacing: { before: 0, after: 35 } }),
          twoColumnSection(leftTopRows, rightTopRows),
          new Paragraph({
            spacing: { before: 0, after: postMainSpacer },
            children: [new TextRun({ text: '', font: 'Aptos Display' })],
          }),
          new Paragraph({
            spacing: { before: 0, after: 20, line: 180 },
            children: [
              new TextRun({
                text: 'Disclaimer: This form contains confidential employee information intended solely for authorized administrative and human resource purposes. Unauthorized copying, disclosure, or distribution of any information contained herein is strictly prohibited.',
                italics: true,
                size: 18,
                color: '000000',
                font: 'Aptos Display',
              }),
            ],
          }),
          new Paragraph({
            spacing: { before: 0, after: disclaimerToFooterGap },
            children: [new TextRun({ text: '', font: 'Aptos Display' })],
          }),
          new Paragraph({
            spacing: { before: formNoTopGap, after: 220 },
            children: [new TextRun({ text: 'NC HR Form No. 16a - Employees Profile Rev. 01', size: 22, color: '000000', font: 'Aptos Display' })],
          }),
          new Paragraph({
            spacing: { before: dashedLineTopGap, after: 0 },
            border: { bottom: { style: BorderStyle.DASHED, size: 4, color: '000000' } },
          }),
          new Paragraph({
            spacing: { before: 40, after: 0, line: 200 },
            children: [new TextRun({ text: 'Employee ID Information - Office of EDP / NCIS. Official employee identification record.', bold: true, font: 'Aptos Display' })],
          }),
          lineTable(employeeDetails),
          new Paragraph(''),
        ],
      },
    ],
  });

  Packer.toBlob(doc)
    .then((blob) => {
      const filename = `Employee_Profile_${Date.now()}.docx`;
      window.saveAs(blob, filename);
    })
    .catch(() => {
      alert('Failed to export DOCX. Please try again.');
    });
}

function applyEmptyValueIndicators() {
  const profileForm = document.getElementById('profile-form');
  if (!profileForm) return;

  const isEmptyDisplayValue = (text) => {
    const normalized = (text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    if (!normalized) return true;

    if (['-', 'n/a', 'na', 'unspecified', 'not set'].includes(normalized)) {
      return true;
    }

    return normalized.includes('school n/a')
      || normalized.includes('year n/a')
      || normalized === 'n/a, n/a';
  };

  const targets = profileForm.querySelectorAll('.value, .split-value, .edu-title, .edu-meta');
  targets.forEach((node) => {
    if (node.closest('#action-buttons')) return;
    const text = node.textContent || '';
    node.classList.toggle('empty-data-flag', isEmptyDisplayValue(text));
  });
}

document.addEventListener('DOMContentLoaded', () => {
  applyEmptyValueIndicators();

  const profileForm = document.getElementById('profile-form');
  if (!profileForm || typeof MutationObserver === 'undefined') return;

  const observer = new MutationObserver(() => {
    applyEmptyValueIndicators();
  });

  observer.observe(profileForm, {
    subtree: true,
    childList: true,
    characterData: true,
  });
});
</script>

    </div>
    </div>
</div>



