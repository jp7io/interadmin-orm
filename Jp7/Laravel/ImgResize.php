<?php

namespace Jp7\Laravel;

use HtmlObject\Image;

class ImgResize {
	public static function tag($img, $template = null, $options = array()) {
		if (!$img) return;
		
		$url = self::url($img, $template);
		if ($template) {
			$options['class'] = trim(array_get($options, 'class') . ' '  . $template);
		}
		if (empty($options['title'])) {
			$options['title'] = is_object($img) ? $img->getText() : '';
		}
		return Image::create($url, $options['title'], $options);
	}
	
	public static function url($url, $template = null) {
		if (is_object($url)) {
			$url = $url->getUrl();
		}
		if ($template) {
			$url = self::resolveExternal($url);
			$url = str_replace('/assets/', '/imagecache/' . $template . '/', $url);
		}
		return Cdn::asset($url);
	}
	
	// External images are downloaded locally to resize them
	private static function resolveExternal($url) {
		if (empty(parse_url($url)['host'])) {
			return $url;
		}
		
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

}