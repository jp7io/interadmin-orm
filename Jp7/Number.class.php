<?php

class Jp7_Number {
	private static $_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	
	public static function base10to62($num)
	{
	    $base = 62; //strlen(self::$_characters);
	    $string = self::$_characters[$num % $base];
		
	    while (($num = intval($num / $base)) > 0) {
	        $string = self::$_characters[$num % $base] . $string;
	    }
		
	    return $string;
	}
	
	public static function base62to10($str) {
		$num = 0;
		$str = strrev($str);
		for ($i = 0; $i < strlen($str); $i++) {
			$pos = strpos(self::$_characters, $str[$i]);
			$num += $pos * pow(62, $i);
		}		
		return $num;
	}
}