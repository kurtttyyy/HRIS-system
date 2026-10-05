<?php

// Recover only missing account numbers from retained employee imports.
// Run with --apply to save; the default reports counts without changing data.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(\App\Http\Controllers\AdministratorStoreController::class);
$invoke = static fn (string $method, array $args) => (new ReflectionMethod($controller, $method))->invokeArgs($controller, $args);
$files = glob(storage_path('app/private/employee_imports/*.xlsx')) ?: [];
sort($files);
$accounts = [];
foreach ($files as $file) {
    $sheet = $invoke('findEmployeeImportWorksheet', [$file]);
    $raw = $invoke('extractRawRowsFromXlsx', [$file, $sheet, false]);
    foreach ($invoke('mapEmployeeImportRows', [$raw]) as $row) {
        $id = $invoke('normalizeEmployeeImportId', [$row['employee_id'] ?? $row['employee_number'] ?? $row['employee_no'] ?? $row['id_number'] ?? null]);
        $account = trim((string) ($row['account_number'] ?? ''));
        if ($id && !in_array(strtolower($account), ['', '-', 'n/a', 'na', 'none', 'null'], true)) {
            $accounts[$id] = $account;
        }
    }
}
$apply = in_array('--apply', $argv, true);
$eligible = 0;
$updated = 0;
\Illuminate\Support\Facades\DB::transaction(function () use ($accounts, $apply, &$eligible, &$updated) {
    foreach ($accounts as $id => $account) {
        $query = \Illuminate\Support\Facades\DB::table('employees')->where('employee_id', (string) $id)
            ->where(function ($query) {
                $query->whereNull('account_number')->orWhereRaw("LOWER(TRIM(account_number)) IN ('', '-', 'n/a', 'na', 'none', 'null')");
            });
        $eligible += (clone $query)->count();
        if ($apply) {
            $updated += $query->update(['account_number' => $account]);
        }
    }
});
echo json_encode(['stored_imports' => count($files), 'source_employee_accounts' => count($accounts), 'missing_accounts_recoverable' => $eligible, 'updated' => $updated], JSON_PRETTY_PRINT).PHP_EOL;
