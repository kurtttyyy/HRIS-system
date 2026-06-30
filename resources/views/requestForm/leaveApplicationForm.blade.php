<style>
    .recommendation-disapprove-line {
        margin-left: 5.9rem;
    }

    .final-disapprove-line {
        margin-left: 5.55rem;
    }

    .final-signatory-block {
        text-align: center;
    }

    .final-signatory-line {
        margin-left: auto;
        margin-right: auto;
        width: 18rem;
        max-width: 100%;
    }

    .final-signatory-label {
        display: block;
        margin-left: auto;
        margin-right: auto;
        max-width: 18rem;
        text-align: center;
        width: 100%;
    }

    .print-signatory-margin {
        font-size: 0;
        white-space: nowrap;
        width: 100%;
    }

    .print-signatory-margin .final-signatory-cell {
        display: inline-block;
        font-size: 0.875rem;
        text-align: center;
        vertical-align: top;
        width: 50%;
    }

    .print-signatory-margin .final-signatory-cell:first-child {
        padding-right: 1.25rem;
    }

    .print-signatory-margin .final-signatory-cell:last-child {
        padding-left: 1.25rem;
    }

    @page {
        size: 8.5in 13in;
        margin: 0.18in 0.22in;
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
            font-size: 0 !important;
            margin-top: 4.5rem !important;
            white-space: nowrap !important;
            width: 100% !important;
        }

        .print-signatory-margin .final-signatory-cell {
            display: inline-block !important;
            font-size: 0.875rem !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            text-align: center !important;
            vertical-align: top !important;
            width: 50% !important;
        }

        .final-signatory-line {
            margin-left: auto !important;
            margin-right: auto !important;
            width: 2.35in !important;
        }

        .final-signatory-label {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: 2.35in !important;
            text-align: center !important;
        }

        .print-signatory-margin .final-signatory-cell:first-child {
            padding-right: 1.25rem !important;
        }

        .print-signatory-margin .final-signatory-cell:last-child {
            padding-left: 1.25rem !important;
        }

    }
</style>

@php
    $formEarnedVacationValue = (float) ($formEarnedVacation ?? $annualLimit ?? 0);
    $formEarnedSickValue = (float) ($formEarnedSick ?? $sickLimit ?? 0);
    $formEarnedTotalValue = (float) ($formEarnedTotal ?? $totalEarnedDays ?? 0);
    $availableVacationDays = max((float) ($beginningVacationBalance ?? 0) + $formEarnedVacationValue, 0);
    $availableSickDays = max((float) ($beginningSickBalance ?? 0) + $formEarnedSickValue, 0);
@endphp

