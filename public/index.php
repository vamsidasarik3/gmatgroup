<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
if (file_exists(__DIR__.'/../vendor/autoload.php') && file_exists(__DIR__.'/../vendor/laravel/framework')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    $loader = require 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
    $loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
    $loader->setPsr4('Database\\Factories\\', [dirname(__DIR__).'/database/factories/']);
    $loader->setPsr4('Database\\Seeders\\', [dirname(__DIR__).'/database/seeders/']);

    foreach (glob(dirname(__DIR__) . '/app/Models/*.php') as $modelFile) {
        require_once $modelFile;
    }
}

// Bootstrap Laravel and handle the request...
/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
