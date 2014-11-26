<?php

// INTERADMIN COMPATIBILITY FUNCTIONS 

function isoentities($string) {
	return htmlentities($string, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1');
}

function isospecialchars($string) {
	return htmlspecialchars($string, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1');
}

/**
 * Adds a trailing slash on a path, in case it doesn't have one.
 *
 * @param string $S Input String (Path, URL).
 * @param bool $reverse If <tt>TRUE</tt> the trailing slash is removed instead of added, the default value is <tt>FALSE</tt>.
 * @return string String with a trailing slash.
 * @version (2003/08/25)
 */
function jp7_path($S, $reverse = FALSE){
	if ($reverse) {
		return (substr($S, strlen($S) - 1) == '/') ? substr($S, 0, strlen($S) - 1) : $S;
	} else {
		return (substr($S, -1) == '/' || !$S) ? $S : $S . '/';
	}
}

/**
 * Formats a DSN from an object with 'type', 'host', 'user', 'pass' and 'name'.
 * @param object $db	Object with the database information.
 * @return string DSN
 */
function jp7_formatDsn($db) {
	$dsn = $db->type . '://' . $db->user . ':' . $db->pass . '@' . $db->host . '/' . $db->name;
	if ($db->flags) {
		$dsn .= $db->flags;
	}
	return $dsn;
}

/**
 * Same as str_replace but only if the string starts with $search.
 * 
 * @param string $search
 * @param string $replace
 * @param string $subject
 * @return string
 */
function jp7_replace_beginning($search, $replace, $subject) {
	if (strpos($subject, $search) === 0) {
		return $replace . substr($subject, strlen($search));
	} else {
		return $subject;
	}	
}

/**
 * Splits the string into an array. The difference from explode() is that jp7_explode() unsets empty values.
 * 
 * @param string $separator
 * @param string $string
 * @param bool $useTrim If set the function will trim() each part of the string. Defaults to <tt>TRUE</tt>.
 * @return array Array of parts withuot any empty value.
 */
function jp7_explode($separator, $string, $useTrim = true) {
	$array = explode($separator, $string);
	if ($useTrim) {
		return array_filter($array, 'trim');
	} else {
		return array_filter($array, create_function('$a', 'return (bool) $a;'));
	}
}

/**
 * Takes off diacritics and empty spaces from a string, if $tofile is <tt>FALSE</tt> (default) the case is changed to lowercase.
 *
 * @param string $S String to be formatted.
 * @param bool $tofile Sets whether it will be used for a filename or not, <tt>FALSE</tt> is the default value.
 * @param string $separador	Separator used to replace empty spaces.
 * @return string Formatted string.
 * @version (2006/01/18)
 */
function toId($string, $tofile = false, $separador = '') {
	$string = str_replace([
		'/'
	], '-', $string);

	$string = str_replace([
		'®'
	], '', $string);

	$string = str_replace([
		'&'
	], [
		'e'
	], $string);

	$string = Str::slug($string);
	
	if ($tofile) {
		$string = preg_replace("([^(\d\w)])", '_', $string);
	} else {
		$string = preg_replace("([^\d\w]+)", $separador, $string);
		$string = trim(strtolower($string), $separador);
	}
	if ($separador != '-') {
		$string = preg_replace("([/-])", '_', $string);
	}
	return $string;
}

function toSeo($string) {
	return toId($string, false, '-');
}

function toSlug($string) {
	return toId($string, false, '-');
}

/**
 * Joins the array into a string. The difference from implode() is that jp7_implode() discards empty values.
 * 
 * @param string $separator
 * @param string $string
 * @param bool $useTrim If set the function will trim() each part of the string. Defaults to <tt>TRUE</tt>.
 * @return string
 */
function jp7_implode($separator, $array, $useTrim = true) {
	return implode($separator, array_filter($array));
}

function jp7_normalize($string) {
	$table = array(
		'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a', 'ª' => 'a',
		'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
		'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', '&' => 'e',
		'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
		'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
		'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
		'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o', 'º' => 'o',
		'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
		'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
		'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
		'ç' => 'c',
		'Ç' => 'C',
		'ñ' => 'n',
		'Ñ' => 'N'
    );
    return strtr($string, $table);
}

/**
 * Generates a SQL WHERE statement with REGEXP for 'decoding' the toSeo() function.
 *
 * @param string $field Field where the data will be searched, e.g. varchar_key.
 * @param string $str String to be formatted and searched.
 * @param string $regexp Optional REGEXP string, the default value is '[^\d\w]?'.
 * @return string Formatted SQL WHERE statement with a REGEXP.
 * @author Carlos Rodrigues
 * @version (2008/06/12) 
 */
function toSeoSearch($field, $str, $regexp = '[^[:alnum:]]*'){
	$sql_where = $regexp;
	for ($i = 0; $i < strlen($str); $i++){
		$char = $str[$i];
		$char = str_replace('a', '[aáàãâäª]', $char);
		$char =	str_replace('e', '[eéèêë&]', $char);
		$char = str_replace('i', '[iíìîï]', $char);
		$char =	str_replace('o', '[oóòõôöº]', $char);
		$char =	str_replace('u', '[uúùûü]', $char);
		$char =	str_replace('c', '[cç]', $char);
		$char =	str_replace('n', '[nñ]', $char);
		$sql_where .= $char . $regexp;
	}
	return "REPLACE(".$field.",' ','') REGEXP '^" . $sql_where . "$'";
}

function jp7_debug($msg) {
	throw new Exception($msg);
}


// OVERIDE LARAVEL FUNCTIONS
function snake_case($value, $delimiter = '_') {
	if (ctype_lower($value)) return $value;
	
	return strtolower(preg_replace('/(.)(?=[A-Z])/', '$1'.$delimiter, $value));
}


function link_to($url, $title = null, $attributes = array(), $secure = null, $entities = false)
{
	if (is_null($title) || $title === false) $title = $url;

	return '<a href="'.$url.'"'.HTML::attributes($attributes).'>'. ($entities ? HTML::entities($title) : $title) .'</a>';
}