<?php

namespace Jp7\InterAdmin;

use Cache;
use DB;

/**
 * @phpstan-consistent-constructor
 */
abstract class BaseClassMap
{
    // $instance, CACHE_KEY and CLASS_ATTRIBUTE are deliberately NOT declared here: undefined
    // on the base is what makes a subclass that forgets one a fatal rather than a map cached
    // under '' and a singleton shared with its sibling. PHPStan reports all six; baselined.

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

    protected static function prepareMap($attr): array
    {
        $arr = [];
        $roots = []; // keep track of duplicated classes
        try {
            $types = DB::table('types')
                ->select($attr, 'type_id', 'inherited')
                ->where($attr, '<>', '')
                ->where('deleted_tipo', '=', '')
                ->where('mostrar', '<>', '')
                ->orderByRaw("inherited LIKE '%".$attr."%'")
                ->get();

            foreach ($types as $type) {
                $class = $type->$attr;
                if (config('interadmin.psr-4')) {
                    $class = str_replace('_', '\\', $class);
                }
                if (!$type->inherited || !in_array($attr, explode(',', $type->inherited))) {
                    if (array_key_exists($class, $roots) && config('interadmin.namespace')) {
                        throw new \UnexpectedValueException('Duplicate entry for class: '.$class.' in type_id: '.$type->type_id);
                    }
                    $roots[$class] = true;
                }
                $arr[$type->type_id] = $class;
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

    public function clearCache(): void
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
                    $cache->put(static::CACHE_KEY, $this->classes, Type::CACHE_TTL);
                }
            }
        }
        return $this->classes;
    }

    /**
     * @param  string $class
     * @return int   type_id
     */
    public function getClassTypeId($class): int|string|false
    {
        $type_id = array_search($class, $this->getClasses());
        if ($type_id === false && strpos($class, '\\') !== false) {
            // Tenants with interadmin.psr-4=false bind types to underscore class
            // names, but a legacy underscore->namespace bridge (class_alias) makes
            // get_called_class() report the namespaced form (e.g. Ci\Loja instead
            // of Ci_Loja). Fall back to the underscore key so static finders
            // (::where/::find/::query/::orderBy) resolve for those aliased classes.
            // Purely additive: only runs when the direct lookup already missed.
            $type_id = array_search(str_replace('\\', '_', $class), $this->getClasses());
        }
        return $type_id;
    }

    /**
     * @param  int $type_id
     * @return string Class
     */
    public function getClass($type_id)
    {
        $classes = $this->getClasses();
        return isset($classes[$type_id]) ? $classes[$type_id] : null;
    }
}
