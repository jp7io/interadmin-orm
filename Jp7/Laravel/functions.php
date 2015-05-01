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

function img_tag($img, $template = null, $options = array()) {
	if (is_object($img)) {
		$url = $img->getUrl();
		$alt = $img->getText();
	} else {
		$url = $img;
		$alt = array_get($options, 'alt');
	}
	if ($url) {
		if ($template) {
			$parsed = parse_url($url);
			if (!empty($parsed['host'])) {
				$url = copy_external_image($url);
			}
			
			$url = str_replace('/assets/', '/imagecache/' . $template . '/', $url);
			$options['class'] = trim(array_get($options, 'class') . ' '  . $template);
		}
		return \HtmlObject\Image::create(URL::to($url), $alt, $options);
	}
}

function img_resize_url($url, $template = null) {
	if (is_object($url)) {
		$url = $url->getUrl();
	}
	if ($template) {
		$url = str_replace('/assets/', '/imagecache/' . $template . '/', $url);
	}
	return $url;	
}

function collect($arr) {
	return new \Jp7\Interadmin\Collection($arr);
}

function interadmin_data($record) {
	if ($record instanceof InterAdmin) {
		echo ' data-ia="' . $record->id . ':' . $record->id_tipo . '"';
	}
}

function copy_external_image($url) {
	$local = to_slug(dirname($url)) . '_' . basename($url);
	$dir = storage_path('upload/_external');
	
	if (!is_file($dir . '/' . $local)) {
		if ($file = @file_get_contents($url)) {
			if (!is_dir($dir)) {
				mkdir($dir);
			}
			file_put_contents($dir . '/' . $local, $file);
		}
	}
	return '/assets/_external/' . $local;
}

function versioned_asset($extension) {
	ob_start();
	if ($extension == 'css') {
		stylesheet_link_tag();
	} elseif ($extension == 'js') {
		javascript_include_tag();	
	}
	$html = ob_get_clean();	
	$version = filemtime(base_path('.git'));
	
	return str_replace('.' . $extension, '.' . $extension . '?v=' . $version, $html);
}

/*
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
		return URL::to('assets/placeholder.gif?' . $img->url);
	}
}
*/
function km($object, $search = '.*') {
	$methods = get_class_methods($object);
	$methods = array_filter($methods, function ($a) use ($search) {
		return preg_match('/' . $search . '/i', $a);
	});
	kd($methods, KRUMO_EXPAND_ALL);
}
