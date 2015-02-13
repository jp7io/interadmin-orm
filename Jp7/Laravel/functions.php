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
		if (!is_file($img->getFilename())) {
			// FIXME temporario local
			$remoteUrl = preg_replace('/^\.\.\/\.\./', 'http://static.ci.com.br', $img->url);
			
			if ($file = @file_get_contents($remoteUrl)) {
				$dir = storage_path() . '/upload/' . basename(dirname($img->getFilename())); # ex.: app/storage/upload/cursos
				
				if (!is_dir($dir)) {
					mkdir($dir);
				}
				
				file_put_contents($dir . '/' . basename($img->getFilename()), $file);
				
				$url = $img->getUrl();
			} else {
				$url = 'assets/placeholder.gif?' . $img->url;
			}
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
			$url = preg_replace('~^assets/~', 'imagecache/' . $template . '/', $url);
		}
		return \HtmlObject\Image::create(URL::to($url), $alt, $options);
	}
}

function km($object, $search = '.*') {
	$methods = get_class_methods($object);
	$methods = array_filter($methods, function ($a) use ($search) {
		return preg_match('/' . $search . '/i', $a);
	});
	kd($methods);
}
/*
function link_to($url, $title = null, $attributes = array(), $secure = null, $entities = false)
{
	if (is_null($title) || $title === false) $title = $url;

	return '<a href="'.$url.'"'.HTML::attributes($attributes).'>'. ($entities ? HTML::entities($title) : $title) .'</a>';
}
*/