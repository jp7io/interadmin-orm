<?php

namespace Jp7\Interadmin;

use Illuminate\Support\Collection as BaseCollection;

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
                    $subitem->$keepItemsAs = array();
                    $subitems[$key] = $subitem;
                }
                $subitems[$key]->{$keepItemsAs}[] = $item;
            }
        }

        return new self($subitems);
    }

    /**
     * Just like ->lists(), but chainable.
     */
    public function collect($value, $key = null)
    {
        return new self(array_pluck($this->items, $value, $key));
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
        foreach ($this->items as $item) {
            $array[] = [
                'key' => $item->$key,
                'value' => $item->$column,
            ];
        }

        return $array;
    }

    public function humanImplode($column, $glue, $lastGlue)
    {
        if ($items = $this->lists($column)) {
            $last = implode($lastGlue, array_splice($items, -2));
            array_push($items, $last);
        }

        return implode($glue, $items);
    }

    public function keySort(\Closure $callback)
    {
        uksort($this->items, $callback);
    }
}
