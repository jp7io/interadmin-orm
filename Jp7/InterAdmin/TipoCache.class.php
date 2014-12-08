<?php

namespace Jp7\Interadmin;

class TipoCache {
	
	protected $key;
	protected static $cache = array();
	
	public static function getInstance() {
		$key = implode('{;}', func_get_args());
		
		return new self($key);
	}
	
	public function __construct($key) {
		$this->key = $key;	
	}
	
	public function get($var) {
		return self::$cache[$this->key][$var];
	}
		
	public function set($var, $value) {
		self::$cache[$this->key][$var] = $value;
	}
}