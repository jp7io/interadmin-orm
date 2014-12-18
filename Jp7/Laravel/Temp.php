<?php

namespace Jp7\Laravel;
use Blade;

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
		
		Blade::setEscapedContentTags('{{', '}}');
    	Blade::setContentTags('{!!', '!!}');
	}
}