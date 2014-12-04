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
	
	public function pluck($value, $key = null) {
		return new self(array_pluck($this->items, $value, $key));		
	}
}
