<?php

use App\Http\Controllers\AdministratorPageController;
use App\Http\Controllers\AdministratorStoreController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\EmployeePageController;
use App\Http\Controllers\EmployeeStoreController;
use App\Http\Controllers\GuestPageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegisterLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

Route::controller(PageController::class)->group(function () {
    Route::get('/login', 'display_login')->name('login_display');
    Route::get('/register', 'display_register')->name('register');
});

Route::controller(GuestPageController::class)->group(function () {
    Route::get('/application', 'display_application')->name('guest.application');
    Route::get('/application/non_teaching/procedure/{id}', 'display_non_teaching')->name('guest.applicationNonTeachingSteps');
    Route::get('/application/teaching/procedure', 'display_teaching')->name('guest.applicationTeachingSteps');
    Route::get('/', 'display_index')->name('guest.index');
    Route::get('/index', 'display_index')->name('guest.index.alias');
    Route::get('/about', 'display_about')->name('guest.about');
    Route::get('/policy', 'display_policy')->name('guest.policy');
    Route::get('/terms-of-service', 'display_terms')->name('guest.terms');
    Route::get('/cookie-policy', 'display_cookie')->name('guest.cookie');
    Route::post('/chatbot/reply', 'chat_reply')->name('guest.chat.reply');
    Route::get('/job/available', 'job_open_landing')->name('guest.jobOpenLanding');
    Route::get('/job/available/{id}', 'display_job')->name('guest.jobOpen');
});

Route::controller(ApplicantController::class)->group(function () {
    Route::post('applicant/store', 'applicant_stores')->name('applicant.store');
    Route::post('applicant/pds/scan', 'scan_pds')->name('applicant.pds.scan');
    Route::post('applicant/document-draft', 'upload_document_draft')->name('applicant.document.draft');
    Route::post('applicant/rating', 'store_rating')->name('applicant.rating.store');
    Route::post('/application', 'display_application')->name('guest.application.submit');
});

Route::controller(RegisterLoginController::class)->group(function () {
    Route::post('register/store', 'register_store')->name('register.store');
    Route::post('login', 'login_store')->name('login');
    Route::get('forgot-password', 'forgot_password')->name('password.request');
    Route::post('forgot-password', 'send_password_reset_link')->name('password.email');
    Route::get('reset-password/{token}', 'reset_password')->name('password.reset');
    Route::post('reset-password', 'update_password')->name('password.update');
    Route::post('logout', 'logout')->name('logout');
});

Route::controller(EmployeePageController::class)->group(function () {
    Route::get('employee/dashboard', 'display_home')->name('employee.employeeHome');
    Route::get('employee/staff-guide', 'display_staff_guide')->name('employee.employeeStaffGuide');
    Route::get('employee/notifications', 'display_notifications')->name('employee.employeeNotifications');
    Route::get('employee/notifications/summary', 'notification_summary')->name('employee.employeeNotifications.summary');
    Route::get('employee/hierarchy', 'display_hierarchy')->name('employee.employeeHierarchy');
    Route::get('employee/evaluation', 'display_evaluation')->name('employee.employeeEvaluation');
    Route::get('employee/leave', 'display_leave')->name('employee.employeeLeave');
    Route::get('employee/profile', 'display_profile')->name('employee.employeeProfile');
    Route::get('employee/communication', 'display_communication')->name('employee.employeeCommunication');
    Route::get('employee/resignation', 'display_resignation')->name('employee.employeeResignation');
    Route::get('employee/document', 'display_document')->name('employee.employeeDocument');
    Route::get('employee/document/preview/{id}', 'display_document_preview')->name('employee.employeeDocument.preview');
    Route::get('employee/document/view/{id}', 'view_document')->name('employee.employeeDocument.view');
    Route::get('employee/payslip', 'display_payslip')->name('employee.employeePayslip');
});

