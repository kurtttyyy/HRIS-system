<?php

namespace App\Support;

class EmployeeDirectorySearch
{
    public static function text($employee): string
    {
        return implode(' ', [
            trim(($employee->last_name ?? '').', '.trim(($employee->first_name ?? '').' '.($employee->middle_name ?? '')), ', '),
            (string) data_get($employee, 'employee.position', ''),
            (string) ($employee->position ?? ''),
            (string) data_get($employee, 'applicant.position.title', ''),
            (string) ($employee->job_role ?? ''),
        ]);
    }
}
