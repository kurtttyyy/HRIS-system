<?php

namespace Tests\Feature;

use App\Models\{User, Applicant, Resignation};
use App\Support\EmployeeArchive;
use Tests\TestCase;

class EmployeeArchiveTest extends TestCase
{
    public function test_resigned_employees_are_excluded_but_pending_and_rehired_employees_remain(): void
    {
        $user = new User;
        $user->setRelation('applicant', null);
        $record = (new Resignation)->forceFill(['status' => 'Pending', 'effective_date' => '2026-10-01', 'processed_at' => '2026-09-30']);
        $user->setRelation('resignations', collect([$record]));
        $this->assertFalse(EmployeeArchive::isResigned($user));
        foreach (['Approved', 'Completed'] as $status) {
            $record->status = $status;
            $this->assertTrue(EmployeeArchive::isResigned($user));
        }
        $user->setRelation('applicant', (new Applicant)->forceFill(['date_hired' => '2026-11-01', 'created_at' => '2026-10-20']));
        $this->assertFalse(EmployeeArchive::isResigned($user));
        $user->setRelation('applicant', (new Applicant)->forceFill(['date_hired' => '2026-01-01', 'created_at' => '2025-12-01']));
        $this->assertTrue(EmployeeArchive::isResigned($user));
    }
}
