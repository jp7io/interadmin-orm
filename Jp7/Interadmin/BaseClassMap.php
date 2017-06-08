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
        $tipos = DB::table('tipos')
            ->select($attr, 'id_tipo', 'inherited')
            ->where($attr, '<>', '')
            ->where('deleted_tipo', '=', '')
            ->where('mostrar', '<>', '')
            ->orderByRaw("inherited LIKE '%".$attr."%'")
            ->get();

        $arr = [];
        foreach ($tipos as $tipo) {
            if (config('interadmin.psr-4')) {
                $arr[$tipo->id_tipo] = str_replace('_', '\\', $tipo->$attr);
            } else {
                $arr[$tipo->id_tipo] = $tipo->$attr;
            }
        }
        return $arr;
    }

    public function clearCache()
    {
        Cache::forget(static::CACHE_KEY);
    }

    public function getClasses()
    {
        return Cache::remember(static::CACHE_KEY, 5, function () {
            return static::prepareMap(static::CLASS_ATTRIBUTE);
        });
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
