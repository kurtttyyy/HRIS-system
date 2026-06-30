<style>
    @page {
        size: 8.5in 13in;
        margin-top: 0;
        margin-bottom: 0;
        margin-left: -70px;
        margin-right: -70px;
    }

    @media print {
        .print-row-two {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 1rem !important;
        }

        .print-row-three {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 1rem !important;
        }

        .print-details-two {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 2rem !important;
            align-items: stretch !important;
        }

        .print-right-divider {
            border-left: 1px solid #000 !important;
            padding-left: 1.25rem !important;
        }

        .print-action-two {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 2rem !important;
            align-items: stretch !important;
        }

        .print-signatory-margin {
            margin-top: 4.5rem !important;
        }

    }
</style>

@php
    $formEarnedVacationValue = (float) ($formEarnedVacation ?? $annualLimit ?? 0);
    $formEarnedSickValue = (float) ($formEarnedSick ?? $sickLimit ?? 0);
    $formEarnedTotalValue = (float) ($formEarnedTotal ?? $totalEarnedDays ?? 0);
    $obfAvailableVacationDays = max((float) ($beginningVacationBalance ?? 0) + $formEarnedVacationValue, 0);
    $obfAvailableSickDays = max((float) ($beginningSickBalance ?? 0) + $formEarnedSickValue, 0);
