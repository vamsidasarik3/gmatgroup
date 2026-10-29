<?php

$loader = require_once 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
$loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
$loader->setPsr4('Database\\Seeders\\', [dirname(__DIR__).'/database/seeders/']);
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

require_once __DIR__ . '/../database/seeders/GnatGroupAdminSeeder.php';
use Database\Seeders\GnatGroupAdminSeeder;
use App\Models\User;
use App\Models\Member;
use App\Models\SalesTransaction;
use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;

echo "Running GnatGroupAdminSeeder...\n";
$seeder = new GnatGroupAdminSeeder();
$seeder->run();

echo "Seeding completed!\n";
echo "- Users: " . User::count() . "\n";
echo "- Members: " . Member::count() . "\n";
echo "- Sales: " . SalesTransaction::count() . "\n";
echo "- Calculation Runs: " . CalculationRun::count() . "\n";
echo "- Ledger Entries: " . CommissionLedgerEntry::count() . "\n";
