<?php

class Jp7_View_Helper_HeadLink extends Zend_View_Helper_HeadLink {
	
	public function removeStylesheet($filename) {
		$stack = $this->getContainer();
		foreach ($stack as $key => $value) {
			if (is_array($filename) && in_array($value->href, $filename)) {
				unset($stack[$key]);
			} elseif ($value->href == $filename) {
				unset($stack[$key]);
				break;
			}
		}
	}
	
	/**
	 * Replace stylesheet maintaining the same key
	 * @param string $search Current file
	 * @param string $replace New file
	 * @return void
	 */
	public function replaceStylesheet($search, $replace) {
		$stack = $this->getContainer();
		
		foreach ($stack as $key => $value) {
			if ($value->href == $search) {
				$value->href = $replace;
				break;
			}
		}
	}
	
	public function toString($indent = null) {
		$config = Zend_Registry::get('config');
		foreach ($this as $item) {
			if ($item->rel == 'stylesheet') {
				$item->href .= (strpos($item->href, '?') ? '&' : '?') . 'build=' . $config->build;
			}
        }
		return parent::toString($indent);
	}
	
}
