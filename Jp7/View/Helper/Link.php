<?php

class Jp7_View_Helper_Link extends Zend_View_Helper_Abstract {
	
	public function Link($href, $text = '') {
		return '<a href="' . $href . '">' . $text . '</a>';
	}
}
