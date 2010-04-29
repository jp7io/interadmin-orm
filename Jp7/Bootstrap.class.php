<?php

class Jp7_Bootstrap {
	
	public static function run() {
		global $config;
		
		include_once APPLICATION_PATH . '/../interadmin/config.php';
		
		Zend_Registry::set('config', $config);
		Zend_Registry::set('session', new Zend_Session_Namespace());
		Zend_Registry::set('post', new Zend_Filter_Input(null, null, $_POST));
		Zend_Registry::set('get', new Zend_Filter_Input(null, null, $_GET));
		
		self::initDataBase();
		self::initFrontController();
		self::initLanguage();
		self::initLayout();
		self::preDispatch();
		self::dispatch();
		self::postDispatch();		
	}
	
	public static function initDataBase() {
		global $db;
		$config = Zend_Registry::get('config');
		
		/* DB Connection */
		// TODO Corrigir, utilizando OOP
		if (!$config->db->type) {
			$config->db->type = 'mysql';
		}
		include jp7_path_find('../inc/3thparty/adodb/adodb.inc.php');
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$ADODB_LANG = 'pt-br';
		$dsn = "{$config->db->type}://{$config->db->user}:{$config->db->pass}@{$config->db->host}/{$config->db->name}";
		$db = ADONewConnection($dsn);
		/* /DB Connection */
	}
	
	public static function initFrontController() {
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->setControllerDirectory(APPLICATION_PATH . '/controllers');
		
		$frontController->addModuleDirectory(APPLICATION_PATH . '/modules');
		
		$frontController->throwExceptions(true); // @todo Usar config para determinar ambiente
		$frontController->setParam('env', 'development');
	}
	
	public static function initLanguage() {
		// TODO Retirar língua das variáveis globais
		global $lang;
		
		$config = Zend_Registry::get('config');
		$frontController = Zend_Controller_Front::getInstance();
		$request = new Zend_Controller_Request_Http();
		foreach ($config->langs as $language) {
			if ($language->lang == $config->lang_default) {
				continue;
			}
			// http://localhost/CLIENTE/en/
			if (preg_match('~^' . $request->getBaseUrl() . '/' . $language->lang . '(/|$)~', $request->getRequestUri())) {
				$lang = new Jp7_Locale($language->lang);
				$request->setBaseUrl($request->getBaseUrl() . '/' . $language->lang);
				$frontController->setRequest($request);
				break;
			}
		}
		if (!$lang) {
			$lang = new Jp7_Locale($config->lang_default);
		}
		
		$config->lang = $config->langs[$lang->lang];
		Zend_Registry::set('lang', $lang);
	}	
	
	public static function initLayout() {
		Zend_Layout::startMvc(APPLICATION_PATH . '/layouts/scripts');
		$view = Zend_Layout::getMvcInstance()->getView();
		$view->doctype('XHTML1_STRICT');
	}
	
	public static function preDispatch() {
		$config = Zend_Registry::get('config');
		$lang = Zend_Registry::get('lang');
				
		$metas = array(
			'language' => $lang->lang,
			'description' => $config->lang->description,
			'keywords' => $config->lang->keywords,
			'copyright' => date('Y') . ' ' . $config->copyright,
			'robots' => 'all',
			'author' => 'JP7 - http://jp7.com.br',
			'generator' => 'JP7 InterAdmin'
		);
		
		$scripts = array(
			'/_default/js/interdyn.js',
			'/_default/js/interdyn_checkflash.js',
			'/_default/js/interdyn_form.js',
			'/_default/js/interdyn_form_lang_' . $lang->lang . '.js',
			'/_default/js/swfobject.js',
			'/_default/js/jquery/jquery-1.3.2.min.js',
			'/_default/js/interdyn_menu.js',
			'js/functions.js'
		);
		
		$links = array(
			'/_default/css/7_w3c.css',
			'css/main.css'
		);
				
		Zend_Registry::set('metas', $metas);
		Zend_Registry::set('scripts', $scripts);
		Zend_Registry::set('links', $links);
	}
	
	public static function dispatch() {
		Zend_Controller_Front::getInstance()->dispatch();
	}
	
	public static function postDispatch() {
		// Cache
		if (Jp7_Cache_Output::hasStarted()) {
			 Jp7_Cache_Output::getInstance()->end();
		}
	}
	
}
