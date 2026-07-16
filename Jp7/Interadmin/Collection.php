<?php

namespace Jp7\Interadmin;

use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Arr;

class Collection extends BaseCollection
{
    private $onDestruct;
    public $_refs;
    
    /**
     * @deprecated Don't extend Collection
     */
    public function jsonList($column, $key)
    {
        $array = [];
        foreach (Arr::pluck($this->items, $column, $key) as $key2 => $value) {
            $array[] = [
                'key' => $key2,
                'value' => $value,
            ];
        }
        return $array;
    }

    /**
     * @deprecated use pluck() instead
     */
    public function lists($value, $key = null)
    {
        return $this->pluck($value, $key);
    }

    /**
     * @deprecated Don't extend Collection
     */
    public function radioList($column, $key)
    {
        $array = [];
        foreach (Arr::pluck($this->items, $column, $key) as $key2 => $value) {
            $array[$value] = [
                'value' => $key2
            ];
        }
        return $array;
    }

    /**
     * @deprecated Don't extend Collection
     */
    public function humanImplode($column, $glue, $lastGlue)
    {
        if ($items = $this->lists($column)) {
            $last = $items->splice(-2)->implode($lastGlue);
            $items->push($last);
        }

        return $items->implode($glue);
    }

    public function onDestruct($closure)
    {
        $this->onDestruct = $closure;
    }

    public function __destruct()
    {
        if ($this->onDestruct) {
            ($this->onDestruct)();
        }
    }
    
    public function __sleep()
    {
        $this->onDestruct = null; // PHP can't serialize Closures
        return array_keys(get_object_vars($this));
    }
}
