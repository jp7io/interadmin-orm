<?php

namespace Jp7\Laravel;

class Cdn {
	public static function asset($url) {
		return self::replace(asset($url));
	}
	
	public static function css() {
		return self::assetTag('css');
	}
	
	public static function js() {
		return self::assetTag('js');
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
	
	private static function assetTag($ext) {
		ob_start();
		if ($ext == 'css') {
			stylesheet_link_tag();
		} elseif ($ext == 'js') {
			javascript_include_tag();	
		}
		$html = ob_get_clean();
		
		$html = str_replace(
			'.' . $ext, 
			'.' . $ext . '?v=' . self::getVersion(), 
			$html
		);
		
		return self::replace($html);
	}
	
	private static function getVersion() {
		// Using timestamp of the .git directory as version number
		return filemtime(base_path('.git'));
	}
}
