<?php

namespace Jp7\Interadmin;

use Cache;
use DB;

class BaseClassMap
{
    protected function __construct()
    {
        // singleton
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        // singleton
        static::$instance = static::$instance ?: new static;
        return static::$instance;
    }

    protected static function prepareMap($attr)
    {
        $arr = [];
        try {
            $tipos = DB::table('tipos')
                ->select($attr, 'id_tipo', 'inherited')
                ->where($attr, '<>', '')
                ->where('deleted_tipo', '=', '')
                ->where('mostrar', '<>', '')
                ->orderByRaw("inherited LIKE '%".$attr."%'")
                ->get();

            foreach ($tipos as $tipo) {
                if (config('interadmin.psr-4')) {
                    $arr[$tipo->id_tipo] = str_replace('_', '\\', $tipo->$attr);
                } else {
                    $arr[$tipo->id_tipo] = $tipo->$attr;
                }
            }
        } catch (\PDOException $e) {
            $message = "InterAdmin database not connected";
            if (!\App::runningInConsole()) {
                throw new DbNotConnectedException($message, 0, $e);
            }
            // Exception is not thrown because artisan commands would stop working
            \Log::error($e);
            echo '[Skipped ClassMap] '.$message.PHP_EOL;
        }
        return $arr;
    }

    public function clearCache()
    {
        static::$classes = null;
        Cache::forget(static::CACHE_KEY);
    }

    public function getClasses()
    {
        if (static::$classes === null) {
            // check cache first
            static::$classes = Cache::get(static::CACHE_KEY);
            if (!static::$classes) {
                // not cached: call method
                static::$classes = static::prepareMap(static::CLASS_ATTRIBUTE);
                if (static::$classes) {
                    // only cache if it has classes
                    Cache::put(static::CACHE_KEY, static::$classes, 5);
                }
            }
        }
        return static::$classes;
    }

    /**
     * @param  string $class
     * @return int   id_tipo
     */
    public function getClassIdTipo($class)
    {
        return array_search($class, $this->getClasses());
    }

    /**
     * @param  int $id_tipo
     * @return string Class
     */
    public function getClass($id_tipo)
    {
        $classes = $this->getClasses();
        return isset($classes[$id_tipo]) ? $classes[$id_tipo] : null;
    }
}
