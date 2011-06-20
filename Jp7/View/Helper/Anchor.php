<?php

class Jp7_View_Helper_Anchor extends Zend_View_Helper_Abstract {
	
	public $already_used = array(
		'master' => 1,
		'header' => 1,
		'container' => 1,
		'container-header' => 1,
		'footer' => 1,
		'breadcrumbs' => 1,
		'menu' => 1
	);
	
	public function Anchor($title) {
		$anchor = toSeo($title);
		$this->already_used[$anchor]++;
		if ($this->already_used[$anchor] > 1) {
			$anchor .= '_' . $this->already_used[$anchor];
		}
		return $anchor;
	}
}
