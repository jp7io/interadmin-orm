<?php

class Jp7_Cache_Data {
	
	protected $id;
	protected $options;
	protected static $_cachedir = './cache/';
	protected static $_logdir = './interadmin/';
	
	/**
	 * 
	 * @param string 	$id
	 * @param int 	$lifetime 	Lifetime in seconds. Cached files with $lifetime == 0 expire using getLogTime().
	 * @return 
	 */
	public function __construct($id, $options = array()) {
		if (!is_scalar($id)) {
			$id = md5(serialize($id));
		}
		$this->id = $id; 
		$this->options = $options + array(
			'lifetime' => 0,
			'cache_dir' => self::$_cachedir
		);
	}
	
	public function load() {
		if (is_file($this->getFilename()) && $this->_checkLog()) {
			return unserialize(file_get_contents($this->getFilename()));
		}
	}
	
	public function save($data) {
		file_put_contents($this->getFilename(), serialize($data));
	}
	
	public function getFilename() {
		return $this->options['cache_dir'] . '_' . $this->id . '.cache';
	}
	
	/**
	 * Verifica se o log do InterAdmin foi alterado. E limpa o cache se necessário.
	 * 
	 * @return bool 
	 */
	protected function _checkLog() {
		$lifetime = $this->options['lifetime'];
		
		$cache_time = @filemtime($this->getFilename());
		if ($lifetime) {
			if ($cache_time && ($cache_time > (time() - $lifetime))) {
				return true; // Está válido
			}
		} else {
			// Verificação do log
			$log_time = @filemtime(self::$_logdir . 'interadmin.log');
			// Outro dia é atualizado o cache
			if (($log_time < $cache_time) && date('d', $cache_time) == date('d')) {
				return true; // TRUE = Cache is ok, no need to refresh
			}
		}
		// FALSE = Atualizar cache
		return false;
	}
}