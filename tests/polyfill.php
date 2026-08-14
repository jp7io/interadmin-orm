<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;

/*
 * Minimal stand-ins for the Laravel services the ORM reaches for through global class names.
 *
 * The package always runs inside a real Laravel app in production; its own suite boots no
 * framework, so anything the ORM calls statically has to exist here or the tests cannot load.
 * Keep this list as small as the ORM's actual surface -- every entry added here is a Laravel
 * API the ORM depends on without declaring it, and therefore worth removing rather than
 * polyfilling.
 */

class Cache extends Illuminate\Support\Facades\Cache
{
    protected static function resolveFacadeInstance($name)
    {
        static $cache;
        if (!$cache) {
            $cache = new Repository(new ArrayStore());
        }
        return $cache;
    }

    public static function store($name = null)
    {
        return self::resolveFacadeInstance('');
    }
}

class DB extends Illuminate\Support\Facades\DB
{
    public static function raw($string)
    {
        return new Illuminate\Database\Query\Expression($string);
    }

    protected static function resolveFacadeInstance($name)
    {
        static $db;
        if (!$db) {
            $db = new Capsule;
            $db->addConnection(testDatabaseConfig() + [
                'driver' => 'mysql',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => 'interadmin_teste_',
                'strict' => false,
                'timezone' => '-03:00',
            ]);
            $db->setAsGlobal();
        }
        return $db;
    }
}

class App
{
    public static function bound($interface)
    {
        return $interface === 'config';
    }

    public static function setLocale($locale)
    {
        Lang::setLocale($locale);
    }

    public static function getLocale()
    {
        return Lang::getLocale();
    }

    public static function runningInConsole()
    {
        return true;
    }
}

class Lang
{
    private static $repository;
    private static $locale;

    public static function setLocale($locale)
    {
        $map = [
            'pt-BR' => [
                'prefix' => '',
                'suffix' => '',
            ],
            'en' => [
                'prefix' => 'en_',
                'suffix' => '_en',
            ]
        ];

        self::$locale = $locale;
        self::$repository = new Illuminate\Config\Repository([
            'interadmin' => $map[$locale]
        ]);
    }

    public static function getLocale()
    {
        return self::$locale;
    }

    public static function has($key)
    {
        return isset(self::$repository[$key]);
    }

    public static function get($key)
    {
        if (!isset(self::$repository[$key])) {
            throw new OutOfBoundsException($key);
        }
        return self::$repository[$key];
    }
}

class Request
{
    public static function ip()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '::1';
    }
}

class Log
{
    public static function error($e)
    {
        echo 'Log@error: '.$e->getMessage().PHP_EOL;
    }

    // Discarded rather than echoed: the ORM writes these per query and per lazy load.
    public static function debug($message)
    {
    }

    public static function notice($message)
    {
    }
}

/**
 * Connection settings shared by the Capsule above and by TestCase's own PDO handle.
 *
 * @return array{host: string, port: string, database: string, username: string, password: string}
 */
function testDatabaseConfig(): array
{
    return [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'interadmin_orm_test',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ];
}

function base_path($path = '')
{
    return BASE_PATH.($path ? DIRECTORY_SEPARATOR.$path : $path);
}

function config($key)
{
    $repository = new Illuminate\Config\Repository([
        'interadmin' => [
            'psr-4' => false,
            'preview' => true, // publish
            'storage' => [
                // Prefix path InterAdmin saves
                'backend_path' => '../..',
                // Prefix path on the URL for the browser
                'path' => '',
                // Storage HOST
                'host' => 'example.org',
            ]
        ]
    ]);
    if (!isset($repository[$key])) {
        throw new OutOfBoundsException($key);
    }
    return $repository[$key];
}
