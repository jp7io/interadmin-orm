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
			'from' => self::$prefix . 'blogs',
			'fields' => '*'
		);
		
		return self::retrieveObjects($this->_db, $options, get_class($this) . '_Blog');
	}
	
	public function getFirstPost($options = array()) {
		return reset($this->getPosts(array('limit' => 1) + $options));
	}
	
	public function getPosts($options = array()) {
		$options += array(
			'from' => self::$prefix . 'posts',
			'fields' => '*'
		);
		return self::retrieveObjects($this->_db, $options, get_class($this) . '_Post');
	}
	
	public function getOptionByName($name, $options = array()) {
		$options['where'][] = "name = '" . $name . "'";
		return $this->getFirstOption($options);
	}
	
	public function getFirstOption($options = array()) {
		return reset($this->getOptions(array('limit' => 1) + $options));
	}
	
	public function getOptions($options = array()) {
		$options += array(
			'from' => self::$prefix . 'options',
			'fields' => '*'
		);
		return self::retrieveObjects($this->_db, $options, get_class($this) . '_Option');
	}
	
	public static function setPrefix($prefix) {
		self::$prefix = $prefix;
	}
	
	public static function getPrefix() {
		return self::$prefix;
	}
}