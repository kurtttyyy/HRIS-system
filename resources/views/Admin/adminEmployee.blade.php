<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub – HR Dashboard</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    [x-cloak] {
      display: none !important;
    }
    body {
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease;
    }
    main {
      --admin-employee-sidebar-gap: 2rem;
      transition: margin-left 0.3s ease;
    }
    main.main-with-collapsed-sidebar {
      margin-left: calc(4rem + var(--admin-employee-sidebar-gap));
      width: calc(100vw - (4rem + var(--admin-employee-sidebar-gap)));
    }
    main.main-with-expanded-sidebar {
      margin-left: calc(18rem + var(--admin-employee-sidebar-gap));
      width: calc(100vw - (18rem + var(--admin-employee-sidebar-gap)));
    }
    .employee-missing-badge {
      position: absolute;
      top: 0.85rem;
      right: 0.85rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      background: transparent;
      box-shadow: none;
      z-index: 12;
    }
    .employee-missing-icon {
      width: 1.1rem;
      height: 1.1rem;
      border-radius: 999px;
      background: #ef4444;
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.72rem;
      line-height: 1;
      box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
      animation: employeeMissingPulse 1.2s ease-in-out infinite;
    }
    @keyframes employeeMissingPulse {
      0%,
      100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
      }
      50% {
        transform: scale(1.08);
        box-shadow: 0 0 0 7px rgba(239, 68, 68, 0);
      }
    }
    .employee-profile-modal-scroll {
      flex: 1 1 auto;
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-gutter: stable both-edges;
    }
    .employee-page-reveal {
      opacity: 0;
      transform: translate3d(0, 22px, 0) scale(0.985);
      transition:
        opacity 680ms cubic-bezier(0.22, 0.9, 0.2, 1),
        transform 680ms cubic-bezier(0.22, 0.9, 0.2, 1);
      transition-delay: var(--employee-reveal-delay, 0ms);
      will-change: opacity, transform;
      backface-visibility: hidden;
    }
    .employee-page-reveal.employee-reveal-from-top {
      transform: translate3d(0, -22px, 0) scale(0.985);
    }
    .employee-page-reveal.employee-reveal-visible {
      opacity: 1;
      transform: translate3d(0, 0, 0) scale(1);
    }
    .employee-card-motion {
      transition:
        transform 260ms cubic-bezier(0.22, 0.9, 0.2, 1),
        border-color 260ms ease,
        background-color 260ms ease;
    }
    .employee-card-motion:hover {
      transform: translate3d(0, -3px, 0);
      filter: none;
    }
    .employee-card-name,
    .employee-card-role {
      display: -webkit-box;
      overflow: hidden;
      -webkit-box-orient: vertical;
      overflow-wrap: anywhere;
    }
    .employee-card-name {
      -webkit-line-clamp: 2;
    }
    .employee-card-role {
      -webkit-line-clamp: 1;
    }
    .employee-table-row-motion {
      transition: transform 220ms cubic-bezier(0.22, 0.9, 0.2, 1), background-color 220ms ease;
    }
    .employee-table-row-motion:hover {
      transform: translateX(3px);
      background-color: rgba(240, 253, 250, 0.72);
    }
    @media (prefers-reduced-motion: reduce) {
      .employee-page-reveal,
      .employee-page-reveal.employee-reveal-from-top,
      .employee-page-reveal.employee-reveal-visible,
      .employee-card-motion:hover,
      .employee-table-row-motion:hover {
        opacity: 1;
        transform: none;
        transition: none;
      }
    }
  </style>
</head>

<body class="bg-slate-100">

<div class="flex min-h-screen">

  <!-- Sidebar -->
  @include('components.adminSideBar')

  @php
    $resolveDepartment = function ($emp) {
      return trim((string) (data_get($emp, 'applicant.position.department') ?: data_get($emp, 'employee.department') ?: ($emp->department ?? '')));
    };
    $isMissingEmployeeValue = function ($value): bool {
      if (is_null($value)) {
        return true;
      }

      $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
      if ($normalized === '') {
        return true;
      }

      return in_array($normalized, [
        '-',
        'n/a',
        'na',
        'unspecified',
        'not set',
        'school n/a',
        'year n/a',
        'school n/a, year n/a',
      ], true);
    };
    $displayEmployeeId = function ($emp, string $emptyValue = '-') {
      $value = trim((string) (data_get($emp, 'employee.employee_id') ?: ($emp->employee_id ?? '')));
      if ($value === '') {
        return $emptyValue;
      }

      $userId = (int) ($emp->id ?? 0);
      $generatedId = $userId > 0 ? 'EMP-'.str_pad((string) $userId, 5, '0', STR_PAD_LEFT) : '';
      if ($generatedId !== '' && strcasecmp($value, $generatedId) === 0) {
        return $emptyValue;
      }

      return $value;
    };
    $employeeAddressParts = function ($emp): array {
      $rawAddress = trim((string) (data_get($emp, 'employee.address') ?: data_get($emp, 'applicant.address') ?: ($emp->address ?? '')));
      if ($rawAddress === '') {
        return [];
      }

      return collect(preg_split('/\s*,\s*/', $rawAddress))
        ->map(fn ($item) => trim((string) $item))
        ->values()
        ->all();
    };
    $employeeMissingAddressPartLabels = function ($emp) use ($isMissingEmployeeValue, $employeeAddressParts) {
      $parts = $employeeAddressParts($emp);
      $labels = [
        'Address: Barangay' => $parts[0] ?? null,
        'Address: Municipality' => $parts[1] ?? null,
        'Address: Province' => $parts[2] ?? null,
      ];

      return collect($labels)
        ->filter(fn ($value) => $isMissingEmployeeValue($value))
        ->keys()
        ->values();
    };
    $employeeHasMissingAddressParts = fn ($emp): bool => $employeeMissingAddressPartLabels($emp)->isNotEmpty();
    $parseLeaveStatusDate = function ($value) {
      $text = trim((string) ($value ?? ''));
      if ($text === '') {
        return null;
      }

      foreach (['Y-m-d', 'm/d/Y', 'n/j/Y'] as $format) {
        try {
          return \Carbon\Carbon::createFromFormat($format, $text)->startOfDay();
        } catch (\Throwable $e) {
        }
      }

      try {
        return \Carbon\Carbon::parse($text)->startOfDay();
      } catch (\Throwable $e) {
        return null;
      }
    };
    $resolveLeaveRange = function ($application) use ($parseLeaveStatusDate) {
      $inclusiveDates = trim((string) (data_get($application, 'inclusive_dates') ?? ''));
      $matchedDates = [];
      if ($inclusiveDates !== '') {
        preg_match_all('/\b\d{4}-\d{2}-\d{2}\b|\b\d{1,2}\/\d{1,2}\/\d{4}\b/', $inclusiveDates, $matches);
        $matchedDates = $matches[0] ?? [];
      }

      $startDate = isset($matchedDates[0]) ? $parseLeaveStatusDate($matchedDates[0]) : null;
      $endDate = isset($matchedDates[1]) ? $parseLeaveStatusDate($matchedDates[1]) : null;

      if (!$startDate) {
        $startDate = $parseLeaveStatusDate(data_get($application, 'filing_date'))
          ?: $parseLeaveStatusDate(data_get($application, 'created_at'));
      }

      $days = (float) (data_get($application, 'number_of_working_days') ?? 0);
      if ($days <= 0) {
        $days = max(
          (float) (data_get($application, 'days_with_pay') ?? 0),
          (float) (data_get($application, 'applied_total') ?? 0)
        );
      }

      $rangeDays = max((int) ceil($days), 1);
      if (!$endDate && $startDate) {
        $endDate = $startDate->copy()->addDays($rangeDays - 1);
      }

      if ($startDate && $endDate && $endDate->lt($startDate)) {
        [$startDate, $endDate] = [$endDate, $startDate];
      }

      return [$startDate, $endDate];
    };
    $resolveLatestApprovedResignationDate = function ($emp) use ($parseLeaveStatusDate) {
      return collect(data_get($emp, 'resignations', []))
        ->filter(function ($row) {
          $status = strtolower(trim((string) (data_get($row, 'status') ?? '')));
          return in_array($status, ['approved', 'completed'], true);
        })
        ->map(function ($row) use ($parseLeaveStatusDate) {
          return $parseLeaveStatusDate(data_get($row, 'effective_date'))
            ?: $parseLeaveStatusDate(data_get($row, 'processed_at'))
            ?: $parseLeaveStatusDate(data_get($row, 'submitted_at'))
            ?: $parseLeaveStatusDate(data_get($row, 'created_at'));
        })
        ->filter()
        ->sortByDesc(fn ($date) => $date->timestamp)
        ->first();
    };
    $resolveLatestApprovedResignationDecisionDate = function ($emp) use ($parseLeaveStatusDate) {
      return collect(data_get($emp, 'resignations', []))
        ->filter(function ($row) {
          $status = strtolower(trim((string) (data_get($row, 'status') ?? '')));
          return in_array($status, ['approved', 'completed'], true);
        })
        ->map(function ($row) use ($parseLeaveStatusDate) {
          return $parseLeaveStatusDate(data_get($row, 'processed_at'))
            ?: $parseLeaveStatusDate(data_get($row, 'submitted_at'))
            ?: $parseLeaveStatusDate(data_get($row, 'created_at'))
            ?: $parseLeaveStatusDate(data_get($row, 'effective_date'));
        })
        ->filter()
        ->sortByDesc(fn ($date) => $date->timestamp)
        ->first();
    };
    $wasRehiredAfterResignation = function ($emp) use ($parseLeaveStatusDate, $resolveLatestApprovedResignationDecisionDate) {
      $latestResignationDate = $resolveLatestApprovedResignationDecisionDate($emp);
      if (!$latestResignationDate) {
        return false;
      }

      $rehireReference = $parseLeaveStatusDate(data_get($emp, 'applicant.date_hired'))
        ?: $parseLeaveStatusDate(data_get($emp, 'applicant.created_at'));

      return $rehireReference && $rehireReference->gt($latestResignationDate);
    };
    $resolveDisplayAccountStatus = function ($emp) use ($resolveLeaveRange, $wasRehiredAfterResignation) {
      $accountStatus = strtolower(trim((string) (data_get($emp, 'account_status') ?? '')));
      $isRehiredAfterResignation = $wasRehiredAfterResignation($emp);
      if ($accountStatus === 'inactive' && !$isRehiredAfterResignation) {
        return 'Inactive';
      }
      if ($accountStatus === 'on leave') {
        return 'On Leave';
      }

      $hasApprovedOrCompletedResignation = collect(data_get($emp, 'resignations', []))
        ->contains(function ($row) {
          $status = strtolower(trim((string) (data_get($row, 'status') ?? '')));
          return in_array($status, ['approved', 'completed'], true);
        });

      if ($hasApprovedOrCompletedResignation && !$isRehiredAfterResignation) {
        return 'Inactive';
      }

      $today = \Carbon\Carbon::today();
      $hasApprovedLeaveToday = collect(data_get($emp, 'leave_applications', []))
        ->contains(function ($row) use ($resolveLeaveRange, $today) {
          $status = strtolower(trim((string) (data_get($row, 'status') ?? '')));
          if ($status !== 'approved') {
            return false;
          }

          [$startDate, $endDate] = $resolveLeaveRange($row);
          if (!$startDate || !$endDate) {
            return false;
          }

          return $today->betweenIncluded($startDate, $endDate);
        });

      return $hasApprovedLeaveToday ? 'On Leave' : 'Active';
    };
  @endphp

  <!-- Main Content -->
