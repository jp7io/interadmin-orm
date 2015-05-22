<?php

namespace Jp7\Laravel;

class Filters {
	public static function qaAuth($request) {
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		if (starts_with($host, 'qa.') || starts_with($host, 'alt.')) {
			$config = \InterSite::config();
			if (empty($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $config->name_id || $_SERVER['PHP_AUTH_PW'] != $config->name_id) {
				header('WWW-Authenticate: Basic realm="' . $config->name . '"');
				header('HTTP/1.0 401 Unauthorized');
				echo '401 Unauthorized';
				exit;
			}
		}
	}
	
	public static function cdnCors($request, $response) {
		if ($request->segment(1) === 'assets') {
			// Asset Pipeline doesn't set this
			$response->headers->set('Cache-Control', 'public, max-age=604800');
			$response->headers->set('Expires', \Date::make('+1 month')->format('r'));
			// CDN - Font CORS
			$response->headers->set('Access-Control-Allow-Origin', 'http://' . \InterSite::config()->server->host);
		}
	}
}