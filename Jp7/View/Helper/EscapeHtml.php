<?php

class Jp7_View_Helper_EscapeHtml extends Zend_View_Helper_Abstract {
	
	public function EscapeHtml($html) {
		return strip_tags($html, '<b><i><p><br>');
	}
}
