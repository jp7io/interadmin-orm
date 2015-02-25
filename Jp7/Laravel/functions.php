<?php

// INTERADMIN COMPATIBILITY FUNCTIONS 
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

function jp7_debug($msg) {
	throw new Exception($msg);
}

// OVERRIDE LARAVEL FUNCTIONS
function snake_case($value, $delimiter = '_') {
	if (ctype_lower($value)) return $value;
	
	return strtolower(preg_replace('/(.)(?=[A-Z])/', '$1'.$delimiter, $value));
}

function img_tag($img, $template = null, $options = array()) {
	if (is_object($img)) {
		if ($img->getFilename() && !is_file($img->getFilename())) {
			$url = copy_production_file($img);
		} else {
			$url = $img->getUrl();
		}
		$alt = $img->text;
	} else {
		$url = $img;
		$alt = isset($options['alt']) ? $options['alt'] : '';
	}
	if ($url) {
		if ($template) {
			$url = str_replace('/assets/', '/imagecache/' . $template . '/', $url);
			$options['class'] = trim(@$options['class'] . ' '  . $template);
		}
		return \HtmlObject\Image::create(URL::to($url), $alt, $options);
	}
}

function collect($arr) {
	return new \Jp7\Interadmin\Collection($arr);
}

function copy_production_file($img) {
	$filename = $img->getFilename();
	// FIXME temporario local
	$remoteUrl = preg_replace('/^\.\.\/\.\./', 'http://static.ci.com.br', $img->url);
	
	if ($file = @file_get_contents($remoteUrl)) {
		$dir = dirname($filename);

		if (!is_dir($dir)) {
			mkdir($dir);
		}
		
		file_put_contents($filename, $file);
		
	 	return $img->getUrl();
	} else {
		return 'assets/placeholder.gif?' . $img->url;
	}
}

function km($object, $search = '.*') {
	$methods = get_class_methods($object);
	$methods = array_filter($methods, function ($a) use ($search) {
		return preg_match('/' . $search . '/i', $a);
	});
	kd($methods, KRUMO_EXPAND_ALL);
}
