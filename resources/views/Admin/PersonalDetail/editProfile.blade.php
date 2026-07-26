    <!-- ================= EDIT PROFILE MODAL (EXACT DESIGN) ================= -->
    <div
      x-show="openEditProfile && modalTarget === 'general'"
      x-transition
      @click.self="openEditProfile=false; modalTarget = ''"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      style="display:none"
    >
      <div class="bg-white w-full max-w-3xl rounded-xl shadow-xl max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex justify-between px-6 py-4 border-b">
          <h2 class="font-semibold text-gray-800">Edit Employee Profile</h2>
          <button type="button" @click="openEditProfile=false; modalTarget = ''" class="text-xl text-gray-400">&times;</button>
        </div>

        <form action="{{ route('admin.updateGeneralProfile') }}" method="POST" class="contents">
          @csrf
          <input type="hidden" name="user_id" :value="selectedEmployee?.id">

          <!-- Content -->
          <div class="overflow-y-auto px-6 py-5 space-y-6 text-sm">

            <!-- Personal Information -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Personal Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <input name="first" class="border rounded-md px-3 py-2" x-model="selectedEmployee.first_name">
                <input name="last" class="border rounded-md px-3 py-2" x-model="selectedEmployee.last_name">
                <input name="middle" class="border rounded-md px-3 py-2" x-model="selectedEmployee.middle_name">
                <input type="date" name="birthday" class="border rounded-md px-3 py-2" :value="selectedEmployee?.employee?.birthday ? selectedEmployee.employee.birthday.split('T')[0] : ''">
                <select name="gender" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.sex">
                  <option value="">Sex</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
            </section>

            <!-- Contact -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Contact Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <input name="email" class="border rounded-md px-3 py-2" x-model="selectedEmployee.email">
                <input name="contact_number" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.contact_number">
              </div>
            </section>

            <!-- Employment -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Employment Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <input name="position" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.position">
                <input name="department" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.department">
                <input name="employee_id" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.employee_id">
                <select class="border rounded-md px-3 py-2 bg-gray-50" :value="employmentClassificationLabel()" disabled>
                  <option value="">Classification</option>
                  <option value="Full-time">Full-time</option>
                  <option value="Part-time">Part-time</option>
                  <option value="Non-Teaching">Non-Teaching</option>
                </select>
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
                    :value="addressFormParts().addressLine"
                  >
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Barangay</span>
                  <input
                    name="barangay"
                    class="w-full border rounded-md px-3 py-2"
                    placeholder="e.g. San Fabian"
                    :value="addressFormParts().barangay"
                  >
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Municipality / City</span>
                  <input
                    name="municipality"
                    class="w-full border rounded-md px-3 py-2"
                    placeholder="e.g. Echague"
                    :value="addressFormParts().municipality"
                  >
                </label>
                <label class="space-y-1">
                  <span class="text-xs font-medium text-gray-500">Province</span>
                  <input
                    name="province"
                    class="w-full border rounded-md px-3 py-2"
                    placeholder="e.g. Isabela"
                    :value="addressFormParts().province"
                  >
                </label>
              </div>
            </section>

            <!-- Emergency -->
            <section>
              <h3 class="text-red-500 font-semibold mb-4 flex items-center gap-2">Emergency Contact</h3>
              <div class="grid grid-cols-2 gap-4">
                <input name="emergency_contact_name" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.emergency_contact_name">
                <input name="emergency_contact_relationship" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.emergency_contact_relationship">
                <input name="emergency_contact_number" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.emergency_contact_number">
              </div>
            </section>

            <!-- Bank -->
            <section>
              <h3 class="text-indigo-600 font-semibold mb-4 flex items-center gap-2">Bank Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <input name="account_number" class="border rounded-md px-3 py-2" x-model="selectedEmployee.employee.account_number" placeholder="Account Number">
                <input name="MID" class="border rounded-md px-3 py-2" x-model="selectedEmployee.government.MID" placeholder="PAG IBIG MID">
                <input name="RTN" class="border rounded-md px-3 py-2" x-model="selectedEmployee.government.RTN" placeholder="PAG IBIG RTN">
                <input name="SSS" class="border rounded-md px-3 py-2" x-model="selectedEmployee.government.SSS" placeholder="SSS">
                <input name="PhilHealth" class="border rounded-md px-3 py-2" x-model="selectedEmployee.government.PhilHealth" placeholder="PHILHEALTH">
                <input name="TIN" class="border rounded-md px-3 py-2" x-model="selectedEmployee.government.TIN" placeholder="TIN">
              </div>
            </section>

          </div>

          <!-- Footer -->
          <div class="border-t px-6 py-4 flex justify-end gap-3">
            <button type="button" @click="openEditProfile=false; modalTarget = ''" class="px-4 py-2 bg-gray-100 rounded-md">Cancel</button>
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-md">Save Changes</button>
          </div>
        </form>

      </div>
    </div>
