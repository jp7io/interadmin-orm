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
		    $pattern = '/(?<!\w)(\s*)@include(\s*\((.*)\.)/';
		    return preg_replace($pattern, '$1@include$2_', $view);
		});
		
		Blade::extend(function($view, $compiler) {
			 $pattern = $compiler->createMatcher('ia');
			 
			 return preg_replace($pattern, '$1<?php echo interadmin_data$2; ?>', $view);
		});
		
		Blade::setEscapedContentTags('{{', '}}');
    	Blade::setContentTags('{!!', '!!}');
	}
	
	public static function extendWhoops() {
		if (Request::ajax() || PHP_SAPI === 'cli') return;
		if (App::bound("whoops")) {
			$whoops = App::make("whoops");

			$whoops->pushHandler(function($exception, $exceptionInspector, $runInstance) {
				if (!Input::get('whoopsAll')) {
					// Get the collection of stack frames for the current exception:
					$frames = $exceptionInspector->getFrames();
					
					$originalFrames = $frames->getArray(); 
					// Filter existing frames so we only keep the ones inside the app/ folder
					$frames->filter(function($frame) {
						$filePath = $frame->getFile();

						// Match any file path containing /app/...
						return preg_match("/\/app\/.+/i", $filePath);
					});
					if (!count($frames)) {
						$frames->prependFrames($originalFrames);
					}					
				}
				
				$query = $_GET;
				unset($query['whoopsAll']);
				?>
				<div style="position: absolute;z-index: 999;left: 435px;top:0;border-radius: 5px;">
					<a href="?<?= http_build_query($query) ?>" style="color:white;background:#666; padding: 5px;display:inline-block;border-right: 1px solid black">app</a><!--
					--><a href="?<?= http_build_query($query + array('whoopsAll' => true)) ?>" style="color:white;background:#666; padding: 5px;display:inline-block;">all</a>
				</div>
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