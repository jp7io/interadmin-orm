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
			
			$cacheEnabled = ($config->cache && !$debugger->debugFilename && !$debugger->debugSql);
	
			// Front
			$frontDefault = array(
				'lifetime' => 86400 // 1 dia
			);

			$frontend = new Jp7_Cache_Output($frontOptions + $frontDefault);
			
			// Back
			$backDefault = array(
				'cache_dir' => './cache/'
			);
	
			$backend = new Zend_Cache_Backend_File($backOptions + $backDefault);
			 
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
	 public function start()
	 {
	 	// Criando ID pelo module/controller/action
	 	$params = Zend_Controller_Front::getInstance()->getRequest()->getParams();

	 	$id = toId(implode('_', array(
	 		$params['controller'],
	 		$params['action'], 
	 		$params['lang'], 
	 		$params['module'])
	 	));

	 	if (func_num_args()) {
	 		$args = func_get_args();
	 		if (count($args) == 1 && is_string($args[0])) {
	 			$id .= '_' . toId($args[0]);
	 		} else {
	 			$id .= '_' . md5(serialize(func_get_args()));
	 		}
	 	}

	 	$retorno = parent::start($id);
		
	 	if ($retorno) {
	 		exit;
	 	}
		
	 	self::$_started = true;
		
	 	return $retorno;
	 }

	 /**
	  * Retorna true se o cache tiver iniciado.
	  * 
	  * @return bool
	  */
	 public static function hasStarted()
	 {
	 	return self::$_started;
	 }
}