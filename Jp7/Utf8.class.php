<?php

class Jp7_Utf8 {
	static $cp1252 = array(
		"\xC2\x80",
		"\xC2\x82",
		"\xC2\x83",
		"\xC2\x84",
		"\xC2\x85",
		"\xC2\x86",
		"\xC2\x87",
		"\xC2\x88",
		"\xC2\x89",
		"\xC2\x8A",
		"\xC2\x8B",
		"\xC2\x8C",
		"\xC2\x8E",
		"\xC2\x91",
		"\xC2\x92",
		"\xC2\x93",
		"\xC2\x94",
		"\xC2\x95",
		"\xC2\x96",
		"\xC2\x97",
		"\xC2\x98",
		"\xC2\x99",
		"\xC2\x9A",
		"\xC2\x9B",
		"\xC2\x9C",
		"\xC2\x9E",
		"\xC2\x9F"
	);
	
	static $utf8 = array(
		"\xE2\x82\xAC",  // EURO SIGN
		"\xE2\x80\x9A",  // SINGLE LOW-9 QUOTATION MARK
		"\xC6\x92",      // LATIN SMALL LETTER F WITH HOOK
		"\xE2\x80\x9E",  // DOUBLE LOW-9 QUOTATION MARK
		"\xE2\x80\xA6",  // HORIZONTAL ELLIPSIS
		"\xE2\x80\xA0",  // DAGGER
		"\xE2\x80\xA1",  // DOUBLE DAGGER
		"\xCB\x86",      // MODIFIER LETTER CIRCUMFLEX ACCENT
		"\xE2\x80\xB0",  // PER MILLE SIGN
		"\xC5\xA0",      // LATIN CAPITAL LETTER S WITH CARON
		"\xE2\x80\xB9",  // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
		"\xC5\x92",      // LATIN CAPITAL LIGATURE OE
		"\xC5\xBD",      // LATIN CAPITAL LETTER Z WITH CARON
		"\xE2\x80\x98",  // LEFT SINGLE QUOTATION MARK
		"\xE2\x80\x99",  // RIGHT SINGLE QUOTATION MARK
		"\xE2\x80\x9C",  // LEFT DOUBLE QUOTATION MARK
		"\xE2\x80\x9D",  // RIGHT DOUBLE QUOTATION MARK
		"\xE2\x80\xA2",  // BULLET
		"\xE2\x80\x93",  // En Dash
		"\xE2\x80\x94",  // Em Dash
		"\xCB\x9C",      // Small Tilde
		"\xE2\x84\xA2",  // Trade Mark Sign
		"\xC5\xA1",      // Latin Small Letter S With Caron
		"\xE2\x80\xBA",  // Single Right-Pointing Angle Quotation Mark
		"\xC5\x93",      // Latin Small Ligature Oe
		"\xC5\xBE",      // Latin Small Letter Z With Caron
		"\xC5\xB8"       // Latin Capital Letter Y With Diaeresis
	); 
			
	/**
	 * Converts a string with mixed encodings (ASCII, ISO-8859-1, CP1252) to UTF-8
	 * @param string $str
	 * @return string
	 */
	public static function encode($str) {
		if (preg_match('/[\x80-\x9F]/', $str)) {
			$output = utf8_encode($str);
			$output = str_replace(self::$cp1252, self::$utf8, $output);
			return $output;
		} else {
			return utf8_encode($str);
		}
	}
	
	/**
	 * Decodes a UTF-8 string back to a string with mixed encodings (ASCII, ISO-8859-1, CP1252).
	 * @param string $str
	 * @return string
	 */
	public static function decode($str) {
		return utf8_decode(str_replace(self::$utf8, self::$cp1252, $str));
	}	
}