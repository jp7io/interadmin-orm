<?php

class Jp7_WordPress extends Jp7_WordPress_BaseAbstract {	protected static $prefix = 'wp_';
	
	public function __construct($dbData) {
		$dsn = jp7_formatDsn($dbData);
		$this->_db = ADONewConnection($dsn);
	}
	
	public function getFirstBlog($options = array()) {
		return reset($this->getBlogs(array('limit' => 1) + $options));
	}
	
	public function getBlogs($options = array()) {
		$options += array(
			'from' => self::$prefix . 'blogs'
		);
		return self::retrieveObjects($this->_db, $options, __CLASS__ . '_Blog');
	}
	
	public function getFirstPost($options = array()) {
		return reset($this->getPosts(array('limit' => 1) + $options));
	}
	
	public function getPosts($options = array()) {
		$options += array(
			'from' => self::$prefix . 'posts'
		);
		return self::retrieveObjects($this->_db, $options, __CLASS__ . '_Post');
	}
	
	public static function setPrefix($prefix) {
		self::$prefix = $prefix;
	}
	
	public static function getPrefix() {
		return self::$prefix;
	}
}