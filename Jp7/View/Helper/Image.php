<?php

class Jp7_View_Helper_Image extends Zend_View_Helper_Abstract {
	
	public function Image($file, $size = false, $crop = true) {
		if ($file) {
			$text = $file->getText();
			return '<img src="' . $file->getUrl($size, $crop) . '" title="' . $text . '" alt="' . $text . '" />';
		}
	}
}
