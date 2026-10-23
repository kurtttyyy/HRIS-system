    <!-- ================= EDIT PROFILE MODAL (EXACT DESIGN) ================= -->
    <div
      x-show="openEditProfile && modalTarget === 'general'"
      x-transition
      @click.self="cancelGeneralProfileEdit()"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      style="display:none"
    >
      <div class="bg-white w-full max-w-3xl rounded-xl shadow-xl max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex justify-between px-6 py-4 border-b">
          <h2 class="font-semibold text-gray-800">Edit Employee Profile</h2>
          <button type="button" @click="cancelGeneralProfileEdit()" class="text-xl text-gray-400">&times;</button>
        </div>

        <form action="{{ route('admin.updateGeneralProfile') }}" method="POST" class="contents">
          @csrf
          <input type="hidden" name="user_id" :value="generalProfileDraft?.id">

          <!-- Content -->
          <div class="overflow-y-auto px-6 py-5 space-y-6 text-sm">

            <!-- Personal Information -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Personal Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">First Name</span>
                  <input name="first" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.first_name">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Last Name</span>
                  <input name="last" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.last_name">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Middle Name</span>
                  <input name="middle" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.middle_name">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Date of Birth</span>
                  <input type="date" name="birthday" class="w-full border rounded-md px-3 py-2" :value="generalProfileDraft?.employee?.birthday ? generalProfileDraft.employee.birthday.split('T')[0] : ''">
                </label>
                <label class="space-y-1"><span class="text-xs font-medium text-gray-500">Sex</span><select name="gender" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.sex">
                  <option value="">Sex</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select></label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Bank Account Number</span>
                  <input name="account_number" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.account_number" placeholder="Account Number">
                </label>
              </div>
            </section>

            <!-- Contact -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Contact Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Email</span>
                  <input name="email" placeholder="Email" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.email">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Contact Number</span>
                  <input name="contact_number" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.contact_number">
                </label>
              </div>
            </section>

            <!-- Employment -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Employment Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Position</span>
                  <input name="position" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.position">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Department</span>
                  <input name="department" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.department">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Employee ID Number</span>
                  <input name="employee_id" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.employee_id">
                </label>
                <label class="space-y-1"><span class="text-xs font-medium text-gray-500">Employee Type</span><select name="job_type" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.job_type">
                  <option value="" disabled>Select employee type</option>
                  <option value="Teaching">Teaching</option>
                  <option value="Non-Teaching">Non-Teaching</option>
                </select></label>
              </div>
            </section>

            <!-- Address -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Address</h3>
              <div class="grid grid-cols-2 gap-4">
                <label class="col-span-2 space-y-1">
                  <span class="text-xs font-medium text-gray-500">House No. / Street / Sitio / Subdivision</span>
                  <input
                    name="address_line"
                    class="w-full border rounded-md px-3 py-2"
                    placeholder="e.g. Maligaya"
                    x-model="generalProfileDraft.addressParts.addressLine"
                  >
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Barangay</span>
                  <input
                    name="barangay"
                    class="w-full border rounded-md px-3 py-2"
                    placeholder="e.g. San Fabian"
                    x-model="generalProfileDraft.addressParts.barangay"
                  >
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Municipality / City</span>
                  <input
                    name="municipality"
                    class="w-full border rounded-md px-3 py-2"
                    placeholder="e.g. Echague"
                    x-model="generalProfileDraft.addressParts.municipality"
                  >
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Province</span>
                  <input
                    name="province"
                    class="w-full border rounded-md px-3 py-2"
                    placeholder="e.g. Isabela"
                    x-model="generalProfileDraft.addressParts.province"
                  >
                </label>
              </div>
            </section>

            <!-- Emergency -->
            <section>
              <h3 class="text-red-500 font-semibold mb-4 flex items-center gap-2">Emergency Contact</h3>
              <div class="grid grid-cols-2 gap-4">
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Contact Person's Full Name</span>
                  <input name="emergency_contact_name" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.emergency_contact_name" placeholder="e.g. Juan Dela Cruz">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Relationship to Employee</span>
                  <input name="emergency_contact_relationship" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.emergency_contact_relationship" placeholder="e.g. Spouse, Parent, Sibling">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Contact Person's Phone Number</span>
                  <input name="emergency_contact_number" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.employee.emergency_contact_number" placeholder="e.g. 0917-123-4567">
                </label>
              </div>
            </section>

            <!-- Government IDs -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Government ID Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Pag-IBIG MID Number</span>
                  <input name="MID" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.government.MID" placeholder="PAG IBIG MID">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Pag-IBIG Registration Tracking Number (RTN)</span>
                  <input name="RTN" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.government.RTN" placeholder="PAG IBIG RTN">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">SSS Number</span>
                  <input name="SSS" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.government.SSS" placeholder="SSS">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">PhilHealth Number</span>
                  <input name="PhilHealth" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.government.PhilHealth" placeholder="PHILHEALTH">
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Tax Identification Number (TIN)</span>
                  <input name="TIN" class="w-full border rounded-md px-3 py-2" x-model="generalProfileDraft.government.TIN" placeholder="TIN">
                </label>
              </div>
            </section>

          </div>

          <!-- Footer -->
          <div class="border-t px-6 py-4 flex justify-end gap-3">
            <button type="button" @click="cancelGeneralProfileEdit()" class="px-4 py-2 bg-gray-100 rounded-md">Cancel</button>
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-md">Save Changes</button>
          </div>
        </form>

      </div>
    </div>
