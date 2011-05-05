<?php

class Jp7_View_Helper_HeadLink extends Zend_View_Helper_HeadLink {
	
	public function removeStylesheet($filename) {
		$stack = $this->getContainer();
		
		foreach ($stack as $key => $value) {
			if ($value->href == $filename) {
				unset($stack[$key]);
				break;
			}
		}
	}
	
	public function toString($indent = null) {
		$config = Zend_Registry::get('config');
		foreach ($this as $item) {
			$item->href .= (strpos($item->href, '?') ? '&' : '?') . 'build=' . $config->build;
        }
		return parent::toString($indent);	
	}
	
}
