<?php

class Jp7_View_Helper_HeadScript extends Zend_View_Helper_HeadScript {
	
	public function removeFile($filename) {
		$stack = $this->getContainer();
		foreach ($stack as $key => $value) {
			if (is_array($filename) && in_array($value->attributes['src'], $filename)) {
				unset($stack[$key]);
			} elseif ($value->attributes['src'] == $filename) {
				unset($stack[$key]);
				break;
			}
		}
	}
	
	/**
	 * Replace file maintaining the same key
	 * @param string $search Current file
	 * @param string $replace New file
	 * @return void
	 */
	public function replaceFile($search, $replace) {
		$stack = $this->getContainer();
		
		foreach ($stack as $key => $value) {
			if ($value->attributes['src'] == $search) {
				$value->attributes['src'] = $replace;
				break;
			}
		}
	}
	
	public function toString($indent = null) {
		$config = Zend_Registry::get('config');
		foreach ($this as $item) {
			if ($item->attributes['src']) {
				$item->attributes['src'] .= (strpos($item->attributes['src'], '?') ? '&' : '?') . 'build=' . $config->build;
        	}
		}
		return parent::toString($indent);	
	}
}
