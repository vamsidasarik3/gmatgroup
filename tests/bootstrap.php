<?php

$loader = require 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
if (!is_object($loader)) {
    foreach (spl_autoload_functions() as $f) {
        if (is_array($f) && isset($f[0]) && $f[0] instanceof \Composer\Autoload\ClassLoader) {
            $loader = $f[0];
            break;
        }
    }
}
if (is_object($loader)) {
    $loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
    $loader->setPsr4('Tests\\', [__DIR__]);
}
