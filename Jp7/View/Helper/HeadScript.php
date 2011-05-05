<?php

class Jp7_View_Helper_HeadScript extends Zend_View_Helper_HeadScript {
	
	public function removeFile($filename) {
		$stack = $this->getContainer();
		foreach ($stack as $key => $value) {
			if ($value->attributes['src'] == $filename) {
				unset($stack[$key]);
				break;
			}
		}
	}
	
	public function toString($indent = null) {
		$config = Zend_Registry::get('config');
		foreach ($this as $item) {
			$item->attributes['src'] .= (strpos($item->attributes['src'], '?') ? '&' : '?') . 'build=' . $config->build;
        }
		return parent::toString($indent);	
	}
}
