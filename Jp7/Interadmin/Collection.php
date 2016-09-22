<?php

namespace Jp7\Interadmin;

use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Arr;

class Collection extends BaseCollection
{
    /**
     * Converts from $item->subitems to $subitem->items.
     */
    public function flips($property, $keepItemsAs = 'items')
    {
        $subitems = [];
        foreach ($this->items as $item) {
            $subitem = $item->$property;
            if (is_object($subitem)) {
                $key = $subitem->__toString();
                if (!array_key_exists($key, $subitems)) {
                    $subitem->$keepItemsAs = [];
                    $subitems[$key] = $subitem;
                }
                $subitems[$key]->{$keepItemsAs}[] = $item;
            }
        }

        return new self($subitems);
    }

    public function split($parts)
    {
        $size = ceil(count($this) / $parts);
        if ($size > 0) {
            return $this->chunk($size);
        }

        return $this;
    }

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

    public function humanImplode($column, $glue, $lastGlue)
    {
        if ($items = $this->lists($column)) {
            $last = $items->splice(-2)->implode($lastGlue);
            $items->push($last);
        }

        return $items->implode($glue);
    }

    public function keySort(\Closure $callback)
    {
        uksort($this->items, $callback);
    }
}
