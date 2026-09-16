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
                ->whereNull('deleted_at')
                ->where('visible', true)
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
        if ($type_id === false) {
            // The map's spelling is the TENANT's and the caller's is its own app's, and the two
            // disagree in BOTH directions. psr-4 off leaves the column's `Ci_Loja` in the map
            // while the alias bridge makes get_called_class() report `Ci\Loja`; psr-4 on, or a
            // tenant migrated to namespaced bindings, puts `Ci\Loja` in the map while ci's own
            // code still names `Ci_Loja`. Each app holds the other's code, ci-intranet vendoring
            // ci, so neither direction can be assumed away.
            //
            // ⚠ Without the underscore->namespaced arm, a tenant whose bindings have been
            // migrated cannot resolve an underscore name at all: the lookup misses, DynamicLoader
            // gets null from getCode() and declares nothing, and every static finder dies on null.
            //
            // Purely additive: it only runs when the direct lookup already returned false.
            $type_id = array_search(strpos($class, '\\') !== false
                ? str_replace('\\', '_', $class)
                : str_replace('_', '\\', $class), $this->getClasses());
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
