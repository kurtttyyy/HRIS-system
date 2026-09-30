              @php
                $requestFilingDate = $request->filing_date ? \Carbon\Carbon::parse($request->filing_date)->format('M d, Y') : optional($request->created_at)->format('M d, Y');
                $requestFilingTime = optional($request->created_at)->format('g:i A');
                $requestDays = rtrim(rtrim(number_format((float) ($request->number_of_working_days ?? 0), 1, '.', ''), '0'), '.');
                $requestLeaveType = !in_array(strtolower(trim((string) $request->leave_type)), ['', 'leave', 'leave request'], true)
                  ? trim((string) $request->leave_type)
                  : 'Type not specified';
                $requestDates = $request->inclusive_dates ?: '-';
                $requestReason = str_contains(strtolower((string) $requestLeaveType), 'official business')
                  ? 'Business Trip'
                  : (str_contains(strtolower((string) $requestLeaveType), 'annual leave') ? 'Personal vacation' : (str_contains(strtolower((string) $requestLeaveType), 'sick leave') ? 'Not fit for work due to health reasons' : $requestDates));
                $employeeName = trim((string) ($request->employee_name ?? '-'));
                $nameParts = array_values(array_filter(explode(' ', $employeeName)));
                $initials = strtoupper(substr($nameParts[0] ?? 'L', 0, 1).substr($nameParts[count($nameParts) - 1] ?? 'R', 0, 1));
                $medicalCertificateUrl = !empty($request->medical_receipt_path) ? asset('storage/'.$request->medical_receipt_path) : null;
                $medicalCertificateExtension = strtolower(pathinfo((string) ($request->medical_receipt_name ?? $request->medical_receipt_path ?? ''), PATHINFO_EXTENSION));
                $medicalCertificateMime = strtolower((string) ($request->medical_receipt_mime ?? ''));
                $isMedicalCertificatePdf = $medicalCertificateExtension === 'pdf' || str_contains($medicalCertificateMime, 'pdf');
                $isMedicalCertificateImage = in_array($medicalCertificateExtension, ['jpg', 'jpeg', 'png', 'webp'], true)
                  || in_array($medicalCertificateMime, ['image/jpeg', 'image/png', 'image/webp'], true);
                $formatLeaveValue = static function ($value) {
                  return rtrim(rtrim(number_format((float) ($value ?? 0), 1, '.', ''), '0'), '.');
                };
              @endphp
              <div id="leave-review-modal-{{ $request->id }}" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="leave-review-title-{{ $request->id }}">
                <button type="button" data-leave-review-close class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" aria-label="Close review"></button>
                <div class="relative z-10 flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-[1.75rem] bg-white shadow-2xl">
                  <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4 md:px-7">
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600">{{ $readOnly ? 'Approved request' : 'Review before deciding' }}</p>
                      <h3 id="leave-review-title-{{ $request->id }}" class="mt-1 text-xl font-black text-slate-900">{{ $requestLeaveType }}</h3>
                      <p class="mt-1 text-sm text-slate-500">{{ $employeeName }} • Filed {{ $requestFilingDate }}{{ $requestFilingTime ? ' at '.$requestFilingTime : '' }}</p>
                    </div>
                    <button type="button" data-leave-review-close class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900" aria-label="Close review">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </div>

                  <div class="overflow-y-auto px-5 py-5 md:px-7">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                      <section>
                        <h4 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-500">Submitted Leave Form</h4>
                        <dl class="mt-3 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                          <div><dt class="text-xs font-semibold text-slate-400">Employee</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $employeeName }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Employee ID</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->employee_id ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Office / Department</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->office_department ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Position</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->position ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Date of Filing</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestFilingDate }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Salary</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->salary ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Leave Type</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestLeaveType }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Working Days</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestDays }} day(s)</dd></div>
                          <div class="sm:col-span-2"><dt class="text-xs font-semibold text-slate-400">Inclusive Dates</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestDates }}</dd></div>
                          <div class="sm:col-span-2"><dt class="text-xs font-semibold text-slate-400">Commutation</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->commutation ?: '-' }}</dd></div>
                        </dl>

                        <h4 class="mt-5 text-sm font-bold uppercase tracking-[0.14em] text-slate-500">Leave Credits</h4>
                        <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200">
                          <table class="w-full min-w-[520px] text-left text-sm">
                            <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-500">
                              <tr><th class="px-4 py-3">Balance</th><th class="px-4 py-3">Vacation</th><th class="px-4 py-3">Sick</th><th class="px-4 py-3">Total</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                              <tr><th class="px-4 py-3 font-semibold">Beginning</th><td class="px-4 py-3">{{ $formatLeaveValue($request->beginning_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->beginning_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->beginning_total) }}</td></tr>
                              <tr><th class="px-4 py-3 font-semibold">Earned</th><td class="px-4 py-3">{{ $formatLeaveValue($request->earned_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->earned_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->earned_total) }}</td></tr>
                              <tr><th class="px-4 py-3 font-semibold">Applied</th><td class="px-4 py-3">{{ $formatLeaveValue($request->applied_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->applied_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->applied_total) }}</td></tr>
                              <tr><th class="px-4 py-3 font-semibold">Ending</th><td class="px-4 py-3">{{ $formatLeaveValue($request->ending_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->ending_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->ending_total) }}</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </section>

                      <section>
                        <div class="flex items-center gap-3">
                          <h4 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-500">Medical Certificate</h4>
                        </div>

                        @if($medicalCertificateUrl && $isMedicalCertificateImage)
                          <button
                            type="button"
                            data-medical-image-zoom
                            data-image-src="{{ $medicalCertificateUrl }}"
                            data-image-alt="Medical certificate for {{ $employeeName }}"
                            class="group relative mt-3 block w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 text-left focus:outline-none focus:ring-4 focus:ring-blue-300"
                            aria-label="Enlarge medical certificate"
                          >
                            <img src="{{ $medicalCertificateUrl }}" alt="Medical certificate for {{ $employeeName }}" class="max-h-[520px] w-full cursor-zoom-in object-contain transition group-hover:brightness-90">
                            <span class="absolute bottom-3 left-1/2 inline-flex -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full bg-slate-950/85 px-4 py-2 text-sm font-semibold text-white shadow-lg">
                              <i class="fa-solid fa-magnifying-glass-plus"></i>
                              Click to enlarge
                            </span>
                          </button>
                        @elseif($medicalCertificateUrl && $isMedicalCertificatePdf)
                          <iframe src="{{ $medicalCertificateUrl }}" title="Medical certificate for {{ $employeeName }}" class="mt-3 h-[520px] w-full rounded-2xl border border-slate-200 bg-white"></iframe>
                        @elseif($medicalCertificateUrl)
                          <div class="mt-3 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-8 text-center">
                            <i class="fa-solid fa-file-medical text-3xl text-blue-600"></i>
                            <p class="mt-3 text-sm font-semibold text-slate-800">{{ $request->medical_receipt_name ?: 'Medical certificate' }}</p>
                            <p class="mt-1 text-xs text-slate-500">This file format must be opened in its original viewer.</p>
                          </div>
                        @else
                          <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                            <i class="fa-regular fa-file-lines text-3xl text-slate-400"></i>
                            <p class="mt-3 text-sm font-semibold text-slate-700">No medical certificate attached.</p>
                            <p class="mt-1 text-xs text-slate-500">Certificates are required for newly submitted Sick Leave requests.</p>
                          </div>
                        @endif
                      </section>
                    </div>
                  </div>

                  <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end md:px-7">
                    <button type="button" data-leave-review-close class="rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Close</button>
                    @if(!$readOnly)
                    <form data-leave-decision-form method="POST" action="{{ route('admin.updateLeaveRequestStatus', $request->id) }}" class="flex flex-col-reverse gap-3 sm:flex-row sm:items-end">
                      @csrf
                      <input type="hidden" name="month" value="{{ $selectedMonthValue }}">
                      <button type="submit" name="status" value="Rejected" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                        <i class="fa-solid fa-xmark"></i>
                        Reject
                      </button>
                      <button type="submit" name="status" value="Approved" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <i class="fa-solid fa-check"></i>
                        Approve
                      </button>
                    </form>
                    @endif
                  </div>
                </div>
              </div>