<main class="main-with-collapsed-sidebar min-w-0 transition-all duration-300"
      x-data="{
        openProfile:false,
        openEditProfile:false,
        modalTarget: '',
        tab:'overview',
        viewMode:'cards',
        exportMenuOpen:false,
        showDepartmentSummary:false,
        department:@js($employeeFilters['department'] ?? 'All'),
        statusFilter:@js($employeeFilters['status'] ?? 'All'),
        searchInput:@js($employeeFilters['search'] ?? ''),
        search:@js($employeeFilters['search'] ?? ''),
        openImageZoom: false,
        zoomImageUrl: '',
        employeeIndex: [],
        employeeRecords: [],
        employeeById: {},
        employeeFilterSequence: 0,
        employeeFilterLoading: false,
        accountStatusVersion: '',
        accountStatusPolling: false,
        accountStatusPollTimer: null,
        startAccountStatusPolling() {
          this.refreshAccountStatuses();
          this.accountStatusPollTimer = window.setInterval(() => this.refreshAccountStatuses(), 8000);
        },
        async refreshAccountStatuses() {
          if (this.accountStatusPolling || document.hidden) return;
          this.accountStatusPolling = true;

          try {
            const response = await fetch(@js(route('admin.employeeAccountStatuses')), {
              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              cache: 'no-store',
            });
            if (!response.ok) return;

            const payload = await response.json();
            if (!Array.isArray(payload.employees) || payload.version === this.accountStatusVersion) return;
            this.accountStatusVersion = payload.version ?? '';

            payload.employees.forEach((employee) => {
              const userId = Number.parseInt((employee?.id ?? '').toString(), 10);
              if (!Number.isFinite(userId) || userId <= 0) return;

              const status = (employee.account_status || 'Active').toString().trim();
              const normalized = this.normalize(status);
              const badge = document.querySelector(`[data-employee-account-status='${userId}']`);
              if (badge && this.normalize(badge.textContent) !== normalized) {
                badge.textContent = status;
                badge.classList.remove('text-green-700', 'bg-green-100', 'text-orange-700', 'bg-orange-100/70', 'text-red-700', 'bg-red-100/70');
                badge.classList.add(...(normalized === 'active'
                  ? ['text-green-700', 'bg-green-100']
                  : normalized === 'on leave'
                    ? ['text-orange-700', 'bg-orange-100/70']
                    : ['text-red-700', 'bg-red-100/70']));
              }

              if (this.employeeById[userId]) {
                this.employeeById[userId].account_status = status;
                this.employeeById[userId].status = employee.approval_status;
                if (normalized === 'active' && this.normalize(employee.approval_status) === 'approved') {
                  this.employeeById[userId].temporary_pin = null;
                }
              }

              if (Number(this.selectedEmployee?.id) === userId) {
                this.selectedEmployee.account_status = status;
                this.selectedEmployee.status = employee.approval_status;
                if (normalized === 'active' && this.normalize(employee.approval_status) === 'approved') {
                  this.selectedEmployee.temporary_pin = null;
                }
              }
              const indexEntry = this.employeeIndex.find((row) => Number(row.id) === userId);
              if (indexEntry) indexEntry.status = status;
            });
          } catch (error) {
            // A temporary network failure should not disturb the admin page.
          } finally {
            this.accountStatusPolling = false;
          }
        },
        normalize(value) {
          return (value ?? '').toString().trim().toLowerCase();
        },
        decodeDisplayText(value) {
          let text = (value ?? '').toString();
          if (!text) {
            return '';
          }

          const suspiciousEncodingPattern = /(?:Ã.|Â.|â.|ðŸ|�)/;
          if (!suspiciousEncodingPattern.test(text) || typeof TextDecoder === 'undefined') {
            return text.trim();
          }

          for (let index = 0; index < 3; index += 1) {
            try {
              const bytes = Uint8Array.from(text, (character) => character.charCodeAt(0) & 0xff);
              const decoded = new TextDecoder('utf-8').decode(bytes);
              if (!decoded || decoded === text) {
                break;
              }
              text = decoded;
            } catch (error) {
              break;
            }
          }

          return text.trim();
        },
        normalizeAddressText(value) {
          return (value ?? '')
            .toString()
            .split(',')
            .map((part) => {
              let text = this.decodeDisplayText(part);

              for (let index = 0; index < 5; index += 1) {
                let nextText = text;
                try {
                  nextText = decodeURIComponent(escape(nextText));
                } catch (error) {
                }

                if (!nextText || nextText === text) {
                  break;
                }

                text = nextText;
              }

              return text.trim();
            })
            .filter(Boolean)
            .join(', ');
        },
        employeeAddressParts() {
          return this.normalizeAddressText(this.selectedEmployee?.employee?.address ?? '')
            .split(',')
            .map((part) => this.decodeDisplayText(part))
            .filter(Boolean);
        },
        isPlaceholderValue(value) {
          const normalized = this.normalize(value);
          return normalized === '' || normalized === 'n/a' || normalized === 'na' || normalized === '-';
        },
        cleanDisplayValue(value) {
          if (value === null || value === undefined) {
            return '';
          }

          let text = this.decodeDisplayText(value);
          text = (text ?? '').toString().replace(/\s+/g, ' ').trim();
          if (text === '') {
            return '';
          }

          const normalized = this.normalize(text);
          if ([
            '-',
            'n/a',
            'na',
            'unspecified',
            'not set',
            'school n/a',
            'year n/a',
            'school n/a, year n/a',
          ].includes(normalized)) {
            return '';
          }

          // Catch mojibake/fallback artifacts that should be treated as empty.
          if (/(?:Ã|Â|â€”|â€“|â€|ï¿½|�)/.test(text)) {
            return '';
          }

          return text;
        },
        isMissingDisplayValue(value) {
          return this.cleanDisplayValue(value) === '';
        },
        firstCleanDisplayValue(...values) {
          for (const value of values) {
            const cleaned = this.cleanDisplayValue(value);
            if (cleaned !== '') {
              return cleaned;
            }
          }
          return '';
        },
        displayValueOrNoInfo(value) {
          const cleaned = this.cleanDisplayValue(value);
          return cleaned === '' ? 'No information' : cleaned;
        },
        isMissingInfoValue(value) {
          if (value === null || value === undefined) {
            return true;
          }

          const normalized = (value ?? '')
            .toString()
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

          if (normalized === '') {
            return true;
          }

          return [
            '-',
            'n/a',
            'na',
            'unspecified',
            'not set',
            'school n/a',
            'year n/a',
            'school n/a, year n/a',
          ].includes(normalized);
        },
        firstMeaningfulValue(...values) {
          for (const value of values) {
            if (!this.isMissingInfoValue(value)) {
              return value;
            }
          }
          return values[0] ?? '';
        },
        countMissingValues(values = []) {
          return values.filter((value) => this.isMissingInfoValue(value)).length;
        },
        personalAddressMissingCount() {
          const parts = this.employeeAddressParts();
          const slots = [parts[0], parts[1], parts[2]];
          return slots.filter((value) => this.isMissingInfoValue(value)).length;
        },
        biometricDegreeMissingCount() {
          const levels = ['bachelor', 'master', 'doctorate'];
          let missingCount = 0;

          const fallbackByLevel = {
            bachelor: {
              degree: this.selectedEmployee?.education?.bachelor,
              school: this.selectedEmployee?.applicant?.bachelor_school_name,
              year: this.selectedEmployee?.applicant?.bachelor_year_finished,
            },
            master: {
              degree: this.selectedEmployee?.education?.master,
              school: this.selectedEmployee?.applicant?.master_school_name,
              year: this.selectedEmployee?.applicant?.master_year_finished,
            },
            doctorate: {
              degree: this.selectedEmployee?.education?.doctorate,
              school: this.selectedEmployee?.applicant?.doctoral_school_name,
              year: this.selectedEmployee?.applicant?.doctoral_year_finished,
            },
          };

          levels.forEach((level) => {
            const rows = this.degreeRows(level);
            if (rows.length) {
              rows.forEach((row) => {
                const hasMissingDegreeRow = this.isMissingInfoValue(row?.degree_name)
                  || this.isMissingInfoValue(row?.school_name)
                  || this.isMissingInfoValue(row?.year_finished);
                if (hasMissingDegreeRow) {
                  missingCount += 1;
                }
              });
              return;
            }

            const fallback = fallbackByLevel[level] ?? {};
            const hasMissingFallback = this.isMissingInfoValue(fallback?.degree)
              || this.isMissingInfoValue(fallback?.school)
              || this.isMissingInfoValue(fallback?.year);
            if (hasMissingFallback) {
              missingCount += 1;
            }
          });

          return missingCount;
        },
        missingDocumentsCount() {
          const docs = this.selectedEmployee?.applicant?.missing_documents;
          if (!Array.isArray(docs)) {
            return 0;
          }
          return docs.filter((value) => !this.isMissingInfoValue(value)).length;
        },
        tabMissingCount(tabName) {
          const tab = this.normalize(tabName);
          const email = this.firstMeaningfulValue(
            this.selectedEmployee?.email,
            this.selectedEmployee?.employee?.email,
            this.selectedEmployee?.applicant?.email
          );
          const contactNumber = this.firstMeaningfulValue(
            this.selectedEmployee?.employee?.contact_number,
            this.selectedEmployee?.applicant?.phone
          );
          const address = this.firstMeaningfulValue(
            this.selectedEmployee?.employee?.address,
            this.selectedEmployee?.applicant?.address
          );
          const classification = this.firstMeaningfulValue(
            this.selectedEmployee?.employee?.classification,
            this.selectedEmployee?.applicant?.position?.employment,
            this.selectedEmployee?.employee?.job_type,
            this.selectedEmployee?.applicant?.position?.job_type
          );

          if (tab === 'overview') {
            return this.countMissingValues([
              email,
              contactNumber,
              this.selectedEmployee?.employee?.birthday,
              address,
            ]);
          }

          if (tab === 'personal') {
            return this.countMissingValues([
              this.selectedEmployee?.employee?.account_number,
              this.firstMeaningfulValue(
                this.selectedEmployee?.employee?.sex,
                this.selectedEmployee?.employee?.gender
              ),
              this.selectedEmployee?.employee?.civil_status,
              contactNumber,
              this.selectedEmployee?.employee?.birthday,
              this.selectedEmployee?.government?.SSS,
              this.selectedEmployee?.government?.TIN,
              this.selectedEmployee?.government?.PhilHealth,
              this.selectedEmployee?.government?.MID,
              this.selectedEmployee?.government?.RTN,
            ]) + this.personalAddressMissingCount();
          }

          if (tab === 'performance') {
            return 0;
          }

          if (tab === 'documents') {
            return this.missingDocumentsCount();
          }

          if (tab === 'record') {
            return this.countMissingValues([
              this.selectedEmployee?.employee?.employee_id,
              this.currentServiceStartRaw(),
              this.firstMeaningfulValue(
                this.selectedEmployee?.employee?.department,
                this.selectedEmployee?.applicant?.position?.department
              ),
              classification,
            ]);
          }

          if (tab === 'biometric') {
            return this.countMissingValues([
              this.selectedEmployee?.employee?.employee_id,
              this.selectedEmployee?.employee?.account_number,
              this.firstMeaningfulValue(
                this.selectedEmployee?.employee?.sex,
                this.selectedEmployee?.employee?.gender
              ),
              this.selectedEmployee?.employee?.civil_status,
              contactNumber,
              this.selectedEmployee?.employee?.birthday,
              address,
              this.selectedEmployee?.license?.license,
              this.selectedEmployee?.license?.registration_number,
              this.selectedEmployee?.government?.SSS,
              this.selectedEmployee?.government?.TIN,
              this.selectedEmployee?.government?.PhilHealth,
              this.selectedEmployee?.government?.MID,
              this.selectedEmployee?.government?.RTN,
              this.selectedEmployee?.salary?.salary,
            ]) + this.biometricDegreeMissingCount();
          }

          return 0;
        },
        matchesDepartment(empDepartment) {
          if (this.department === 'All') return true;
          return this.normalize(empDepartment) === this.normalize(this.department);
        },
        matchesSearch(empName) {
          const query = this.normalize(this.search);
          if (!query) return true;
          return this.normalize(empName).includes(query);
        },
        matchesStatus(empStatus, empHasMissingInfo = false) {
          if (this.statusFilter === 'All') return true;
          if (this.normalize(this.statusFilter) === 'missing info') {
            return Boolean(empHasMissingInfo);
          }
          return this.normalize(empStatus) === this.normalize(this.statusFilter);
        },
        hasVisibleEmployees() {
          return this.employeeIndex.some(emp =>
            this.matchesDepartment(emp.department) &&
            this.matchesSearch(emp.name) &&
            this.matchesStatus(emp.status, emp.has_missing_info)
          );
        },
        beginEmployeeSearch() {
          // Invalidate an older response immediately while the debounced search
          // waits to run, and show feedback from the first typed character.
          this.employeeFilterSequence += 1;
          this.employeeFilterLoading = true;
        },
        applyEmployeeDirectoryFilters() {
          const params = new URLSearchParams(window.location.search);
          const search = (this.searchInput ?? '').toString().trim();
          const department = (this.department ?? 'All').toString().trim();
          const status = (this.statusFilter ?? 'All').toString().trim();

          search ? params.set('search', search) : params.delete('search');
          department && department !== 'All' ? params.set('department', department) : params.delete('department');
          status && status !== 'All' ? params.set('status', status) : params.delete('status');
          params.delete('page');

          const query = params.toString();
          const url = query ? `${window.location.pathname}?${query}` : window.location.pathname;
          this.loadEmployeeDirectoryResults(url);
        },
        async loadEmployeeDirectoryResults(url) {
          const targetUrl = new URL(url, window.location.origin);
          const sequence = ++this.employeeFilterSequence;

          this.employeeFilterLoading = true;

          try {
            const response = await fetch(targetUrl.toString(), {
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
              },
              cache: 'no-store',
            });

            if (!response.ok) {
              throw new Error(`Employee directory request failed (${response.status}).`);
            }

            const documentHtml = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextResults = documentHtml.querySelector('[data-employee-directory-results]');
            const currentResults = document.querySelector('[data-employee-directory-results]');
            const requestedSearch = targetUrl.searchParams.get('search') ?? '';
            const currentSearchInput = (this.searchInput ?? '').toString().trim();
            if (
              !nextResults ||
              !currentResults ||
              sequence !== this.employeeFilterSequence ||
              requestedSearch !== currentSearchInput
            ) {
              return;
            }

            const alpine = window.Alpine;
            const parentDataStack = alpine?.closestDataStack?.(currentResults) ?? [];

            const replaceResults = () => {
              Array.from(currentResults.children).forEach((child) => {
                alpine?.destroyTree?.(child);
              });
              currentResults.innerHTML = nextResults.innerHTML;

              const recordsJson = currentResults.querySelector('[data-employee-page-records]')?.textContent ?? '[]';
              const records = JSON.parse(recordsJson);
              this.employeeRecords = Array.isArray(records) ? records : [];
              this.employeeById = Object.fromEntries(this.employeeRecords
                .map((row) => {
                  const userId = Number.parseInt((row?.id ?? '').toString(), 10);
                  return Number.isFinite(userId) && userId > 0 ? [userId, row] : null;
                })
                .filter((entry) => Array.isArray(entry))
              );

              this.search = requestedSearch;
              this.department = targetUrl.searchParams.get('department') ?? 'All';
              this.statusFilter = targetUrl.searchParams.get('status') ?? 'All';

              Array.from(currentResults.children).forEach((child) => {
                // innerHTML does not inherit Alpine's component scope. Attach the
                // existing page scope before initializing the new cards/controls.
                if (parentDataStack.length > 0) {
                  child._x_dataStack = parentDataStack;
                }
                alpine?.initTree?.(child);
              });

              const newEmployeeCards = Array.from(
                currentResults.querySelectorAll('.employee-directory-card')
              );
              const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;

              // The page-load IntersectionObserver only tracks the original cards.
              // Explicitly reveal cards inserted by search/pagination so they do
              // not remain at opacity: 0 until a full browser refresh.
              window.requestAnimationFrame(() => {
                newEmployeeCards.forEach((card, index) => {
                  const reveal = () => card.classList.add('employee-reveal-visible');
                  if (reduceMotion) {
                    reveal();
                  } else {
                    window.setTimeout(reveal, Math.min(index, 9) * 55);
                  }
                });
              });
            };

            if (alpine?.mutateDom) {
              alpine.mutateDom(replaceResults);
            } else {
              replaceResults();
            }

            window.history.replaceState({}, '', `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`);
          } catch (error) {
            console.error(error);
          } finally {
            if (sequence === this.employeeFilterSequence) {
              this.employeeFilterLoading = false;
            }
          }
        },
        degreeRows(level) {
          const rows = Array.isArray(this.selectedEmployee?.applicant?.degrees)
            ? this.selectedEmployee.applicant.degrees
            : [];
          const target = this.normalize(level);
          return rows.filter((row) => this.normalize(row?.degree_level) === target);
        },
        hasDegreeRows(level) {
          return this.degreeRows(level).length > 0;
        },
        degreeEditRows: {
          bachelor: [],
          master: [],
          doctorate: [],
        },
        initDegreeEditRows() {
          const sourceRows = Array.isArray(this.selectedEmployee?.applicant?.degrees)
            ? this.selectedEmployee.applicant.degrees
            : [];
          const normalizeRows = (level, fallback) => {
            const rows = sourceRows
              .filter((row) => this.normalize(row?.degree_level) === level)
              .map((row) => ({
                id: row?.id ?? null,
                degree_level: level,
                degree_name: (row?.degree_name ?? '').toString(),
                school_name: (row?.school_name ?? '').toString(),
                year_finished: (row?.year_finished ?? '').toString(),
              }));

            if (rows.length) {
              return rows;
            }

            return [{
              id: null,
              degree_level: level,
              degree_name: (fallback?.degree_name ?? '').toString(),
              school_name: (fallback?.school_name ?? '').toString(),
              year_finished: (fallback?.year_finished ?? '').toString(),
            }];
          };

          this.degreeEditRows = {
            bachelor: normalizeRows('bachelor', {
              degree_name: this.selectedEmployee?.education?.bachelor ?? '',
              school_name: this.selectedEmployee?.applicant?.bachelor_school_name ?? '',
              year_finished: this.selectedEmployee?.applicant?.bachelor_year_finished ?? '',
            }),
            master: normalizeRows('master', {
              degree_name: this.selectedEmployee?.education?.master ?? '',
              school_name: this.selectedEmployee?.applicant?.master_school_name ?? '',
              year_finished: this.selectedEmployee?.applicant?.master_year_finished ?? '',
            }),
            doctorate: normalizeRows('doctorate', {
              degree_name: this.selectedEmployee?.education?.doctorate ?? '',
              school_name: this.selectedEmployee?.applicant?.doctoral_school_name ?? '',
              year_finished: this.selectedEmployee?.applicant?.doctoral_year_finished ?? '',
            }),
          };
        },
        addDegreeRow(level) {
          const nextLevel = this.normalize(level);
          if (!['bachelor', 'master', 'doctorate'].includes(nextLevel)) return;
          const rows = Array.isArray(this.degreeEditRows[nextLevel]) ? this.degreeEditRows[nextLevel] : [];
          rows.push({
            id: null,
            degree_level: nextLevel,
            degree_name: '',
            school_name: '',
            year_finished: '',
          });
          this.degreeEditRows[nextLevel] = rows;
        },
        removeDegreeRow(level, index) {
          const nextLevel = this.normalize(level);
          if (!['bachelor', 'master', 'doctorate'].includes(nextLevel)) return;
          const rows = Array.isArray(this.degreeEditRows[nextLevel]) ? this.degreeEditRows[nextLevel] : [];
          if (rows.length <= 1) return;
          rows.splice(index, 1);
          this.degreeEditRows[nextLevel] = rows;
        },
        formatGraduateDegreeTitle(value) {
          let output = (value ?? '').toString().trim();
          // Remove trailing acronym-like abbreviations such as (MSN), (DNP/DNSc), etc.
          while (/\s*\(([A-Za-z0-9\/.&\-\s]{2,30})\)\s*$/.test(output)) {
            output = output.replace(/\s*\(([A-Za-z0-9\/.&\-\s]{2,30})\)\s*$/, '').trim();
          }
          return output || '-';
        },
        profilePhotoPath(emp = this.selectedEmployee) {
          const documents = Array.isArray(emp?.applicant?.documents) ? emp.applicant.documents : [];
          const profilePhotoDoc = documents.find((doc) => ((doc?.type ?? '').toString().trim().toUpperCase() === 'PROFILE_PHOTO') && doc?.filepath);
          const fallbackImageDoc = documents.find((doc) => {
            const mime = (doc?.mime_type ?? '').toString().toLowerCase();
            const filename = (doc?.filename ?? '').toString().toLowerCase();
            const isImageByMime = mime.startsWith('image/');
            const isImageByName = /\.(png|jpe?g|gif|webp)$/i.test(filename);
            return (isImageByMime || isImageByName) && doc?.filepath;
          });
          return (profilePhotoDoc ?? fallbackImageDoc)?.filepath ?? '';
        },
        profilePhotoUrl(emp = this.selectedEmployee) {
          const path = (this.profilePhotoPath(emp) ?? '').toString().trim();
          if (!path) return '';
          if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:') || path.startsWith('/')) {
            return path;
          }
          return `/storage/${path}`;
        },
        hasProfilePhoto(emp = this.selectedEmployee) {
          return this.profilePhotoPath(emp) !== '';
        },
        openImagePreview(url) {
          const nextUrl = (url ?? '').toString().trim();
          if (!nextUrl || nextUrl.startsWith('data:image/svg+xml')) return;
          this.zoomImageUrl = nextUrl;
          this.openImageZoom = true;
        },
        closeImagePreview() {
          this.openImageZoom = false;
          this.zoomImageUrl = '';
        },
        effectiveAccountStatus() {
          const accountStatus = (this.selectedEmployee?.account_status ?? '').toString().trim();
          if (accountStatus !== '') {
            if (accountStatus.toLowerCase() === 'inactive' && !this.wasRehiredAfterResignation()) {
              return 'Inactive';
            }
            if (accountStatus.toLowerCase() === 'on leave') {
              return 'On Leave';
            }
          }

          const hasApprovedOrCompletedResignation = (Array.isArray(this.selectedEmployee?.resignations)
            ? this.selectedEmployee.resignations
            : []
          ).some((row) => {
            const status = (row?.status ?? '').toString().trim().toLowerCase();
            return status === 'approved' || status === 'completed';
          });

          if (hasApprovedOrCompletedResignation && !this.wasRehiredAfterResignation()) {
            return 'Inactive';
          }

          const today = new Date();
          today.setHours(0, 0, 0, 0);
          const hasApprovedLeave = (Array.isArray(this.selectedEmployee?.leave_applications)
            ? this.selectedEmployee.leave_applications
            : []
          ).some((row) => {
            const status = (row?.status ?? '').toString().trim().toLowerCase();
            if (status !== 'approved') return false;

            const range = this.leaveDateRange(row);
            if (!range.start || !range.end) return false;

            return today.getTime() >= range.start.getTime() && today.getTime() <= range.end.getTime();
          });

          if (hasApprovedLeave) {
            return 'On Leave';
          }

          if (accountStatus.toLowerCase() === 'active') {
            return 'Active';
          }

          return 'Active';
        },
        latestApprovedResignationDate() {
          const resignationRows = Array.isArray(this.selectedEmployee?.resignations)
            ? this.selectedEmployee.resignations
            : [];

          const dates = resignationRows
            .filter((row) => {
              const status = (row?.status ?? '').toString().trim().toLowerCase();
              return status === 'approved' || status === 'completed';
            })
            .map((row) => this.parseDateValue(row?.effective_date || row?.processed_at || row?.submitted_at || row?.created_at))
            .filter(Boolean)
            .sort((a, b) => b.getTime() - a.getTime());

          return dates[0] ?? null;
        },
        latestApprovedResignationDecisionDate() {
          const resignationRows = Array.isArray(this.selectedEmployee?.resignations)
            ? this.selectedEmployee.resignations
            : [];

          const dates = resignationRows
            .filter((row) => {
              const status = (row?.status ?? '').toString().trim().toLowerCase();
              return status === 'approved' || status === 'completed';
            })
            .map((row) => this.parseDateValue(row?.processed_at || row?.submitted_at || row?.created_at || row?.effective_date))
            .filter(Boolean)
            .sort((a, b) => b.getTime() - a.getTime());

          return dates[0] ?? null;
        },
        wasRehiredAfterResignation() {
          const latestResignationDate = this.latestApprovedResignationDecisionDate();
          if (!latestResignationDate) return false;

          const rehireDate = this.parseDateValue(this.selectedEmployee?.applicant?.date_hired || this.selectedEmployee?.applicant?.created_at);
          if (!rehireDate) return false;

          return rehireDate.getTime() > latestResignationDate.getTime();
        },
        initialEmploymentStartRaw() {
          return this.selectedEmployee?.employee?.employement_date
            || this.selectedEmployee?.employee?.employment_date
            || this.selectedEmployee?.applicant?.date_hired
            || this.selectedEmployee?.applicant?.created_at
            || '';
        },
        rehireDateRaw() {
          if (!this.wasRehiredAfterResignation()) return '';
          return this.selectedEmployee?.applicant?.date_hired
            || this.selectedEmployee?.applicant?.created_at
            || '';
        },
        currentServiceStartRaw() {
          return this.rehireDateRaw() || this.initialEmploymentStartRaw();
        },
        selectedEmployeeRegularizationDate() {
          const start = this.parseDateValue(this.currentServiceStartRaw());
          if (!start) return null;

          const jobType = this.normalizeClassificationValue(
            this.selectedEmployee?.employee?.job_type
            || this.selectedEmployee?.applicant?.position?.job_type
          );
          const regularizationDate = new Date(start.getTime());

          if (jobType === 'non teaching' || jobType === 'non-teaching' || jobType === 'nt' || jobType === 'nonteaching') {
            regularizationDate.setMonth(regularizationDate.getMonth() + 6);
            return regularizationDate;
          }

          regularizationDate.setFullYear(regularizationDate.getFullYear() + 3);
          return regularizationDate;
        },
        isPermanentClassification(value = this.selectedEmployee?.employee?.classification) {
          const normalized = this.normalizeClassificationValue(value);
          return normalized.includes('permanent') || normalized.includes('regular');
        },
        canMarkSelectedEmployeePermanent() {
          if (!this.selectedEmployee?.id || !this.selectedEmployee?.employee) return false;
          if (this.isPermanentClassification()) return false;

          const regularizationDate = this.selectedEmployeeRegularizationDate();
          if (!regularizationDate) return false;

          const today = new Date();
          today.setHours(0, 0, 0, 0);

          return today.getTime() >= regularizationDate.getTime();
        },
        selectedEmployeePermanentLabel() {
          const regularizationDate = this.selectedEmployeeRegularizationDate();
          if (!regularizationDate) return 'Mark as Permanent';

          return `Mark as Permanent (${regularizationDate.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })})`;
        },
        effectiveAccountStatusClass() {
          const status = this.effectiveAccountStatus().toLowerCase();
          if (status === 'active') return 'bg-green-100 text-green-700';
          if (status === 'on leave') return 'bg-orange-100/70 text-orange-700';
          return 'bg-red-100/70 text-red-700';
        },
        selectedEmployee: {
          applicant: { documents: [], all_documents: [], folders: [], selected_folder_key: 'all', unfiled_count: 0, total_documents: 0, required_documents: [], required_documents_text: '', missing_documents: [], document_notice: '', position: {}, comparison: { is_rehire: false, changed_fields: [], changed_degree_levels: [] } },
          employee: {},
          education: {},
          government: {},
          license: {},
          salary: {},
          leave_applications: [],
          ui_theme: {},
        },
        getEmployeeById(userId) {
          const numericId = Number.parseInt((userId ?? '').toString(), 10);
          if (!Number.isFinite(numericId) || numericId <= 0) {
            return null;
          }
          return this.employeeById[numericId] ?? null;
        },
        async openEmployeeProfile(empOrId, headerStart, headerEnd) {
          const emp = (typeof empOrId === 'object' && empOrId !== null)
            ? empOrId
            : this.getEmployeeById(empOrId);
          if (!emp) {
            return;
          }
          this.openProfile = true;
          this.tab = 'overview';
          await this.setEmployee({
            ...(emp ?? {}),
            ui_theme: {
              header_start: headerStart ?? 'rgb(168 85 247)',
              header_end: headerEnd ?? 'rgb(99 102 241)',
            },
          });
        },
        async openEmployeeFromQuery() {
          const params = new URLSearchParams(window.location.search);
          const userId = Number.parseInt((params.get('user_id') ?? '').toString(), 10);
          if (!Number.isFinite(userId) || userId <= 0) {
            return;
          }

          const matchedEmployee = this.getEmployeeById(userId);

          if (!matchedEmployee) {
            return;
          }

          this.openProfile = true;
          await this.setEmployee(matchedEmployee);

          const requestedTab = this.normalize(params.get('tab'));
          const allowedTabs = ['overview', 'personal', 'performance', 'documents', 'record', 'biometric'];
          if (allowedTabs.includes(requestedTab)) {
            this.tab = requestedTab;
          }
        },
        async setEmployee(emp) {
          const applicantPosition = emp?.applicant?.position ?? {};
          const employeeData = { ...(emp?.employee ?? {}) };
          const applicantData = { ...(emp?.applicant ?? {}) };
          const educationData = { ...(emp?.education ?? {}) };
          const degreeDataRows = Array.isArray(applicantData?.degrees) ? applicantData.degrees : [];
          const findDegreeRow = (level) => degreeDataRows.find((row) => this.normalize(row?.degree_level) === level);
          const bachelorDegreeRow = findDegreeRow('bachelor');
          const masterDegreeRow = findDegreeRow('master');
          const doctorateDegreeRow = findDegreeRow('doctorate');
          const elementaryDegreeRow = findDegreeRow('elementary');
          const secondaryDegreeRow = findDegreeRow('secondary');
          const vocationalTradeDegreeRow = findDegreeRow('vocational_trade');

          if (!employeeData.position && applicantPosition.title) {
            employeeData.position = applicantPosition.title;
          }
          if (!employeeData.position && emp?.position) {
            employeeData.position = emp.position;
          }

          if (!employeeData.department && applicantPosition.department) {
            employeeData.department = applicantPosition.department;
          }
          if (!employeeData.department && emp?.department) {
            employeeData.department = emp.department;
          }

          if (this.isPlaceholderValue(employeeData.address) && !this.isPlaceholderValue(applicantData.address)) {
            employeeData.address = applicantData.address;
          }

          if (typeof employeeData.address === 'string') {
            employeeData.address = this.normalizeAddressText(employeeData.address);
          }
          if (typeof applicantData.address === 'string') {
            applicantData.address = this.normalizeAddressText(applicantData.address);
          }

          if (!employeeData.classification) {
            employeeData.classification = applicantPosition.employment
              || employeeData.job_type
              || applicantPosition.job_type
              || null;
          }
          employeeData.classification = this.canonicalClassificationValue(employeeData.classification);

          if (!educationData.bachelor && bachelorDegreeRow?.degree_name) {
            educationData.bachelor = bachelorDegreeRow.degree_name;
          }
          if (!educationData.master && masterDegreeRow?.degree_name) {
            educationData.master = masterDegreeRow.degree_name;
          }
          if (!educationData.doctorate && doctorateDegreeRow?.degree_name) {
            educationData.doctorate = doctorateDegreeRow.degree_name;
          }
          if (!educationData.elementary_school_name && elementaryDegreeRow?.school_name) {
            educationData.elementary_school_name = elementaryDegreeRow.school_name;
          }
          if (!educationData.elementary_year_finished && elementaryDegreeRow?.year_finished) {
            educationData.elementary_year_finished = elementaryDegreeRow.year_finished;
          }
          if (!educationData.secondary_school_name && secondaryDegreeRow?.school_name) {
            educationData.secondary_school_name = secondaryDegreeRow.school_name;
          }
          if (!educationData.secondary_year_finished && secondaryDegreeRow?.year_finished) {
            educationData.secondary_year_finished = secondaryDegreeRow.year_finished;
          }
          if (!educationData.vocational_trade_school_name && vocationalTradeDegreeRow?.school_name) {
            educationData.vocational_trade_school_name = vocationalTradeDegreeRow.school_name;
          }
          if (!educationData.vocational_trade_year_finished && vocationalTradeDegreeRow?.year_finished) {
            educationData.vocational_trade_year_finished = vocationalTradeDegreeRow.year_finished;
          }

          if (!applicantData.bachelor_school_name && bachelorDegreeRow?.school_name) {
            applicantData.bachelor_school_name = bachelorDegreeRow.school_name;
          }
          if (!applicantData.bachelor_year_finished && bachelorDegreeRow?.year_finished) {
            applicantData.bachelor_year_finished = bachelorDegreeRow.year_finished;
          }
          if (!applicantData.master_school_name && masterDegreeRow?.school_name) {
            applicantData.master_school_name = masterDegreeRow.school_name;
          }
          if (!applicantData.master_year_finished && masterDegreeRow?.year_finished) {
            applicantData.master_year_finished = masterDegreeRow.year_finished;
          }
          if (!applicantData.doctoral_school_name && doctorateDegreeRow?.school_name) {
            applicantData.doctoral_school_name = doctorateDegreeRow.school_name;
          }
          if (!applicantData.doctoral_year_finished && doctorateDegreeRow?.year_finished) {
            applicantData.doctoral_year_finished = doctorateDegreeRow.year_finished;
          }

          this.selectedEmployee = {
            ...emp,
            applicant: { documents: [], all_documents: [], folders: [], selected_folder_key: 'all', unfiled_count: 0, total_documents: 0, required_documents: [], required_documents_text: '', missing_documents: [], document_notice: '', position: {}, comparison: { is_rehire: false, changed_fields: [], changed_degree_levels: [] }, ...applicantData },
            employee: employeeData,
            education: educationData,
            government: emp?.government ?? {},
            license: emp?.license ?? {},
            salary: emp?.salary ?? {},
            leave_applications: Array.isArray(emp?.leave_applications) ? emp.leave_applications : [],
            leave_summary: emp?.leave_summary ?? {},
            ui_theme: emp?.ui_theme ?? {},
          };

          this.ensureEmployeeClassification();

          this.initDegreeEditRows();

          await this.loadDocuments(emp?.id);
        },
        async loadDocuments(userId) {
          if (!userId || !this.selectedEmployee?.applicant?.id) return;

          try {
            const response = await fetch(`/system/employee/${userId}/documents`, {
              headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) return;

            const payload = await response.json();
            this.selectedEmployee.applicant.documents = payload.documents ?? [];
            this.selectedEmployee.applicant.all_documents = payload.all_documents ?? payload.documents ?? [];
            this.selectedEmployee.applicant.folders = payload.folders ?? [];
            this.selectedEmployee.applicant.unfiled_count = payload.unfiled_count ?? 0;
            this.selectedEmployee.applicant.total_documents = payload.total_documents ?? ((payload.all_documents ?? payload.documents ?? []).length);
            this.selectedEmployee.applicant.selected_folder_key = 'all';
            this.selectedEmployee.applicant.required_documents = payload.required_documents ?? [];
            this.selectedEmployee.applicant.required_documents_text = payload.required_documents_text ?? '';
            this.selectedEmployee.applicant.missing_documents = payload.missing_documents ?? [];
            this.selectedEmployee.applicant.document_notice = payload.document_notice ?? '';
            this.selectedEmployee.applicant.comparison = payload.comparison ?? this.selectedEmployee.applicant.comparison ?? { is_rehire: false, changed_fields: [], changed_degree_levels: [] };
          } catch (error) {
            console.error('Unable to load employee documents.', error);
          }
        },
        hasApplicantChange(fieldKey) {
          const changedFields = Array.isArray(this.selectedEmployee?.applicant?.comparison?.changed_fields)
            ? this.selectedEmployee.applicant.comparison.changed_fields
            : [];
          return changedFields.includes((fieldKey ?? '').toString());
        },
        degreeLevelIsNew(level) {
          const changedLevels = Array.isArray(this.selectedEmployee?.applicant?.comparison?.changed_degree_levels)
            ? this.selectedEmployee.applicant.comparison.changed_degree_levels
            : [];
          return changedLevels.includes((level ?? '').toString());
        },
        documentIsNew(doc) {
          return Boolean(doc?.is_new);
        },
        documentIsPreviousApplication(doc) {
          return Boolean(doc?.is_previous_application);
        },
        documentFolderKeyFromPath(path) {
          const normalized = (path ?? '').toString().replace(/\\/g, '/').replace(/^\/+|\/+$/g, '');
          const matches = normalized.match(/^uploads\/applicant-documents\/\d+\/([^/]+)\//i);
          if (!matches) return '';
          const key = (matches[1] ?? '').toString().trim();
          return key === '' || key.toLowerCase() === 'unfiled' ? '' : key;
        },
        applicantFolders() {
          return Array.isArray(this.selectedEmployee?.applicant?.folders)
            ? this.selectedEmployee.applicant.folders
            : [];
        },
        applicantAllDocuments() {
          return Array.isArray(this.selectedEmployee?.applicant?.all_documents)
            ? this.selectedEmployee.applicant.all_documents
            : [];
        },
        selectedDocumentFolderKey() {
          return (this.selectedEmployee?.applicant?.selected_folder_key ?? 'all').toString();
        },
        openDocumentFolder(folderKey = 'all') {
          if (!this.selectedEmployee?.applicant) return;
          this.selectedEmployee.applicant.selected_folder_key = (folderKey ?? 'all').toString();
        },
        selectedDocumentFolderName() {
          const folderKey = this.selectedDocumentFolderKey();
          if (folderKey === 'all') return 'Folders';
          if (folderKey === 'unfiled') return 'Unfiled';
          const folder = this.applicantFolders().find((item) => (item?.key ?? '').toString() === folderKey);
          return (folder?.name ?? 'Folder').toString();
        },
        displayedApplicantDocuments() {
          const folderKey = this.selectedDocumentFolderKey();
          const docs = this.applicantAllDocuments();
          if (folderKey === 'all') return [];
          if (folderKey === 'unfiled') {
            return docs.filter((doc) => this.documentFolderKeyFromPath(doc?.filepath) === '');
          }
          return docs.filter((doc) => this.documentFolderKeyFromPath(doc?.filepath) === folderKey);
        },
        currentDocumentCount() {
          const folderKey = this.selectedDocumentFolderKey();
          if (folderKey === 'all') {
            return Number.parseInt((this.selectedEmployee?.applicant?.total_documents ?? 0).toString(), 10) || 0;
          }
          return this.displayedApplicantDocuments().length;
        },
        removeMissingDocumentNeed(documentName) {
          const applicant = this.selectedEmployee?.applicant;
          if (!applicant) return;

          const normalize = (value) => this.normalize(value);
          const textItems = (applicant.required_documents_text ?? '')
            .split(/\r?\n|,/)
            .map(item => item.trim())
            .filter(item => item !== '');
          const baseItems = textItems.length ? textItems : (applicant.required_documents ?? []);
          const filteredItems = baseItems.filter(item => normalize(item) !== normalize(documentName));

          applicant.required_documents = filteredItems;
          applicant.required_documents_text = filteredItems.join('\n');
          applicant.missing_documents = (applicant.missing_documents ?? [])
            .filter(item => normalize(item) !== normalize(documentName));
        },
        leaveRows() {
          return Array.isArray(this.selectedEmployee?.leave_applications)
            ? this.selectedEmployee.leave_applications
            : [];
        },
        leaveStatusNormalized(value) {
          return (value ?? '').toString().trim().toLowerCase();
        },
        isApprovedLeaveStatus(value) {
          const status = this.leaveStatusNormalized(value);
          return status === 'approved' || status === 'completed';
        },
        numberOrZero(value) {
          const numeric = Number(value);
          return Number.isFinite(numeric) ? numeric : 0;
        },
        latestLeaveBalanceRow() {
          const rows = this.leaveRows();
          if (!rows.length) return null;

          const approvedRows = rows.filter(row => this.isApprovedLeaveStatus(row?.status));
          const sourceRows = approvedRows.length ? approvedRows : rows;

          const withSort = sourceRows
            .map((row) => ({
              row,
              sortDate: this.parseDateValue(row?.filing_date || row?.created_at),
            }))
            .sort((a, b) => {
              const ta = a.sortDate?.getTime() ?? 0;
              const tb = b.sortDate?.getTime() ?? 0;
              return tb - ta;
            });

          return withSort[0]?.row ?? null;
        },
        leaveVacationLimit() {
          const summary = this.selectedEmployee?.leave_summary ?? {};
          if (summary?.vacation_limit != null) {
            return Math.max(this.numberOrZero(summary.vacation_limit), 0);
          }
          const row = this.latestLeaveBalanceRow();
          if (!row) return 0;
          return Math.max(
            this.numberOrZero(row?.beginning_vacation) + this.numberOrZero(row?.earned_vacation),
            0
          );
        },
        leaveVacationAvailable() {
          const summary = this.selectedEmployee?.leave_summary ?? {};
          if (summary?.vacation_available != null) {
            return Math.max(this.numberOrZero(summary.vacation_available), 0);
          }
          const row = this.latestLeaveBalanceRow();
          if (!row) return 0;
          return Math.max(this.numberOrZero(row?.ending_vacation), 0);
        },
        leaveSickLimit() {
          const summary = this.selectedEmployee?.leave_summary ?? {};
          if (summary?.sick_limit != null) {
            return Math.max(this.numberOrZero(summary.sick_limit), 0);
          }
          const row = this.latestLeaveBalanceRow();
          if (!row) return 0;
          return Math.max(
            this.numberOrZero(row?.beginning_sick) + this.numberOrZero(row?.earned_sick),
            0
          );
        },
        leaveSickAvailable() {
          const summary = this.selectedEmployee?.leave_summary ?? {};
          if (summary?.sick_available != null) {
            return Math.max(this.numberOrZero(summary.sick_available), 0);
          }
          const row = this.latestLeaveBalanceRow();
          if (!row) return 0;
          return Math.max(this.numberOrZero(row?.ending_sick), 0);
        },
        leavePersonalLimit() {
          return 3;
        },
        leavePersonalUsed() {
          return this.leaveRows()
            .filter(row => this.isApprovedLeaveStatus(row?.status))
            .filter((row) => {
              const type = (row?.leave_type ?? '').toString().trim().toLowerCase();
              return type.includes('personal');
            })
            .reduce((sum, row) => sum + this.numberOrZero(row?.number_of_working_days), 0);
        },
        leavePersonalAvailable() {
          return Math.max(this.leavePersonalLimit() - this.leavePersonalUsed(), 0);
        },
        leaveBalancePercent(available, limit) {
          const safeLimit = this.numberOrZero(limit);
          if (safeLimit <= 0) return 0;
          const percent = (this.numberOrZero(available) / safeLimit) * 100;
          return Math.max(0, Math.min(100, percent));
        },
        formatLeaveNumber(value) {
          const normalized = Math.max(this.numberOrZero(value), 0);
          if (Number.isInteger(normalized)) {
            return normalized.toString();
          }
          return normalized.toFixed(1).replace(/\.0$/, '');
        },
        formatLeaveBalance(available, limit) {
          return `${this.formatLeaveNumber(available)}/${this.formatLeaveNumber(limit)}`;
        },
        parseDateValue(raw) {
          if (!raw) return null;
          const text = raw.toString().trim();
          const datePart = text.split('T')[0];
          let date = null;

          if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
            const [year, month, day] = datePart.split('-').map(Number);
            if (year && month && day) {
              date = new Date(year, month - 1, day);
            }
          } else if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(datePart)) {
            const [month, day, year] = datePart.split('/').map(Number);
            if (year && month && day) {
              date = new Date(year, month - 1, day);
            }
          }

          if (!date) return null;
          return Number.isNaN(date.getTime()) ? null : date;
        },
        leaveDateRange(row) {
          const inclusive = (row?.inclusive_dates ?? '').toString().trim();
          const matches = inclusive.match(/\b\d{4}-\d{2}-\d{2}\b|\b\d{1,2}\/\d{1,2}\/\d{4}\b/g) || [];
          let start = matches[0] ? this.parseDateValue(matches[0]) : null;
          let end = matches[1] ? this.parseDateValue(matches[1]) : null;

          if (!start) {
            start = this.parseDateValue(row?.filing_date || row?.created_at);
          }

          let days = this.numberOrZero(row?.number_of_working_days);
          if (days <= 0) {
            days = Math.max(this.numberOrZero(row?.days_with_pay), this.numberOrZero(row?.applied_total), 1);
          }

          if (!end && start) {
            end = new Date(start.getTime());
            end.setDate(end.getDate() + Math.max(Math.ceil(days), 1) - 1);
          }

          if (start && end && end.getTime() < start.getTime()) {
            const swap = start;
            start = end;
            end = swap;
          }

          return { start, end };
        },
        formatTimelineDate(raw) {
          const date = this.parseDateValue(raw);
          if (!date) return '-';
          return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        },
        formatServiceLength(startRaw, endRaw) {
          const start = this.parseDateValue(startRaw);
          const end = this.parseDateValue(endRaw);
          if (!start || !end) return '';
          if (end.getTime() < start.getTime()) return '';

          let years = end.getFullYear() - start.getFullYear();
          let months = end.getMonth() - start.getMonth();
          let days = end.getDate() - start.getDate();

          if (days < 0) {
            const previousMonthDays = new Date(end.getFullYear(), end.getMonth(), 0).getDate();
            days += previousMonthDays;
            months -= 1;
          }
          if (months < 0) {
            months += 12;
            years -= 1;
          }

          if (years < 0) return '';
          return `${years}.${months} years`;
        },
        normalizeClassificationValue(value) {
          return (value ?? '')
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[-_]/g, ' ')
            .replace(/\s+/g, ' ');
        },
        canonicalClassificationValue(value) {
          const normalized = this.normalizeClassificationValue(value);
          if (!normalized) return '';
          if (normalized.includes('permanent') || normalized.includes('regular')) return 'Permanent';
          if (normalized.includes('probationary')) return 'Probationary';
          if (normalized.includes('full')) return 'Full-Time';
          if (normalized.includes('part')) return 'Part-Time';
          if (normalized === 'nt' || normalized === 'non teaching' || normalized === 'non-teaching') return 'NT';
          if (normalized.includes('non teaching') || normalized.includes('non-teaching')) return 'NT';
          return (value ?? '').toString().trim();
        },
        ensureEmployeeClassification() {
          if (!this.selectedEmployee) return;
          if (!this.selectedEmployee.employee) this.selectedEmployee.employee = {};

          const current = this.canonicalClassificationValue(this.selectedEmployee.employee.classification);
          if (current) {
            this.selectedEmployee.employee.classification = current;
            return;
          }

          const fallback = this.canonicalClassificationValue(
            this.selectedEmployee?.applicant?.position?.employment
            || this.selectedEmployee?.employee?.job_type
            || this.selectedEmployee?.applicant?.position?.job_type
          );
          if (fallback) {
            this.selectedEmployee.employee.classification = fallback;
          }
        },
        resolveBiometricClassification() {
          const candidates = [
            this.selectedEmployee?.employee?.classification,
            this.selectedEmployee?.applicant?.position?.employment,
            this.selectedEmployee?.employee?.job_type,
            this.selectedEmployee?.applicant?.position?.job_type,
          ];

          for (const candidate of candidates) {
            const canonical = this.canonicalClassificationValue(candidate);
            if (!canonical) continue;
            return this.normalizeClassificationValue(canonical);
          }

          return '';
        },
        isBiometricClassification(type) {
          return this.resolveBiometricClassification() === this.normalizeClassificationValue(type);
        },
        isProbationaryToPermanent(oldValue, newValue) {
          const oldNorm = this.normalizeClassificationValue(oldValue);
          const newNorm = this.normalizeClassificationValue(newValue);
          return oldNorm === 'probationary' && newNorm === 'permanent';
        },
        countPromotionEvents() {
          const historyRows = Array.isArray(this.selectedEmployee?.position_histories)
            ? this.selectedEmployee.position_histories
            : [];

          return historyRows.filter((row) => {
            const oldPos = (row?.old_position ?? '').toString().trim().toLowerCase();
            const newPos = (row?.new_position ?? '').toString().trim().toLowerCase();
            const positionChanged = oldPos !== '' && newPos !== '' && oldPos !== newPos;
            const classPromotion = this.isProbationaryToPermanent(
              row?.old_classification,
              row?.new_classification
            );
            return positionChanged || classPromotion;
          }).length;
        },
        buildServiceTimeline() {
          const items = [];
          const hiredRaw = this.initialEmploymentStartRaw();
          const rehireRaw = this.rehireDateRaw();
          const positionTitle = this.selectedEmployee?.employee?.position || this.selectedEmployee?.applicant?.position?.title || this.selectedEmployee?.position || 'Employee';

          if (hiredRaw) {
            items.push({
              type: 'hire',
              badge: 'Date Hired',
              badgeClass: 'bg-green-100 text-green-700',
              dotClass: 'bg-indigo-500',
              title: 'Initial Employment Start',
              dateLabel: this.formatTimelineDate(hiredRaw),
              description: `Employee was hired as ${positionTitle}.`,
              sortKey: hiredRaw,
            });
          }

          if (rehireRaw) {
            items.push({
              type: 'rehire',
              badge: 'Rehired',
              badgeClass: 'bg-violet-100 text-violet-700',
              dotClass: 'bg-violet-500',
              title: 'Employee Rehired',
              dateLabel: this.formatTimelineDate(rehireRaw),
              description: `Employee was rehired as ${positionTitle}. Service length restarted from this date.`,
              sortKey: rehireRaw,
            });
          }

          const resignationRows = Array.isArray(this.selectedEmployee?.resignations)
            ? this.selectedEmployee.resignations
            : [];

          resignationRows.forEach((row) => {
            const status = (row?.status ?? '').toString().trim().toLowerCase();
            const submittedAt = row?.submitted_at || row?.created_at;
            const effectiveAt = row?.effective_date;
            const processedAt = row?.processed_at;

            if (submittedAt) {
              items.push({
                type: 'resignation-submitted',
                badge: 'Resignation Filed',
                badgeClass: 'bg-amber-100 text-amber-700',
                dotClass: 'bg-amber-500',
                title: 'Resignation Submission',
                dateLabel: this.formatTimelineDate(submittedAt),
                description: 'Employee submitted a resignation request.',
                sortKey: submittedAt,
              });
            }

            if (processedAt && (status === 'approved' || status === 'completed')) {
              const serviceLength = this.formatServiceLength(hiredRaw, effectiveAt || processedAt);
              const baseDescription = row?.admin_note || 'HR approved the resignation request.';
              items.push({
                type: 'resignation-approved',
                badge: 'Resignation Approved',
                badgeClass: 'bg-blue-100 text-blue-700',
                dotClass: 'bg-blue-500',
                title: 'Resignation Request Approved',
                dateLabel: this.formatTimelineDate(processedAt),
                description: serviceLength !== ''
                  ? `${baseDescription} Total length of service: ${serviceLength}.`
                  : baseDescription,
                sortKey: processedAt,
              });
            }

            if (processedAt && (status === 'rejected' || status === 'cancelled')) {
              items.push({
                type: status === 'rejected' ? 'resignation-rejected' : 'resignation-cancelled',
                badge: status === 'rejected' ? 'Resignation Rejected' : 'Resignation Cancelled',
                badgeClass: status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700',
                dotClass: status === 'rejected' ? 'bg-rose-500' : 'bg-slate-500',
                title: status === 'rejected' ? 'Resignation Request Rejected' : 'Resignation Request Cancelled',
                dateLabel: this.formatTimelineDate(processedAt),
                description: row?.admin_note || 'Status was updated by HR.',
                sortKey: processedAt,
              });
            }
          });

          const positionHistoryRows = Array.isArray(this.selectedEmployee?.position_histories)
            ? this.selectedEmployee.position_histories
            : [];

          positionHistoryRows.forEach((row) => {
            const newPosition = (row?.new_position ?? '').toString().trim();
            const changedAt = row?.changed_at || row?.created_at;
            if (!changedAt) return;

            const oldPosition = (row?.old_position ?? '').toString().trim();
            const oldClassification = (row?.old_classification ?? '').toString().trim();
            const newClassification = (row?.new_classification ?? '').toString().trim();
            const hasPositionChange = oldPosition !== '' && newPosition !== '' && oldPosition.toLowerCase() !== newPosition.toLowerCase();
            const hasProbationaryPromotion = this.isProbationaryToPermanent(oldClassification, newClassification);

            if (hasPositionChange) {
              items.push({
                type: 'promotion-position',
                badge: 'Promotion',
                badgeClass: 'bg-emerald-100 text-emerald-700',
                dotClass: 'bg-emerald-500',
                title: 'Position Update',
                dateLabel: this.formatTimelineDate(changedAt),
                description: `Position changed from ${oldPosition} to ${newPosition}.`,
                sortKey: changedAt,
              });
            }

            if (hasProbationaryPromotion) {
              items.push({
                type: 'promotion-classification',
                badge: 'Promotion',
                badgeClass: 'bg-emerald-100 text-emerald-700',
                dotClass: 'bg-emerald-500',
                title: 'Employment Status Update',
                dateLabel: this.formatTimelineDate(changedAt),
                description: 'Classification changed from Probationary to Permanent.',
                sortKey: changedAt,
              });
            }
          });

          return items.sort((a, b) => {
            const ta = this.parseDateValue(a.sortKey)?.getTime() ?? 0;
            const tb = this.parseDateValue(b.sortKey)?.getTime() ?? 0;
            return tb - ta;
          });
        },
      }"
      x-init="employeeIndex = @js(
        $employeeDirectory->map(fn($emp) => [
          'id' => (int) ($emp->id ?? 0),
          'name' => trim(($emp->last_name ?? '').', '.trim(($emp->first_name ?? '').' '.($emp->middle_name ?? '')), ', '),
          'department' => trim((string) (data_get($emp, 'applicant.position.department') ?: data_get($emp, 'employee.department') ?: ($emp->department ?? ''))),
          'status' => $resolveDisplayAccountStatus($emp),
          'has_missing_info' => collect([
            data_get($emp, 'employee.account_number'),
            data_get($emp, 'employee.sex') ?: data_get($emp, 'employee.gender'),
            data_get($emp, 'employee.civil_status'),
            data_get($emp, 'employee.contact_number') ?: data_get($emp, 'applicant.phone'),
            data_get($emp, 'employee.birthday'),
            data_get($emp, 'license.license'),
            data_get($emp, 'license.registration_number'),
            data_get($emp, 'government.SSS'),
            data_get($emp, 'government.TIN'),
            data_get($emp, 'government.PhilHealth'),
            data_get($emp, 'government.MID'),
            data_get($emp, 'government.RTN'),
            data_get($emp, 'salary.salary'),
          ])->contains(fn ($value) => $isMissingEmployeeValue($value)) || $employeeHasMissingAddressParts($emp),
        ])->values()
      ); employeeRecords = @js($employee->values()); employeeById = Object.fromEntries(employeeRecords
        .map((row) => {
          const userId = Number.parseInt((row?.id ?? '').toString(), 10);
          return Number.isFinite(userId) && userId > 0 ? [userId, row] : null;
        })
        .filter((entry) => Array.isArray(entry))
      ); openEmployeeFromQuery(); startAccountStatusPolling()"
