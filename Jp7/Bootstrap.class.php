<?php

class Jp7_Bootstrap {
	
	public static function run() {
		//$GLOBALS['debugger']->startTime();
				
		global $debugger;
		$debugger->setExceptionsEnabled(true);
		$debugger->setSafePoint(true);
		
		Zend_Registry::set('session', new Zend_Session_Namespace());
		Zend_Registry::set('post', new Zend_Filter_Input(null, null, $_POST));
		Zend_Registry::set('get', new Zend_Filter_Input(null, null, $_GET));
		
		self::initConfig();
		self::initAdminBar();
		self::initDataBase();
		self::initFrontController();
		self::initLanguage();
		self::initLayout();
		self::preDispatch();
		self::dispatch();
		self::postDispatch();		
	}
	
	public static function initConfig() {
		global $config;
		include_once jp7_absolute_path(APPLICATION_PATH . '/../interadmin/config.php');
		Zend_Registry::set('config', $config);
		
		// Iniciando $s_session. Compatibilidade
		if (is_null($GLOBALS['s_session'])) {
			if (!is_array($_SESSION[$config->name_id]['interadmin'])) {
				$_SESSION[$config->name_id]['interadmin'] = array();
			}
			$GLOBALS['s_session'] = &$_SESSION[$config->name_id]['interadmin'];
			$GLOBALS['s_user'] = &$GLOBALS['s_session']['user'];
		}
		
		// Classes padrão
		$prefix = ucfirst($config->name_id);
		
		if (InterAdminTipo::getDefaultClass() == 'InterAdminTipo' && class_exists($prefix . '_InterAdminTipo')) {
			InterAdminTipo::setDefaultClass($prefix . '_InterAdminTipo');
		}
		if (Jp7_Controller_Dispatcher::getDefaultParentClass() == 'Jp7_Controller_Action' && class_exists($prefix . '_Controller_Action')) {
        	Jp7_Controller_Dispatcher::setDefaultParentClass($prefix . '_Controller_Action');
		}
		
		$config->build = interadmin_get_version($config->name_id, '{build}');
	}
	
	public static function initAdminBar() {
		global $c_jp7;
		$config = Zend_Registry::get('config');
		
		// Dados da admin bar - Somente se estiver logado
		if ($GLOBALS['s_user'] && $GLOBALS['s_user']['escrita']) {
			if (isset($_GET['ia_hook'])) {
				$GLOBALS['s_session']['no_hook'] = !$_GET['ia_hook'];
			}
			if (isset($_GET['ia_preview'])) {
				$GLOBALS['s_session']['preview'] = (bool) $_GET['ia_preview'];
			}			
			
			$admin_bar_data = array(
				'server' => $config->server->interadmin_remote ? reset($config->server->interadmin_remote) : $_SERVER['HTTP_HOST'],
				'cliente' => $config->name_id,
				'preview' => (bool) $GLOBALS['s_session']['preview'],
				'no_hook' => (bool) $GLOBALS['s_session']['no_hook'],
				'c_jp7' => $c_jp7
			);
			
			setcookie('ia_admin_bar', implode(';', $admin_bar_data), 0, '/');
		} elseif ($_COOKIE['ia_admin_bar']) {
			setcookie('ia_admin_bar', '', 1, '/');
		}	
	}
	
	public static function initDataBase() {
		global $db;
		$config = Zend_Registry::get('config');
		
		/* DB Connection */
		// TODO Corrigir, utilizando OOP
		if (!$config->db->type) {
			$config->db->type = 'mysql';
		}
		if (!function_exists('ADONewConnection')) {
			include ROOT_PATH . '/inc/3thparty/adodb/adodb.inc.php';
		}
		$dsn = jp7_formatDsn($config->db);
		$db = ADONewConnection($dsn);
		
		if (!$db) {
			$config->db->pass = '{pass}';
			throw new Exception('Unable to connect to the database ' . jp7_formatDsn($config->db));
		}
		/* /DB Connection */
	}
	
	public static function initFrontController() {
		$frontController = Zend_Controller_Front::getInstance();
		// Alterando o dispatcher para abrir o template caso o Controller não exista
		$frontController->setDispatcher(new Jp7_Controller_Dispatcher());
		// Alterando o router para que $this->url() funcione corretamente na View
		$frontController->setRouter(new Jp7_Controller_Router());
		$frontController->setControllerDirectory(APPLICATION_PATH . '/controllers');
		//$frontController->setControllerDirectory(ROOT_PATH . '/institucional/application/modules/default/controllers');
		$frontController->addControllerDirectory(ROOT_PATH . '/classes/Jp7', 'jp7');
		
		if (is_dir(APPLICATION_PATH . '/modules')) {
			$frontController->addModuleDirectory(APPLICATION_PATH . '/modules');
		}
		$frontController->throwExceptions(false);
		// @todo Usar config para determinar ambiente
		$frontController->setParam('env', 'development');
	}
	
