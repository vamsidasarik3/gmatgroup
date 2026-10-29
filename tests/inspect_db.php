<?php

$loader = require_once 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
$loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "TABLES IN DATABASE:\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $tableName = array_values((array)$t)[0];
    $count = DB::table($tableName)->count();
    echo "- {$tableName} ({$count} rows)\n";
}
