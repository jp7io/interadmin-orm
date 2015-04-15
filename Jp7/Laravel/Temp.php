<?php

namespace Jp7\Laravel;
use Blade, App, Input, Request, Cache;

class Temp {
	
	/*
	public static function initDataBase() {
		global $db;
		$config = \InterSite::config();
	
		/* DB Connection *-/
		if (!$config->db) {
			throw new Exception('No database. Make sure you call $config->start() on config.php.');
		}
		if (!$config->db->type) {
			$config->db->type = 'mysql';
		}
		if (!function_exists('ADONewConnection')) {
			include '../inc/3thparty/adodb/adodb.inc.php';
		}
		$dsn = jp7_formatDsn($config->db);
		$db = ADONewConnection($dsn);
	
		if (!$db) {
			$config->db->pass = '{pass}';
			throw new Exception('Unable to connect to the database ' . jp7_formatDsn($config->db));
		}
		/* /DB Connection *-/
	}
	*/
	
	public static function extendBlade() {
		Blade::extend(function($view, $compiler) {
			// @include with partials/_partial instead of partials/partial			
		    $pattern = '/(?<!\w)(\s*)@include\(([^,\)]+)/';
		    return preg_replace($pattern, '$1@include(\Jp7\Laravel\Temp::inc($2)', $view);
		});
		
		Blade::extend(function($view, $compiler) {
			 $pattern = $compiler->createMatcher('ia');
			 
			 return preg_replace($pattern, '$1<?php echo interadmin_data$2; ?>', $view);
		});
		
		Blade::setEscapedContentTags('{{', '}}');
    	Blade::setContentTags('{!!', '!!}');
	}
	
	public static function inc($file) {
		$parts = explode('.', $file);
		$parts[] = '_' . array_pop($parts);
		return implode('.', $parts);
	}
	
	public static function extendWhoops() {
		if (Request::ajax() || PHP_SAPI === 'cli') return;
		if (App::bound("whoops")) {
			$whoops = App::make("whoops");
			
			$whoops->pushHandler(function($exception, $exceptionInspector, $runInstance) {
				?>
				<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
				<script>
				setTimeout(function() {
					$('.frame:contains("app"):first()').click();
				}, 200);
				</script>
				<?php
			});			
		}
	}
	
	public static function clearCache() {
		if (Request::server('HTTP_CACHE_CONTROL') === 'no-cache' || PHP_SAPI === 'cli') {
			Cache::forget('Interadmin.routes');
			Cache::forget('Interadmin.classMap');
		}
	}
}