<?php

class Jp7_View_Helper_Money extends Zend_View_Helper_Abstract {
	/**
	 * Formats an string "151,14" or "151" into money format "151,00.
	 * 
	 * @param float $float
	 * @param int $decimals
	 * @param string $dec_point
	 * @param string $thousands_sep
	 * @return string
	 */
	public function Money($float, $decimals = 2, $dec_point = ',', $thousands_sep = '.') {
		return number_format($float, $decimals, $dec_point, $thousands_sep);
	}
}
