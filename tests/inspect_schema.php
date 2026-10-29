<?php

$loader = require_once 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
$loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tablesToCheck = [
    'commission_models',
    'model_nodes',
    'model_edges',
    'commission_ledger',
    'override_relationships',
    'override_sales',
    'override_commissions',
];

foreach ($tablesToCheck as $tbl) {
    echo "\n=== Table: {$tbl} ===\n";
    $columns = DB::select("DESCRIBE `{$tbl}`");
    foreach ($columns as $c) {
        echo "  - {$c->Field} ({$c->Type})\n";
    }
    $sample = DB::table($tbl)->limit(2)->get();
    echo "Sample data: " . json_encode($sample) . "\n";
}
