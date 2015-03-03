<?php

namespace Jp7\Interadmin;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection {
	
	/**
	 * Converts from $item->subitems to $subitem->items
	 */
	public function flips($property, $keepItemsAs = 'items') {
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
	 * Just like ->lists(), but chainable
	 */
	public function collect($value, $key = null) {
		return new self(array_pluck($this->items, $value, $key));		
	}
	
	public function split($parts) {
		return $this->chunk(ceil(count($this) / $parts));
	}
}
