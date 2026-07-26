<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnforceAdminPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = (string) optional($request->route())->getName();

        if (!$user || !in_array(strtolower(trim((string) $user->role)), ['admin', 'administrator'], true) || !Str::startsWith($routeName, 'admin.')) {
            return $next($request);
        }

        // Null means a legacy/main administrator with full access.
        if (is_null($user->admin_permissions)) {
            return $next($request);
        }

        $alwaysAllowed = ['admin.myProfile', 'admin.myProfile.update'];
        if (in_array($routeName, $alwaysAllowed, true)) {
            return $next($request);
        }

        $permission = $this->permissionFor($routeName);
        abort_unless($permission && $user->hasAdminPermission($permission), 403, 'Your administrator account does not have access to this module.');

        return $next($request);
    }

    private function permissionFor(string $routeName): ?string
    {
        return match (true) {
            $routeName === 'admin.accounts.store' => 'manage_admins',
            Str::is(['admin.adminHome', 'admin.adminNotifications', 'admin.adminNotifications.*'], $routeName) => 'dashboard',
            Str::is(['admin.adminEmployee*', 'admin.employee*', 'admin.PersonalDetail.*', 'admin.addDocument', 'admin.saveRequiredDocuments', 'admin.updateEmployee', 'admin.updateBio', 'admin.updateGeneralProfile', 'admin.markEmployeePermanent', 'admin.destroyEmployee'], $routeName) => 'employees',
            Str::is(['admin.adminLeaveManagement', 'admin.leaveManagement.*', 'admin.updateLeaveRequestStatus'], $routeName) => 'leave',
            Str::is(['admin.adminPayslip*', 'admin.uploadPayslipFile', 'admin.scanPayslipFile'], $routeName) => 'payslip',
            Str::is(['admin.adminCommunication', 'admin.communication*'], $routeName) => 'communication',
            Str::is(['admin.adminReports'], $routeName) => 'reports',
            Str::is(['admin.activityLogs*'], $routeName) => 'logs',
            Str::is(['admin.adminApplicant*', 'admin.adminPosition*', 'admin.adminInterview*', 'admin.createPositionStore', 'admin.storeNewInterview', 'admin.storeUpdatedInterview', 'admin.updateStatus', 'admin.destroyPosition', 'admin.restorePosition', 'admin.interviewCancel', 'admin.interview.*'], $routeName) => 'hiring',
            Str::is(['admin.adminLoads*', 'admin.uploadLoadsFile', 'admin.scanLoadsFile', 'admin.deleteLoadsFile'], $routeName) => 'loads',
            Str::is(['admin.schoolAdministrator', 'admin.nonTeachingMatrix', 'admin.teachingMatrix'], $routeName) => 'matrix',
            Str::is(['admin.adminResignations', 'admin.resignations.*', 'admin.resignationAttachment.*', 'admin.updateResignationStatus'], $routeName) => 'resignations',
            Str::is(['admin.adminCalendar', 'admin.adminMeeting', 'admin.syncHiddenOfficialHolidays'], $routeName) => 'calendar',
            default => null,
        };
    }
}