>

    <!-- Header -->
    @php
      $employeeDirectory = collect($employeeDirectory ?? $employee);
      $employeeFilterState = array_merge([
        'search' => '',
        'department' => 'All',
        'status' => 'All',
        'per_page' => 10,
      ], $employeeFilters ?? []);
      $resolveDepartment = function ($emp) {
        return trim((string) (data_get($emp, 'applicant.position.department') ?: data_get($emp, 'employee.department') ?: ($emp->department ?? '')));
      };
      $parseLeaveStatusDate = function ($value) {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
          return null;
        }

        foreach (['Y-m-d', 'm/d/Y', 'n/j/Y'] as $format) {
          try {
            return \Carbon\Carbon::createFromFormat($format, $text)->startOfDay();
          } catch (\Throwable $e) {
          }
        }

        try {
          return \Carbon\Carbon::parse($text)->startOfDay();
        } catch (\Throwable $e) {
          return null;
        }
      };
      $resolveLeaveRange = function ($application) use ($parseLeaveStatusDate) {
        $inclusiveDates = trim((string) (data_get($application, 'inclusive_dates') ?? ''));
        $matchedDates = [];
        if ($inclusiveDates !== '') {
          preg_match_all('/\b\d{4}-\d{2}-\d{2}\b|\b\d{1,2}\/\d{1,2}\/\d{4}\b/', $inclusiveDates, $matches);
          $matchedDates = $matches[0] ?? [];
        }

        $startDate = isset($matchedDates[0]) ? $parseLeaveStatusDate($matchedDates[0]) : null;
        $endDate = isset($matchedDates[1]) ? $parseLeaveStatusDate($matchedDates[1]) : null;

        if (!$startDate) {
          $startDate = $parseLeaveStatusDate(data_get($application, 'filing_date'))
            ?: $parseLeaveStatusDate(data_get($application, 'created_at'));
        }

        $days = (float) (data_get($application, 'number_of_working_days') ?? 0);
        if ($days <= 0) {
          $days = max(
            (float) (data_get($application, 'days_with_pay') ?? 0),
            (float) (data_get($application, 'applied_total') ?? 0)
          );
        }

        $rangeDays = max((int) ceil($days), 1);
        if (!$endDate && $startDate) {
          $endDate = $startDate->copy()->addDays($rangeDays - 1);
        }

        if ($startDate && $endDate && $endDate->lt($startDate)) {
          [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
      };
      $resolveDisplayAccountStatus = function ($emp) use ($resolveLeaveRange, $wasRehiredAfterResignation) {
        $accountStatus = strtolower(trim((string) (data_get($emp, 'account_status') ?? '')));
        $isRehiredAfterResignation = $wasRehiredAfterResignation($emp);
        if ($accountStatus === 'inactive' && !$isRehiredAfterResignation) {
          return 'Inactive';
        }
        if ($accountStatus === 'on leave') {
          return 'On Leave';
        }

        $hasApprovedOrCompletedResignation = collect(data_get($emp, 'resignations', []))
          ->contains(function ($row) {
            $status = strtolower(trim((string) (data_get($row, 'status') ?? '')));
            return in_array($status, ['approved', 'completed'], true);
          });

        if ($hasApprovedOrCompletedResignation && !$isRehiredAfterResignation) {
          return 'Inactive';
        }

        $today = \Carbon\Carbon::today();
        $hasApprovedLeaveToday = collect(data_get($emp, 'leave_applications', []))
          ->contains(function ($row) use ($resolveLeaveRange, $today) {
            $status = strtolower(trim((string) (data_get($row, 'status') ?? '')));
            if ($status !== 'approved') {
              return false;
            }

            [$startDate, $endDate] = $resolveLeaveRange($row);
            if (!$startDate || !$endDate) {
              return false;
            }

            return $today->betweenIncluded($startDate, $endDate);
          });

        return $hasApprovedLeaveToday ? 'On Leave' : 'Active';
      };
      $blankTableValue = function ($value) {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
          return '';
        }

        $normalized = strtolower($text);
        if (in_array($normalized, ['n/a', 'na', '-'], true)) {
          return '';
        }

        return $text;
      };

      $employeeTableRecords = $employee->map(function ($emp, $index) use ($resolveDepartment, $blankTableValue, $resolveDisplayAccountStatus, $wasRehiredAfterResignation, $isMissingEmployeeValue, $employeeHasMissingAddressParts, $displayEmployeeId) {
        $themeSeed = (string) ($emp->id ?? data_get($emp, 'employee.employee_id') ?? $index);
        $hue = ((int) sprintf('%u', crc32($themeSeed))) % 360;
        $headerStart = "hsl({$hue}, 78%, 58%)";
        $headerEndHue = ($hue + 36) % 360;
        $headerEnd = "hsl({$headerEndHue}, 78%, 46%)";
        $classValue = trim((string) (data_get($emp, 'employee.classification') ?: data_get($emp, 'applicant.position.employment') ?: ($emp->classification ?? '')));
        $employmentCode = match (strtolower($classValue)) {
          'full-time', 'full time' => 'FT',
          'part-time', 'part time' => 'PT',
          default => (str_contains(strtolower($classValue), 'part') ? 'PT' : (str_contains(strtolower($classValue), 'full') || str_contains(strtolower($classValue), 'probationary') || str_contains(strtolower($classValue), 'permanent') ? 'FT' : '')),
        };
        $jobTypeValue = trim((string) (data_get($emp, 'applicant.position.job_type') ?: data_get($emp, 'employee.job_type') ?: ($emp->job_type ?? '')));
        $normalizedJobType = strtolower($jobTypeValue);
        $jobTypeCode = match (true) {
          in_array(strtoupper($jobTypeValue), ['NT'], true),
          str_contains($normalizedJobType, 'non-teaching'),
          str_contains($normalizedJobType, 'non teaching') => 'NT',
          in_array(strtoupper($jobTypeValue), ['T'], true),
          str_contains($normalizedJobType, 'teaching'),
          str_contains($normalizedJobType, 'teacher'),
          str_contains($normalizedJobType, 'faculty') => 'T',
          default => $jobTypeValue,
        };
        $classDisplay = collect([$employmentCode, $jobTypeCode])->filter(fn ($value) => trim((string) $value) !== '')->implode('/');
        $birthdayRaw = data_get($emp, 'employee.birthday') ?: ($emp->birthday ?? '');
        $birthdayDisplay = '';
        $ageToDate = '';
        if (!empty($birthdayRaw)) {
          try {
            $birthday = \Carbon\Carbon::parse($birthdayRaw);
            $birthdayDisplay = $birthday->format('F j, Y');
            $ageToDate = (string) $birthday->age;
          } catch (\Throwable $e) {
            $birthdayDisplay = trim((string) $birthdayRaw);
          }
        }

        $isRehiredAfterResignation = $wasRehiredAfterResignation($emp);
        $employmentDateRaw = $isRehiredAfterResignation
          ? (data_get($emp, 'applicant.date_hired')
            ?: data_get($emp, 'applicant.created_at')
            ?: data_get($emp, 'employee.employement_date')
            ?: data_get($emp, 'employee.employment_date')
            ?: ($emp->date_hired ?? ''))
          : (data_get($emp, 'applicant.date_hired')
            ?: data_get($emp, 'employee.employement_date')
            ?: data_get($emp, 'employee.employment_date')
            ?: ($emp->date_hired ?? ''));
        $employmentDateDisplay = '';
        $lengthOfService = '';
        $dateResigned = collect(data_get($emp, 'resignations', []))
          ->first(function ($row) {
            return in_array(strtolower(trim((string) ($row->status ?? ''))), ['approved', 'completed'], true);
          });
        $resignationEndDateRaw = $dateResigned?->effective_date ?: $dateResigned?->processed_at;

        if (!empty($employmentDateRaw)) {
          try {
            $employmentDate = \Carbon\Carbon::parse($employmentDateRaw);
            $employmentDateDisplay = $employmentDate->format('F j, Y');
            $serviceEndDate = (!$isRehiredAfterResignation && !empty($resignationEndDateRaw))
              ? \Carbon\Carbon::parse($resignationEndDateRaw)
              : now();
            $lengthOfService = $employmentDate->diffForHumans($serviceEndDate, [
              'parts' => 3,
              'short' => false,
              'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
            ]);
          } catch (\Throwable $e) {
            $employmentDateDisplay = trim((string) $employmentDateRaw);
          }
        }
        $dateResignedDisplay = '';
        if (!empty($resignationEndDateRaw)) {
          try {
            $dateResignedDisplay = \Carbon\Carbon::parse($resignationEndDateRaw)->format('F j, Y');
          } catch (\Throwable $e) {
            $dateResignedDisplay = trim((string) $resignationEndDateRaw);
          }
        }

        $employmentHistoryDisplay = collect(data_get($emp, 'position_histories', []))
          ->map(function ($history) {
            $oldPosition = trim((string) ($history->old_position ?? ''));
            $newPosition = trim((string) ($history->new_position ?? ''));
            if ($oldPosition !== '' && $newPosition !== '' && strcasecmp($oldPosition, $newPosition) !== 0) {
              return $oldPosition.' to '.$newPosition;
            }
            return $newPosition !== '' ? $newPosition : $oldPosition;
          })
          ->filter()
          ->implode(' | ');

          $missingRequiredDocumentsCount = (int) data_get($emp, 'missing_required_documents_count', 0);
          $employeeIdDisplay = $displayEmployeeId($emp, '');

          $hasMissingInfo = collect([
          data_get($emp, 'employee.account_number'),
          data_get($emp, 'employee.sex') ?: data_get($emp, 'employee.gender'),
          data_get($emp, 'employee.civil_status'),
          data_get($emp, 'employee.contact_number') ?: data_get($emp, 'applicant.phone'),
          data_get($emp, 'employee.birthday'),
          data_get($emp, 'license.license'),
          data_get($emp, 'license.registration_number'),
          data_get($emp, 'government.SSS'),
          data_get($emp, 'government.TIN'),
          data_get($emp, 'government.PhilHealth'),
          data_get($emp, 'government.MID'),
          data_get($emp, 'government.RTN'),
          data_get($emp, 'salary.salary'),
        ])->contains(fn ($value) => $isMissingEmployeeValue($value))
          || $employeeHasMissingAddressParts($emp)
          || $missingRequiredDocumentsCount > 0;

        return [
          'no' => $index + 1,
          'name' => $blankTableValue(trim(($emp->last_name ?? '').', '.trim(($emp->first_name ?? '').' '.($emp->middle_name ?? '')), ', ')),
          'employee_id' => $blankTableValue($employeeIdDisplay),
          'account_number' => $blankTableValue(trim((string) (data_get($emp, 'employee.account_number') ?: data_get($emp, 'employee.user_id') ?: ($emp->account_number ?? $emp->id ?? '')))),
          'sex' => $blankTableValue(trim((string) (data_get($emp, 'employee.sex') ?: data_get($emp, 'employee.gender') ?: ($emp->gender ?? '')))),
          'civil_status' => $blankTableValue(trim((string) (data_get($emp, 'employee.civil_status') ?: ($emp->civil_status ?? '')))),
          'address' => $blankTableValue(trim((string) (data_get($emp, 'employee.address') ?: data_get($emp, 'applicant.address') ?: ($emp->address ?? '')))),
          'contact_number' => $blankTableValue(trim((string) (data_get($emp, 'employee.contact_number') ?: data_get($emp, 'applicant.contact_number') ?: ($emp->contact_number ?? '')))),
          'date_of_birth' => $blankTableValue($birthdayDisplay),
          'age_to_date' => $blankTableValue($ageToDate),
          'employment_date' => $blankTableValue($employmentDateDisplay),
          'length_of_service' => $blankTableValue($lengthOfService),
          'position' => $blankTableValue(trim((string) ($emp->job_role ?? data_get($emp, 'employee.position') ?? ($emp->position ?? data_get($emp, 'applicant.position.title') ?? '')))),
          'department' => $blankTableValue($resolveDepartment($emp)),
          'class' => $blankTableValue($classDisplay),
          'rank' => $blankTableValue(trim((string) (data_get($emp, 'employee.rank') ?: ($emp->rank ?? '')))),
          'grade' => $blankTableValue(trim((string) (data_get($emp, 'employee.grade') ?: ($emp->grade ?? '')))),
          'sss' => $blankTableValue(trim((string) (data_get($emp, 'government.SSS') ?: ''))),
          'tin' => $blankTableValue(trim((string) (data_get($emp, 'government.TIN') ?: ''))),
          'philhealth' => $blankTableValue(trim((string) (data_get($emp, 'government.PhilHealth') ?: ''))),
          'pagibig_mid' => $blankTableValue(trim((string) (data_get($emp, 'government.MID') ?: ''))),
          'pagibig_rtn' => $blankTableValue(trim((string) (data_get($emp, 'government.RTN') ?: ''))),
          'bachelors_degree' => $blankTableValue(trim((string) (data_get($emp, 'education.bachelor') ?: data_get($emp, 'applicant.bachelor_degree') ?: ''))),
          'masters_degree' => $blankTableValue(trim((string) (data_get($emp, 'education.master') ?: data_get($emp, 'applicant.master_degree') ?: ''))),
          'doctorate_degree' => $blankTableValue(trim((string) (data_get($emp, 'education.doctorate') ?: data_get($emp, 'applicant.doctoral_degree') ?: ''))),
          'eligibility' => $blankTableValue(trim((string) (data_get($emp, 'license.license') ?: ''))),
          'registration_number' => $blankTableValue(trim((string) (data_get($emp, 'license.registration_number') ?: ''))),
          'registration_date' => $blankTableValue(!empty(data_get($emp, 'license.registration_date'))
            ? \Carbon\Carbon::parse(data_get($emp, 'license.registration_date'))->format('F j, Y')
            : ''),
          'valid_until' => $blankTableValue(!empty(data_get($emp, 'license.valid_until'))
            ? \Carbon\Carbon::parse(data_get($emp, 'license.valid_until'))->format('F j, Y')
            : ''),
          'rate_per_hour' => $blankTableValue(trim((string) (data_get($emp, 'salary.rate_per_hour') ?: ''))),
          'basic_salary' => $blankTableValue(trim((string) (data_get($emp, 'salary.salary') ?: ''))),
          'allowance' => $blankTableValue(trim((string) (data_get($emp, 'salary.cola') ?: ''))),
          'date_resigned' => $blankTableValue($dateResignedDisplay),
          'employment_history' => $blankTableValue($employmentHistoryDisplay),
          'status' => $resolveDisplayAccountStatus($emp),
          'has_missing_info' => $hasMissingInfo,
          'user_id' => (int) ($emp->id ?? 0),
          'header_start' => $headerStart,
          'header_end' => $headerEnd,
        ];
      })->values();

      $departmentOptions = $employeeDirectory
        ->map(fn($emp) => $resolveDepartment($emp))
        ->filter(fn($dept) => $dept !== '')
        ->unique(fn($dept) => strtolower($dept))
        ->sort()
        ->values();
      $departmentStaffingSummary = $employeeDirectory
        ->groupBy(fn ($emp) => ($resolveDepartment($emp) !== '' ? $resolveDepartment($emp) : 'Unassigned'))
        ->map(function ($departmentEmployees, $department) {
          $employeeFlags = $departmentEmployees->map(function ($emp) {
            $jobRoleValue = trim((string) ($emp->job_role ?? ''));
            $positionFieldValue = trim((string) (data_get($emp, 'employee.position') ?? ($emp->position ?? data_get($emp, 'applicant.position.title') ?? '')));
            $positionTitle = trim((string) ($positionFieldValue !== '' ? $positionFieldValue : $jobRoleValue));
            $rankValue = trim((string) (data_get($emp, 'employee.rank') ?: ($emp->rank ?? '')));
            $jobTypeValue = trim((string) (data_get($emp, 'applicant.position.job_type') ?: data_get($emp, 'employee.job_type') ?: ($emp->job_type ?? '')));
            $classificationValue = trim((string) (data_get($emp, 'employee.classification') ?: data_get($emp, 'applicant.position.employment') ?: ($emp->classification ?? '')));
            $jobRoleText = strtolower($jobRoleValue);
            $positionFieldText = strtolower($positionFieldValue);
            $positionText = strtolower($positionTitle);
            $rankText = strtolower($rankValue);
            $jobTypeText = strtolower($jobTypeValue);
            $classificationText = strtolower($classificationValue);
            $combinedRoleText = trim($positionText.' '.$rankText);
            $normalizedRoleText = preg_replace('/[^a-z0-9]+/i', ' ', $combinedRoleText);
            $normalizedRoleText = trim((string) preg_replace('/\s+/', ' ', (string) $normalizedRoleText));
            $serviceRecordRows = collect(is_array(data_get($emp, 'employee.service_record_rows')) ? data_get($emp, 'employee.service_record_rows') : []);
            $isNonTeachingJobType = str_contains($jobTypeText, 'non-teaching')
              || str_contains($jobTypeText, 'non teaching')
              || trim($jobTypeText) === 'nt';
            $isTeachingJobType = !$isNonTeachingJobType && (
              str_contains($jobTypeText, 'teaching')
              || str_contains($jobTypeText, 'faculty')
              || trim($jobTypeText) === 't'
            );

            $containsRoleKeyword = static function (string $needle) use ($combinedRoleText, $normalizedRoleText): bool {
              $needle = strtolower(trim($needle));
              if ($needle === '') {
                return false;
              }

              return str_contains($combinedRoleText, $needle) || str_contains($normalizedRoleText, str_replace('&', 'and', $needle));
            };
            $containsDeanKeyword = static function (?string $value): bool {
              $text = strtolower(trim((string) ($value ?? '')));
              if ($text === '') {
                return false;
              }

              $normalized = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9]+/i', ' ', $text)));
              return str_contains($text, 'dean') || str_contains($normalized, 'dean');
            };
            $latestServiceRecordAction = $serviceRecordRows
              ->reverse()
              ->map(function ($row) use ($containsDeanKeyword) {
                $designation = trim((string) (data_get($row, 'designation') ?? ''));
                $remarks = trim((string) (data_get($row, 'remarks') ?? ''));
                $action = null;
                if (preg_match('/\bpromoted\b/i', $remarks) === 1) {
                  $action = 'promoted';
                } elseif (preg_match('/\b(resigned|resign)\b/i', $remarks) === 1) {
                  $action = 'resigned';
                }

                return [
                  'has_content' => $designation !== '' || $remarks !== '',
                  'matches_dean' => $containsDeanKeyword($designation) || $containsDeanKeyword($remarks),
                  'action' => $action,
                ];
              })
              ->first(fn ($row) => $row['has_content'] ?? false);
            $hasActiveDeanServiceRecord = ($latestServiceRecordAction['matches_dean'] ?? false)
              && (($latestServiceRecordAction['action'] ?? null) !== 'resigned');

            $isCoordinator = str_contains($combinedRoleText, 'coordinator') || str_contains($combinedRoleText, 'coor');
            $isInstructorLike = str_contains($combinedRoleText, 'instructor')
              || str_contains($combinedRoleText, 'faculty')
              || str_contains($combinedRoleText, 'professor')
              || str_contains($combinedRoleText, 'proffesor')
              || str_contains($combinedRoleText, 'profesor')
              || str_contains($combinedRoleText, 'lecturer')
              || str_contains($combinedRoleText, 'teacher');
            $isInstructor = $isInstructorLike && !$isNonTeachingJobType;
            $isVicePresidentRole = preg_match('/\b(v\.?\s*p\.?|vice president)\b/i', $jobRoleValue) === 1;
            $isTeachingTopHeadRole =
              $containsRoleKeyword('dean')
              || $containsRoleKeyword('college dean')
              || $containsRoleKeyword('executive dean')
              || $containsRoleKeyword('associate dean')
              || $containsRoleKeyword('assistant dean')
              || $containsRoleKeyword('program head')
              || $containsRoleKeyword('department head')
              || $containsRoleKeyword('head')
              || $containsRoleKeyword('chairperson')
              || $containsRoleKeyword('chairman')
              || $containsRoleKeyword('department chair')
              || $containsRoleKeyword('program chair')
              || str_contains($combinedRoleText, 'chair ')
              || str_ends_with($combinedRoleText, ' chair');
            $isTeachingSubordinateRole =
              $containsRoleKeyword('vice dean')
              || $containsRoleKeyword('assistant dean')
              || $containsRoleKeyword('associate dean')
              || $containsRoleKeyword('coordinator')
              || $containsRoleKeyword('coor');
            $isNonTeachingHeadRole =
              $containsRoleKeyword('dean')
              || $containsRoleKeyword('legal counsel')
              || $containsRoleKeyword('director')
              || preg_match('/\b(o\.?\s*i\.?\s*c\.?|office in ?charge|office incharge)\b/i', $combinedRoleText) === 1
              || $containsRoleKeyword('school treasurer')
              || $containsRoleKeyword('school accountant')
              || $containsRoleKeyword('chief librarian')
              || $containsRoleKeyword('guidance counselor')
              || $containsRoleKeyword('guidance counsellor')
              || $containsRoleKeyword('focal person')
              || $containsRoleKeyword('coordinator')
              || $containsRoleKeyword('principal')
              || $containsRoleKeyword('building property custodian')
              || $containsRoleKeyword('building and property custodian')
              || $containsRoleKeyword('building & property custodian')
              || $containsRoleKeyword('supervisor');
            $isTeachingTrack = $isInstructor
              || str_contains($jobTypeText, 'teaching')
              || str_contains($jobTypeText, 'faculty')
              || $containsRoleKeyword('dean')
              || $containsRoleKeyword('college dean')
              || $containsRoleKeyword('executive dean')
              || $containsRoleKeyword('associate dean')
              || $containsRoleKeyword('assistant dean')
              || $containsRoleKeyword('vice dean')
              || $containsRoleKeyword('program head')
              || $containsRoleKeyword('department head')
              || $containsRoleKeyword('head')
              || $containsRoleKeyword('chairperson')
              || $containsRoleKeyword('chairman')
              || $containsRoleKeyword('department chair')
              || $containsRoleKeyword('program chair')
              || str_contains($combinedRoleText, 'chair ')
              || str_ends_with($combinedRoleText, ' chair');
            $isDirectLeadershipHead = $jobRoleText === 'president'
              || $positionFieldText === 'dean'
              || $isVicePresidentRole
              || $hasActiveDeanServiceRecord
              || $isTeachingTopHeadRole
              || $isNonTeachingHeadRole;
            $isHead = $isDirectLeadershipHead || (!$isCoordinator && !$isInstructor && (
              $containsRoleKeyword('head')
              || $containsRoleKeyword('chief')
              || $containsRoleKeyword('dean')
              || $containsRoleKeyword('director')
              || $containsRoleKeyword('president')
              || preg_match('/\b(v\.?\s*p\.?|vice president)\b/i', $combinedRoleText) === 1
              || $containsRoleKeyword('registrar')
              || $containsRoleKeyword('chairperson')
              || $containsRoleKeyword('chairman')
              || str_contains($combinedRoleText, 'chair ')
              || str_ends_with($combinedRoleText, ' chair')
              || $containsRoleKeyword('legal counsel')
              || preg_match('/\b(o\.?\s*i\.?\s*c\.?|office in ?charge|office incharge)\b/i', $combinedRoleText) === 1
              || $containsRoleKeyword('school treasurer')
              || $containsRoleKeyword('school accountant')
              || $containsRoleKeyword('chief librarian')
              || $containsRoleKeyword('guidance counselor')
              || $containsRoleKeyword('guidance counsellor')
              || $containsRoleKeyword('focal person')
              || $containsRoleKeyword('coordinator')
              || $containsRoleKeyword('principal')
              || $containsRoleKeyword('building property custodian')
              || $containsRoleKeyword('building and property custodian')
              || $containsRoleKeyword('building & property custodian')
              || $containsRoleKeyword('manager')
              || $containsRoleKeyword('supervisor')
            ));

            return [
              'classification_text' => $classificationText,
              'is_coordinator' => $isCoordinator,
              'is_instructor' => $isInstructor,
              'is_teaching_track' => $isTeachingTrack,
              'is_teaching_job_type' => $isTeachingJobType,
              'is_teaching_top_head' => $isTeachingTopHeadRole || $jobRoleText === 'president' || $isVicePresidentRole,
              'is_teaching_subordinate' => $isTeachingSubordinateRole,
              'is_non_teaching_head' => $isNonTeachingHeadRole,
              'is_head' => $isHead,
            ];
          })->values();

          $hasHigherTeachingHeadInDepartment = $employeeFlags->contains(function ($flags) {
            return ($flags['is_teaching_track'] ?? false) && ($flags['is_teaching_top_head'] ?? false);
          });

          $summary = [
            'department' => $department,
            'heads' => 0,
            'coordinator' => 0,
            'staff' => 0,
            'instructors_ft' => 0,
            'instructors_pt' => 0,
            'total' => 0,
            'is_teaching_department' => $employeeFlags->contains(function ($flags) {
              return (bool) ($flags['is_teaching_job_type'] ?? false);
            }),
          ];

          foreach ($employeeFlags as $flags) {
            $shouldDowngradeTeachingRoleToCoordinator = $hasHigherTeachingHeadInDepartment
              && ($flags['is_teaching_track'] ?? false)
              && ($flags['is_teaching_subordinate'] ?? false)
              && !($flags['is_non_teaching_head'] ?? false);

            if (($flags['is_head'] ?? false) && !$shouldDowngradeTeachingRoleToCoordinator) {
              $summary['heads']++;
            } elseif (($flags['is_coordinator'] ?? false) || $shouldDowngradeTeachingRoleToCoordinator) {
              $summary['coordinator']++;
            } elseif ($flags['is_instructor'] ?? false) {
              if (str_contains($flags['classification_text'] ?? '', 'part-time') || str_contains($flags['classification_text'] ?? '', 'part time')) {
                $summary['instructors_pt']++;
              } else {
                $summary['instructors_ft']++;
              }
            } else {
              $summary['staff']++;
            }

            $summary['total']++;
          }

          $summary['is_teaching_department'] = (bool) ($summary['is_teaching_department'] ?? false)
            || (int) ($summary['instructors_ft'] ?? 0) > 0
            || (int) ($summary['instructors_pt'] ?? 0) > 0;

          return $summary;
        })
        ->sort(function ($a, $b) {
          $departmentA = strtolower(trim((string) ($a['department'] ?? '')));
          $departmentB = strtolower(trim((string) ($b['department'] ?? '')));

          $resolveDepartmentPriority = static function (array $row, string $department): int {
            $isTeachingDepartment = (bool) ($row['is_teaching_department'] ?? false);

            if (!$isTeachingDepartment) {
              return 0;
            }

            if (str_contains($department, 'graduate school')) {
              return 1;
            }

            if (str_contains($department, 'law') || str_contains($department, 'jd')) {
              return 2;
            }

            if (str_contains($department, 'bec')) {
              return 4;
            }

            return 3;
          };

          $priorityA = $resolveDepartmentPriority($a, $departmentA);
          $priorityB = $resolveDepartmentPriority($b, $departmentB);

          if ($priorityA === $priorityB) {
            return strnatcasecmp($departmentA, $departmentB);
          }

          return $priorityA <=> $priorityB;
        })
        ->values()
        ->all();
      $departmentStaffingSummary = collect($departmentStaffingSummary);
      $departmentStaffingTotals = [
        'heads' => $departmentStaffingSummary->sum('heads'),
        'coordinator' => $departmentStaffingSummary->sum('coordinator'),
        'staff' => $departmentStaffingSummary->sum('staff'),
        'instructors_ft' => $departmentStaffingSummary->sum('instructors_ft'),
        'instructors_pt' => $departmentStaffingSummary->sum('instructors_pt'),
        'total' => $departmentStaffingSummary->sum('total'),
      ];
      $nonTeachingCategoryTotal = (int) ($departmentStaffingTotals['heads'] ?? 0)
        + (int) ($departmentStaffingTotals['coordinator'] ?? 0)
        + (int) ($departmentStaffingTotals['staff'] ?? 0);
      $teachingCategoryTotal = (int) ($departmentStaffingTotals['instructors_ft'] ?? 0)
        + (int) ($departmentStaffingTotals['instructors_pt'] ?? 0);
      $employeeCategoryTotals = $employeeDirectory->reduce(function ($carry, $emp) use ($resolveDepartment) {
        $department = strtolower(trim((string) ($resolveDepartment($emp) ?? '')));
        $jobTypeValue = strtolower(trim((string) (data_get($emp, 'applicant.position.job_type') ?: data_get($emp, 'employee.job_type') ?: ($emp->job_type ?? ''))));
        $classificationValue = strtolower(trim((string) (data_get($emp, 'employee.classification') ?: data_get($emp, 'applicant.position.employment') ?: ($emp->classification ?? ''))));
        $isNonTeaching = str_contains($jobTypeValue, 'non-teaching')
          || str_contains($jobTypeValue, 'non teaching')
          || $jobTypeValue === 'nt';
        $isTeaching = !$isNonTeaching && (
          str_contains($jobTypeValue, 'teaching')
          || str_contains($jobTypeValue, 'faculty')
          || $jobTypeValue === 't'
        );

        if ($isNonTeaching) {
          $carry['non_teaching']++;
        }

        if ($isTeaching) {
          $carry['teaching']++;

          if (
            str_contains($department, 'graduate school')
            || str_contains($department, 'law')
            || str_contains($department, 'jd')
          ) {
            $carry['law_gsc']++;
            if (str_contains($classificationValue, 'part-time') || str_contains($classificationValue, 'part time')) {
              $carry['law_gsc_pt']++;
            } else {
              $carry['law_gsc_ft']++;
            }
          } elseif (str_contains($department, 'bec')) {
            $carry['bec']++;
          } else {
            $carry['college']++;
          }
        }

        $carry['grand_total']++;

        return $carry;
      }, [
        'non_teaching' => 0,
        'teaching' => 0,
        'college' => 0,
        'bec' => 0,
        'law_gsc' => 0,
        'law_gsc_ft' => 0,
        'law_gsc_pt' => 0,
        'grand_total' => 0,
      ]);
      $tableSummaryEmploymentDate = $employeeTableRecords->first()['employment_date'] ?? 'February 27, 2026';
    @endphp
    @include('components.adminHeader.employeeHeader', ['departmentOptions' => $departmentOptions])

    <div
      x-cloak
      x-show="employeeFilterLoading"
      x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in duration-150"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      class="pointer-events-none fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/5 backdrop-blur-[1px]"
      role="status"
      aria-live="polite"
      aria-label="Searching employees"
    >
      <div class="flex min-w-[15rem] flex-col items-center rounded-[1.75rem] border border-white/80 bg-white/95 px-8 py-7 shadow-[0_24px_70px_rgba(15,23,42,0.22)]">
        <img
          src="{{ asset('images/animation_searching.gif') }}?v={{ filemtime(public_path('images/animation_searching.gif')) }}"
          alt=""
          aria-hidden="true"
          class="h-28 w-28 object-contain"
        >
        <p class="mt-4 text-sm font-black tracking-wide text-slate-800">AI Search</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Finding matching employees...</p>
      </div>
    </div>

    <!-- ================= DASHBOARD CONTENT ================= -->
