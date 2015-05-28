<?php

namespace Jp7\Laravel;

class Cdn {
	public static function asset($url) {
		return self::replace(asset($url));
	}
	
	public static function css($url) {
		return '<link href="' . self::asset($url) . '?v=' . self::getVersion() . '"  rel="stylesheet" type="text/css">';
	}
	
	public static function js($url, $attrs = "") {
		return '<script src="' . self::asset($url) . '?v=' . self::getVersion() . '" ' . $attrs . '></script>';
	}
	
	private static function replace($url) {
		$config = \InterSite::config();
		if (!empty($config->cdn_domain)) {
			$url = str_replace(
				$config->url,
				'http://' . $config->cdn_domain . '/', 
				$url
			);
		}
		return $url;
	}
		
	private static function getVersion() {
		// Using timestamp of the .git directory as version number
		return filemtime(base_path('.git'));
	}
}
