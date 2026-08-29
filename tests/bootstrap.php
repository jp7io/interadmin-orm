<?php

use Dotenv\Dotenv;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH.'/vendor/autoload.php';

// The suite reads DB_* from .env.testing; see .env.example. Kept optional so a CI job can
// supply the same names as real environment variables instead of writing a file.
if (is_file(BASE_PATH.'/.env.testing')) {
    Dotenv::createImmutable(BASE_PATH, '.env.testing')->load();
}

// Standalone facades for the handful of Laravel services the ORM touches. The package is
// consumed by full Laravel apps, but its own suite boots no framework -- see the file.
require __DIR__.'/polyfill.php';

date_default_timezone_set('America/Sao_Paulo');
App::setLocale('pt-BR');

class_alias(Carbon\Carbon::class, 'Date');

Jp7\Laravel\CacheExtension::apply();

// Registers the autoloader that evals a class body for every row in `tipos`, which is how
// the tests' Test_User / Test_Store come into existence at all.
Jp7\InterAdmin\DynamicLoader::register();

TestCase::createSchema();