<form id="leave-application-form" method="POST" action="{{ route('employee.leaveApplication.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if (request()->filled('tab_session'))
        <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
    @endif

    <div id="leave-application-print-area" class="space-y-5  border border-black bg-white p-6 text-sm text-black">
        <div class="print-row-two grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-medium">Office / Department</label>
                <input name="office_department" type="text" class="w-full rounded border border-black px-3 py-1 text-base leading-tight">
            </div>
            <div>
                <label class="mb-1 block font-medium">Name (Last, First, Middle)</label>
                <input
                    name="employee_name"
                    type="text"
                    value="{{ old('employee_name', $employeeFormName ?? $employeeDisplayName ?? '') }}"
                    class="w-full rounded border border-black px-3 py-1 text-base leading-tight"
                >
            </div>
        </div>

        <div class="print-row-three grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block font-medium">Date of Filing</label>
                <input name="filing_date" type="date" class="w-full rounded border border-black px-3 py-1 text-base leading-tight">
            </div>
            <div>
                <label class="mb-1 block font-medium">Position</label>
                <input
                    name="position"
                    type="text"
                    value="{{ old('position', $employeeFormPosition ?? '') }}"
                    class="w-full rounded border border-black px-3 py-1 text-base leading-tight"
                >
            </div>
            <div>
                <label class="mb-1 block font-medium">Salary</label>
                <input name="salary" type="text" class="w-full rounded border border-black px-3 py-1 text-base leading-tight">
            </div>
        </div>

        <section class="leave-details-section border-t border-black pt-5">
            <h5 class="mb-6 text-center font-bold tracking-wide uppercase">Details of Application</h5>

            <div class="print-details-two grid grid-cols-1 gap-8 md:grid-cols-2 md:items-stretch">
                <div class="leave-details-left space-y-4">
                    <div>
                        <p class="mb-2 font-medium">Type of Leave</p>
                        <div>
                            <label class="block"><input id="leave-type-vacation" type="checkbox" class="mr-2">Vacation</label>
                            <label class="block"><input id="leave-type-sick" type="checkbox" class="mr-2">Sick</label>
                            <label class="block"><input type="checkbox" class="mr-2">Maternity</label>
                            <label class="block"><input type="checkbox" class="mr-2">Paternity</label>
                            <label class="block"><input type="checkbox" class="mr-2">Others (please specify)</label>
                            <input type="text" class="mt-1 w-full rounded border border-black px-3 py-1 text-base leading-tight" placeholder="Specify other type of leave">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block font-medium">Number of working days applied for</label>
                        <input id="leave-days-requested" name="number_of_working_days" type="number" min="0" step="0.5" class="w-full rounded border border-black px-3 py-1 text-base leading-tight">
                    </div>

                    <div>
                        <label class="mb-1 block font-medium">Inclusive Dates</label>
                        <input id="leave-inclusive-dates" name="inclusive_dates" type="text" class="w-full rounded border border-black px-3 py-1 text-base leading-tight" readonly>
                    </div>
                </div>

                <div class="leave-details-right print-right-divider border-l border-black pl-5">
                    <div>
                        <p class="mb-2 font-medium">Where leave will be spent</p>
                        <div class="leave-spent-group space-y-1">
                            <label class="block"><input type="checkbox" class="mr-2">Within the Philippines</label>
                            <label class="block"><input type="checkbox" class="mr-2">Abroad (please specify)</label>
                            <input type="text" class="mt-1 w-full rounded border border-black px-2 py-1" placeholder="Specify country">
                        </div>
                    </div>

                    <div class="sick-leave-section mt-4 space-y-4 border-t border-black pt-4">
                        <div>
                            <p class="mb-2 font-medium">In case of sick leave</p>
                            <div class="sick-leave-options space-y-1">
                                <label class="block"><input type="checkbox" class="mr-2">In hospital (please specify)</label>
                                <input type="text" class="w-full rounded border border-black px-2 py-1" placeholder="Hospital name">
                                <label class="block"><input type="checkbox" class="mr-2">Outpatient (please specify)</label>
                                <input type="text" class="w-full rounded border border-black px-2 py-1" placeholder="Outpatient details">
                            </div>
                        </div>

                        <div class="commutation-group">
                            <p class="mb-2 font-medium">Commutation</p>
                            <label class="block"><input type="radio" name="commutation" value="Requested" class="mr-2">Requested</label>
                            <label class="block"><input type="radio" name="commutation" value="Not Requested" class="mr-2">Not Requested</label>
                        </div>

                        <div class="applicant-signature-block pt-8 text-center">
                            <div class="mx-auto h-0.5 w-full max-w-xs border-b border-black"></div>
                            <p class="mt-2 font-medium">Signature of Applicant</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="action-details-section border-t border-black pt-6">
            <h5 class="mb-6 text-center font-bold tracking-wide uppercase">Details on Action of Application</h5>

            <div class="print-action-two grid grid-cols-1 gap-8 md:grid-cols-2">
                <div class="leave-credits-block">
                    <label class="leave-credits-label block font-medium">
                        Certification of Leave Credits (As of)
                        <input type="text" value="{{ now()->format('F, Y') }}" class="mt-1 w-full border-0 border-b-2 border-black px-0 py-1 focus:outline-none focus:ring-0" readonly>
                    </label>

                    <table class="leave-credits-table mt-3 w-full border-collapse border border-black text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-black px-2 py-1"></th>
                                <th class="border border-black px-2 py-1">Vacation</th>
                                <th class="border border-black px-2 py-1">Sick</th>
                                <th class="border border-black px-2 py-1">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-black px-2 py-1">Beginning Balance</td>
                                <td id="beginning-vacation-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format((float) ($beginningVacationBalance ?? 0), 1, '.', ''), '0'), '.') }}</td>
                                <td id="beginning-sick-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format((float) ($beginningSickBalance ?? 0), 1, '.', ''), '0'), '.') }}</td>
                                <td id="beginning-total-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format((float) (($beginningVacationBalance ?? 0) + ($beginningSickBalance ?? 0)), 1, '.', ''), '0'), '.') }}</td>
                            </tr>
                            <tr>
                                <td class="border border-black px-2 py-2">
                                    <span class="block">Add: Earned Leave/s</span>
                                    <span class="block">Date: {{ $earnedRangeLabel ?? '-' }}</span>
                                </td>
                                <td id="earned-vacation-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format($formEarnedVacationValue, 1, '.', ''), '0'), '.') }}</td>
                                <td id="earned-sick-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format($formEarnedSickValue, 1, '.', ''), '0'), '.') }}</td>
                                <td id="earned-total-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format($formEarnedTotalValue, 1, '.', ''), '0'), '.') }}</td>
                            </tr>
                            <tr>
                                <td class="border border-black px-2 py-1">Less: Applied Leave/s</td>
                                <td id="applied-vacation-balance" class="border border-black px-2 py-1">0</td>
                                <td id="applied-sick-balance" class="border border-black px-2 py-1">0</td>
                                <td id="applied-total-balance" class="border border-black px-2 py-1">0</td>
                            </tr>
                            <tr>
                                <td class="border border-black px-2 py-1">Ending Balance</td>
                                <td id="ending-vacation-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format((float) (($beginningVacationBalance ?? 0) + $formEarnedVacationValue), 1, '.', ''), '0'), '.') }}</td>
                                <td id="ending-sick-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format((float) (($beginningSickBalance ?? 0) + $formEarnedSickValue), 1, '.', ''), '0'), '.') }}</td>
                                <td id="ending-total-balance" class="border border-black px-2 py-1">{{ rtrim(rtrim(number_format((float) ((($beginningVacationBalance ?? 0) + $formEarnedVacationValue) + (($beginningSickBalance ?? 0) + $formEarnedSickValue)), 1, '.', ''), '0'), '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="recommendation-block space-y-4 border-l border-black pl-5">
                    <p class="font-medium">Recommendation</p>
                    <label class="block"><input type="checkbox" name="recommendation" class="mr-2">Approved</label>
                    <div>
                        <label class="block"><input type="checkbox" name="recommendation" class="mr-2">Disapproved due to:</label>
                        <div class="recommendation-disapprove-line mt-2 h-0.5 w-56 border-b border-black"></div>
                    </div>

                    <div class="pt-10 text-center">
                        <div class="mx-auto h-0.5 w-full max-w-xs border-b border-black"></div>
                        <p class="mt-2 font-medium">Authorized Signature</p>
                    </div>
                </div>
            </div>

            <div class="print-action-two mt-6 grid grid-cols-1 gap-8 border-t border-black pt-5 md:grid-cols-2">
                <div class="approved-for-block space-y-3">
                    <p class="font-medium">Approved for:</p>
                    <div class="flex items-end gap-2">
                        <div id="days-with-pay-value" class="min-w-[6rem] border-b border-black text-center">0</div>
                        <span>Day(s) with pay</span>
                    </div>
                    <div class="flex items-end gap-2">
                        <div id="days-without-pay-value" class="min-w-[6rem] border-b border-black text-center">0</div>
                        <span>Day(s) without pay</span>
                    </div>
                    <div class="flex items-end gap-2">
                        <div class="approved-for-others-line h-0.5 w-24 border-b border-black"></div>
                        <span>Others (please specify)</span>
                    </div>
                </div>

                <div class="final-disapprove-block">
                    <p class="font-medium">Disapproved due to:</p>
                    <div class="final-disapprove-line mt-2 h-0.5 w-56 border-b border-black"></div>
                </div>
            </div>

            <table class="print-signatory-margin mt-10 text-sm" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; margin-top: 2.5rem; border-collapse: collapse; table-layout: fixed; border: 0;">
                <tr style="border: 0;">
                    <td width="50%" align="center" valign="top" style="width: 50%; border: 0; padding: 0 1.25rem 0 0; text-align: center; vertical-align: top; font-size: 0.875rem;">
                        <div class="final-signatory-line h-0.5 border-b border-black" style="width: 2.4in; max-width: 100%; height: 1px; margin: 0 auto; border-bottom: 1px solid #000;"></div>
                        <p class="final-signatory-label mt-2 font-semibold" style="width: 2.4in; max-width: 100%; margin: 0.5rem auto 0; text-align: center; font-size: 0.875rem;">President</p>
                    </td>
                    <td width="50%" align="center" valign="top" style="width: 50%; border: 0; padding: 0 0 0 1.25rem; text-align: center; vertical-align: top; font-size: 0.875rem;">
                        <div class="final-signatory-line h-0.5 border-b border-black" style="width: 2.4in; max-width: 100%; height: 1px; margin: 0 auto; border-bottom: 1px solid #000;"></div>
                        <p class="final-signatory-label mt-2 font-semibold" style="width: 2.4in; max-width: 100%; margin: 0.5rem auto 0; text-align: center; font-size: 0.875rem;">Director of Human Resources</p>
                    </td>
                </tr>
            </table>

            <div class="mt-10 text-center">
                <div class="mx-auto h-0.5 border-b border-black" style="width: 2.4in; max-width: 100%;"></div>
                <p class="mt-2 font-semibold">Date</p>
            </div>
        </section>
    </div>

    <div id="leave-medical-certificate-section" class="hidden rounded-lg border border-blue-200 bg-blue-50 p-4">
        <label for="leave-medical-certificate" class="block text-sm font-semibold text-blue-950">
            Medical Certificate / Receipt <span class="text-red-600">*</span>
        </label>
        <p class="mt-1 text-xs text-blue-800">
            Required for Sick Leave. Upload a PDF or photo (including iPhone HEIC/HEIF) up to 15 MB.
        </p>
        <input
            id="leave-medical-certificate"
            name="medical_receipt"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.heif,application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif"
            class="mt-3 block w-full rounded-md border border-blue-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded file:border-0 file:bg-blue-600 file:px-3 file:py-1.5 file:font-semibold file:text-white hover:file:bg-blue-700"
        >
        <p id="leave-medical-certificate-name" class="mt-2 hidden text-xs font-medium text-slate-700"></p>
    </div>

    <input type="hidden" name="leave_type" id="leave-type-hidden">
    <input type="hidden" name="as_of_label" value="{{ now()->format('F, Y') }}">
    <input type="hidden" name="earned_date_label" value="{{ $earnedRangeLabel ?? '-' }}">
    <input type="hidden" name="beginning_vacation" id="beginning-vacation-hidden" value="{{ (float) ($beginningVacationBalance ?? 0) }}">
    <input type="hidden" name="beginning_sick" id="beginning-sick-hidden" value="{{ (float) ($beginningSickBalance ?? 0) }}">
    <input type="hidden" name="beginning_total" id="beginning-total-hidden" value="{{ (float) (($beginningVacationBalance ?? 0) + ($beginningSickBalance ?? 0)) }}">
    <input type="hidden" name="earned_vacation" id="earned-vacation-hidden" value="{{ $formEarnedVacationValue }}">
    <input type="hidden" name="earned_sick" id="earned-sick-hidden" value="{{ $formEarnedSickValue }}">
    <input type="hidden" name="earned_total" id="earned-total-hidden" value="{{ $formEarnedTotalValue }}">
    <input type="hidden" name="applied_vacation" id="applied-vacation-hidden" value="0">
    <input type="hidden" name="applied_sick" id="applied-sick-hidden" value="0">
    <input type="hidden" name="applied_total" id="applied-total-hidden" value="0">
    <input type="hidden" name="ending_vacation" id="ending-vacation-hidden" value="{{ (float) (($beginningVacationBalance ?? 0) + $formEarnedVacationValue) }}">
    <input type="hidden" name="ending_sick" id="ending-sick-hidden" value="{{ (float) (($beginningSickBalance ?? 0) + $formEarnedSickValue) }}">
    <input type="hidden" name="ending_total" id="ending-total-hidden" value="{{ (float) ((($beginningVacationBalance ?? 0) + $formEarnedVacationValue) + (($beginningSickBalance ?? 0) + $formEarnedSickValue)) }}">
    <input type="hidden" name="days_with_pay" id="days-with-pay-hidden" value="0">
    <input type="hidden" name="days_without_pay" id="days-without-pay-hidden" value="0">

    <div class="flex justify-end">
        <button
            id="leave-application-download-button"
            type="button"
            onclick="downloadLeaveApplicationWordFormV2()"
            class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700"
        >
            Download Word Form
        </button>
    </div>
</form>

<section id="leave-application-instructions" class="-mt-4 bg-white p-4 text-xs leading-relaxed" style="margin-top: -60px;">
    <p class="font-bold uppercase">Instructions:</p>
    <ol class="mt-1 list-decimal space-y-1 pl-5">
        <li style="margin-bottom: -7px">Application for vacation or sick leave for one full day or more shall be made on this form and to be accomplished at least in a duplicate.</li>
        <li style="margin-bottom: -7px">Application for vacation leave shall be filed five (5) days in advance before going on such leave.</li>
        <li style="margin-bottom: -7px">Application for sick leave filed in advance or exceeding five (5) days shall be accompanied by a medical certificate.</li>
        <li style="margin-bottom: -7px">An employee who is absent without approved leave shall not be entitled salary corresponding to the period of absence.</li>
        <li style="margin-bottom: -7px">Application for leave of absence for thirty (30) calendar days or more shall be accompanied by clearance from money and property accountabilities.</li>
    </ol>
    <p class="download-form-footer mt-4">NC HR Form No. 14 -Leave Application Form Rev. 01</p>
</section>

<script>
    const leaveApplicationLogoUrl = @json(file_exists(public_path('images/logo.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png'))) : asset('images/logo.png'));
    const leaveApplicationCsrfTokenUrl = @json(route('csrf.token'));
    const leaveBalanceState = {
        availableVacation: {{ json_encode((float) $availableVacationDays) }},
        availableSick: {{ json_encode((float) $availableSickDays) }},
        beginningVacation: {{ json_encode((float) ($beginningVacationBalance ?? 0)) }},
        beginningSick: {{ json_encode((float) ($beginningSickBalance ?? 0)) }},
        earnedVacation: {{ json_encode($formEarnedVacationValue) }},
        earnedSick: {{ json_encode($formEarnedSickValue) }},
    };

    function formatDayValue(value) {
        const safeValue = Number.isFinite(value) ? Math.max(0, value) : 0;
        return safeValue % 1 === 0 ? `${safeValue}` : safeValue.toFixed(1);
    }

    function validateLeaveRequestBalance() {
        const requestedDaysInput = document.getElementById('leave-days-requested');

        if (!requestedDaysInput) {
            return true;
        }

        const requestedDays = parseFloat(requestedDaysInput.value || '0');
        if (!Number.isFinite(requestedDays) || requestedDays <= 0) {
            return true;
        }
        return true;
    }

    function deriveLeaveTypeValue() {
        const vacationCheckbox = document.getElementById('leave-type-vacation');
        const sickCheckbox = document.getElementById('leave-type-sick');

        if (vacationCheckbox?.checked && sickCheckbox?.checked) {
            return 'Vacation/Sick';
        }
        if (vacationCheckbox?.checked) {
            return 'Annual Leave';
        }
        if (sickCheckbox?.checked) {
            return 'Sick Leave';
        }

        return '';
    }

    function updateLeaveMedicalCertificateField() {
        const sickCheckbox = document.getElementById('leave-type-sick');
        const section = document.getElementById('leave-medical-certificate-section');
        const input = document.getElementById('leave-medical-certificate');
        if (!section || !input) {
            return;
        }

        const isSickLeave = Boolean(sickCheckbox?.checked);
        section.classList.toggle('hidden', !isSickLeave);
        input.required = isSickLeave;

        if (!isSickLeave) {
            input.value = '';
            updateLeaveMedicalCertificateName();
        }
    }

    function updateLeaveMedicalCertificateName() {
        const input = document.getElementById('leave-medical-certificate');
        const name = document.getElementById('leave-medical-certificate-name');
        if (!name) {
            return;
        }

        const file = input?.files?.[0];
        name.textContent = file ? `Selected: ${file.name}` : '';
        name.classList.toggle('hidden', !file);
    }

    function validateLeaveMedicalCertificate() {
        const sickCheckbox = document.getElementById('leave-type-sick');
        const input = document.getElementById('leave-medical-certificate');
        if (!sickCheckbox?.checked) {
            return true;
        }

        const file = input?.files?.[0];
        if (!file) {
            document.getElementById('leave-medical-certificate-section')?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
            input?.focus();
            alert('Please upload a medical certificate or receipt for Sick Leave.');
            return false;
        }

        if (file.size > 15 * 1024 * 1024) {
            input.value = '';
            updateLeaveMedicalCertificateName();
            input.focus();
            alert('The medical certificate or receipt must not be larger than 15 MB.');
            return false;
        }

        return true;
    }

    function updateLeaveSummaryTable() {
        const vacationCheckbox = document.getElementById('leave-type-vacation');
        const sickCheckbox = document.getElementById('leave-type-sick');
        const requestedDaysInput = document.getElementById('leave-days-requested');

        const requestedDaysRaw = parseFloat(requestedDaysInput?.value || '0');
        const requestedDays = Number.isFinite(requestedDaysRaw) && requestedDaysRaw > 0 ? requestedDaysRaw : 0;

        const availableVacation = Math.max(leaveBalanceState.beginningVacation + leaveBalanceState.earnedVacation, 0);
        const availableSick = Math.max(leaveBalanceState.beginningSick + leaveBalanceState.earnedSick, 0);

        let appliedVacation = 0;
        let appliedSick = 0;

        if (vacationCheckbox?.checked && !sickCheckbox?.checked) {
            appliedVacation = Math.min(requestedDays, availableVacation);
        } else if (sickCheckbox?.checked && !vacationCheckbox?.checked) {
            appliedSick = Math.min(requestedDays, availableSick);
        } else if (vacationCheckbox?.checked && sickCheckbox?.checked) {
            const splitHalf = requestedDays / 2;
            appliedVacation = Math.min(splitHalf, availableVacation);
            appliedSick = Math.min(splitHalf, availableSick);
        }

        const appliedTotal = appliedVacation + appliedSick;
        const endingVacation = Math.max(availableVacation - appliedVacation, 0);
        const endingSick = Math.max(availableSick - appliedSick, 0);
        const endingTotal = endingVacation + endingSick;
        const withoutPay = Math.max(requestedDays - appliedTotal, 0);

        const map = {
            'applied-vacation-balance': appliedVacation,
            'applied-sick-balance': appliedSick,
            'applied-total-balance': appliedTotal,
            'ending-vacation-balance': endingVacation,
            'ending-sick-balance': endingSick,
            'ending-total-balance': endingTotal,
            'days-with-pay-value': appliedTotal,
            'days-without-pay-value': withoutPay,
        };

        Object.keys(map).forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = formatDayValue(map[id]);
            }
        });

        const hiddenMap = {
            'leave-type-hidden': deriveLeaveTypeValue(),
            'applied-vacation-hidden': appliedVacation,
            'applied-sick-hidden': appliedSick,
            'applied-total-hidden': appliedTotal,
            'ending-vacation-hidden': endingVacation,
            'ending-sick-hidden': endingSick,
            'ending-total-hidden': endingTotal,
            'days-with-pay-hidden': appliedTotal,
            'days-without-pay-hidden': withoutPay,
        };

        Object.keys(hiddenMap).forEach((id) => {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }

            const value = hiddenMap[id];
            if (typeof value === 'string') {
                el.value = value;
            } else {
                el.value = Number.isFinite(value) ? value.toFixed(1) : '0.0';
            }
        });
    }

    function formatInclusiveDateRange(startDate, endDate) {
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

    function updateInclusiveDatesFromRequestedDays() {
        const requestedDaysInput = document.getElementById('leave-days-requested');
        const inclusiveDatesInput = document.getElementById('leave-inclusive-dates');
        if (!requestedDaysInput || !inclusiveDatesInput) {
            return;
        }

        const requestedDays = parseFloat(requestedDaysInput.value || '0');
        if (!Number.isFinite(requestedDays) || requestedDays <= 0) {
            inclusiveDatesInput.value = '';
            return;
        }

        const wholeDays = Math.max(1, Math.ceil(requestedDays));
        const today = new Date();
        const startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + (wholeDays - 1));

        inclusiveDatesInput.value = formatInclusiveDateRange(startDate, endDate);
    }

    document.getElementById('leave-type-vacation')?.addEventListener('change', validateLeaveRequestBalance);
    document.getElementById('leave-type-sick')?.addEventListener('change', validateLeaveRequestBalance);
    document.getElementById('leave-type-vacation')?.addEventListener('change', updateLeaveSummaryTable);
    document.getElementById('leave-type-sick')?.addEventListener('change', updateLeaveSummaryTable);
    document.getElementById('leave-type-sick')?.addEventListener('change', updateLeaveMedicalCertificateField);
    document.getElementById('leave-medical-certificate')?.addEventListener('change', updateLeaveMedicalCertificateName);
    document.getElementById('leave-days-requested')?.addEventListener('input', function () {
        validateLeaveRequestBalance();
        updateInclusiveDatesFromRequestedDays();
        updateLeaveSummaryTable();
    });
    updateInclusiveDatesFromRequestedDays();
    updateLeaveSummaryTable();
    updateLeaveMedicalCertificateField();

    function buildLeaveApplicationExportSignatoryTable() {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <table class="print-signatory-margin mt-10 text-sm" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="width: 100%; margin-top: 2.5rem; border-collapse: collapse; table-layout: fixed; border: 0;">
                <tr style="border: 0;">
                    <td width="50%" align="center" valign="top" style="width: 50%; border: 0; padding: 0 1.25rem 0 0; text-align: center; vertical-align: top;">
                        <div style="width: 2.4in; max-width: 100%; height: 1px; margin: 0 auto; border-bottom: 1px solid #000;"></div>
                        <p style="width: 2.4in; max-width: 100%; margin: 0.5rem auto 0; text-align: center; font-weight: 600;">President</p>
                    </td>
                    <td width="50%" align="center" valign="top" style="width: 50%; border: 0; padding: 0 0 0 3rem; text-align: center; vertical-align: top;">
                        <div style="width: 2.4in; max-width: 100%; height: 1px; margin: 0 0 0 auto; border-bottom: 1px solid #000;"></div>
                        <p style="width: 2.4in; max-width: 100%; margin: 0.5rem 0 0 auto; text-align: center; font-weight: 600;">Director of Human Resources</p>
                    </td>
                </tr>
            </table>
        `.trim();

        return wrapper.firstElementChild;
    }

    function buildLeaveApplicationPrintMarkup(printArea) {
        const clone = printArea.cloneNode(true);
        const originalFields = printArea.querySelectorAll('input, textarea, select');
        const clonedFields = clone.querySelectorAll('input, textarea, select');

        clone.querySelectorAll('script, img, .print-form-header, #leave-application-instructions, .download-form-footer').forEach((node) => {
            node.remove();
        });

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

        const clonedSignatory = clone.querySelector('.print-signatory-margin');
        if (clonedSignatory) {
            clonedSignatory.replaceWith(buildLeaveApplicationExportSignatoryTable());
        }

        return clone.outerHTML;
    }

    function buildLeaveApplicationInstructionsMarkup(instructionsArea) {
        if (!instructionsArea) {
            return '';
        }

        const clone = instructionsArea.cloneNode(true);
        clone.removeAttribute('style');
        clone.querySelectorAll('[style]').forEach((node) => node.removeAttribute('style'));

        return clone.outerHTML;
    }

    function escapeLeaveApplicationExportValue(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getLeaveApplicationFieldValue(selector) {
        const field = document.querySelector(selector);
        return escapeLeaveApplicationExportValue(field?.value || field?.textContent || '');
    }

    function getLeaveApplicationExportCheckbox(labelText) {
        const label = Array.from(document.querySelectorAll('#leave-application-print-area label'))
            .find((item) => item.textContent.trim().toLowerCase().includes(labelText.toLowerCase()));
        const input = label?.querySelector('input[type="checkbox"], input[type="radio"]');

        return input?.checked ? '&#9745;' : '&#9744;';
    }

    function getLeaveApplicationExportData() {
        return {
            officeDepartment: getLeaveApplicationFieldValue('input[name="office_department"]'),
            employeeName: getLeaveApplicationFieldValue('input[name="employee_name"]'),
            filingDate: getLeaveApplicationFieldValue('input[name="filing_date"]'),
            position: getLeaveApplicationFieldValue('input[name="position"]'),
            salary: getLeaveApplicationFieldValue('input[name="salary"]'),
            otherLeave: getLeaveApplicationFieldValue('#leave-application-print-area input[placeholder="Specify other type of leave"]'),
            daysRequested: getLeaveApplicationFieldValue('#leave-days-requested'),
            inclusiveDates: getLeaveApplicationFieldValue('#leave-inclusive-dates'),
            country: getLeaveApplicationFieldValue('#leave-application-print-area input[placeholder="Specify country"]'),
            hospital: getLeaveApplicationFieldValue('#leave-application-print-area input[placeholder="Hospital name"]'),
            outpatient: getLeaveApplicationFieldValue('#leave-application-print-area input[placeholder="Outpatient details"]'),
            asOf: getLeaveApplicationFieldValue('input[name="as_of_label"]') || @json(now()->format('F, Y')),
            earnedDate: getLeaveApplicationFieldValue('input[name="earned_date_label"]') || @json($earnedRangeLabel ?? '-'),
            beginningVacation: getLeaveApplicationFieldValue('#beginning-vacation-balance'),
            beginningSick: getLeaveApplicationFieldValue('#beginning-sick-balance'),
            beginningTotal: getLeaveApplicationFieldValue('#beginning-total-balance'),
            earnedVacation: getLeaveApplicationFieldValue('#earned-vacation-balance'),
            earnedSick: getLeaveApplicationFieldValue('#earned-sick-balance'),
            earnedTotal: getLeaveApplicationFieldValue('#earned-total-balance'),
            appliedVacation: getLeaveApplicationFieldValue('#applied-vacation-balance'),
            appliedSick: getLeaveApplicationFieldValue('#applied-sick-balance'),
            appliedTotal: getLeaveApplicationFieldValue('#applied-total-balance'),
            endingVacation: getLeaveApplicationFieldValue('#ending-vacation-balance'),
            endingSick: getLeaveApplicationFieldValue('#ending-sick-balance'),
            endingTotal: getLeaveApplicationFieldValue('#ending-total-balance'),
            daysWithPay: getLeaveApplicationFieldValue('#days-with-pay-value'),
            daysWithoutPay: getLeaveApplicationFieldValue('#days-without-pay-value'),
            checks: {
                vacation: getLeaveApplicationExportCheckbox('Vacation'),
                sick: getLeaveApplicationExportCheckbox('Sick'),
                maternity: getLeaveApplicationExportCheckbox('Maternity'),
                paternity: getLeaveApplicationExportCheckbox('Paternity'),
                others: getLeaveApplicationExportCheckbox('Others'),
                philippines: getLeaveApplicationExportCheckbox('Within the Philippines'),
                abroad: getLeaveApplicationExportCheckbox('Abroad'),
                hospital: getLeaveApplicationExportCheckbox('In hospital'),
                outpatient: getLeaveApplicationExportCheckbox('Outpatient'),
                requested: getLeaveApplicationExportCheckbox('Requested'),
                notRequested: getLeaveApplicationExportCheckbox('Not Requested'),
            },
        };
    }

    function buildLeaveApplicationWordFormMarkup() {
        const data = getLeaveApplicationExportData();
        const line = 'border-bottom: 1px solid #000; min-height: 16px;';
        const label = 'font-weight: 700; padding-bottom: 2px;';
        const cell = 'border: 0; padding: 4px 7px; vertical-align: top;';
        const leftEdge = 'border-left: 0.75pt solid #000; mso-border-left-alt: solid #000 0.75pt;';
        const rightEdge = 'border-right: 0.75pt solid #000; mso-border-right-alt: solid #000 0.75pt;';
        const topEdge = 'border-top: 0.75pt solid #000; mso-border-top-alt: solid #000 0.75pt;';
        const bottomEdge = 'border-bottom: 0.75pt solid #000; mso-border-bottom-alt: solid #000 0.75pt;';
        const boxedCell = 'border: 1px solid #000; padding: 3px 5px; vertical-align: top;';
        const heading = 'border: 0; border-top: 1px solid #000; padding: 7px 0 6px; text-align: center; font-weight: 700; text-transform: uppercase;';
        return `
            <table class="leave-export-shell" width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; width: 100%;">
                <tr>
                    <td style="border: 0; padding: 0;">
            <table class="leave-export-form" width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; width: 100%; table-layout: fixed;">
                <colgroup>
                    <col style="width: 16.66%;">
                    <col style="width: 16.66%;">
                    <col style="width: 16.66%;">
                    <col style="width: 16.66%;">
                    <col style="width: 16.66%;">
                    <col style="width: 16.7%;">
                </colgroup>
                <tr>
                    <td colspan="3" style="${cell} ${leftEdge} ${topEdge}" width="50%">
                        <div style="${label}">Office / Department</div>
                        <div style="${line}">${data.officeDepartment}&nbsp;</div>
                    </td>
                    <td colspan="3" style="${cell} ${rightEdge} ${topEdge}" width="50%">
                        <div style="${label}">Name (Last, First, Middle)</div>
                        <div style="${line}">${data.employeeName}&nbsp;</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="${cell} ${leftEdge}" width="33.33%">
                        <div style="${label}">Date of Filing</div>
                        <div style="${line}">${data.filingDate}&nbsp;</div>
                    </td>
                    <td colspan="2" style="${cell}" width="33.33%">
                        <div style="${label}">Position</div>
                        <div style="${line}">${data.position}&nbsp;</div>
                    </td>
                    <td colspan="2" style="${cell} ${rightEdge}" width="33.33%">
                        <div style="${label}">Salary</div>
                        <div style="${line}">${data.salary}&nbsp;</div>
                    </td>
                </tr>
                <tr><td colspan="6" style="${heading} ${leftEdge} ${rightEdge}">Details of Application</td></tr>
                <tr>
                    <td colspan="3" style="${cell} ${leftEdge} width: 50%; padding-right: 10px;">
                        <div style="${label}">Type of Leave</div>
                        <div>${data.checks.vacation} Vacation</div>
                        <div>${data.checks.sick} Sick</div>
                        <div>${data.checks.maternity} Maternity</div>
                        <div>${data.checks.paternity} Paternity</div>
                        <div>${data.checks.others} Others (please specify)</div>
                        <div style="${line} margin-top: 2px;">${data.otherLeave}&nbsp;</div>
                        <div style="${label} margin-top: 8px;">Number of working days applied for</div>
                        <div style="${line}">${data.daysRequested}&nbsp;</div>
                        <div style="${label} margin-top: 8px;">Inclusive Dates</div>
                        <div style="${line}">${data.inclusiveDates}&nbsp;</div>
                    </td>
                    <td colspan="3" style="${cell} ${rightEdge} width: 50%; border-left: 1px solid #000; padding-left: 12px;">
                        <div style="${label}">Where leave will be spent</div>
                        <div>${data.checks.philippines} Within the Philippines</div>
                        <div>${data.checks.abroad} Abroad (please specify)</div>
                        <div style="${line} margin-top: 2px;">${data.country}&nbsp;</div>
                        <div style="border-top: 1px solid #000; margin-top: 8px; padding-top: 7px;">
                            <div style="${label}">In case of sick leave</div>
                            <div>${data.checks.hospital} In hospital (please specify)</div>
                            <div style="${line}">${data.hospital}&nbsp;</div>
                            <div>${data.checks.outpatient} Outpatient (please specify)</div>
                            <div style="${line}">${data.outpatient}&nbsp;</div>
                        </div>
                        <div style="margin-top: 8px;">
                            <div style="${label}">Commutation</div>
                            <div>${data.checks.requested} Requested</div>
                            <div>${data.checks.notRequested} Not Requested</div>
                        </div>
                        <div style="height: 28px;"></div>
                        <div style="font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 11pt; text-align: center;">________________________</div>
                        <div style="text-align: center; font-weight: 700; margin-top: 4px;">Signature of Applicant</div>
                    </td>
                </tr>
                <tr><td colspan="6" style="${heading} ${leftEdge} ${rightEdge}">Details on Action of Application</td></tr>
                <tr>
                    <td colspan="3" style="${cell} ${leftEdge} width: 50%; padding-right: 10px;">
                        <div style="${label}">Certification of Leave Credits (As of)</div>
                        <div style="${line}; margin-bottom: 5px;">${data.asOf}&nbsp;</div>
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; table-layout: fixed;">
                            <tr>
                                <th style="${boxedCell} width: 45%;">&nbsp;</th>
                                <th style="${boxedCell} text-align: center;">Vacation</th>
                                <th style="${boxedCell} text-align: center;">Sick</th>
                                <th style="${boxedCell} text-align: center;">Total</th>
                            </tr>
                            <tr>
                                <td style="${boxedCell}">Beginning Balance</td>
                                <td style="${boxedCell}">${data.beginningVacation}</td>
                                <td style="${boxedCell}">${data.beginningSick}</td>
                                <td style="${boxedCell}">${data.beginningTotal}</td>
                            </tr>
                            <tr>
                                <td style="${boxedCell}">Add: Earned Leave/s<br>Date: ${data.earnedDate}</td>
                                <td style="${boxedCell}">${data.earnedVacation}</td>
                                <td style="${boxedCell}">${data.earnedSick}</td>
                                <td style="${boxedCell}">${data.earnedTotal}</td>
                            </tr>
                            <tr>
                                <td style="${boxedCell}">Less: Applied Leave/s</td>
                                <td style="${boxedCell}">${data.appliedVacation}</td>
                                <td style="${boxedCell}">${data.appliedSick}</td>
                                <td style="${boxedCell}">${data.appliedTotal}</td>
                            </tr>
                            <tr>
                                <td style="${boxedCell}">Ending Balance</td>
                                <td style="${boxedCell}">${data.endingVacation}</td>
                                <td style="${boxedCell}">${data.endingSick}</td>
                                <td style="${boxedCell}">${data.endingTotal}</td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="3" style="${cell} ${rightEdge} width: 50%; border-left: 1px solid #000; padding-left: 12px;">
                        <div style="${label}">Recommendation</div>
                        <div>&#9744; Approved</div>
                        <div>&#9744; Disapproved due to:</div>
                        <div style="${line}; width: 86%; margin-left: 58px;">&nbsp;</div>
                        <div style="height: 50px;"></div>
                        <div style="font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 11pt; margin-top: 24px; text-align: center;">__________________</div>
                        <div style="text-align: center; font-weight: 700; margin-top: 4px;">Authorized Signature</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="${cell} ${leftEdge} border-top: 1px solid #000; padding: 18px 24px 8px 24px;">
                        <div style="${label}">Approved for:</div>
                        <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; margin-top: 12px; width: 100%;">
                            <tr>
                                <td style="border: 0; border-bottom: 1px solid #000; padding: 0; text-align: center; width: 95px;">${data.daysWithPay || '0'}</td>
                                <td style="border: 0; padding: 0 0 0 8px;">Day(s) with pay</td>
                            </tr>
                            <tr>
                                <td style="border: 0; height: 10px; padding: 0;"></td>
                                <td style="border: 0; height: 10px; padding: 0;"></td>
                            </tr>
                            <tr>
                                <td style="border: 0; border-bottom: 1px solid #000; padding: 0; text-align: center; width: 95px;">${data.daysWithoutPay || '0'}</td>
                                <td style="border: 0; padding: 0 0 0 8px;">Day(s) without pay</td>
                            </tr>
                            <tr>
                                <td style="border: 0; height: 10px; padding: 0;"></td>
                                <td style="border: 0; height: 10px; padding: 0;"></td>
                            </tr>
                            <tr>
                                <td style="border: 0; border-bottom: 1px solid #000; padding: 0; width: 95px;">&nbsp;</td>
                                <td style="border: 0; padding: 0 0 0 8px;">Others (please specify)</td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="3" style="${cell} ${rightEdge} border-top: 1px solid #000; padding: 18px 24px 8px 24px;">
                        <div style="${label}">Disapproved due to:</div>
                        <div style="border-bottom: 1px solid #000; height: 16px; margin-left: 105px; width: 2.35in;">&nbsp;</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" style="${cell}; ${leftEdge} ${rightEdge} ${bottomEdge} padding: 40px 24px 28px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border: 0; border-collapse: collapse; width: 100%; table-layout: fixed;">
                            <tr>
                                <td style="border: 0; padding: 0 132px 0 12px; text-align: center;">
                                    <div style="font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 11pt; margin: 0 auto; text-align: center; width: 2.35in;">________________________</div>
                                    <div style="font-weight: 700; margin-top: 4px;">President</div>
                                </td>
                                <td style="border: 0; padding: 0 24px; text-align: center;">
                                    <div style="font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 11pt; margin: 0 0 0 auto; text-align: center; width: 2.35in;">________________________</div>
                                    <div style="font-weight: 700; margin: 4px 0 0 auto; width: 2.35in; text-align: center;">Director of Human Resources</div>
                                </td>
                            </tr>
                        </table>
                        <div style="height: 24px;"></div>
                        <div style="font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 11pt; margin-top: 8px; text-align: center;">____________________</div>
                        <div style="font-weight: 700; text-align: center; margin-top: 4px;">Date</div>
                    </td>
                </tr>
            </table>
                    </td>
                </tr>
            </table>
        `;
    }

    function buildLeaveApplicationWordDocument(printArea, instructionsArea) {
        return `
            <!doctype html>
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="utf-8">
                <title>Leave Application Form</title>
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
                        font-family: Calibri, Arial, sans-serif;
                        color: #000;
                        font-size: 8.5pt;
                        line-height: 1.15;
                    }
                    .WordSection1 {
                        page: WordSection1;
                        width: 576pt;
                    }
                    .print-form-header {
                        text-align: center;
                        margin: 0 0 7px;
                        line-height: 1.1;
                        padding-bottom: 6px;
                    }
                    .print-form-header img {
                        display: block;
                        height: 0.68in;
                        margin: 0 auto 0.04in;
                        max-width: 100%;
                        width: 4.3in;
                    }
                    .print-form-header h3 {
                        font-size: 8pt;
                        font-weight: bold;
                        line-height: 1.05;
                        margin: 0.015in 0;
                        text-transform: uppercase;
                    }
                    #leave-application-print-area {
                        border: 1px solid #000;
                        box-sizing: border-box;
                        padding: 8px 12px;
                        width: 100%;
                        font-size: 9pt;
                        line-height: 1.15;
                    }
                    #leave-application-print-area div,
                    #leave-application-print-area section {
                        box-sizing: border-box;
                    }
                    .print-row-two,
                    .print-details-two,
                    .print-action-two {
                        width: 100%;
                    }
                    .print-row-two > div,
                    .print-details-two > div,
                    .print-action-two > div {
                        display: inline-block;
                        vertical-align: top;
                        width: 48%;
                    }
                    .print-row-three > div {
                        display: inline-block;
                        vertical-align: top;
                        width: 32%;
                    }
                    .print-row-two,
                    .print-row-three,
                    .print-details-two,
                    .print-action-two {
                        margin-bottom: 6px;
                    }
                    label,
                    p,
                    span {
                        font-size: 9pt;
                        line-height: 1.15;
                        margin: 0 0 2px;
                    }
                    label {
                        display: block;
                        font-weight: 600;
                    }
                    input {
                        border: 1px solid #000;
                        box-sizing: border-box;
                        font-size: 9pt;
                        min-height: 15px;
                        padding: 1px 4px;
                    }
                    input[type="checkbox"],
                    input[type="radio"] {
                        border: none;
                        min-height: auto;
                        padding: 0;
                    }
                    .w-full {
                        width: 100%;
                    }
                    section {
                        border-top: 1px solid #000;
                        margin-top: 6px;
                        padding-top: 5px;
                    }
                    section h5 {
                        margin: 0 0 4px;
                        text-align: center;
                        font-size: 9pt;
                        font-weight: bold;
                        text-transform: uppercase;
                    }
                    .print-right-divider,
                    .recommendation-block {
                        border-left: 1px solid #000;
                        padding-left: 12px;
                    }
                    .applicant-signature-block {
                        padding-top: 12px;
                        text-align: center;
                    }
                    .applicant-signature-block > div,
                    .recommendation-block .mx-auto,
                    .print-signatory-margin .mx-auto,
                    .mt-10 .mx-auto {
                        border-bottom: 1px solid #000;
                        height: 1px;
                    }
                    table {
                        border-collapse: collapse;
                        width: 100%;
                    }
                    th,
                    td {
                        border: 1px solid #000;
                        font-size: 8.5pt;
                        padding: 2px 4px;
                    }
                    .recommendation-disapprove-line,
                    .final-disapprove-line,
                    .approved-for-others-line,
                    #days-with-pay-value,
                    #days-without-pay-value {
                        border-bottom: 1px solid #000;
                        display: inline-block;
                    }
                    .print-signatory-margin {
                        margin-top: 22px;
                        table-layout: fixed;
                        width: 100%;
                    }
                    .print-signatory-margin,
                    .print-signatory-margin tr,
                    .print-signatory-margin td {
                        border: 0;
                    }
                    .final-signatory-line {
                        border-bottom: 1px solid #000;
                        height: 1px;
                        margin-left: auto;
                        margin-right: auto;
                        width: 2.1in;
                    }
                    .final-signatory-label {
                        display: block;
                        margin-left: auto;
                        margin-right: auto;
                        max-width: 2.1in;
                        text-align: center;
                        width: 100%;
                    }
                    .mt-10 {
                        margin-top: 18px;
                    }
                    .pt-10 {
                        padding-top: 12px;
                    }
                    .pt-8 {
                        padding-top: 10px;
                    }
                    .mt-6 {
                        margin-top: 6px;
                    }
                    .block {
                        display: block;
                    }
                    .font-bold,
                    .font-semibold,
                    .font-medium {
                        font-weight: 700;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .uppercase {
                        text-transform: uppercase;
                    }
                    .space-y-5 > *,
                    .space-y-4 > *,
                    .space-y-3 > *,
                    .space-y-1 > * {
                        margin-top: 0;
                        margin-bottom: 5px;
                    }
                    .space-y-5,
                    .space-y-4,
                    .space-y-3,
                    .space-y-1 {
                        margin-top: 0;
                        margin-bottom: 0;
                    }
                    #leave-application-instructions {
                        margin-top: 8px;
                        font-size: 7.8pt;
                        line-height: 1.15;
                    }
                    #leave-application-instructions p,
                    #leave-application-instructions li {
                        font-size: 7.8pt;
                        line-height: 1.15;
                        margin: 0;
                    }
                    #leave-application-instructions ol {
                        margin: 2px 0 0 18px;
                        padding: 0;
                    }
                    .download-form-footer {
                        margin-top: 6px;
                    }
                    .leave-export-form,
                    .leave-export-shell,
                    .leave-export-form table {
                        mso-table-lspace: 0pt;
                        mso-table-rspace: 0pt;
                    }
                    .leave-export-shell > tbody > tr > td,
                    .leave-export-shell > tr > td {
                        border: 0;
                        padding: 0;
                    }
                    .leave-export-form td,
                    .leave-export-form th {
                        font-size: 8.5pt;
                        line-height: 1.14;
                    }
                </style>
            </head>
            <body>
                <div class="WordSection1">
                    <div class="print-form-header">
                        <img src="${leaveApplicationLogoUrl}" alt="Logo" width="413" height="65" style="display: block; width: 4.3in; height: 0.68in; max-width: 100%; margin: 0 auto 0.04in;">
                        <h3>Office of the Human Resource</h3>
                        <h3>Leave Application Form</h3>
                    </div>
                    ${buildLeaveApplicationWordFormMarkup()}
                    ${buildLeaveApplicationInstructionsMarkup(instructionsArea)}
                </div>
            </body>
            </html>
        `;
    }

    function downloadLeaveApplicationWordFile(html) {
        const blob = new Blob(['\ufeff', html], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `leave-application-form-${new Date().toISOString().slice(0, 10)}.doc`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    async function saveLeaveApplicationRecord() {
        const form = document.getElementById('leave-application-form');
        if (!form) {
            return { ok: false, message: 'Leave application form was not found.' };
        }

        try {
            const tokenResponse = await fetch(`${leaveApplicationCsrfTokenUrl}?_=${Date.now()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const tokenData = tokenResponse.ok ? await tokenResponse.json().catch(() => ({})) : {};
            const freshToken = typeof tokenData.token === 'string' ? tokenData.token : '';
            const tokenInput = form.querySelector('input[name="_token"]');
            if (freshToken && tokenInput) {
                tokenInput.value = freshToken;
            }

            const payload = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(freshToken ? { 'X-CSRF-TOKEN': freshToken } : {}),
                },
                credentials: 'same-origin',
                body: payload,
            });

            if (response.ok) {
                return { ok: true, message: '' };
            }

            const data = await response.json().catch(() => ({}));
            if (response.status === 419) {
                return {
                    ok: false,
                    message: 'Your secure session expired. Refresh the page, sign in again if requested, and resubmit the form.',
                };
            }
            const errors = data && typeof data === 'object' ? data.errors : null;
            const firstError = errors && typeof errors === 'object'
                ? Object.values(errors).flat().find(Boolean)
                : '';

            return {
                ok: false,
                message: firstError || data.message || 'Please complete the required fields before downloading.',
            };
        } catch (error) {
            console.error('Error saving leave application', error);
            return { ok: false, message: 'The leave application could not be saved. Please try again.' };
        }
    }

    async function downloadLeaveApplicationWordFormV2() {
        const button = document.getElementById('leave-application-download-button');
        const originalButtonText = button ? button.textContent : '';

        if (!validateLeaveRequestBalance()) {
            return;
        }

        if (!validateLeaveMedicalCertificate()) {
            return;
        }

        if (button) {
            button.disabled = true;
            button.textContent = 'Preparing...';
            button.classList.add('opacity-70', 'cursor-not-allowed');
        }

        updateLeaveSummaryTable();
        const saveResult = await saveLeaveApplicationRecord();
        if (!saveResult.ok) {
            alert(saveResult.message);
            if (button) {
                button.disabled = false;
                button.textContent = originalButtonText;
                button.classList.remove('opacity-70', 'cursor-not-allowed');
            }
            return;
        }

        const printArea = document.getElementById('leave-application-print-area');
        const instructionsArea = document.getElementById('leave-application-instructions');
        if (!printArea) {
            if (button) {
                button.disabled = false;
                button.textContent = originalButtonText;
                button.classList.remove('opacity-70', 'cursor-not-allowed');
            }
            return;
        }

        downloadLeaveApplicationWordFile(buildLeaveApplicationWordDocument(printArea, instructionsArea));
        setTimeout(() => window.location.reload(), 300);
    }
</script>
