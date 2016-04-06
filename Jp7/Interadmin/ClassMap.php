<?php

namespace Jp7\Interadmin;

class ClassMap
{
    CONST CACHE_KEY = 'Interadmin.classMap';
    private static $instance;

    private $classes;
    private $classesTipos;

    private function __construct()
    {
        // singleton
    }

    public static function getInstance()
    {
        // singleton
        if (!self::$instance) {
            self::$instance = \Cache::remember(self::CACHE_KEY, 60, function () {
                // cache instance
                $instance = new self();
                $instance->setClasses(self::loadMap('class'));
                $instance->setClassesTipos(self::loadMap('class_tipo'));
                // return cached instance
                return $instance;
            });
            if (self::$instance->isEmpty()) {
                \Cache::forget(self::CACHE_KEY);
            }
        }

        return self::$instance;
    }

    private static function loadMap($attr)
    {
        $tipos = \DB::table('tipos')
            ->select($attr, 'id_tipo', 'inherited')
            ->where($attr, '<>', '')
            ->where('deleted_tipo', '=', '')
            ->where('mostrar', '<>', '')
            ->orderByRaw("inherited LIKE '%".$attr."%'")
            ->get();

        $arr = [];
        foreach ($tipos as $tipo) {
            $arr[$tipo->id_tipo] = $tipo->$attr;
        }

        return $arr;
    }

    public function isEmpty()
    {
        return !$this->classes && !$this->classesTipos;
    }
    
    public function setClasses(array $classes)
    {
        $this->classes = $classes;
    }

    public function setClassesTipos(array $classesTipos)
    {
        $this->classesTipos = $classesTipos;
    }

    public function getClassIdTipo($class)
    {
        return array_search($class, $this->classes);
    }

    public function getClassTipoIdTipo($classTipo)
    {
        return array_search($classTipo, $this->classesTipos);
    }

    public function getClass($id_tipo)
    {
        return isset($this->classes[$id_tipo]) ? $this->classes[$id_tipo] : null;
    }

    public function getClassTipo($id_tipo)
    {
        return isset($this->classesTipos[$id_tipo]) ? $this->classesTipos[$id_tipo] : null;
    }
}