Route::controller(EmployeeStoreController::class)->group(function () {
    //POST
    Route::post('upload/documents', 'upload_store')->name('employee.upload_documents');
    Route::post('employee/document/folder', 'create_folder')->name('employee.document.folder.store');
    Route::post('employee/document/folder/{folderKey}/remove', 'remove_folder')->name('employee.document.folder.remove');
    Route::post('employee/document/{id}/move', 'move_document')->name('employee.document.move');
    Route::post('employee/document/{id}/remove', 'remove_document')->name('employee.remove_document');
    Route::post('employee/leave/application', 'leave_application_store')->name('employee.leaveApplication.store');
    Route::post('employee/resignation/store', 'store_resignation')->name('employee.storeResignation');
    Route::post('employee/communication/send', 'send_communication_message')->name('employee.communication.send');

});

Route::controller(AdministratorPageController::class)->group(function () {
    Route::get('system/dashboard', 'display_home')->name('admin.adminHome');
    Route::get('system/notifications', 'display_notifications')->name('admin.adminNotifications');
    Route::get('system/notifications/summary', 'notification_summary')->name('admin.adminNotifications.summary');
    Route::get('system/communication', 'display_communication')->name('admin.adminCommunication');
    Route::get('system/employee', 'display_employee')->name('admin.adminEmployee');
    Route::get('system/attendance', 'display_attendance')->name('admin.adminAttendance');
    Route::get('system/attendance/present', 'display_attendance_present')->name('admin.attendance.present');
    Route::get('system/attendance/absent', 'display_attendance_absent')->name('admin.attendance.absent');
    Route::get('system/attendance/tardiness', 'display_attendance_tardiness')->name('admin.attendance.tardiness');
    Route::get('system/attendance/total-employee', 'display_attendance_total_employee')->name('admin.attendance.totalEmployee');
    Route::get('system/leave/management', 'display_leave')->name('admin.adminLeaveManagement');
    Route::get('system/payslip', 'display_payslip')->name('admin.adminPayslip');
    Route::get('system/payslip/view', 'display_payslip_view')->name('admin.adminPaySlipView');
    Route::get('system/resignations', 'display_resignations')->name('admin.adminResignations');
    Route::get('system/reports', 'display_reports')->name('admin.adminReports');
    Route::get('system/logs', 'display_activity_logs')->name('admin.activityLogs');
    Route::get('system/matrix/school-administrator', 'display_school_administrator')->name('admin.schoolAdministrator');
    Route::get('system/matrix/non-teaching', 'display_non_teaching_matrix')->name('admin.nonTeachingMatrix');
    Route::get('system/matrix/teaching', 'display_teaching_matrix')->name('admin.teachingMatrix');
    Route::get('system/loads', 'display_loads')->name('admin.adminLoads');

    Route::get('system/applicant', 'display_applicant')->name('admin.adminApplicant');
    Route::get('system/applicants/ID/{id}', 'display_applicant_ID');
    Route::get('system/interviewers/ID/{id}', 'display_interview_ID');
    Route::get('system/edit/position/{id}', 'display_edit_position')->name('admin.adminEditPosition');
    Route::get('system/interview', 'display_interview')->name('admin.adminInterview');
    Route::get('system/calendar', 'display_calendar')->name('admin.adminCalendar');
    Route::get('system/meeting', 'display_meeting')->name('admin.adminMeeting');
    Route::get('system/position', 'display_position')->name('admin.adminPosition');
    Route::get('system/show/position/{id}', 'display_show_position')->name('admin.adminShowPosition');
    Route::get('system/employee/overview', 'display_overview')->name('admin.adminEmployeeOverview');
    Route::get('system/employee/{id}/documents', 'employee_documents')->name('admin.employeeDocuments');
    Route::get('system/create/position', 'display_create_position')->name('admin.adminCreatePosition');

    //PersonalDetail
    Route::get('system/personal/detail/employee/documents', 'display_documents')->name('admin.PersonalDetail.adminEmployeeDocuments');
    Route::get('system/personal/detail/employee/overview', 'display_personal_detail_overview')->name('admin.PersonalDetail.adminEmployeeOverview');
    Route::get('system/personal/detail/employee/PD', 'display_pd')->name('admin.PersonalDetail.adminEmployeePD');
    Route::get('system/personal/detail/employee/performance', 'display_performance')->name('admin.PersonalDetail.adminEmployeePerformance');
    Route::get('system/personal/detail/edit', 'display_edit')->name('admin.PersonalDetail.editProfile');
    Route::get('system/personal/detail/service-record/edit', 'display_service_record_edit')->name('admin.PersonalDetail.serviceRecordEdit');
    Route::get('system/personal/detail/service-record/download-word', 'download_service_record_word')->name('admin.PersonalDetail.serviceRecordEdit.downloadWord');
    Route::get('system/personal/detail/employee/documents/{id}/download', 'download_employee_document')->name('admin.employeeDocuments.download');
});

