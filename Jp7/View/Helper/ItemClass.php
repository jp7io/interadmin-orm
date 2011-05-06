<?php

class Jp7_View_Helper_ItemClass extends Zend_View_Helper_Abstract {
	
	public function ItemClass($count, $key, $item = null) {
		$classes = array();
		if ($key == 0) {
			$classes[] = 'first-child';
		}
		if ($key + 1 == $count) {
			$classes[] = 'last-child';
		}
		if ($key % 2) {
			$classes[] = 'even';
		} else {
			$classes[] = 'odd';
		}
		if ($item instanceof InterAdmin) {
			$classes[] = 'id-' . $item->id;
			$classes[] = 'tipo-' . $item->id_tipo;
		}
		//$classes[] = 'pos-' . ($key + 1);
		return implode(' ', $classes);
	}
}
