<?php

class Jp7_View_Helper_ImgUrl extends Zend_View_Helper_Abstract {
	
	public function ImgUrl($file) {
		$template_path = Zend_Registry::get('config')->template_path;
		if ($template_path) {
			return $template_path . '/' . $file;
		} else {
			return $file;
		}
	}
}
