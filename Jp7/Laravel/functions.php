<?php

// INTERADMIN COMPATIBILITY FUNCTIONS 
function human_filesize($file, $decimals = 2) {
	$bytes = filesize($file);	
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

function jp7_debug($msg) {
	throw new Exception($msg);
}


// OVERIDE LARAVEL FUNCTIONS
function snake_case($value, $delimiter = '_') {
	if (ctype_lower($value)) return $value;
	
	return strtolower(preg_replace('/(.)(?=[A-Z])/', '$1'.$delimiter, $value));
}

function img_tag($img, $template = null, $options = array()) {
	if (is_object($img)) {
		$url = $img->getUrl();
	} else {
		$url = $img;
	}
	if ($url) {
		if ($template) {
			$url = preg_replace('~^assets/~', 'imagecache/' . $template . '/', $url);
		}
		return HTML::image($url, $img->text, $options);
	}
}

/*
function link_to($url, $title = null, $attributes = array(), $secure = null, $entities = false)
{
	if (is_null($title) || $title === false) $title = $url;

	return '<a href="'.$url.'"'.HTML::attributes($attributes).'>'. ($entities ? HTML::entities($title) : $title) .'</a>';
}
*/