<?php
class Jp7_Session_File { 
	static $save_path;
	static $maxlifetime = 43200; // 12 Horas
	static $save_handler;
	
	protected function __construct() {
		// Private
	}
	
	public static function open($sess_path, $sess_name) {
	    static::$save_path = ($sess_path ? $sess_path : '/tmp') . '/';
	    return true;
	}
	
	public static function close() {
	    return true;
	}
	
	public static function read($sess_id) {
		global $c_jp7;
		if ($c_jp7 && !is_dir(static::$save_path)) {
			if (!mkdir(static::$save_path)) {
				die(jp7_debug('Unable to create directory for session: ' . self::$save_path));
			}
		}
		
		$sess_file = static::$save_path . 'sess_' . $sess_id;
		if (is_file($sess_file)) {
  			return file_get_contents($sess_file);
		}
	}
	
	public static function write($sess_id, $data) {
		$sess_file = static::$save_path . 'sess_' . $sess_id;
		return @file_put_contents($sess_file, $data);
	}
	
	public static function destroy($sess_id) {
		$sess_file = static::$save_path . 'sess_' . $sess_id;
  		return @unlink($sess_file);
	}
	
	public static function gc() {
		foreach (glob(static::$save_path . 'sess_*') as $filename) {
			if (filemtime($filename) + self::$maxlifetime < time()) {
				@unlink($filename);
			}
		}
	    return true;
	}
	
	public static function register() {
		$static_class = get_called_class();
		static::$save_handler = $static_class;
		return session_set_save_handler(
			array($static_class, 'open'),
			array($static_class, 'close'),
			array($static_class, 'read'),
			array($static_class, 'write'),
			array($static_class, 'destroy'),
			array($static_class, 'gc')
		);
	}
	
	public static function getSaveHandler() {
		return static::$save_handler;
	}
}