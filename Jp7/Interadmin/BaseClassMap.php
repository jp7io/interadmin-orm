<?php

namespace Jp7\Interadmin;

use Cache;
use DB;

class BaseClassMap
{
    protected $classes;

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
        $roots = []; // keep track of duplicated classes
        try {
            $tipos = DB::table('tipos')
                ->select($attr, 'id_tipo', 'inherited')
                ->where($attr, '<>', '')
                ->where('deleted_tipo', '=', '')
                ->where('mostrar', '<>', '')
                ->orderByRaw("inherited LIKE '%".$attr."%'")
                ->get();

            foreach ($tipos as $tipo) {
                $class = $tipo->$attr;
                if (config('interadmin.psr-4')) {
                    $class = str_replace('_', '\\', $class);
                }
                if (!$tipo->inherited || !in_array($attr, explode(',', $tipo->inherited))) {
                    if (array_key_exists($class, $roots) && config('interadmin.namespace')) {
                        throw new \UnexpectedValueException('Duplicate entry for class: '.$class.' in id_tipo: '.$tipo->id_tipo);
                    }
                    $roots[$class] = true;
                }
                $arr[$tipo->id_tipo] = $class;
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
        Cache::tag(Type::CACHE_TAG)->forget(static::CACHE_KEY);
        static::getInstance()->classes = null;
    }

    public function getClasses()
    {
        if ($this->classes === null) {
        	$cache = Cache::tag(Type::CACHE_TAG);
            // check cache first
            $this->classes = $cache->get(static::CACHE_KEY);
            if (!$this->classes) {
                // not cached: call method
                $this->classes = static::prepareMap(static::CLASS_ATTRIBUTE);
                if ($this->classes) {
                    // only cache if it has classes
                    $cache->put(static::CACHE_KEY, $this->classes, 5);
                }
            }
        }
        return $this->classes;
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
