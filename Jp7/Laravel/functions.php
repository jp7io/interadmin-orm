<?php

function human_filesize($file, $decimals = 2) {
	$bytes = @filesize($file);	
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function to_slug($string, $separator = '-') {
	$string = str_replace('/', '-', $string);
	$string = str_replace('®', '', $string);
	$string = str_replace('&', 'e', $string);
	
	return Str::slug($string, $separator);
}

function interadmin_data($record) {
	if ($record instanceof InterAdmin) {
		echo ' data-ia="' . $record->id . ':' . $record->id_tipo . '"';
	}
}

function error_controller($action) {
	$request = Request::create('/error/' . $action, 'GET', array());
	return Route::dispatch($request);
}

function img_tag($img, $template = null, $options = array()) {
	return ImgResize::tag($img, $template, $options);
}

function _try($object) {
	return $object ?: new \Jp7\NullObject;	
}

function memoize(Closure $closure) {
	static $memoized = [];
	
	list(, $caller) = debug_backtrace(false, 2);
	
	$key = $caller['class'] . ':' . $caller['function'];
	
	foreach ($caller['args'] as $arg) {
		$key .= ",\0" . (is_array($arg) ? serialize($arg) : (string) $arg);
	}
	
	$cache = &$memoized[$key];
	
	if (!isset($cache)) {
		$cache = call_user_func_array($closure, $caller['args']);
	}
	return $cache;
}

function km($object, $search = '.*') {
	$methods = get_class_methods($object);
	$methods = array_filter($methods, function ($a) use ($search) {
		return preg_match('/' . $search . '/i', $a);
	});
	
	kd(['methods' => $methods, 'object' => $object], KRUMO_EXPAND_ALL);
}

// INTERADMIN COMPATIBILITY FUNCTIONS 
function jp7_debug($msg) {
	throw new Exception($msg);
}

// Laravel 5 functions
function collect($arr) {
	return new \Jp7\Interadmin\Collection($arr);
}

// OVERRIDE LARAVEL FUNCTIONS
function snake_case($value, $delimiter = '_') {
	if (ctype_lower($value)) return $value;
	
	return strtolower(preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $value));
}
