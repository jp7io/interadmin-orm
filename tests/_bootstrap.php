<?php
// This is global bootstrap for autoloading
define('BASE_PATH', __DIR__.'/..');

#error_reporting(E_ALL ^ E_NOTICE);

require BASE_PATH . '/vendor/autoload.php'; // Composer Autoload
require __DIR__.'/_laravel_polyfill.php';

// Set default locale
App::setLocale('pt-BR');

class_alias(Carbon\Carbon::class, 'Date');

Jp7\Interadmin\DynamicLoader::register();
