<?php
class Jp7_PageMap {
	public function getHtml() {
		$str = '';
		foreach ($this as $type => $objects) {
			if (!is_array($objects)) {
				$objects = array($objects);
			}
			foreach ($objects as $attributes) {
				$str .= "\t" . '<DataObject type="' . $type . '">' . "\r\n";
				foreach ($attributes as $name => $values) {
					if (!is_array($values)) {
						$values = array($values);
					}
					foreach ($values as $value) {
						$str .= "\t\t" . '<Attribute name="' . $name .'" value="' . $value . '" />' . "\r\n";
					}
				}
				$str .= "\t" . '</DataObject>' . "\r\n";
			}
		}
		if ($str) {
			return '<!--' . "\r\n" .
				'<PageMap>' . "\r\n" .
				$str .
				'</PageMap>' . "\r\n" . 
				'-->' . "\r\n";
		}
	}
}