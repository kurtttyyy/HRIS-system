<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Create Position</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    :root {
      --page-bg: #edf4f1;
      --panel-bg: rgba(255, 255, 255, 0.92);
      --panel-border: rgba(148, 163, 184, 0.24);
      --text-strong: #0f172a;
      --text-soft: #475569;
      --brand: #0f766e;
      --brand-deep: #115e59;
      --accent: #f59e0b;
    }

    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      transition: margin-left 0.3s ease;
      background:
        radial-gradient(circle at top left, rgba(20, 184, 166, 0.14), transparent 26%),
        radial-gradient(circle at top right, rgba(251, 191, 36, 0.14), transparent 24%),
        linear-gradient(180deg, #f6fbf9 0%, var(--page-bg) 100%);
    }

    main {
      transition: margin-left 0.3s ease;
    }

    aside ~ main {
      margin-left: 16rem;
    }

    .position-shell {
      max-width: 84rem;
      margin: 0 auto;
    }

    .position-hero {
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(15, 118, 110, 0.18);
      border-radius: 1.9rem;
      background: linear-gradient(135deg, #020617 0%, #020617 42%, #111827 68%, #064e3b 100%);
      box-shadow: 0 28px 70px rgba(15, 23, 42, 0.14);
    }

    .position-hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 28%),
        radial-gradient(circle at bottom left, rgba(20, 184, 166, 0.22), transparent 24%);
      pointer-events: none;
    }

    .position-panel {
      border: 1px solid var(--panel-border);
      border-radius: 1.45rem;
      background: var(--panel-bg);
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
      backdrop-filter: blur(12px);
    }

    .section-kicker {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      border-radius: 999px;
      padding: 0.45rem 0.8rem;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.22em;
      text-transform: uppercase;
    }

    .section-kicker--teal {
      background: rgba(15, 118, 110, 0.1);
      color: var(--brand);
    }

    .section-kicker--gold {
      background: rgba(245, 158, 11, 0.12);
      color: #b45309;
    }

    .section-title {
      margin-top: 1rem;
      font-size: 1.55rem;
      font-weight: 900;
      letter-spacing: -0.03em;
      color: var(--text-strong);
    }

    .section-copy {
      margin-top: 0.45rem;
      font-size: 0.95rem;
      line-height: 1.7;
      color: var(--text-soft);
    }

    .field-label {
      display: block;
      margin-bottom: 0.45rem;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #64748b;
    }

    .helper-text {
      margin-top: 0.7rem;
      font-size: 0.78rem;
      line-height: 1.55;
      color: #64748b;
    }

    .cta-row {
      position: sticky;
      bottom: 1.2rem;
      z-index: 10;
    }

    .input {
      width: 100%;
      border: 1px solid rgba(148, 163, 184, 0.45);
      border-radius: 1rem;
      padding: 0.9rem 1rem;
      background: rgba(255, 255, 255, 0.96);
      color: #0f172a;
      transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .input:focus {
      outline: none;
      border-color: rgba(15, 118, 110, 0.65);
      box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
      transform: translateY(-1px);
    }

    .input::placeholder {
      color: #94a3b8;
    }

    textarea.input {
      min-height: 10rem;
      line-height: 1.7;
    }
  </style>
</head>
<body>

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    <div class="p-4 pt-20 md:p-8">
      <div class="position-shell space-y-6">
        @if (session('position_created') || request()->boolean('created'))
          <div
            id="position-success-modal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="position-success-title"
          >
            <div class="w-full max-w-md rounded-3xl border border-emerald-100 bg-white p-6 shadow-2xl">
              <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <i class="fa-solid fa-check text-xl"></i>
              </div>
              <div class="mt-5 text-center">
                <h2 id="position-success-title" class="text-2xl font-black text-slate-900">Successfully created</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Do you want to add more positions?</p>
              </div>
              <div class="mt-6 grid grid-cols-2 gap-3">
                <button
                  type="button"
                  id="position-add-more-yes"
                  class="inline-flex items-center justify-center rounded-2xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-800"
                >
                  Yes
                </button>
                <a
                  href="{{ route('admin.adminPosition') }}"
                  class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                >
                  No
                </a>
              </div>
            </div>
          </div>
        @endif

        <div
          id="position-validation-modal"
          class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          aria-labelledby="position-validation-title"
        >
          <div class="w-full max-w-lg overflow-hidden rounded-3xl border border-amber-100 bg-white shadow-2xl">
            <div class="bg-gradient-to-r from-amber-50 via-white to-emerald-50 px-6 py-5">
              <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                  <i class="fa-solid fa-circle-exclamation text-xl"></i>
                </div>
                <div>
                  <p class="text-[11px] font-black uppercase tracking-[0.22em] text-amber-700">Incomplete Form</p>
                  <h2 id="position-validation-title" class="mt-1 text-2xl font-black tracking-tight text-slate-900">Complete the missing details</h2>
                  <p class="mt-2 text-sm leading-6 text-slate-500">
                    The position was not saved. Fill in the required fields below, then create the position again.
                  </p>
                </div>
              </div>
            </div>

            <div class="px-6 py-5">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Missing fields</p>
                <ul id="position-validation-list" class="mt-3 grid gap-2 text-sm font-semibold text-slate-700 sm:grid-cols-2"></ul>
              </div>

              <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  id="position-validation-close"
                  class="inline-flex items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#0f766e,#0f172a)] px-5 py-3 text-sm font-bold text-white shadow-[0_14px_28px_rgba(15,118,110,0.22)] transition hover:-translate-y-0.5"
                >
                  <i class="fa-solid fa-pen-to-square text-xs"></i>
                  Continue Editing
                </button>
              </div>
            </div>
          </div>
        </div>

        <section class="position-hero px-6 py-7 text-white md:px-8 md:py-8">
          <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
              <div class="section-kicker border border-white/10 bg-white/10 text-emerald-50">
                <span class="h-2 w-2 rounded-full bg-amber-300"></span>
                Hiring Setup
              </div>
              <h1 class="mt-5 text-4xl font-black tracking-tight md:text-5xl">Add New Position</h1>
              <p class="mt-3 max-w-2xl text-sm leading-7 text-emerald-50/85 md:text-base">
                Build a job opening with clearer role details, better structure, and a posting layout that is easier for HR to review later.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100">Checklist</p>
                <p class="mt-1 text-sm font-semibold text-white">Role, details, requirements, benefits</p>
              </div>
              <a href="{{ route('admin.adminPosition') }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/15">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                Cancel
              </a>
            </div>
          </div>
        </section>

        <form action="{{ route('admin.createPositionStore') }}" method="POST" id="create-position-form" novalidate>
          @csrf

          @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">
              Please complete all required fields before creating the position.
            </div>
          @endif

          <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.8fr)_minmax(20rem,0.9fr)]">
            <div class="space-y-6">
              <div class="position-panel p-6 md:p-7">
                <div class="section-kicker section-kicker--teal">Role Foundation</div>
                <h2 class="section-title">Job Overview</h2>
                <p class="section-copy">Start with the core identity of the opening so the rest of the posting stays consistent.</p>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div>
                    <label class="field-label">Job Title</label>
                    <input class="input" placeholder="Dean of Student Affairs" name="title" value="{{ old('title') }}" data-required-label="Job Title">
                  </div>
                  <div>
                    <label class="field-label">Department</label>
                    <input class="input" placeholder="Library Department" name="department" value="{{ old('department') }}" data-required-label="Department">
                  </div>
                  <div>
                    <label class="field-label">Employment Type</label>
                    <select class="input" name="employment" data-required-label="Employment Type">
                      <option value="">Employment Type</option>
                      <option value="Full-Time" @selected(old('employment') === 'Full-Time')>Full-Time</option>
                      <option value="Part-Time" @selected(old('employment') === 'Part-Time')>Part-Time</option>
                    </select>
                  </div>
                  <div>
                    <label class="field-label">Work Mode</label>
                    <select class="input" name="mode" data-required-label="Work Mode">
                      <option value="">Work Mode</option>
                      <option value="Remote" @selected(old('mode') === 'Remote')>Remote</option>
                      <option value="Onsite" @selected(old('mode') === 'Onsite')>Onsite</option>
                      <option value="Hybrid" @selected(old('mode') === 'Hybrid')>Hybrid</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="position-panel p-6 md:p-7">
                <div class="section-kicker section-kicker--gold">Role Story</div>
                <h2 class="section-title">Job Description</h2>
                <p class="section-copy">Describe the purpose of the role, the team context, and the kind of impact the hire is expected to make.</p>
                <textarea
                  rows="6"
                  name="description"
                  class="input resize-none bullet-textarea"
                  placeholder="- Describe the position"
                  data-required-label="Job Description"
                >{{ old('description') }}</textarea>
              </div>

              <div class="position-panel p-6 md:p-7">
                <div class="section-kicker section-kicker--gold">Delivery Scope</div>
                <h2 class="section-title">Responsibilities</h2>
                <p class="section-copy">List the work the employee will own day to day so the role feels concrete and actionable.</p>
                <textarea
                  rows="5"
                  name="responsibilities"
                  class="input resize-none bullet-textarea"
                  placeholder="- Lead departmental planning and coordination"
                  data-required-label="Responsibilities"
                >{{ old('responsibilities') }}</textarea>
              </div>

              <div class="position-panel p-6 md:p-7">
                <div class="section-kicker section-kicker--teal">Candidate Fit</div>
                <h2 class="section-title">Requirements</h2>
                <p class="section-copy">Clarify what qualifications, certifications, and experience level are expected before shortlisting.</p>
                <textarea
                  rows="5"
                  name="requirements"
                  class="input resize-none bullet-textarea"
                  placeholder="- 5+ years of related experience"
                  data-required-label="Requirements"
                >{{ old('requirements') }}</textarea>
              </div>
            </div>

            <div class="space-y-6">
              <div class="position-panel p-6 md:p-7">
                <div class="space-y-4">
                  <div>
                    <label class="field-label">Experience Level</label>
                    <select class="input" name="level" data-required-label="Experience Level">
                      <option value="">Experience Level</option>
                      <option value="Junior" @selected(old('level') === 'Junior')>Junior</option>
                      <option value="Mid" @selected(old('level') === 'Mid')>Mid</option>
                      <option value="Senior" @selected(old('level') === 'Senior')>Senior</option>
                    </select>
                  </div>

                  <div>
                    <label class="field-label">Job Type</label>
                    <select class="input" name="job_type" data-required-label="Job Type">
                      <option value="">Job Type</option>
                      <option value="Teaching" @selected(old('job_type') === 'Teaching')>Teaching</option>
                      <option value="Non-Teaching" @selected(old('job_type') === 'Non-Teaching')>Non-Teaching</option>
                    </select>
                  </div>

                  <div>
                    <label class="field-label">Location</label>
                    <input class="input" placeholder="Santiago City Campus" name="location" value="{{ old('location') }}" data-required-label="Location">
                  </div>

                  <div>
                    <label class="field-label">Start Date</label>
                    <input type="date" class="input" name="start_date" value="{{ old('start_date') }}" data-required-label="Start Date">
                  </div>

                  <div>
                    <label class="field-label">Close Date</label>
                    <input type="date" class="input" name="end_date" value="{{ old('end_date') }}" data-required-label="Close Date">
                  </div>

                  <p class="helper-text">
                    Tip: use the closing date to keep the hiring board clean and help applicants understand urgency.
                  </p>
                </div>
              </div>

              <div class="position-panel p-6 md:p-7">
                <div class="section-kicker section-kicker--teal">Capability Focus</div>
                <h2 class="section-title">Required Skills</h2>
                <p class="section-copy">Highlight the strongest practical strengths the role needs from day one.</p>
                <div class="mt-5">
                  <label class="field-label">Skills</label>
                  <input type="hidden" name="skills" id="skills-value" value="{{ old('skills') }}" data-required-label="Skills" data-focus-target="skill-input">
                  <div class="flex gap-2">
                    <input id="skill-input" class="input" placeholder="Type skill" autocomplete="off">
                    <button type="button" id="add-skill-button" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-[linear-gradient(135deg,#0f766e,#0f172a)] px-4 py-3 text-sm font-bold text-white shadow-[0_14px_26px_rgba(15,118,110,0.18)] transition hover:-translate-y-0.5">
                      <i class="fa-solid fa-plus text-xs"></i>
                      Add
                    </button>
                  </div>
                  <div id="skills-list" class="mt-3 flex flex-wrap gap-2"></div>
                </div>
              </div>

              <div class="position-panel p-6 md:p-7">
                <div class="section-kicker section-kicker--gold">Offer Highlights</div>
                <h2 class="section-title">Benefits & Perks</h2>
                <p class="section-copy">Show the practical value of the role so the posting feels attractive, not just demanding.</p>
                <textarea
                  rows="4"
                  name="benefits"
                  class="input resize-none bullet-textarea"
                  placeholder="- Health insurance"
                  data-required-label="Benefits & Perks"
                >{{ old('benefits') }}</textarea>
              </div>

              <div class="cta-row">
                <div class="position-panel p-4">
                  <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('admin.adminPosition') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 sm:w-auto">
                      <i class="fa-solid fa-xmark text-xs"></i>
                      Cancel
                    </a>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#0f766e,#0f172a)] px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_30px_rgba(15,118,110,0.24)] transition hover:-translate-y-0.5 hover:shadow-[0_18px_34px_rgba(15,118,110,0.30)]">
                      <i class="fa-solid fa-briefcase text-xs"></i>
                      Create Position
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<script>
  const bullet = '- ';
  const createPositionForm = document.getElementById('create-position-form');
  const validationModal = document.getElementById('position-validation-modal');
  const validationList = document.getElementById('position-validation-list');
  const validationCloseButton = document.getElementById('position-validation-close');
  const skillInput = document.getElementById('skill-input');
  const addSkillButton = document.getElementById('add-skill-button');
  const skillsValue = document.getElementById('skills-value');
  const skillsList = document.getElementById('skills-list');

  function isBlankRequiredValue(field) {
    const value = field.value.trim();
    return value === '' || value === bullet.trim();
  }

  if (createPositionForm) {
    function getMissingRequiredFields() {
      return Array.from(createPositionForm.querySelectorAll('[data-required-label]'))
        .filter(isBlankRequiredValue);
    }

    function openValidationModal(missingFields) {
      validationList.innerHTML = '';

      missingFields.forEach((field) => {
        const item = document.createElement('li');
        item.className = 'flex items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm';
        item.innerHTML = '<i class="fa-solid fa-circle-dot text-[10px] text-amber-500"></i><span></span>';
        item.querySelector('span').textContent = field.dataset.requiredLabel;
        validationList.appendChild(item);
      });

      validationModal.classList.remove('hidden');
      validationModal.classList.add('flex');
      validationCloseButton.focus();
    }

    function closeValidationModal() {
      validationModal.classList.add('hidden');
      validationModal.classList.remove('flex');
      const firstMissing = getMissingRequiredFields()[0];
      const focusTargetId = firstMissing?.dataset.focusTarget;
      (focusTargetId ? document.getElementById(focusTargetId) : firstMissing)?.focus();
    }

    validationCloseButton?.addEventListener('click', closeValidationModal);
    validationModal?.addEventListener('click', (event) => {
      if (event.target === validationModal) {
        closeValidationModal();
      }
    });

    createPositionForm.addEventListener('submit', (event) => {
      const missingFields = getMissingRequiredFields();

      if (missingFields.length === 0) {
        return;
      }

      event.preventDefault();
      openValidationModal(missingFields);
    });

    @if ($errors->any())
      openValidationModal(getMissingRequiredFields());
    @endif
  }

  if (skillInput && addSkillButton && skillsValue && skillsList) {
    let skills = skillsValue.value
      .split(',')
      .map((skill) => skill.trim())
      .filter(Boolean);

    function renderSkills() {
      skillsValue.value = skills.join(', ');
      skillsList.innerHTML = '';

      skills.forEach((skill, index) => {
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-2 rounded-full border border-teal-100 bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-800';
        chip.innerHTML = '<span></span><button type="button" class="text-teal-700 transition hover:text-red-600" aria-label="Remove skill"><i class="fa-solid fa-xmark text-xs"></i></button>';
        chip.querySelector('span').textContent = skill;
        chip.querySelector('button').addEventListener('click', () => {
          skills.splice(index, 1);
          renderSkills();
          skillInput.focus();
        });
        skillsList.appendChild(chip);
      });
    }

    function addSkill() {
      const skill = skillInput.value.trim();
      if (!skill) {
        skillInput.focus();
        return;
      }

      if (!skills.some((existingSkill) => existingSkill.toLowerCase() === skill.toLowerCase())) {
        skills.push(skill);
        renderSkills();
      }

      skillInput.value = '';
      skillInput.focus();
    }

    addSkillButton.addEventListener('click', addSkill);
    skillInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        addSkill();
      }
    });

    renderSkills();
  }

  document.querySelectorAll('.bullet-textarea').forEach((textarea) => {
    textarea.addEventListener('focus', () => {
      if (textarea.value.trim() === '') {
        textarea.value = bullet;
      }
    });

    textarea.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const start = this.selectionStart;
        this.value =
          this.value.substring(0, start) + '\n' + bullet +
          this.value.substring(this.selectionEnd);
        this.selectionStart = this.selectionEnd = start + bullet.length + 1;
      }
    });
  });

  const positionSuccessModal = document.getElementById('position-success-modal');
  const addMoreButton = document.getElementById('position-add-more-yes');
  if (positionSuccessModal && addMoreButton) {
    if (window.history.replaceState) {
      const cleanUrl = new URL(window.location.href);
      cleanUrl.searchParams.delete('created');
      window.history.replaceState({}, document.title, cleanUrl.toString());
    }

    addMoreButton.addEventListener('click', () => {
      positionSuccessModal.classList.add('hidden');
      document.querySelector('input[name="title"]')?.focus();
    });
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
