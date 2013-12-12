<?php

class Jp7_View_Helper_HeadMeta extends Zend_View_Helper_HeadMeta {
	private $_toRemove = array();

	public function removeMeta($type, $key = '') {
		$this->_toRemove[] = array($type, $key);
	}
	
	public function toString($indent = null) {
		foreach ($this->_toRemove as $item) {
			foreach ($this as $key => $meta) {
				if (($item[0] == 'http-equiv' && $meta->type == 'http-equiv') || $meta->name == $item[1]) {
					unset($this[$key]);

					continue 2;
				} 
			}
		}

		return parent::toString($indent);
	}
}
