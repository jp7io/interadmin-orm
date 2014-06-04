<?php

abstract class Jp7_Tag_Container extends Jp7_Tag {
	protected $items = array();
	
	public function __construct($attrs = array()) {
		$this->attrs = $attrs;
	}
	/**
	 * @param Jp7_Tag $item
	 */
	public function add(Jp7_Tag $item) {
		$this->items[] = $item;
	}
	
	public function val($value = null) {
		if (is_null($value)) {
			$s = '';
			foreach ($this->items as $item) {
				$s .= (string) $item;
			}
			return $s;
		}
	}
	/**
	 * @return array
	 */
	public function getItems() {
		return $this->items;
	}
}