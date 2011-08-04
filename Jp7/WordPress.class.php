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
	
	public function getFirstUser($options = array()) {
		return reset($this->getUsers(array('limit' => 1) + $options));
	}
	
	public function getUsers($options = array()) {
		$options += array(
			'from' => self::$prefix . 'users',
			'fields' => '*'
		);
		return self::retrieveObjects($this->_db, $options, __CLASS__ . '_User');
	}
	
	/**
	 * Creates an User.
	 * @return Jp7_WordPress_User
	 */
	public function createUser($username, $password, $email = '') {
		$className = get_class($this) . '_User';
		
		$hasher = new Jp7_WordPress_PasswordHash();
		
		$user = new $className($this->_db, self::$prefix . 'users');
		$user->setAttributes(array(
		   'ID' => '',
		   'user_login' => $username,
		   'user_pass' => $hasher->HashPassword($password),
		   'user_nicename' => $username,
		   'user_email' => $email,
		   'user_url' => '',
		   'user_registered' => date('Y-m-d H:i:s'),
		   'user_activation_key' => '',
		   'user_status' => '0',
		   'display_name' => '',
		   'spam' => '0',
		   'deleted' => '0',
		));
		return $user;
	}
	
	public static function setPrefix($prefix) {
		self::$prefix = $prefix;
	}
	
	public static function getPrefix() {
		return self::$prefix;
	}
}