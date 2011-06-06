<?php

class Jp7_View_Helper_Image extends Zend_View_Helper_Abstract {	
	public function Image($file, $size = false, $crop = true, $wrapper = true) {
		if ($file) {
			$text = $file->getText();
			
			$img = '<img src="' . $file->getUrl($size, $crop) . '" title="' . $text . '" alt="' . $text . '" />';
			if ($wrapper) {
				$img = '<div class="img-wrapper">' . $img . '</div>';
			}
			return $img;
		}
	}
}
