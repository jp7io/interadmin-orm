<?php

/**
 * Extende o Zend_Cache_Frontend_Output com Zend_Cache_Backend_File.
 * 
 * @category Jp7
 * @package Jp7_Cache
 */
 
 class Jp7_Cache_Output extends Zend_Cache_Frontend_Output
 {
	 protected static $_instance = null;
	 protected static $_started = false;
	 protected static $_enabled = false;
	 protected static $_cachedir = './cache/';
	 protected static $_logdir = './interadmin/';
	 protected static $_delay = 0;
	 
	 /**
	  * Retorna uma instância configurada do Jp7_Cache_Page.
	  * 
	  * @todo Ver como irá funcionar com o Preview do InterAdmin 
	  * @param array $frontOptions
	  * @param array $backOptions
	  * @return Jp7_Cache_Output
	  */
	public static function getInstance(array $frontOptions = array(), array $backOptions = array())
	{
		if (!self::$_instance) {
			global $debugger;
			$config = Zend_Registry::get('config');
			
			if ($config->cache && !$debugger->debugFilename && !$debugger->debugSql) {
				self::$_enabled = true;
			}
			
			self::$_delay = intval($config->cache_delay);
			
			$frontDefault = array(
				'lifetime' => 86400 // 1 dia
			);
			$backDefault = array(
				'cache_dir' => self::$_cachedir,
                'cache_file_umask' => 0777,
				'file_name_prefix' => 'zf'
			);

			self::$_cachedir = $backDefault['cache_dir'];
			
			$frontend = new Jp7_Cache_Output($frontOptions + $frontDefault);

			if (is_dir(self::$_cachedir)) {
				$backend = new Zend_Cache_Backend_File($backOptions + $backDefault);
			} else {
				$backend = new Zend_Cache_Backend_Test();
				self::$_enabled = false;
			}

			$frontend->setBackend($backend);		
						 
			self::$_instance = $frontend;
		}
		return self::$_instance;
	 }
	 
	 /**
	  * Inicia o cache.
	  * 
	  * @param mixed $_ Valores que se alteram na página e que portanto geram outra versão de cache.
	  * @see Zend/Cache/Frontend/Zend_Cache_Frontend_Page#start()
	  */	 
	 public function start(/* Dynamic args*/)
	 {
	 	if (!self::$_enabled) {
	 		return false;
	 	}

	 	// Gera o id do cache
	 	$id = $this->_makeId(func_get_args());

	 	// Verifica se o log foi alterado
	 	$logTime = $this->_checkLog();

	 	// Desabilita o cache individual da página pelo $_GET
	 	if (isset($_GET['nocache_force'])) {
	 		$this->remove($id);
	 	}
	 	
	 	$retorno = parent::start($id);

	 	if ($retorno) {
	 		$this->_showDebug($id, $logTime);
	 		exit;
	 	}

	 	self::$_started = true;

	 	return $retorno;
	}
	
	/**
	 * Cancela o cache.
	 * 
	 * @return void
	 */
	public function cancel() {
		self::$_enabled = false;
		ob_get_clean();	
	}

	/**
	 * Retorna true se o cache tiver iniciado.
	 * 
	 * @return bool
	 */
	public static function hasStarted() {
		return self::$_started;
	}
	 
	 
	 /**
	  * Cria um ID na forma: controller_action_lang_module_SUFIXO
	  * 
	  * @param mixed $data Gera um hash e adiciona como sufixo ao ID.
	  * @return string ID gerado.
	  */
	 protected function _makeId($data)
	 {
	 	$params = Zend_Controller_Front::getInstance()->getRequest()->getParams();

	 	$id = toId(implode('_', array(
	 		$params['controller'],
	 		$params['action'], 
	 		$params['lang'], 
	 		$params['module'])
	 	));
		
	 	if ($data) {
			if (count($data) == 1 && is_string($data[0]) && strlen($data[0]) < 100) {
		 		$id .= '_' . toId($data[0]);
		 	} else {
		 		$id .= '_' . md5(serialize($data));
		 	}
	 	}
		
	 	return $id;
	}

	/**
	 * Verifica se o log do InterAdmin foi alterado. E limpa o cache se necessário.
	 * 
	 * @return int Retorna a data de alteração do arquivo de log. 
	 */
	protected function _checkLog()
	{
		$lastLogFilename = 'logcheck.log';
		$lastLogTime = intval(@file_get_contents(self::$_cachedir . $lastLogFilename));

		// Verificação do log
	 	$logTime = @filemtime(self::$_logdir . 'interadmin.log');
		
	 	// Grava último log, se necessário
	 	if ($logTime != $lastLogTime && $logTime + self::$_delay < time()) {
	 		$this->clean();
	 		file_put_contents(self::$_cachedir . $lastLogFilename, $logTime);
	 	}
	 	
	 	return $logTime;
	}
	
	/**
	 * Exibe informações para debug em um overlay no topo da página.
	 * 
	 * @param string $id ID do cache.
	 * @param int $logTime Timestamp da última alteração do log.
	 */
	protected function _showDebug($id, $logTime)
	{
		global $c_jp7;
		
		if ($c_jp7) {
			$metas = $this->getBackend()->getMetadatas($id);
			
			$css = 'position:absolute;border:1px solid black;border-top:0px;font-weight:bold;top:0px;padding:5px;background:#FFCC00;filter:alpha(opacity=50);opacity: .5;z-index:1000;cursor:pointer;';
			$title = array(
				'# Cache: ',
					'  ' . self::$_cachedir . $id,
					'  ' . date('d/m/Y H:i:s', $metas['mtime']), 
				'# Log: ',
					'  ' . self::$_logdir . 'interadmin.log',
					'  ' . date('d/m/Y H:i:s', $logTime),
				'# Hora do servidor: ' . date('d/m/Y H:i:s', time()),
				'# Delay para limpeza: ' . self::$_delay . ' segundos',
			);

			$title = implode('&#013;', $title);
			
			$urlNoCache = preg_replace('/^([^&]*)([&]?)([^&]*)$/', '$1?$3$2nocache_force=true', str_replace('?', '&', $_SERVER['REQUEST_URI']));
            $event = 'onclick="if (confirm(\'Deseja atualizar o cache desta página?\')) window.location = \'' . $urlNoCache . '\'"';
	 		
	 		echo '<div style="' . $css . 'left:0px;" title="' . $title . '" ' . $event . '>CACHE</div>';
	 		echo '<div style="' . $css . 'right:0px;" title="' . $title . '" ' . $event . '>CACHE</div>';
		}
	}
	
}