@endphp

        <form id="application-obf-form" method="POST" action="{{ route('employee.leaveApplication.store') }}" class="space-y-6">
            @csrf
            @if (request()->filled('tab_session'))
                <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
            @endif

            <!-- APPLICATION FORM FOR OFFICIAL BUSINESS AND OFFICIAL TIME -->
            <div id="application-obf-print-area" class="border border-black p-6  space-y-4 ">

                <h4 class="text-center font-semibold text-gray-800 mb-6 tracking-wide uppercase">
                    APPLICATION FORM FOR OFFICIAL BUSINESS AND OFFICIAL TIME
                </h4>

                <!-- Top Information -->
                <div class="print-row-two grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium ">Office / Department</label>
                        <input name="office_department" type="text" class="w-full border rounded px-3 py-2 border-black">
                    </div>

                    <div>
                        <label class="text-sm font-medium">Name (Last, First, Middle)</label>
                        <input
                            name="employee_name"
                            type="text"
                            value="{{ $employeeFormName ?? $employeeDisplayName ?? '' }}"
                            class="w-full border rounded px-3 py-2 border-black"
                        >
                    </div>
                </div>

                <div class="print-row-three grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium">Date of Filing</label>
                        <input id="obf-filing-date" name="filing_date" type="date" class="w-full border rounded px-3 py-2 border-black">
                    </div>

                    <div>
                        <label class="text-sm font-medium">Position</label>
                        <input
                            name="position"
                            type="text"
                            value="{{ $employeeFormPosition ?? '' }}"
                            class="w-full border rounded px-3 py-2 border-black"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium">Salary</label>
                        <input name="salary" type="text" class="w-full border rounded px-3 py-2 border-black  ">
                    </div>
                </div>

                <!-- DETAILS OF APPLICATION -->
                <div class="border-t pt-4 border-black">
                    <h5 class="font-semibold mb-8 text-center tracking-wide uppercase">DETAILS OF APPLICATION</h5>

                    <div class="print-details-two grid grid-cols-1 md:grid-cols-2 gap-6 ">

                        <!-- Left Column -->
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium">Type of Leave</label>
                                <div class="space-y-1 text-sm">
                                    <label><input id="obf-type-time" type="checkbox" class="mr-2">Official Time</label><br>
                                    <label class="block">
                                        <input id="obf-type-others" type="checkbox" class="mr-2">
                                        Others (please specify):
                                    </label>
                                    <input id="obf-other-type" type="text" class="w-full border rounded px-2 py-1 mt-1 border-black" placeholder="Specify other type">
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium">
                                    Number of working days applied for:
                                </label>
                                <input id="obf-working-days" name="number_of_working_days" type="number" min="0" step="0.5" class="w-full border border-black rounded px-3 py-2">
                            </div>


                            <div>
                                <label class="text-sm font-medium">Inclusive Dates</label>
                                <input id="obf-inclusive-dates" name="inclusive_dates" type="text" class="w-full border rounded px-3 py-2 border-black" readonly>
                            </div>
                        </div>


                        <!-- Right Column -->
                        <div class="print-right-divider space-y-3 pl-4 border-l border-black">
                            <div>
                                <label class="text-sm ">
                                    Purpose of Business
                                </label>
                                <div class="space-y-1 text-sm">
                                    <input type="text" class="w-full border rounded px-2 py-1 mt-1 border-black" placeholder="Purpose of Business">
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Venue</label>
                                <div class="space-y-1 text-sm">
                                    <input type="text" class="w-full border rounded px-2 py-1 mt-1 border-black" placeholder="Venue location">
                                    <label class="flex items-center gap-2">
                                        Inclusive Dates
                                    </label>
                                    <input type="text" class="w-full border rounded px-2 py-1 mt-1 border-black" placeholder="Inclusive Dates">
                                </div>
                            </div>



                            <!-- Signature (CENTERED ONLY) -->
                            <div class="flex justify-center mt-6">
                                <div class="w-full md:w-1/2 text-center">
                                            <!-- Signature Line -->
                                    <div class="border-b border-gray-600 w-full h-0.5 mt-20"></div>
                                    <label class="text-sm font-medium block mb-2">Signature of Applicant</label>
                                </div>
                            </div>


                        </div>


                    </div>
                </div>
                



                <!-- DETAILS ON ACTION OF APPLICATION -->
                <div class="border-t pt-6 space-y-4 border-black">
                    <h5 class="font-semibold mb-8 text-center tracking-wide uppercase">DETAILS ON ACTION OF APPLICATION</h5>
                    <div class="print-action-two grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Leave Credits (Left Column) -->
                        <div>
                            <label class="text-sm font-medium">Recommendation</label>

                            <label class="block text-sm">
                                <input type="checkbox" name="recommendation" class="mr-2">
                                Approved
                            </label>

                            <label class="block text-sm">
                                <input type="checkbox" name="recommendation" class="mr-2">
                                Disapproved due to:
                                    <div class="w-full text-center" style="width: 120px; margin-left: 155px; margin-top: -35px;">
                                    <!-- Signature Line -->
                                    <div class="border-b border-gray-600 w-full h-0.5 mt-8"></div>
                                    </div>
                            </label>



                            <div class="flex justify-left" style="margin-top: -20px;">
                                <div class="w-full text-left">
                                    <!-- Signature Line -->
                                    <div class="border-b border-gray-600 h-0.5 mt-20" style="width:275px"></div>
                                    <label class="text-sm  block mb-2">Immediate Supervisor</label>
                                </div>
                            </div>
                            
                            <div class="flex justify-left" style="margin-top: 40px;">
                                <div class="w-full text-left">

                                    <!-- Name -->
                                    <h1 class="text-sm font-bold" style="margin-bottom: -6px; font-size: 17px;">
                                        DR. DIONICIO D. VILORIA, ACP
                                    </h1>

                                    <!-- Signature Line -->
                                    <div class="border-b border-gray-600 h-0.5 mt-1 mb-1" style="width:275px"></div>

                                    <!-- Position -->
                                    <label class="text-sm  block">
                                        Human Resources Director
                                    </label>

                                </div>
                            </div>
                        </div>
                    </div>



                     <!-- Final Approval -->
                    <div class="border-t pt-4 grid grid-cols-1 gap-6 border-black ">

                    <!-- Approved for (Left Column) -->
                    <div class="space-y-4">
                            <label class="block text-sm" style="margin-bottom: -18px;">
                                <input type="checkbox" name="recommendation" class="mr-2">
                                Approved
                            </label>

                            <label class="block text-sm">
                                <input type="checkbox" name="recommendation" class="mr-2">
                                Disapproved due to:
                                    <div class="w-full text-center" style="width: 120px; margin-left: 155px; margin-top: -35px;">
                                    <!-- Signature Line -->
                                    <div class="border-b border-gray-600 w-full h-0.5 mt-8"></div>
                                    </div>
                            </label>
                    </div>
                            <div class="final-approval-signatory flex justify-center mt-6">
                                <div class="w-full text-center" style="width: 240px;">
                                    <h1 class="text-sm font-bold" style="margin-bottom: 0; font-size: 17px; line-height: 1.05;">
                                        TOMAS C. BAUTISTA, PhD
                                    </h1>
                                    <div class="border-b border-gray-600 w-full h-0.5 mt-0"></div>
                                    <label class="text-sm font-medium block mt-1 mb-2">President</label>
                                </div>
                            </div>
                            <label class="block text-sm">
                                Attachments:
                                    <div class="w-full text-center" style="width: 120px; margin-left: 85px; margin-top: -38px;">
                                    <!-- Signature Line -->
                                    <div class="border-b border-gray-600 w-full h-0.5 mt-8"></div>
                                    </div>
                            </label>
                            <label class="block text-sm" style="margin-top: -20px;">
                                Date:
                                    <div class="w-full text-center" style="width: 120px; margin-left: 35px; margin-top: -38px;">
                                    <!-- Signature Line -->
                                    <div class="border-b border-gray-600 w-full h-0.5 mt-8"></div>
                                    </div>
                            </label>

                    </div>


            </div>

            <input type="hidden" name="leave_type" id="obf-leave-type-hidden">
            <input type="hidden" name="as_of_label" value="{{ now()->format('F, Y') }}">
            <input type="hidden" name="earned_date_label" value="{{ $earnedRangeLabel ?? '-' }}">
            <input type="hidden" name="beginning_vacation" id="obf-beginning-vacation-hidden" value="{{ (float) ($beginningVacationBalance ?? 0) }}">
            <input type="hidden" name="beginning_sick" id="obf-beginning-sick-hidden" value="{{ (float) ($beginningSickBalance ?? 0) }}">
            <input type="hidden" name="beginning_total" id="obf-beginning-total-hidden" value="{{ (float) (($beginningVacationBalance ?? 0) + ($beginningSickBalance ?? 0)) }}">
            <input type="hidden" name="earned_vacation" id="obf-earned-vacation-hidden" value="{{ $formEarnedVacationValue }}">
            <input type="hidden" name="earned_sick" id="obf-earned-sick-hidden" value="{{ $formEarnedSickValue }}">
            <input type="hidden" name="earned_total" id="obf-earned-total-hidden" value="{{ $formEarnedTotalValue }}">
            <input type="hidden" name="applied_vacation" id="obf-applied-vacation-hidden" value="0">
            <input type="hidden" name="applied_sick" id="obf-applied-sick-hidden" value="0">
            <input type="hidden" name="applied_total" id="obf-applied-total-hidden" value="0">
            <input type="hidden" name="ending_vacation" id="obf-ending-vacation-hidden" value="{{ $obfAvailableVacationDays }}">
            <input type="hidden" name="ending_sick" id="obf-ending-sick-hidden" value="{{ $obfAvailableSickDays }}">
            <input type="hidden" name="ending_total" id="obf-ending-total-hidden" value="{{ (float) ($obfAvailableVacationDays + $obfAvailableSickDays) }}">
            <input type="hidden" name="days_with_pay" id="obf-days-with-pay-hidden" value="0">
            <input type="hidden" name="days_without_pay" id="obf-days-without-pay-hidden" value="0">
        </div>
                

        </form>
            <p class="download-form-footer mt-4">NC HR Form No. 13 - Application for Official Business and Official Time Rev. 01</p>


        <div class="mt-6 flex justify-end">
            <button
                id="application-obf-download-button"
                type="button"
                onclick="downloadApplicationOBFForm()"
                class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700"
            >
                Download Word Form
            </button>
        </div>



        <script>
            const applicationOBFLogoUrl = @json(file_exists(public_path('images/logo.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png'))) : asset('images/logo.png'));
            const obfLeaveBalanceState = {
                beginningVacation: {{ json_encode((float) ($beginningVacationBalance ?? 0)) }},
                beginningSick: {{ json_encode((float) ($beginningSickBalance ?? 0)) }},
                earnedVacation: {{ json_encode($formEarnedVacationValue) }},
                earnedSick: {{ json_encode($formEarnedSickValue) }},
            };

            function buildApplicationOBFPrintMarkup(printArea) {
                const clone = printArea.cloneNode(true);
                const originalFields = printArea.querySelectorAll('input, textarea, select');
                const clonedFields = clone.querySelectorAll('input, textarea, select');

                originalFields.forEach((field, index) => {
                    const clonedField = clonedFields[index];
                    if (!clonedField) {
                        return;
                    }

                    if (field.tagName === 'INPUT') {
                        const type = (field.getAttribute('type') || 'text').toLowerCase();
                        if (type === 'checkbox' || type === 'radio') {
                            if (field.checked) {
                                clonedField.setAttribute('checked', 'checked');
                            } else {
                                clonedField.removeAttribute('checked');
                            }
                        } else {
                            clonedField.setAttribute('value', field.value || '');
                        }
                    } else if (field.tagName === 'TEXTAREA') {
                        clonedField.textContent = field.value || '';
                    } else if (field.tagName === 'SELECT') {
                        Array.from(clonedField.options).forEach((option, optionIndex) => {
                            const isSelected = field.options[optionIndex]?.selected;
                            if (isSelected) {
                                option.setAttribute('selected', 'selected');
                            } else {
                                option.removeAttribute('selected');
                            }
                        });
                    }
                });

                return clone.outerHTML;
            }

            function escapeApplicationOBFExportValue(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function getApplicationOBFFieldValue(selector) {
                const field = document.querySelector(selector);
                return escapeApplicationOBFExportValue(field?.value || field?.textContent || '');
            }

            function getApplicationOBFCheckbox(id) {
                return document.getElementById(id)?.checked ? '&#9745;' : '&#9744;';
            }

            function getApplicationOBFExportData() {
                return {
                    officeDepartment: getApplicationOBFFieldValue('input[name="office_department"]'),
                    employeeName: getApplicationOBFFieldValue('input[name="employee_name"]'),
                    filingDate: getApplicationOBFFieldValue('input[name="filing_date"]'),
                    position: getApplicationOBFFieldValue('input[name="position"]'),
                    salary: getApplicationOBFFieldValue('input[name="salary"]'),
                    otherType: getApplicationOBFFieldValue('#obf-other-type'),
                    workingDays: getApplicationOBFFieldValue('#obf-working-days'),
                    inclusiveDates: getApplicationOBFFieldValue('#obf-inclusive-dates'),
                    purpose: getApplicationOBFFieldValue('#application-obf-print-area input[placeholder="Purpose of Business"]'),
                    venue: getApplicationOBFFieldValue('#application-obf-print-area input[placeholder="Venue location"]'),
                    venueInclusiveDates: getApplicationOBFFieldValue('#application-obf-print-area input[placeholder="Inclusive Dates"]'),
                    checks: {
                        officialTime: getApplicationOBFCheckbox('obf-type-time'),
                        others: getApplicationOBFCheckbox('obf-type-others'),
                    },
                };
            }

            function buildApplicationOBFWordFormMarkup() {
                const data = getApplicationOBFExportData();
                const officialBusinessCheck = data.checks.officialTime === '&#9744;' && data.checks.others === '&#9744;' ? '&#9745;' : '&#9744;';
                const line = 'border-bottom: 1px solid #000; display: inline-block; min-height: 15px;';
                const cell = 'border: 1px solid #000; padding: 5px 7px; vertical-align: top;';
                const sectionHeading = 'border: 1px solid #000; padding: 2px 0; text-align: center; font-weight: 700; text-transform: uppercase; font-size: 9pt; line-height: 1.05;';
                const bold = 'font-weight: 700;';

                return `
                    <table class="obf-export-form" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #000; border-collapse: collapse; width: 100%; table-layout: fixed;">
                        <colgroup>
                            <col style="width: 16.66%;">
                            <col style="width: 16.66%;">
                            <col style="width: 16.66%;">
                            <col style="width: 16.66%;">
                            <col style="width: 16.66%;">
                            <col style="width: 16.7%;">
                        </colgroup>
                        <tr>
                            <td colspan="2" style="${cell}; height: 0.36in;">
                                <div><span style="${bold}">1. Office/Department</span></div>
                                <div style="${line}; width: 95%;">${data.officeDepartment}&nbsp;</div>
                            </td>
                            <td colspan="4" style="${cell}; height: 0.36in;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse;">
                                    <tr>
                                        <td style="border: 0; font-weight: 700; width: 0.85in; white-space: nowrap;">2. Name</td>
                                        <td style="border: 0; width: 0.85in; white-space: nowrap;">(Last)</td>
                                        <td style="border: 0; width: 0.85in; white-space: nowrap;">(First)</td>
                                        <td style="border: 0; width: 0.85in; white-space: nowrap;">(Middle)</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="border: 0; border-bottom: 1px solid #000; height: 14px; font-size: 8pt;">${data.employeeName}&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="${cell}; height: 0.34in;">
                                <div><span style="${bold}">3. Date of Filing</span></div>
                                <div style="${line}; width: 85%;">${data.filingDate}&nbsp;</div>
                            </td>
                            <td colspan="2" style="${cell}; height: 0.34in;">
                                <div><span style="${bold}">4.Position</span></div>
                                <div style="${line}; width: 85%;">${data.position}&nbsp;</div>
                            </td>
                            <td colspan="2" style="${cell}; height: 0.34in;">
                                <div><span style="${bold}">5.Salary</span></div>
                                <div style="${line}; width: 85%;">${data.salary}&nbsp;</div>
                            </td>
                        </tr>
                        <tr><td colspan="6" style="${sectionHeading}">Details of Application</td></tr>
                        <tr>
                            <td colspan="3" style="${cell}; width: 50%; height: 2.95in; padding: 6px 12px;">
                                <div style="${bold}">6. a) Application for</div>
                                <div style="margin-left: 0.42in; margin-top: 4px;">${officialBusinessCheck} Official Business</div>
                                <div style="margin-left: 0.42in;">${data.checks.officialTime} Official Time</div>
                                <div style="margin-left: 0.42in;">${data.checks.others} Others (Specify)</div>
                                <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 12px;">
                                    <tr>
                                        <td style="border: 0; width: 0.76in;">&nbsp;</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; width: 2.25in; height: 16px;">${data.otherType}&nbsp;</td>
                                        <td style="border: 0;">&nbsp;</td>
                                    </tr>
                                </table>
                                <table cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 24px; width: auto;">
                                    <tr>
                                        <td style="border: 0; font-weight: 700; white-space: nowrap; padding-right: 6px;">6. c) Number of Working Days Applied for:</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; width: 0.85in; height: 16px;">${data.workingDays}&nbsp;</td>
                                    </tr>
                                </table>
                                <table cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 52px; margin-left: 0.28in; width: auto;">
                                    <tr>
                                        <td style="border: 0; white-space: nowrap; padding-right: 6px;">Inclusive dates:</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; width: 1.55in; height: 16px;">${data.inclusiveDates}&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                            <td colspan="3" style="${cell}; width: 50%; height: 2.95in; padding: 6px 16px;">
                                <div style="${bold}">6. b) Purpose of Business</div>
                                <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 18px;">
                                    <tr>
                                        <td style="border: 0; width: 0.55in;">&nbsp;</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; height: 16px;">${data.purpose}&nbsp;</td>
                                    </tr>
                                </table>
                                <div style="margin-left: 0.55in; margin-top: 28px;"><span style="${bold}">2. Venue</span></div>
                                <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 10px;">
                                    <tr>
                                        <td style="border: 0; width: 0.55in;">&nbsp;</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; height: 16px;">${data.venue}&nbsp;</td>
                                    </tr>
                                </table>
                                <div style="margin-left: 0.55in; margin-top: 30px;"><span style="${bold}">3. Inclusive Dates</span></div>
                                <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 8px;">
                                    <tr>
                                        <td style="border: 0; width: 0.55in;">&nbsp;</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; height: 16px;">${data.venueInclusiveDates || data.inclusiveDates}&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 0; width: 0.55in;">&nbsp;</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                                    </tr>
                                </table>
                                <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 46px;">
                                    <tr>
                                        <td style="border: 0; width: 0.95in;">&nbsp;</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                                    </tr>
                                </table>
                                <div style="font-weight: 700; text-align: center; margin-top: 2px;">(Signature of Applicant)</div>
                            </td>
                        </tr>
                        <tr><td colspan="6" style="${sectionHeading}">Details of Action on Application</td></tr>
                        <tr>
                            <td colspan="6" style="${cell}; height: 2.3in; padding: 7px 10px;">
                                <div><span style="${bold}">7. Recommendation</span></div>
                                <div>&#9744; Approved</div>
                                <div>&#9744; Disapproved due to</div>
                                <div style="${line}; width: 2.45in; margin-top: 18px;">&nbsp;</div>
                                <div style="${line}; width: 2.45in; margin-top: 54px;">&nbsp;</div>
                                <div>Immediate Supervisor</div>
                                <div style="margin-top: 58px; font-weight: 700; text-decoration: underline;">DR. DIONICIO D. VILORIA, ACP</div>
                                <div>Human Resources Director</div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" style="${cell}; height: 2.1in; padding: 8px 10px;">
                                <div><span style="${bold}">8. &#9744; Approved</span></div>
                                <div style="margin-left: 0.17in;">&#9744; Disapproved due to</div>
                                <div style="height: 0.72in;">&nbsp;</div>
                                <div style="font-weight: 700; text-align: center; text-decoration: underline; margin-top: 0.38in;">TOMAS C. BAUTISTA, PhD</div>
                                <div style="text-align: center;">President</div>
                                <div style="height: 0.2in;"></div>
                                <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse;">
                                    <tr>
                                        <td style="border: 0; width: 1.1in; padding: 2px 0;">Attachments:</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; width: 1.7in; padding: 2px 0;">&nbsp;</td>
                                        <td style="border: 0;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 0; width: 1.1in; padding: 3px 0 0;">Date:</td>
                                        <td style="border: 0; border-bottom: 1px solid #000; width: 1.7in; padding: 3px 0 0;">&nbsp;</td>
                                        <td style="border: 0;">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <div style="text-align: center; font-size: 9pt; margin-top: 0.26in;">
                        NC HR Form No. 13 - Application for Official Business and Official Time Rev. 01
                    </div>
                `;
            }

            function buildApplicationOBFWordDocument() {
                return `
                    <!doctype html>
                    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <meta charset="utf-8">
                        <title>Application for Official Business / Official Time</title>
                        <!--[if gte mso 9]>
                        <xml>
                            <w:WordDocument>
                                <w:View>Print</w:View>
                                <w:Zoom>90</w:Zoom>
                                <w:DoNotOptimizeForBrowser/>
                            </w:WordDocument>
                        </xml>
                        <![endif]-->
                        <style>
                            @page {
                                size: 612pt 936pt;
                                margin: 13pt 18pt;
                            }
                            @page WordSection1 {
                                size: 612pt 936pt;
                                margin: 13pt 18pt;
                            }
                            html,
                            body {
                                width: 576pt;
                                margin: 0;
                                padding: 0;
                                background: #fff;
                                color: #000;
                                font-family: Calibri, Arial, sans-serif;
                                font-size: 8.5pt;
                                line-height: 1.14;
                            }
                            .WordSection1 {
                                page: WordSection1;
                                width: 576pt;
                            }
                            .print-form-header {
                                text-align: center;
                                margin-bottom: 0.04in;
                            }
                            .print-form-header img {
                                display: block;
                                width: 4.3in;
                                height: 0.68in;
                                max-width: 100%;
                                margin: 0 auto 0.04in;
                            }
                            .print-form-header h3 {
                                font-size: 10pt;
                                font-weight: 700;
                                line-height: 1.05;
                                margin: 0;
                                text-transform: uppercase;
                            }
                            .obf-export-form,
                            .obf-export-form table {
                                mso-table-lspace: 0pt;
                                mso-table-rspace: 0pt;
                            }
                            .obf-export-form td,
                            .obf-export-form th {
                                font-size: 8.5pt;
                                line-height: 1.14;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="WordSection1">
                            <div class="print-form-header">
                                <img src="${applicationOBFLogoUrl}" alt="Logo" width="413" height="65">
                                <h3>Office of the Human Resource</h3>
                                <h3>Application Form for Official Business and Official Time</h3>
                            </div>
                            ${buildApplicationOBFWordFormMarkup()}
                        </div>
                    </body>
                    </html>
                `;
            }

            function downloadApplicationOBFWordFile(html) {
                const blob = new Blob(['\ufeff', html], { type: 'application/msword' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `official-business-form-${new Date().toISOString().slice(0, 10)}.doc`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            }

            function deriveOBFLeaveTypeValue() {
                const types = [];
                const timeCheckbox = document.getElementById('obf-type-time');
                const othersCheckbox = document.getElementById('obf-type-others');
                const otherTypeInput = document.getElementById('obf-other-type');
                if (timeCheckbox?.checked) {
                    types.push('Official Time');
                }
                if (othersCheckbox?.checked) {
                    const otherLabel = (otherTypeInput?.value || '').trim();
                    types.push(otherLabel !== '' ? `Others: ${otherLabel}` : 'Others');
                }

                return types.join(', ');
            }

            function formatOBFInclusiveDateRange(startDate, endDate) {
                const startMonth = startDate.toLocaleString('en-US', { month: 'short' });
                const endMonth = endDate.toLocaleString('en-US', { month: 'short' });
                const startDay = startDate.getDate();
                const endDay = endDate.getDate();
                const startYear = startDate.getFullYear();
                const endYear = endDate.getFullYear();

                if (startYear === endYear && startMonth === endMonth) {
                    if (startDay === endDay) {
                        return `${startMonth} ${startDay}, ${startYear}`;
                    }

                    return `${startMonth} ${startDay}-${endDay}, ${startYear}`;
                }

                if (startYear === endYear) {
                    return `${startMonth} ${startDay} - ${endMonth} ${endDay}, ${startYear}`;
                }

                return `${startMonth} ${startDay}, ${startYear} - ${endMonth} ${endDay}, ${endYear}`;
            }

            function updateOBFInclusiveDates() {
                const daysInput = document.getElementById('obf-working-days');
                const filingDateInput = document.getElementById('obf-filing-date');
                const inclusiveDatesInput = document.getElementById('obf-inclusive-dates');

                if (!daysInput || !inclusiveDatesInput) {
                    return;
                }

                const requestedDays = parseFloat(daysInput.value || '0');
                if (!Number.isFinite(requestedDays) || requestedDays <= 0) {
                    inclusiveDatesInput.value = '';
                    return;
                }

                const wholeDays = Math.max(1, Math.ceil(requestedDays));
                const baseDate = filingDateInput?.value
                    ? new Date(`${filingDateInput.value}T00:00:00`)
                    : new Date();
                const startDate = new Date(baseDate.getFullYear(), baseDate.getMonth(), baseDate.getDate());
                const endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + (wholeDays - 1));

                inclusiveDatesInput.value = formatOBFInclusiveDateRange(startDate, endDate);
            }

            function updateOBFPayDays() {
                const daysInput = document.getElementById('obf-working-days');
                const appliedVacationInput = document.getElementById('obf-applied-vacation-hidden');
                const appliedSickInput = document.getElementById('obf-applied-sick-hidden');
                const appliedTotalInput = document.getElementById('obf-applied-total-hidden');
                const endingVacationInput = document.getElementById('obf-ending-vacation-hidden');
                const endingSickInput = document.getElementById('obf-ending-sick-hidden');
                const endingTotalInput = document.getElementById('obf-ending-total-hidden');
                const withPayInput = document.getElementById('obf-days-with-pay-hidden');
                const withoutPayInput = document.getElementById('obf-days-without-pay-hidden');

                if (
                    !daysInput
                    || !appliedVacationInput
                    || !appliedSickInput
                    || !appliedTotalInput
                    || !endingVacationInput
                    || !endingSickInput
                    || !endingTotalInput
                    || !withPayInput
                    || !withoutPayInput
                ) {
                    return;
                }

                const availableVacation = Math.max(obfLeaveBalanceState.beginningVacation + obfLeaveBalanceState.earnedVacation, 0);
                const availableSick = Math.max(obfLeaveBalanceState.beginningSick + obfLeaveBalanceState.earnedSick, 0);
                const requestedDays = parseFloat(daysInput.value || '0');
                if (!Number.isFinite(requestedDays) || requestedDays <= 0) {
                    appliedVacationInput.value = '0.0';
                    appliedSickInput.value = '0.0';
                    appliedTotalInput.value = '0.0';
                    endingVacationInput.value = availableVacation.toFixed(1);
                    endingSickInput.value = availableSick.toFixed(1);
                    endingTotalInput.value = (availableVacation + availableSick).toFixed(1);
                    withPayInput.value = '0.0';
                    withoutPayInput.value = '0.0';
                    return;
                }

                // OBF types are treated as work-with-pay and do not consume leave credits.
                appliedVacationInput.value = '0.0';
                appliedSickInput.value = '0.0';
                appliedTotalInput.value = '0.0';
                endingVacationInput.value = availableVacation.toFixed(1);
                endingSickInput.value = availableSick.toFixed(1);
                endingTotalInput.value = (availableVacation + availableSick).toFixed(1);
                withPayInput.value = requestedDays.toFixed(1);
                withoutPayInput.value = '0.0';
            }

            async function saveApplicationOBFRecord() {
                const form = document.getElementById('application-obf-form');
                if (!form) {
                    return false;
                }

                const leaveTypeInput = document.getElementById('obf-leave-type-hidden');
                if (leaveTypeInput) {
                    leaveTypeInput.value = deriveOBFLeaveTypeValue();
                }
                updateOBFPayDays();

                try {
                    const payload = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });

                    return response.ok;
                } catch (error) {
                    console.error('Error saving OBF application', error);
                    return false;
                }
            }

            document.getElementById('obf-working-days')?.addEventListener('input', () => {
                updateOBFInclusiveDates();
                updateOBFPayDays();
            });
            document.getElementById('obf-filing-date')?.addEventListener('change', updateOBFInclusiveDates);
            updateOBFInclusiveDates();
            updateOBFPayDays();

            async function downloadApplicationOBFForm() {
                const button = document.getElementById('application-obf-download-button');
                const originalButtonText = button ? button.textContent : '';

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Preparing...';
                    button.classList.add('opacity-70', 'cursor-not-allowed');
                }

                const isSaved = await saveApplicationOBFRecord();
                if (!isSaved) {
                    console.warn('OBF application was not saved, continuing with download.');
                }

                downloadApplicationOBFWordFile(buildApplicationOBFWordDocument());

                if (button) {
                    button.disabled = false;
                    button.textContent = originalButtonText;
                    button.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            }
        </script>
