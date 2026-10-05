<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorStoreController;
use ReflectionMethod;
use Tests\TestCase;

class EmployeeImportAccountNumberTest extends TestCase
{
    public function test_account_hash_header_keeps_account_numbers_as_strings(): void
    {
        $controller = app(AdministratorStoreController::class);
        $method = new ReflectionMethod($controller, 'mapEmployeeImportRows');
        $rows = $method->invoke($controller, [
            ['A' => 'Name', 'B' => 'ID number', 'C' => 'Account #'],
            ['A' => 'Sample, Employee', 'B' => '12345678', 'C' => '0012345678'],
        ]);

        $this->assertSame('0012345678', $rows[0]['account_number']);
    }
}
