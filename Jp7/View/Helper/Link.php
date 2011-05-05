<?php

class Jp7_View_Helper_Link extends Zend_View_Helper_Abstract {
	
	protected $href;
	protected $text;
	
	public function Link($href, $text = '') {
		$this->href = $href;
		$this->text = $text;
		return '<a href="' . $this->href . '">' . $this->text . '</a>';
	}
}
