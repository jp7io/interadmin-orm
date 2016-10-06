<?php

use Codeception\Configuration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;

// Laravel facades
class Cache extends Illuminate\Support\Facades\Cache
{
    protected static function resolveFacadeInstance($name)
    {
        static $cache;
        if (!$cache) {
            $arraystore = new ArrayStore();
            $cache = new Repository($arraystore);
        }
        return $cache;
    }
}

class DB extends Illuminate\Support\Facades\DB
{
    protected static function resolveFacadeInstance($name)
    {
        static $db;
        if (!$db) {
            $db = new Capsule;

            $config = (object) Configuration::config()['modules']['config']['Db'];
            $db->addConnection([
                'driver'    => 'mysql',
                'host'      => $config->host,
                'database'  => $config->database,
                'username'  => $config->user,
                'password'  => $config->password,
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => 'interadmin_teste_',
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

function base_path($path = '')
{
    return BASE_PATH.($path ? DIRECTORY_SEPARATOR.$path : $path);
}

function config($key) {
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
