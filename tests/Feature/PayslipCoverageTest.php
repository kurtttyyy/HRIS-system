<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayslipRecord;
use App\Models\User;
use App\Support\PayslipCoverage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayslipCoverageTest extends TestCase
{
    public function test_duplicate_uploads_count_each_employee_once(): void
    {
        Schema::create('payslip_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('employee_id')->nullable();
            $table->timestamps();
        });
        $employees = collect([1, 2, 3])->map(function ($id) {
            $user = new User;
            $user->id = $id;
            $user->setRelation('employee', new Employee(['employee_id' => 'EMP-'.$id]));
            return $user;
        });
        $coverage = app(PayslipCoverage::class);
        $this->assertSame(0, $coverage->countEmployees($employees));
        for ($index = 0; $index < 5; $index++) {
            PayslipRecord::create(['user_id' => 1, 'employee_id' => 'EMP-1']);
        }
        $this->assertSame(1, $coverage->countEmployees($employees));
        PayslipRecord::create(['employee_id' => 'EMP-2']);
        PayslipRecord::create(['employee_id' => 'EMP-2']);
        PayslipRecord::create(['user_id' => 99, 'employee_id' => 'EMP-3']);
        $this->assertSame(2, $coverage->countEmployees($employees));
        $this->assertSame(0, $coverage->countEmployees(collect()));
    }
}