	public static function initLanguage() {
		/**
		 * É utilizada pela InterAdmin para gerar URL com SEO, que é o padrão no MVC.
		 * @var bool
		 */
		global $seo;
		$seo = true;
		// TODO Retirada de $lang das variáveis globais, alterando a InterAdmin
		global $lang;
		
		$config = Zend_Registry::get('config');
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->setBaseUrl(jp7_path('/' . $config->server->path));
		
		// Roteando o idioma na URL
		$request = new Zend_Controller_Request_Http();
		foreach ($config->langs as $language) {
			if ($language->lang == $config->lang_default) {
				continue;
			}
			// http://localhost/CLIENTE/en/ 
			// http://localhost/CLIENTE/en 
			// http://localhost/CLIENTE/en?*
			if (preg_match('~^' . $frontController->getBaseUrl() . $language->lang . '(/|\?|$)~', $request->getRequestUri())) {
				$lang = new Jp7_Locale($language->lang);
				$frontController->setBaseUrl($frontController->getBaseUrl() . $language->lang);
				break;
			}
		}
		// Lang da JP7			
		if (!$lang) {
			$lang = new Jp7_Locale($config->lang_default);
		}
		$config->lang = $config->langs[$lang->lang];
		Zend_Registry::set('lang', $lang);
		$request->setParam('lang', $lang->lang);
		$frontController->setRequest($request);
		
		// Zend Translate
		$language_file = APPLICATION_PATH . '/languages/' . $lang->lang . '.php';
		if (is_file($language_file)) {
			$translate = new Zend_Translate('array', $language_file, $lang->lang);
			Zend_Registry::set('Zend_Translate', $translate);
		}
	}	
	
	public static function initLayout() {
		Zend_Layout::startMvc(APPLICATION_PATH . '/layouts/scripts');
		//Zend_Layout::startMvc(ROOT_PATH . '/institucional/application/layouts/scripts');
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
		$viewRenderer->setView(new Jp7_View());
		$view = Zend_Layout::getMvcInstance()->getView();
		
		if (is_dir(APPLICATION_PATH . '/modules/default')) {
			$view->setBasePath(APPLICATION_PATH . '/modules/default/views');
		}
		// Permite o uso de templates no _default
		$view->setScriptPath(array_merge(
			array(ROOT_PATH . '/_default/application/views/scripts'),
			//array(ROOT_PATH . '/institucional/application/modules/default/views/scripts'),
			$view->getScriptPaths()
		));
		// Permite o uso de Helpers customizados da Jp7
		$view->addHelperPath('Jp7/View/Helper', 'Jp7_View_Helper');
		
		// Adicionando JS e CSS padrão
		$config = Zend_Registry::get('config');
		$lang = Zend_Registry::get('lang');
		
		$metas = array(
			'language' => $lang->lang,
			'description' => $config->lang->description,
			'keywords' => $config->lang->keywords,
			'copyright' => date('Y') . ' ' . $config->copyright,
			'robots' => 'all',
			'author' => 'JP7 - http://www.jp7.com.br',
			'generator' => 'JP7 InterAdmin'
		);
		if ($config->google_site_verification) { 
			$metas['google-site-verification'] = $config->google_site_verification;
 		}
		
		defined('DEFAULT_PATH') || define('DEFAULT_PATH', '/_default/');
		
		// JS
		$scripts = array(
			DEFAULT_PATH . 'js/jquery/jquery-1.3.2.min.js',
			DEFAULT_PATH . 'js/interdyn.js',
			DEFAULT_PATH . 'js/interdyn_checkflash.js',
			DEFAULT_PATH . 'js/interdyn_form.js',
			DEFAULT_PATH . 'js/interdyn_form_lang_' . $lang->lang . '.js',
			DEFAULT_PATH . 'js/swfobject.js',			
			DEFAULT_PATH . 'js/interdyn_menu.js',
			'js/functions.js'
		);
		foreach ($scripts as $file) {
			$view->headScript()->appendFile($file);
		}
		// CSS
		$view->headLink()->appendStylesheet(DEFAULT_PATH . 'css/7_w3c.css');
		$view->headLink()->appendStylesheet('css/main.css');
		
		Zend_Registry::set('metas', $metas);
	}
	
	public static function preDispatch() {
		
	}
	
	public static function dispatch() {
		//$GLOBALS['debugger']->getTime(true, 'Bootstrap');
		Zend_Controller_Front::getInstance()->dispatch();
	}
	
	public static function postDispatch() {
		// Cache
		if (Jp7_Cache_Output::hasStarted()) {
			 Jp7_Cache_Output::getInstance()->end();
		}
		
		//$GLOBALS['debugger']->getTime(true, 'Página inteira');
		//echo memory_get_usage() . ' de Memória<br />';
		//echo memory_get_peak_usage() . ' de pico de Memória<br />';
	}
}
