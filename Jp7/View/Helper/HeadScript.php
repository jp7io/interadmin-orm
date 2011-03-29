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
}
