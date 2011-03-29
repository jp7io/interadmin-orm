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
}
