<?php

$loader = require_once 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
$loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$migrationFiles = [
    '2026_10_13_000001_enhance_commission_models_table.php',
    '2026_10_13_000002_create_members_table.php',
    '2026_10_13_000003_create_member_relationships_table.php',
    '2026_10_13_000004_create_sales_transactions_table.php',
    '2026_10_13_000005_create_calculation_runs_table.php',
    '2026_10_13_000006_create_commission_ledger_entries_table.php',
    '2026_10_13_000007_add_role_to_users_table.php',
];

$maxBatch = DB::table('migrations')->max('batch') ?? 0;
$nextBatch = $maxBatch + 1;

foreach ($migrationFiles as $file) {
    $migrationName = str_replace('.php', '', $file);
    $alreadyRun = DB::table('migrations')->where('migration', $migrationName)->exists();
    if ($alreadyRun) {
        echo "Already migrated: {$migrationName}\n";
        continue;
    }
    
    echo "Running migration: {$migrationName}...\n";
    $migration = require_once __DIR__ . '/../database/migrations/' . $file;
    $migration->up();
    
    DB::table('migrations')->insert([
        'migration' => $migrationName,
        'batch' => $nextBatch,
    ]);
    echo "Done: {$migrationName}\n";
}

echo "\nMigration check:\n";
$tables = ['members', 'member_relationships', 'sales_transactions', 'calculation_runs', 'commission_ledger_entries'];
foreach ($tables as $t) {
    echo "- Table '{$t}' exists: " . (Schema::hasTable($t) ? 'YES' : 'NO') . "\n";
}