<div id="admin-employee-page" class="min-w-0 p-4 md:p-8 space-y-6 pt-20">

    <!-- TOP BAR -->
<div class="employee-status-legend-bar flex flex-wrap items-center justify-between gap-4">

    <!-- Status Legend -->
    <div class="flex items-center gap-6 text-sm text-gray-600">

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
            Active
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
            On Leave
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            Inactive
        </div>

    </div>
    <!-- FILTER BAR -->



</div>




    <div
      id="department-staffing-summary"
      x-show="showDepartmentSummary"
      style="display:none;"
      class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.08)]"
    >
      <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
        <div>
          <h3 class="text-lg font-black tracking-tight text-slate-900">Department Staffing Summary</h3>
          <p class="text-sm text-slate-600">Counts are grouped by department using the current employee records.</p>
        </div>
        <div class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
          {{ $departmentStaffingTotals['total'] }} Total Employees
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm text-slate-800">
          <thead>
            <tr class="bg-[#eef6f4] text-center text-xs font-black uppercase tracking-[0.08em] text-slate-900">
              <th rowspan="2" class="border border-slate-300 px-3 py-3">No.</th>
              <th rowspan="2" class="border border-slate-300 px-4 py-3 text-left">Department</th>
              <th rowspan="2" class="border border-slate-300 px-3 py-3">Heads</th>
              <th rowspan="2" class="border border-slate-300 px-3 py-3">Coor</th>
              <th rowspan="2" class="border border-slate-300 px-3 py-3">Staff</th>
              <th colspan="2" class="border border-slate-300 px-3 py-3">Instructors</th>
              <th rowspan="2" class="border border-slate-300 px-3 py-3">Total</th>
            </tr>
            <tr class="bg-[#eef6f4] text-center text-xs font-black uppercase tracking-[0.08em] text-slate-900">
              <th class="border border-slate-300 px-3 py-2">FT</th>
              <th class="border border-slate-300 px-3 py-2">PT</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($departmentStaffingSummary as $summaryRow)
              <tr class="odd:bg-white even:bg-slate-50/70">
                <td class="border border-slate-300 px-3 py-2 text-center font-semibold">{{ $loop->iteration }}</td>
                <td class="border border-slate-300 px-4 py-2 font-semibold uppercase tracking-[0.03em] text-slate-900">{{ $summaryRow['department'] }}</td>
                <td class="border border-slate-300 px-3 py-2 text-center">{{ $summaryRow['heads'] ?: '-' }}</td>
                <td class="border border-slate-300 px-3 py-2 text-center">{{ $summaryRow['coordinator'] ?: '-' }}</td>
                <td class="border border-slate-300 px-3 py-2 text-center">{{ $summaryRow['staff'] ?: '-' }}</td>
                <td class="border border-slate-300 px-3 py-2 text-center">{{ $summaryRow['instructors_ft'] ?: '-' }}</td>
                <td class="border border-slate-300 px-3 py-2 text-center">{{ $summaryRow['instructors_pt'] ?: '-' }}</td>
                <td class="border border-slate-300 px-3 py-2 text-center font-black">{{ $summaryRow['total'] }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="border border-slate-300 px-4 py-6 text-center text-slate-500">No department summary available.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr class="bg-slate-900 text-center text-sm font-black uppercase tracking-[0.08em] text-white">
              <td colspan="2" class="border border-slate-300 px-4 py-3">Total</td>
              <td class="border border-slate-300 px-3 py-3">{{ $departmentStaffingTotals['heads'] ?: '-' }}</td>
              <td class="border border-slate-300 px-3 py-3">{{ $departmentStaffingTotals['coordinator'] ?: '-' }}</td>
              <td class="border border-slate-300 px-3 py-3">{{ $departmentStaffingTotals['staff'] ?: '-' }}</td>
              <td class="border border-slate-300 px-3 py-3">{{ $departmentStaffingTotals['instructors_ft'] ?: '-' }}</td>
              <td class="border border-slate-300 px-3 py-3">{{ $departmentStaffingTotals['instructors_pt'] ?: '-' }}</td>
              <td class="border border-slate-300 px-3 py-3">{{ $departmentStaffingTotals['total'] }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div
      x-show="showDepartmentSummary"
      class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/90 shadow-[0_18px_40px_rgba(15,23,42,0.08)]"
    >
      <div class="border-b border-slate-200 bg-[linear-gradient(135deg,rgba(248,250,252,0.96),rgba(240,253,250,0.94))] px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-[11px] font-black uppercase tracking-[0.28em] text-slate-500">Employee</p>
            <h4 class="mt-1 text-lg font-black tracking-tight text-slate-900">Category Totals</h4>
          </div>
          <div class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-emerald-700">
            {{ $employeeCategoryTotals['grand_total'] }} Employees
          </div>
        </div>
      </div>

      <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-[repeat(5,minmax(0,1fr))_1.2fr]">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
          <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">Non-Teaching</p>
          <div class="mt-3 flex items-end justify-between">
            <p class="text-3xl font-black text-slate-900">{{ $nonTeachingCategoryTotal }}</p>
            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-700">Staff</span>
          </div>
        </div>

        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4">
          <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-sky-600">Teaching</p>
          <div class="mt-3 flex items-end justify-between">
            <p class="text-3xl font-black text-sky-950">{{ $teachingCategoryTotal }}</p>
            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-sky-700">Faculty</span>
          </div>
        </div>

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-4">
          <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-indigo-600">College</p>
          <div class="mt-3 flex items-end justify-between">
            <p class="text-3xl font-black text-indigo-950">{{ $employeeCategoryTotals['college'] }}</p>
            <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-indigo-700">Main</span>
          </div>
        </div>

        <div class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-4">
          <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-teal-600">BEC</p>
          <div class="mt-3 flex items-end justify-between">
            <p class="text-3xl font-black text-teal-950">{{ $employeeCategoryTotals['bec'] }}</p>
            <span class="rounded-full bg-teal-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-teal-700">Basic Ed</span>
          </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
          <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-700">Law / GSC</p>
          <div class="mt-3 flex items-end justify-between gap-3">
            <p class="text-3xl font-black text-amber-950">{{ $employeeCategoryTotals['law_gsc'] }}</p>
            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-amber-700">JD + Grad</span>
          </div>
          <div class="mt-3 flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800">
            <span class="rounded-full bg-white/80 px-2.5 py-1">FT {{ $employeeCategoryTotals['law_gsc_ft'] }}</span>
            <span class="rounded-full bg-white/80 px-2.5 py-1">PT {{ $employeeCategoryTotals['law_gsc_pt'] }}</span>
          </div>
        </div>

        <div class="rounded-[24px] border border-emerald-200 bg-[linear-gradient(135deg,rgba(236,253,245,1),rgba(209,250,229,0.92))] px-5 py-4 shadow-[0_10px_30px_rgba(16,185,129,0.12)]">
          <p class="text-[11px] font-black uppercase tracking-[0.22em] text-emerald-700">Grand Total</p>
          <div class="mt-3 flex items-end justify-between gap-3">
            <p class="text-4xl font-black leading-none text-emerald-950">{{ $employeeCategoryTotals['grand_total'] }}</p>
            <span class="rounded-full bg-white/80 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-700">All Employees</span>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-6" data-employee-directory-results :aria-busy="employeeFilterLoading ? 'true' : 'false'">
    <!-- Employee Cards Grid -->
    <div class="employee-card-grid grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5" x-show="!showDepartmentSummary && viewMode === 'cards'">

        @foreach ($employee as $emp)
        @php
          $themeSeed = (string) ($emp->id ?? data_get($emp, 'employee.employee_id') ?? $loop->index);
          $hue = ((int) sprintf('%u', crc32($themeSeed))) % 360;
          $headerStart = "hsl({$hue}, 78%, 58%)";
          $headerEndHue = ($hue + 36) % 360;
          $headerEnd = "hsl({$headerEndHue}, 78%, 46%)";
          $avatarHue = ($hue + 18) % 360;
          $avatarColor = "hsl({$avatarHue}, 72%, 40%)";
          $profilePhotoDocument = optional($emp->applicant)->documents
            ?->first(function ($doc) {
              return strtoupper(trim((string) ($doc->type ?? ''))) === 'PROFILE_PHOTO' && !empty($doc->filepath);
            });
          if (!$profilePhotoDocument) {
            $profilePhotoDocument = optional($emp->applicant)->documents
              ?->first(function ($doc) {
                $mime = strtolower(trim((string) ($doc->mime_type ?? '')));
                $filename = strtolower(trim((string) ($doc->filename ?? '')));
                return (!empty($doc->filepath)) && (str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|gif|webp)$/i', $filename));
              });
          }
          $profilePhotoUrl = $profilePhotoDocument?->filepath ? asset('storage/'.$profilePhotoDocument->filepath) : null;
          $contactNumberValue = data_get($emp, 'employee.contact_number') ?: data_get($emp, 'applicant.phone');
          $sexValue = data_get($emp, 'employee.sex') ?: data_get($emp, 'employee.gender');
          $addressValue = data_get($emp, 'employee.address') ?: data_get($emp, 'applicant.address');
          $missingAddressParts = $employeeMissingAddressPartLabels($emp);

          $personalMissingCount = collect([
            data_get($emp, 'employee.account_number'),
            $sexValue,
            data_get($emp, 'employee.civil_status'),
            $contactNumberValue,
            data_get($emp, 'employee.birthday'),
            data_get($emp, 'government.SSS'),
            data_get($emp, 'government.TIN'),
            data_get($emp, 'government.PhilHealth'),
            data_get($emp, 'government.MID'),
            data_get($emp, 'government.RTN'),
          ])->filter(fn ($value) => $isMissingEmployeeValue($value))->count() + $missingAddressParts->count();

          $degreeRows = collect(is_array(data_get($emp, 'applicant.degrees')) ? data_get($emp, 'applicant.degrees') : []);
          $biometricDegreeMissingCount = collect(['bachelor', 'master', 'doctorate'])
            ->map(function ($level) use ($degreeRows, $emp, $isMissingEmployeeValue) {
              $rowsForLevel = $degreeRows->filter(function ($row) use ($level) {
                return strtolower(trim((string) data_get($row, 'degree_level'))) === $level;
              });

              if ($rowsForLevel->isNotEmpty()) {
                return $rowsForLevel
                  ->filter(function ($row) use ($isMissingEmployeeValue) {
                    return $isMissingEmployeeValue(data_get($row, 'degree_name'))
                      || $isMissingEmployeeValue(data_get($row, 'school_name'))
                      || $isMissingEmployeeValue(data_get($row, 'year_finished'));
                  })
                  ->count();
              }

              $fallback = match ($level) {
                'bachelor' => [
                  data_get($emp, 'education.bachelor'),
                  data_get($emp, 'applicant.bachelor_school_name'),
                  data_get($emp, 'applicant.bachelor_year_finished'),
                ],
                'master' => [
                  data_get($emp, 'education.master'),
                  data_get($emp, 'applicant.master_school_name'),
                  data_get($emp, 'applicant.master_year_finished'),
                ],
                default => [
                  data_get($emp, 'education.doctorate'),
                  data_get($emp, 'applicant.doctoral_school_name'),
                  data_get($emp, 'applicant.doctoral_year_finished'),
                ],
              };

              return collect($fallback)->contains(fn ($value) => $isMissingEmployeeValue($value)) ? 1 : 0;
            })
            ->sum();

          $biometricMissingCount = collect([
            $displayEmployeeId($emp, ''),
            data_get($emp, 'employee.account_number'),
            $sexValue,
            data_get($emp, 'employee.civil_status'),
            $contactNumberValue,
            data_get($emp, 'employee.birthday'),
            $addressValue,
            data_get($emp, 'license.license'),
            data_get($emp, 'license.registration_number'),
            data_get($emp, 'government.SSS'),
            data_get($emp, 'government.TIN'),
            data_get($emp, 'government.PhilHealth'),
            data_get($emp, 'government.MID'),
            data_get($emp, 'government.RTN'),
            data_get($emp, 'salary.salary'),
          ])->filter(fn ($value) => $isMissingEmployeeValue($value))->count() + $biometricDegreeMissingCount;

          $missingRequiredDocuments = collect(data_get($emp, 'missing_required_documents', []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();
          $documentsMissingCount = $missingRequiredDocuments->count();
          $missingCardCount = $personalMissingCount + $biometricMissingCount + $documentsMissingCount;
          $missingCardParts = collect();
          if ($personalMissingCount > 0) {
            $missingCardParts->push('Personal: '.$personalMissingCount);
          }
          if ($biometricMissingCount > 0) {
            $missingCardParts->push('Biometric: '.$biometricMissingCount);
          }
          if ($documentsMissingCount > 0) {
            $missingCardParts->push('Documents: '.$documentsMissingCount);
          }
          $missingCardTitle = $missingCardCount > 0
            ? 'Missing: '.$missingCardParts->implode(' | ')
            : '';
          $employeeCardName = trim(($emp->last_name ?? '').', '.trim(($emp->first_name ?? '').' '.($emp->middle_name ?? '')), ', ');
          $employeeCardRole = trim((string) ($emp->job_role ?? '')) !== ''
            ? trim((string) $emp->job_role)
            : trim((string) (data_get($emp, 'employee.position') ?? $emp->position ?? data_get($emp, 'applicant.position.title') ?? ''));
        @endphp
        <!-- Employee Card -->
        <div
            class="employee-directory-card employee-page-reveal employee-card-motion relative flex min-h-[365px] min-w-0 w-full flex-col overflow-hidden rounded-xl bg-white shadow-md"
            style="--employee-reveal-delay: 0ms;"
            x-show="matchesDepartment(@js($resolveDepartment($emp))) &&
                    matchesSearch(@js(trim(($emp->last_name ?? '').', '.trim(($emp->first_name ?? '').' '.($emp->middle_name ?? '')), ', '))) &&
                    matchesStatus(@js($resolveDisplayAccountStatus($emp)), @js($missingCardCount > 0))"
        >
            @if($missingCardCount > 0)
                <div class="employee-missing-badge" title="{{ $missingCardTitle }}">
                    <span class="employee-missing-icon">!</span>
                </div>
            @endif
            <div class="h-24 flex justify-center items-center" style="background-image: linear-gradient(to right, {{ $headerStart }}, {{ $headerEnd }});">
                <div class="w-16 h-16 rounded-full text-white flex items-center justify-center text-lg font-bold border-4 border-white mt-24 overflow-hidden" style="background-color: {{ $avatarColor }};">
                    @if($profilePhotoUrl)
                        <img
                          src="{{ $profilePhotoUrl }}"
                          alt="Employee Photo"
                          class="w-full h-full object-cover cursor-zoom-in"
                          @click.stop="openImagePreview('{{ $profilePhotoUrl }}')"
                        />
                    @else
                        {{$emp->initials}}
                    @endif
                </div>
            </div>

            <div class="mt-7 flex flex-1 flex-col p-4">
                <div class="flex min-h-[3.5rem] items-center justify-center">
                  <h3 class="employee-card-name text-center text-lg font-bold leading-6 text-gray-800" title="{{ $employeeCardName }}">{{ $employeeCardName }}</h3>
                </div>
                <div class="flex min-h-[1.5rem] items-center justify-center">
                  <p class="employee-card-role max-w-full text-center text-sm text-gray-500" title="{{ $employeeCardRole }}">{{ $employeeCardRole }}</p>
                </div>

                <div class="mt-4 space-y-1 text-gray-500 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="fa-regular fa-id-badge"></i>
                        {{ $displayEmployeeId($emp, '') }}
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-sitemap"></i>
                        {{ $resolveDepartment($emp) }}
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar"></i>
                        Hired {{$emp->applicant?->formatted_date_hired }}
                    </div>
                </div>

                <hr class="mb-3 mt-auto">

                <div class="flex justify-between items-center">
                    <div class="flex items-center -space-x-">
                        @php
                          $accountStatus = $resolveDisplayAccountStatus($emp);
                          $normalizedStatus = strtolower($accountStatus);
                          $statusBadgeClass = match ($normalizedStatus) {
                            'active' => 'text-green-700 bg-green-100',
                            'on leave' => 'text-orange-700 bg-orange-100/70',
                            default => 'text-red-700 bg-red-100/70',
                          };
                        @endphp
                        <span data-employee-account-status="{{ (int) ($emp->id ?? 0) }}" class="px-2 py-1 rounded-full text-xs font-medium z-10 transition-colors duration-300 {{ $statusBadgeClass }}">
                            {{ $accountStatus }}
                        </span>

                        @if($wasRehiredAfterResignation($emp))
                            <span class="px-2 py-1 rounded-full text-xs font-medium text-violet-700 bg-violet-100">
                                Rehired
                            </span>
                        @endif
                    </div>
                    <button
                        type="button"
                        @click="openEmployeeProfile({{ (int) ($emp->id ?? 0) }}, @js($headerStart), @js($headerEnd))"
                        class="text-blue-500 text-sm font-medium hover:underline">
                        View Profile
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if (isset($employeePaginator) && $employeePaginator->hasPages())
      <div
        x-show="!showDepartmentSummary"
        class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 shadow-sm md:flex-row md:items-center md:justify-between"
      >
        <div class="text-sm text-slate-600">
          Showing
          <span class="font-bold text-slate-900">{{ $employeePaginator->firstItem() }}</span>
          to
          <span class="font-bold text-slate-900">{{ $employeePaginator->lastItem() }}</span>
          of
          <span class="font-bold text-slate-900">{{ $employeePaginator->total() }}</span>
          employees
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
            Per page
            <select
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
              @change="
                const params = new URLSearchParams(window.location.search);
                params.set('per_page', $event.target.value);
                params.delete('page');
                loadEmployeeDirectoryResults(`${window.location.pathname}?${params.toString()}`);
              "
            >
              @foreach ([5, 10, 15, 25] as $pageSize)
                <option value="{{ $pageSize }}" @selected((int) ($employeeFilterState['per_page'] ?? 10) === $pageSize)>{{ $pageSize }}</option>
              @endforeach
            </select>
          </label>

          <nav class="flex flex-wrap items-center gap-2" aria-label="Employee pagination">
            @if ($employeePaginator->onFirstPage())
              <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 text-slate-400">
                <i class="fa-solid fa-chevron-left text-xs"></i>
              </span>
            @else
              <a href="{{ $employeePaginator->previousPageUrl() }}" @click.prevent="loadEmployeeDirectoryResults($event.currentTarget.href)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                <i class="fa-solid fa-chevron-left text-xs"></i>
              </a>
            @endif

            @php
              $paginationStart = max(1, $employeePaginator->currentPage() - 1);
              $paginationEnd = min($employeePaginator->lastPage(), $employeePaginator->currentPage() + 1);
            @endphp

            @if ($paginationStart > 1)
              <a href="{{ $employeePaginator->url(1) }}" @click.prevent="loadEmployeeDirectoryResults($event.currentTarget.href)" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">1</a>
              @if ($paginationStart > 2)
                <span class="inline-flex h-10 min-w-10 items-center justify-center px-2 text-sm font-bold text-slate-400">...</span>
              @endif
            @endif

            @foreach (range($paginationStart, $paginationEnd) as $page)
              @if ($page === $employeePaginator->currentPage())
                <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl bg-emerald-600 px-3 text-sm font-bold text-white">{{ $page }}</span>
              @else
                <a href="{{ $employeePaginator->url($page) }}" @click.prevent="loadEmployeeDirectoryResults($event.currentTarget.href)" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">{{ $page }}</a>
              @endif
            @endforeach

            @if ($paginationEnd < $employeePaginator->lastPage())
              @if ($paginationEnd < $employeePaginator->lastPage() - 1)
                <span class="inline-flex h-10 min-w-10 items-center justify-center px-2 text-sm font-bold text-slate-400">...</span>
              @endif
              <a href="{{ $employeePaginator->url($employeePaginator->lastPage()) }}" @click.prevent="loadEmployeeDirectoryResults($event.currentTarget.href)" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">{{ $employeePaginator->lastPage() }}</a>
            @endif

            @if ($employeePaginator->hasMorePages())
              <a href="{{ $employeePaginator->nextPageUrl() }}" @click.prevent="loadEmployeeDirectoryResults($event.currentTarget.href)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                <i class="fa-solid fa-chevron-right text-xs"></i>
              </a>
            @else
              <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 text-slate-400">
                <i class="fa-solid fa-chevron-right text-xs"></i>
              </span>
            @endif
          </nav>
        </div>
      </div>
    @endif

    <div
      x-show="!showDepartmentSummary && viewMode === 'cards' && !hasVisibleEmployees()"
      class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg px-4 py-3"
      style="display:none;"
    >
      No employee found for your filter/search.
    </div>

    <div
      x-show="!showDepartmentSummary && viewMode === 'table'"
      class="w-full max-w-full overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.08)]"
      style="display:none;"
    >
      <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700">
        Slide left or right to view all columns.
      </div>
      <div class="min-w-0 w-full max-w-full overflow-x-auto overflow-y-auto max-h-[50vh]">
        <table id="employee-directory-table-export" class="w-max min-w-full border-collapse text-[13px] text-slate-800">
          <colgroup>
            <col class="w-14">
            <col class="w-72">
            <col class="w-56">
            <col class="w-36">
            <col class="w-20">
            <col class="w-36">
            <col class="w-[28rem]">
            <col class="w-56">
            <col class="w-[18rem]">
            <col class="w-36">
            <col class="w-44">
            <col class="w-56">
            <col class="w-60">
            <col class="w-[18rem]">
            <col class="w-28">
            <col class="w-56">
            <col class="w-24">
            <col class="w-40">
            <col class="w-36">
            <col class="w-40">
            <col class="w-40">
            <col class="w-40">
            <col class="w-[24rem]">
            <col class="w-[24rem]">
            <col class="w-[24rem]">
            <col class="w-44">
            <col class="w-40">
            <col class="w-48">
            <col class="w-44">
            <col class="w-40">
            <col class="w-40">
            <col class="w-40">
            <col class="w-56">
            <col class="w-80">
          </colgroup>
          <thead>
            <tr class="bg-[#66f0cf] text-center text-[12px] font-black uppercase tracking-[0.04em] text-slate-900">
              <th class="sticky left-0 top-0 z-30 border border-black bg-[#66f0cf] px-2 py-1 shadow-[inset_-1px_0_0_#000]">NO.</th>
              <th class="sticky left-14 top-0 z-30 border border-black bg-[#66f0cf] px-2 py-1 shadow-[inset_-1px_0_0_#000]">NAME</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">ID Number</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">Account #</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">SEX</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">CIVIL STATUS</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">ADDRESS</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">CONTACT NO.</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">DATE OF BIRTH</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">AGE TO DATE</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">EMPLOYMENT DATE</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">LENGTH OF SERVICE</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">POSITION</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">DEPARTMENT</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">CLASS</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">RANK</th>
              <th class="sticky top-0 z-10 border border-black bg-[#66f0cf] px-2 py-1">GRADE</th>
              <th class="sticky top-0 z-10 border border-black bg-[#26a9f3] px-2 py-1 text-white">SSS</th>
              <th class="sticky top-0 z-10 border border-black bg-[#26a9f3] px-2 py-1 text-white">TIN</th>
              <th class="sticky top-0 z-10 border border-black bg-[#26a9f3] px-2 py-1 text-white">PHILHEALTH</th>
              <th class="sticky top-0 z-10 border border-black bg-[#26a9f3] px-2 py-1 text-white">PAG-IBIG MID</th>
              <th class="sticky top-0 z-10 border border-black bg-[#26a9f3] px-2 py-1 text-white">PAG-IBIG RTN</th>
              <th class="sticky top-0 z-10 border border-black bg-[#fff200] px-2 py-1">BACHELOR'S DEGREE</th>
              <th class="sticky top-0 z-10 border border-black bg-[#fff200] px-2 py-1">MASTER'S DEGREE</th>
              <th class="sticky top-0 z-10 border border-black bg-[#fff200] px-2 py-1">DOCTORATE DEGREE</th>
              <th class="sticky top-0 z-10 border border-black bg-[#fff200] px-2 py-1">ELIGIBILITY</th>
              <th class="sticky top-0 z-10 border border-black bg-[#fff200] px-2 py-1">Registration No.</th>
              <th class="sticky top-0 z-10 border border-black bg-[#fff200] px-2 py-1">Registration Date</th>
              <th class="sticky top-0 z-10 border border-black bg-[#fff200] px-2 py-1">Valid Until</th>
              <th class="sticky top-0 z-10 border border-black bg-[#39ff14] px-2 py-1">Rate per Hour</th>
              <th class="sticky top-0 z-10 border border-black bg-[#39ff14] px-2 py-1">Basic Salary</th>
              <th class="sticky top-0 z-10 border border-black bg-[#39ff14] px-2 py-1">Allowance</th>
              <th class="sticky top-0 z-10 border border-black bg-[#f79646] px-2 py-1">Date Resigned</th>
              <th class="sticky top-0 z-10 border border-black bg-[#f79646] px-2 py-1">Employment History</th>
            </tr>
          </thead>
          <tbody>
            <tr class="text-[13px] font-bold">
              <td class="sticky left-0 z-20 border border-black bg-white px-2 py-0.5 shadow-[inset_-1px_0_0_#000]"></td>
              <td class="sticky left-14 z-20 border border-black bg-white px-2 py-0.5 shadow-[inset_-1px_0_0_#000]">Northeastern College, Inc.</td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5 text-center">Villasis, Santiago City</td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td
                class="border border-black px-2 py-0.5 text-center font-bold outline-none"
                contenteditable="true"
                spellcheck="false"
                data-employment-date-cell
                data-length-service-target="table-summary-length-of-service"
                onblur="window.updateEmploymentDateCell(this)"
                onkeydown="window.handleEmploymentDateCellKeydown(event, this)"
              >{{ $tableSummaryEmploymentDate }}</td>
              <td class="border border-black px-2 py-0.5">
                <div class="font-black text-red-600">&lt;&lt; DO NOT DELETE&gt;&gt;&gt;&gt;</div>
                <div id="table-summary-length-of-service" class="text-slate-700"></div>
              </td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5 text-center font-black">2026</td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
              <td class="border border-black px-2 py-0.5"></td>
            </tr>
            @foreach ($employeeTableRecords as $row)
              <tr
                x-show="matchesDepartment(@js($row['department'])) && matchesSearch(@js($row['name'])) && matchesStatus(@js($row['status']), @js((bool) ($row['has_missing_info'] ?? false)))"
                class="bg-white text-[13px]"
              >
                <td class="sticky left-0 z-10 border border-black bg-white px-2 py-1 text-center shadow-[inset_-1px_0_0_#000]">{{ $row['no'] }}</td>
                <td class="sticky left-14 z-10 border border-black bg-white px-2 py-1 shadow-[inset_-1px_0_0_#000]">
                  <button
                    type="button"
                    @click="openEmployeeProfile({{ (int) ($row['user_id'] ?? 0) }}, @js($row['header_start']), @js($row['header_end']))"
                    class="font-semibold text-sky-700 underline decoration-sky-300 underline-offset-2 transition hover:text-sky-900 hover:decoration-sky-600"
                  >
                    {{ $row['name'] }}
                  </button>
                </td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['employee_id'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['account_number'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['sex'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['civil_status'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['address'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['contact_number'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['date_of_birth'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['age_to_date'] }}</td>
                <td class="border border-black px-2 py-1 text-center" data-row-employment-date>{{ $row['employment_date'] }}</td>
                <td class="border border-black px-2 py-1" data-row-length-of-service>{{ $row['length_of_service'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['position'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['department'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['class'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['rank'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['grade'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['sss'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['tin'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['philhealth'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['pagibig_mid'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['pagibig_rtn'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['bachelors_degree'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['masters_degree'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['doctorate_degree'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['eligibility'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['registration_number'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['registration_date'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['valid_until'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['rate_per_hour'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['basic_salary'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['allowance'] }}</td>
                <td class="border border-black px-2 py-1 text-center">{{ $row['date_resigned'] }}</td>
                <td class="border border-black px-2 py-1">{{ $row['employment_history'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <script type="application/json" data-employee-page-records>@json($employee->values())</script>
    </div>

</div>


    <!-- ================= PROFILE MODAL ================= -->
    <div
      x-show="openProfile"
      x-transition
      @click.self="openProfile=false"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-40"
      style="display:none"
    >
      <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="employee-profile-modal-scroll">

        <div
          class="p-6 text-white relative"
          :style="`background-image: linear-gradient(to right, ${selectedEmployee?.ui_theme?.header_start || 'rgb(168 85 247)'}, ${selectedEmployee?.ui_theme?.header_end || 'rgb(99 102 241)'})`"
        >
          <button @click="openProfile=false" class="absolute top-4 right-4 text-2xl">&times;</button>

          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center font-bold overflow-hidden">
              <template x-if="hasProfilePhoto()">
                <img
                  :src="profilePhotoUrl()"
                  alt="Employee Photo"
                  class="w-full h-full object-cover cursor-zoom-in"
                  @click.stop="openImagePreview(profilePhotoUrl())"
                >
              </template>
              <template x-if="!hasProfilePhoto()">
                <span x-text="selectedEmployee?.initials"></span>
              </template>
            </div>
            <div>
              <h2 class="text-xl font-semibold"
              x-text="[
                selectedEmployee?.first_name ?? selectedEmployee?.applicant?.first_name ?? '',
                selectedEmployee?.middle_name ?? selectedEmployee?.applicant?.middle_name ?? '',
                selectedEmployee?.last_name ?? selectedEmployee?.applicant?.last_name ?? ''
              ].filter(Boolean).join(' ') || '-'"
              ></h2>
              <p class="text-sm">
                <span x-text="selectedEmployee?.job_role ?? selectedEmployee?.employee?.position ?? selectedEmployee?.position ?? selectedEmployee?.applicant?.position?.title ?? '-'"></span><br>
                <span x-text="selectedEmployee?.employee?.department ?? selectedEmployee?.applicant?.position?.department ?? selectedEmployee?.department ?? '-'"></span>
              </p>
              <template x-if="wasRehiredAfterResignation()">
                <div class="mt-3">
                  <span class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">
                    <i class="fa-solid fa-rotate-left"></i>
                    Rehired
                  </span>
                </div>
              </template>
            </div>
            <span
              class="ml-auto text-xs px-3 py-1 rounded-full"
              :class="effectiveAccountStatusClass()"
              x-text="effectiveAccountStatus()"
            ></span>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-6 px-6 pt-4 border-b text-sm">
          <button @click="tab='overview'" :class="tab==='overview' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-2' : 'text-gray-500'" class="inline-flex items-center gap-1.5">
            <span>Overview</span>
            <span x-show="tabMissingCount('overview') > 0" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">!</span>
          </button>
          <button @click="tab='personal'" :class="tab==='personal' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-2' : 'text-gray-500'" class="inline-flex items-center gap-1.5">
            <span>Personal Details</span>
            <span x-show="tabMissingCount('personal') > 0" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">!</span>
          </button>
          <button @click="tab='performance'" :class="tab==='performance' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-2' : 'text-gray-500'" class="inline-flex items-center gap-1.5">
            <span>Performance</span>
            <span x-show="tabMissingCount('performance') > 0" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">!</span>
          </button>
          <button @click="tab='documents'" :class="tab==='documents' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-2' : 'text-gray-500'" class="inline-flex items-center gap-1.5">
            <span>Documents</span>
            <span x-show="tabMissingCount('documents') > 0" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">!</span>
          </button>
          <button @click="tab='record'" :class="tab==='record' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-2' : 'text-gray-500'" class="inline-flex items-center gap-1.5">
            <span>Service Record</span>
            <span x-show="tabMissingCount('record') > 0" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">!</span>
          </button>
          <button @click="tab='biometric'" :class="tab==='biometric' ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-2' : 'text-gray-500'" class="inline-flex items-center gap-1.5">
            <span>Biometric</span>
            <span x-show="tabMissingCount('biometric') > 0" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">!</span>
          </button>
        </div>

        @include('Admin.PersonalDetail.adminEmployeeOverview')
        @include('Admin.PersonalDetail.adminEmployeePD')
        @include('Admin.PersonalDetail.adminEmployeePerformance')
        @include('Admin.PersonalDetail.adminEmployeeDocuments')
        @include('Admin.PersonalDetail.adminServiceRecord')
        @include('Admin.PersonalDetail.adminBioMetric')



        <!-- Footer -->
        <div class="flex gap-3 p-6 border-t">
          <template x-if="canMarkSelectedEmployeePermanent()">
            <form method="POST" action="{{ route('admin.markEmployeePermanent', ['id' => '__USER_ID__']) }}"
              class="flex-1"
              x-bind:action="'{{ route('admin.markEmployeePermanent', ['id' => '__USER_ID__']) }}'.replace('__USER_ID__', selectedEmployee?.id ?? '')">
              @csrf
              <input type="hidden" name="tab" x-bind:value="tab">
              <button
                type="submit"
                class="w-full bg-emerald-600 text-white py-2 rounded-lg hover:bg-emerald-700"
                x-text="selectedEmployeePermanentLabel()">
              </button>
            </form>
          </template>
          <button
            @click="openEditProfile = true; modalTarget = 'general'"
            class="flex-1 bg-slate-100 py-2 rounded-lg hover:bg-slate-200">
            Edit Profile
          </button>
        </div>

        </div>
      </div>
    </div>

    <div
      x-show="openImageZoom"
      x-transition
      @click="closeImagePreview()"
      @keydown.escape.window="closeImagePreview()"
      class="fixed inset-0 z-[90] bg-black/80 flex items-center justify-center p-6"
      style="display:none;"
    >
      <img
        :src="zoomImageUrl"
        alt="Zoomed employee photo"
        class="max-w-full max-h-full object-contain rounded-lg shadow-2xl"
        @click.stop
      >
    </div>



    <!-- ================= PROFILE EDIT ================= -->
    @include('Admin.PersonalDetail.editProfile')

  </main>
</div>

</body>

@php
  $adminEmployeeExcelRecords = $employee->map(function ($emp) use ($blankTableValue, $resolveDisplayAccountStatus, $wasRehiredAfterResignation, $displayEmployeeId) {
    $classValue = trim((string) (data_get($emp, 'employee.classification') ?: data_get($emp, 'applicant.position.employment') ?: ($emp->classification ?? '')));
    $employmentCode = match (strtolower($classValue)) {
      'full-time', 'full time' => 'FT',
      'part-time', 'part time' => 'PT',
      default => (str_contains(strtolower($classValue), 'part') ? 'PT' : (str_contains(strtolower($classValue), 'full') || str_contains(strtolower($classValue), 'probationary') || str_contains(strtolower($classValue), 'permanent') ? 'FT' : '')),
    };
    $jobTypeValue = trim((string) (data_get($emp, 'applicant.position.job_type') ?: data_get($emp, 'employee.job_type') ?: ($emp->job_type ?? '')));
    $normalizedJobType = strtolower($jobTypeValue);
    $jobTypeCode = match (true) {
      in_array(strtoupper($jobTypeValue), ['NT'], true),
      str_contains($normalizedJobType, 'non-teaching'),
      str_contains($normalizedJobType, 'non teaching') => 'NT',
      in_array(strtoupper($jobTypeValue), ['T'], true),
      str_contains($normalizedJobType, 'teaching'),
      str_contains($normalizedJobType, 'teacher'),
      str_contains($normalizedJobType, 'faculty') => 'T',
      default => $jobTypeValue,
    };
    $classDisplay = collect([$employmentCode, $jobTypeCode])->filter(fn ($value) => trim((string) $value) !== '')->implode('/');

    $birthdayRaw = data_get($emp, 'employee.birthday') ?: ($emp->birthday ?? '');
    $birthdayDisplay = '';
    $ageToDate = '';
    if (!empty($birthdayRaw)) {
      try {
        $birthday = \Carbon\Carbon::parse($birthdayRaw);
        $birthdayDisplay = $birthday->format('F j, Y');
        $ageToDate = (string) $birthday->age;
      } catch (\Throwable $e) {
        $birthdayDisplay = trim((string) $birthdayRaw);
      }
    }

    $isRehiredAfterResignation = $wasRehiredAfterResignation($emp);
    $employmentDateRaw = $isRehiredAfterResignation
      ? (data_get($emp, 'applicant.date_hired')
        ?: data_get($emp, 'applicant.created_at')
        ?: data_get($emp, 'employee.employement_date')
        ?: data_get($emp, 'employee.employment_date')
        ?: ($emp->date_hired ?? ''))
      : (data_get($emp, 'applicant.date_hired')
        ?: data_get($emp, 'employee.employement_date')
        ?: data_get($emp, 'employee.employment_date')
        ?: ($emp->date_hired ?? ''));
    $employmentDateDisplay = '';
    $lengthOfService = '';
    $dateResigned = collect(data_get($emp, 'resignations', []))
      ->first(function ($row) {
        return in_array(strtolower(trim((string) ($row->status ?? ''))), ['approved', 'completed'], true);
      });
    $resignationEndDateRaw = $dateResigned?->effective_date ?: $dateResigned?->processed_at;

    if (!empty($employmentDateRaw)) {
      try {
        $employmentDate = \Carbon\Carbon::parse($employmentDateRaw);
        $employmentDateDisplay = $employmentDate->format('F j, Y');
        $serviceEndDate = (!$isRehiredAfterResignation && !empty($resignationEndDateRaw))
          ? \Carbon\Carbon::parse($resignationEndDateRaw)
          : now();
        $lengthOfService = $employmentDate->diffForHumans($serviceEndDate, [
          'parts' => 3,
          'short' => false,
          'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ]);
      } catch (\Throwable $e) {
        $employmentDateDisplay = trim((string) $employmentDateRaw);
      }
    }
    $dateResignedDisplay = '';
    if (!empty($resignationEndDateRaw)) {
      try {
        $dateResignedDisplay = \Carbon\Carbon::parse($resignationEndDateRaw)->format('F j, Y');
      } catch (\Throwable $e) {
        $dateResignedDisplay = trim((string) $resignationEndDateRaw);
      }
    }

    $employmentHistoryDisplay = collect(data_get($emp, 'position_histories', []))
      ->map(function ($history) {
        $oldPosition = trim((string) ($history->old_position ?? ''));
        $newPosition = trim((string) ($history->new_position ?? ''));
        if ($oldPosition !== '' && $newPosition !== '' && strcasecmp($oldPosition, $newPosition) !== 0) {
          return $oldPosition.' to '.$newPosition;
        }
        return $newPosition !== '' ? $newPosition : $oldPosition;
      })
      ->filter()
      ->implode(' | ');

    return [
      'company' => 'Northeastern College, Inc.',
      'employee_id' => $blankTableValue($displayEmployeeId($emp, '')),
      'name' => $blankTableValue(trim(($emp->last_name ?? '').', '.trim(($emp->first_name ?? '').' '.($emp->middle_name ?? '')), ', ')),
      'account_number' => $blankTableValue(trim((string) (data_get($emp, 'employee.account_number') ?: data_get($emp, 'employee.user_id') ?: ($emp->account_number ?? $emp->id ?? '')))),
      'sex' => $blankTableValue(trim((string) (data_get($emp, 'employee.sex') ?: data_get($emp, 'employee.gender') ?: ($emp->gender ?? '')))),
      'civil_status' => $blankTableValue(trim((string) (data_get($emp, 'employee.civil_status') ?: ($emp->civil_status ?? '')))),
      'address' => $blankTableValue(trim((string) (data_get($emp, 'employee.address') ?: data_get($emp, 'applicant.address') ?: ($emp->address ?? '')))),
      'contact_number' => $blankTableValue(trim((string) (data_get($emp, 'employee.contact_number') ?: data_get($emp, 'applicant.contact_number') ?: ($emp->contact_number ?? '')))),
      'date_of_birth' => $blankTableValue($birthdayDisplay),
      'age_to_date' => $blankTableValue($ageToDate),
      'employment_date' => $blankTableValue($employmentDateDisplay),
      'length_of_service' => $blankTableValue($lengthOfService),
      'position' => $blankTableValue(trim((string) ($emp->job_role ?? data_get($emp, 'employee.position') ?? ($emp->position ?? data_get($emp, 'applicant.position.title') ?? '')))),
      'class' => $blankTableValue($classDisplay),
      'rank' => $blankTableValue(trim((string) (data_get($emp, 'employee.rank') ?: ($emp->rank ?? '')))),
      'grade' => $blankTableValue(trim((string) (data_get($emp, 'employee.grade') ?: ($emp->grade ?? '')))),
      'sss' => $blankTableValue(trim((string) (data_get($emp, 'government.SSS') ?: ''))),
      'tin' => $blankTableValue(trim((string) (data_get($emp, 'government.TIN') ?: ''))),
      'philhealth' => $blankTableValue(trim((string) (data_get($emp, 'government.PhilHealth') ?: ''))),
      'pagibig_mid' => $blankTableValue(trim((string) (data_get($emp, 'government.MID') ?: ''))),
      'pagibig_rtn' => $blankTableValue(trim((string) (data_get($emp, 'government.RTN') ?: ''))),
      'bachelors_degree' => $blankTableValue(trim((string) (data_get($emp, 'education.bachelor') ?: data_get($emp, 'applicant.bachelor_degree') ?: ''))),
      'masters_degree' => $blankTableValue(trim((string) (data_get($emp, 'education.master') ?: data_get($emp, 'applicant.master_degree') ?: ''))),
      'doctorate_degree' => $blankTableValue(trim((string) (data_get($emp, 'education.doctorate') ?: data_get($emp, 'applicant.doctoral_degree') ?: ''))),
      'eligibility' => $blankTableValue(trim((string) (data_get($emp, 'license.license') ?: ''))),
      'registration_number' => $blankTableValue(trim((string) (data_get($emp, 'license.registration_number') ?: ''))),
      'registration_date' => $blankTableValue(!empty(data_get($emp, 'license.registration_date'))
        ? \Carbon\Carbon::parse(data_get($emp, 'license.registration_date'))->format('F j, Y')
        : ''),
      'valid_until' => $blankTableValue(!empty(data_get($emp, 'license.valid_until'))
        ? \Carbon\Carbon::parse(data_get($emp, 'license.valid_until'))->format('F j, Y')
        : ''),
      'rate_per_hour' => $blankTableValue(trim((string) (data_get($emp, 'salary.rate_per_hour') ?: ''))),
      'basic_salary' => $blankTableValue(trim((string) (data_get($emp, 'salary.salary') ?: ''))),
      'allowance' => $blankTableValue(trim((string) (data_get($emp, 'salary.cola') ?: ''))),
      'date_resigned' => $blankTableValue($dateResignedDisplay),
      'employment_history' => $blankTableValue($employmentHistoryDisplay),
      'department' => $blankTableValue(trim((string) (data_get($emp, 'applicant.position.department') ?: data_get($emp, 'employee.department') ?: ($emp->department ?? '')))),
      'status' => $resolveDisplayAccountStatus($emp),
      'email' => $blankTableValue(trim((string) (data_get($emp, 'applicant.email_address') ?: ($emp->email_address ?? $emp->email ?? '')))),
      'temporary_pin' => trim((string) ($emp->temporary_pin ?? '')),
    ];
  })->values();
@endphp

<script>
  window.adminEmployeeExcelRecords = @json($adminEmployeeExcelRecords);

  window.exportAdminEmployeePinsExcel = function exportAdminEmployeePinsExcel(filters = {}) {
    const rows = (window.adminEmployeeExcelRecords ?? []).filter((record) => record.temporary_pin);

    if (!rows.length) {
      window.alert('No employees with temporary PINs match the current filters.');
      return;
    }

    const escapeHtml = (value) => (value ?? '').toString()
      .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    const bodyRows = rows.map((record, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${escapeHtml(record.name)}</td>
        <td style="mso-number-format:'\\@';">${escapeHtml(record.employee_id)}</td>
        <td style="mso-number-format:'000000';">${escapeHtml(record.temporary_pin)}</td>
      </tr>`).join('');
    const html = `<html><head><meta charset="utf-8"><style>
      table{border-collapse:collapse;font-family:Arial,sans-serif;font-size:12pt}
      th,td{border:1px solid #94a3b8;padding:9px 14px;text-align:left;white-space:nowrap}
      th{background:#047857;color:#fff;font-weight:700}
    </style></head><body><table><thead><tr><th>No.</th><th>Employee Name</th><th>Employee ID</th><th>Temporary PIN</th></tr></thead><tbody>${bodyRows}</tbody></table></body></html>`;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `employee-temporary-pins-${new Date().toISOString().slice(0, 10)}.xls`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  };

  window.exportAdminEmployeesExcel = function exportAdminEmployeesExcel(filters = {}) {
    const sourceTable = document.getElementById('employee-directory-table-export');
    if (!sourceTable) {
      window.alert('Table not found for export.');
      return;
    }

    const exportTable = sourceTable.cloneNode(true);
    exportTable.querySelectorAll('tbody tr').forEach((row) => {
      row.style.removeProperty('display');
    });
    exportTable.querySelectorAll('[contenteditable="true"]').forEach((cell) => {
      cell.removeAttribute('contenteditable');
      cell.removeAttribute('spellcheck');
      cell.removeAttribute('onblur');
      cell.removeAttribute('onkeydown');
      cell.removeAttribute('data-employment-date-cell');
      cell.removeAttribute('data-length-service-target');
    });
    exportTable.querySelectorAll('[x-show]').forEach((row) => {
      row.removeAttribute('x-show');
    });

    const sourceCells = Array.from(sourceTable.querySelectorAll('th, td'));
    const exportCells = Array.from(exportTable.querySelectorAll('th, td'));
    exportCells.forEach((cell, index) => {
      const sourceCell = sourceCells[index];
      if (!sourceCell) return;

      const computed = window.getComputedStyle(sourceCell);
      const exportBorder = '0.75px solid #000';
      cell.style.backgroundColor = computed.backgroundColor;
      cell.style.color = computed.color;
      cell.style.fontWeight = computed.fontWeight;
      cell.style.textAlign = computed.textAlign;
      cell.style.verticalAlign = computed.verticalAlign;
      cell.style.borderTop = exportBorder;
      cell.style.borderRight = exportBorder;
      cell.style.borderBottom = exportBorder;
      cell.style.borderLeft = exportBorder;
    });

    const exportRowsCollection = Array.from(exportTable.rows || []);
    const columnCount = Math.max(...exportRowsCollection.map((row) => row.cells?.length || 0), 0);
    const minimumWidths = {
      0: 48,
      1: 300,
      2: 120,
      3: 120,
      4: 72,
      5: 110,
      6: 520,
      7: 140,
      8: 120,
      9: 90,
      10: 130,
      11: 180,
      12: 420,
      13: 520,
      14: 95,
      15: 300,
      16: 85,
      17: 110,
      18: 110,
      19: 120,
      20: 120,
      21: 120,
      22: 360,
      23: 360,
      24: 360,
      25: 320,
      26: 130,
      27: 130,
      28: 130,
      29: 110,
      30: 130,
      31: 110,
      32: 140,
      33: 260,
    };

    const columnWidths = Array.from({ length: columnCount }, (_, columnIndex) => {
      const textLengths = exportRowsCollection.map((row) => {
        const cell = row.cells?.[columnIndex];
        return (cell?.textContent ?? '').replace(/\s+/g, ' ').trim().length;
      });
      const maxLength = Math.max(0, ...textLengths);
      const maxWidth = [1, 6, 12, 13, 15, 22, 23, 24, 25].includes(columnIndex) ? 760 : 320;
      const computedWidth = Math.min(Math.max((maxLength * 8) + 42, minimumWidths[columnIndex] ?? 96), maxWidth);
      return computedWidth;
    });

    exportRowsCollection.forEach((row) => {
      Array.from(row.cells || []).forEach((cell, columnIndex) => {
        const width = columnWidths[columnIndex] ?? 96;
        cell.style.minWidth = `${width}px`;
        cell.style.width = `${width}px`;
        cell.style.padding = '4px 10px';
        cell.style.lineHeight = '1.35';
      });
    });

    if (columnWidths.length) {
      const colgroup = exportTable.ownerDocument.createElement('colgroup');
      columnWidths.forEach((width) => {
        const col = exportTable.ownerDocument.createElement('col');
        col.style.width = `${width}px`;
        colgroup.appendChild(col);
      });
      exportTable.insertBefore(colgroup, exportTable.firstChild);
    }

    const html = `
      <html>
      <head>
        <meta charset="utf-8">
        <style>
          table { border-collapse: collapse; table-layout: auto; font-family: Arial, sans-serif; font-size: 12pt; width: auto; min-width: max-content; }
          th, td { border: 0.5px solid #000; padding: 4px 10px; white-space: nowrap; line-height: 1.35; }
          .sticky { position: static !important; }
          .text-red-600 { color: #dc2626 !important; }
          .font-black, .font-bold { font-weight: 700 !important; }
          .text-center { text-align: center !important; }
          .text-white { color: #fff !important; }
          .bg-white { background: #fff !important; }
        </style>
      </head>
      <body>${exportTable.outerHTML}</body>
      </html>
    `;

    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `all-employees-${new Date().toISOString().slice(0, 10)}.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  window.parseTableDate = function parseTableDate(value) {
    const raw = (value ?? '').toString().trim();
    if (!raw) return null;
    const parsed = new Date(raw);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  };

  window.formatServiceDurationBetween = function formatServiceDurationBetween(startValue, endValue) {
    const startDate = window.parseTableDate(startValue);
    const endDate = window.parseTableDate(endValue);
    if (!startDate || !endDate) {
      return '';
    }

    let from = new Date(startDate);
    let to = new Date(endDate);
    if (from > to) {
      [from, to] = [to, from];
    }

    let years = 0;
    let months = 0;

    while (true) {
      const next = new Date(from);
      next.setFullYear(next.getFullYear() + 1);
      if (next <= to) {
        years += 1;
        from = next;
      } else {
        break;
      }
    }

    while (true) {
      const next = new Date(from);
      next.setMonth(next.getMonth() + 1);
      if (next <= to) {
        months += 1;
        from = next;
      } else {
        break;
      }
    }

    let remainingMinutes = Math.max(0, Math.floor((to.getTime() - from.getTime()) / 60000));
    const minutesPerWeek = 7 * 24 * 60;
    const minutesPerDay = 24 * 60;
    const minutesPerHour = 60;

    const weeks = Math.floor(remainingMinutes / minutesPerWeek);
    remainingMinutes -= weeks * minutesPerWeek;
    const days = Math.floor(remainingMinutes / minutesPerDay);
    remainingMinutes -= days * minutesPerDay;
    const hours = Math.floor(remainingMinutes / minutesPerHour);
    remainingMinutes -= hours * minutesPerHour;
    const minutes = remainingMinutes;

    const parts = [];
    if (years > 0) parts.push(`${years} year${years === 1 ? '' : 's'}`);
    if (months > 0) parts.push(`${months} month${months === 1 ? '' : 's'}`);
    if (weeks > 0) parts.push(`${weeks} week${weeks === 1 ? '' : 's'}`);
    if (days > 0) parts.push(`${days} day${days === 1 ? '' : 's'}`);
    if (hours > 0) parts.push(`${hours} hour${hours === 1 ? '' : 's'}`);
    if (minutes > 0) parts.push(`${minutes} minute${minutes === 1 ? '' : 's'}`);
    if (!parts.length) parts.push('0 days');

    return parts.slice(0, 3).join(' ');
  };

  window.updateEmploymentDateCell = function updateEmploymentDateCell(cell) {
    if (!cell) return;
    const targetId = cell.dataset.lengthServiceTarget || '';
    if (targetId) {
      const target = document.getElementById(targetId);
      if (target) {
        target.textContent = '';
      }
    }

    const referenceDate = cell.textContent;
    const rows = Array.from(document.querySelectorAll('tr'));
    rows.forEach((row) => {
      const employmentDateCell = row.querySelector('[data-row-employment-date]');
      const lengthCell = row.querySelector('[data-row-length-of-service]');
      if (!employmentDateCell || !lengthCell) return;
      lengthCell.textContent = window.formatServiceDurationBetween(employmentDateCell.textContent, referenceDate);
    });
  };

  window.handleEmploymentDateCellKeydown = function handleEmploymentDateCellKeydown(event, cell) {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    cell.blur();
  };

  (function () {
    const initEmployeePageAnimation = () => {
      const page = document.getElementById('admin-employee-page');
      if (!page) return;

      const selectors = [
        '#admin-employee-page > div',
        '#admin-employee-page table',
        '#department-staffing-summary',
        '#employee-directory-table-export',
      ];

      const revealItems = Array.from(document.querySelectorAll(selectors.join(',')))
        .filter((item) => {
          if (!item || item.classList.contains('employee-page-reveal')) return false;
          if (item.classList.contains('employee-status-legend-bar')) return false;
          if (item.classList.contains('employee-card-grid')) return false;
          if (item.classList.contains('employee-directory-card')) return false;
          const tag = item.tagName.toLowerCase();
          return !['script', 'style', 'template'].includes(tag);
        });

      revealItems.forEach((item, index) => {
        item.classList.add('employee-page-reveal');
        item.style.setProperty('--employee-reveal-delay', `${Math.min(index % 7, 6) * 45}ms`);
        if (item.tagName.toLowerCase() !== 'table') {
          item.classList.add('employee-card-motion');
        }
      });

      document.querySelectorAll('#employee-directory-table-export tbody tr').forEach((row) => {
        row.classList.add('employee-table-row-motion');
      });

      const cardGrid = document.querySelector('#admin-employee-page .employee-card-grid');
      const employeeCards = Array.from(document.querySelectorAll('#admin-employee-page .employee-directory-card'));
      const animatedItems = Array.from(document.querySelectorAll('#admin-employee-page .employee-page-reveal:not(.employee-directory-card)'));
      if (!animatedItems.length) return;

      if (!('IntersectionObserver' in window)) {
        animatedItems.forEach((item) => item.classList.add('employee-reveal-visible'));
        return;
      }

      let lastScrollY = window.scrollY;
      let direction = 'down';

      window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        direction = currentScrollY < lastScrollY ? 'up' : 'down';
        lastScrollY = currentScrollY;
      }, { passive: true });

      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          entry.target.classList.toggle('employee-reveal-from-top', direction === 'up');

          if (entry.isIntersecting) {
            entry.target.classList.add('employee-reveal-visible');
            return;
          }

          entry.target.classList.remove('employee-reveal-visible');
        });
      }, {
        threshold: 0.1,
        rootMargin: '-7% 0px -8% 0px',
      });

      animatedItems.forEach((item) => observer.observe(item));

      if (cardGrid && employeeCards.length) {
        let cardTimers = [];

        const clearCardTimers = () => {
          cardTimers.forEach((timer) => window.clearTimeout(timer));
          cardTimers = [];
        };

        const visibleEmployeeCards = () => employeeCards.filter((card) => {
          const styles = window.getComputedStyle(card);
          return styles.display !== 'none' && styles.visibility !== 'hidden';
        });

        const revealCardsInSequence = () => {
          clearCardTimers();
          visibleEmployeeCards().forEach((card, index) => {
            card.classList.toggle('employee-reveal-from-top', direction === 'up');
            card.classList.remove('employee-reveal-visible');
            cardTimers.push(window.setTimeout(() => {
              card.classList.add('employee-reveal-visible');
            }, index * 140));
          });
        };

        const hideCards = () => {
          clearCardTimers();
          employeeCards.forEach((card) => {
            card.classList.remove('employee-reveal-visible');
          });
        };

        const cardObserver = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              revealCardsInSequence();
              return;
            }

            hideCards();
          });
        }, {
          threshold: 0.08,
          rootMargin: '-7% 0px -8% 0px',
        });

        cardObserver.observe(cardGrid);
      }
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initEmployeePageAnimation, { once: true });
    } else {
      initEmployeePageAnimation();
    }
  })();

  const sidebar = document.querySelector('aside');
  const main = document.querySelector('main');
  if (sidebar && main) {
    sidebar.addEventListener('mouseenter', function() {
      main.classList.remove('main-with-collapsed-sidebar');
      main.classList.add('main-with-expanded-sidebar');
    });
    sidebar.addEventListener('mouseleave', function() {
      main.classList.remove('main-with-expanded-sidebar');
      main.classList.add('main-with-collapsed-sidebar');
    });
  }
</script>

</html>