Route::controller(AdministratorStoreController::class)->group(function () {

    //STORE
    Route::post('system/store/new/position', 'store_new_position')->name('admin.createPositionStore');
    Route::post('system/store/ratings', 'store_star_ratings')->name('admin.adminStarStore');
    Route::post('system/store/interview', 'store_interview')->name('admin.storeNewInterview');
    Route::post('system/communication/send', 'send_communication_message')->name('admin.communication.send');
    Route::post('system/employee/document', 'store_document')->name('admin.addDocument');
    Route::post('system/attendance/upload', 'store_attendance_excel')->name('admin.uploadAttendanceExcel');
    Route::post('system/payslip/upload', 'store_payslip_file')->name('admin.uploadPayslipFile');
    Route::post('system/loads/upload', 'store_loads_file')->name('admin.uploadLoadsFile');
    Route::post('system/employee/document/requirements', 'store_required_documents')->name('admin.saveRequiredDocuments');

    //UPDATE
    Route::post('system/edit/position/{id}', 'update_position')->name('admin.updatePosition');
    Route::post('system/update/application/status', 'update_application_status')->name('admin.updateStatus');
    Route::post('system/update/interview', 'updated_interview')->name('admin.storeUpdatedInterview');
    Route::post('system/update/employee/{id}', 'update_employee')->name('admin.updateEmployee');
    Route::post('system/employee/{id}/mark-permanent', 'mark_employee_permanent')->name('admin.markEmployeePermanent');
    Route::post('system/leave/request/{id}/status', 'update_leave_request_status')->name('admin.updateLeaveRequestStatus');
    Route::post('system/resignations/{id}/status', 'update_resignation_status')->name('admin.updateResignationStatus');
    Route::post('system/employee/update/biometric', 'update_bio')->name('admin.updateBio');
    Route::post('system/employee/update/profile', 'update_general_profile')->name('admin.updateGeneralProfile');
    Route::post('system/personal/detail/service-record/update', 'update_service_record')->name('admin.PersonalDetail.serviceRecordEdit.update');
    Route::post('system/calendar/hidden-official-holidays/sync', 'sync_hidden_official_holidays')->name('admin.syncHiddenOfficialHolidays');
    Route::post('system/logs/{activityLog}/note', 'update_activity_log_note')->name('admin.activityLogs.note');
    Route::post('admin/attendance/update-status/{id}', 'update_attendance_status')->name('admin.updateAttendanceStatus');
    Route::post('admin/attendance/delete/{id}', 'delete_attendance_file')->name('admin.deleteAttendanceFile');
    Route::post('admin/payslip/update-status/{id}', 'scan_payslip_file')->name('admin.scanPayslipFile');
    Route::post('system/loads/delete/{id}', 'delete_loads_file')->name('admin.deleteLoadsFile');
    Route::post('system/loads/update-status/{id}', 'scan_loads_file')->name('admin.scanLoadsFile');


    //DELETE
    Route::post('system/delete/position/{id}', 'destroy_position')->name('admin.destroyPosition');
    Route::post('system/reopen/position/{id}', 'restore_position')->name('admin.restorePosition');
    Route::post('system/delete/interview/{id}', 'destroy_interview')->name('admin.interviewCancel');
    Route::post('system/delete/employee/{id}', 'destroy_employee')->name('admin.destroyEmployee');
});
