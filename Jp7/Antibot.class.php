<?php

class Jp7_Antibot {
	protected $redirect;
	protected $captcha_url = '/_default/site/_templates/antibot.php';
	protected $path;
	protected $ip;
	protected $attempts_before_captcha = 3;
	protected $seconds_before_reset = 300; // 5 minutos
	
	protected function __construct($options = array()) {
		$this->redirect = $options['redirect'] ?: $_SERVER['REQUEST_URI'];
		if ($options['captcha_url']) {
			$this->captcha_url = $options['captcha_url'];
		}
		$this->path = jp7_path(sys_get_temp_dir()) . 'antibot/';
		if (!is_dir($this->path)) {
			mkdir($this->path, 0777, true);
			if (!is_dir($this->path)) {
				throw new Exception('Unable to create Antibot directory: ' . $this->path);
			}
		}
		
		// filtro no ip
		$this->filename = preg_replace('/\W+/', '_', $_SERVER['REMOTE_ADDR']);
	}
	
	public static function getInstance($options = array()) {
		return new self($options);
	}
	
	public function check() {
		$data = $this->getData();
		if ($data->count > $this->attempts_before_captcha) {
			$data->redirect = $this->redirect;
			$this->saveData($data);
			header('Location: ' . $this->captcha_url);
			exit;
		}
	}
	
	public function increment() {
		$data = $this->getData();
		$data->count++;
		$this->saveData($data);
	}
		
	public function allow() {
		$data = $this->getData();
		header('Location: ' . $data->redirect);
		$this->saveData('');
		exit;
	}
		
	public function getData() {
		if (is_file($this->path . $this->filename)) {
			if ($content = file_get_contents($this->path . $this->filename)) {
				$object = unserialize($content);
				if ($object->first_attempt > time() - $this->seconds_before_reset) {
					return $object;
				}
			}
		}
		return (object) array('count' => 0, 'first_attempt' => time());
	}
	
	public function saveData($data) {
		file_put_contents($this->path . $this->filename, serialize($data));
	}
	
	public function secret() {
		if (!is_array($_SESSION['_antispoof'])) {
			$_SESSION['_antispoof'] = array();
		}
		$secret = uniqid();
		$_SESSION['_antispoof'][] = $secret;
		return $secret;
	}
	
	public function checkSecret($secret) {
		if ($_SESSION['_antispoof'] && in_array($secret, $_SESSION['_antispoof'])) {
			array_delete($_SESSION['_antispoof'], $secret);
			return true;
		} else {
			return false;
		}
	}
}