<?php

/**
 * Adiciona configurações comuns da JP7 e __call de métodos inexistentes para 
 * templates ao Controller da Zend.
 * 
 * @category Jp7
 * @package Jp7_Controller
 */
class Jp7_Controller_Action extends Zend_Controller_Action
{
	/**
	 * @var InterAdminTipo
	 */
	protected static $tipo;
	/**
	 * @var InterAdmin
	 */
	protected $record;
	
	public function init() {
		if (!Zend_Registry::isRegistered('originalRequest')) {
			Zend_Registry::set('originalRequest', clone $this->getRequest());
		}
	}
	
    public function preDispatch() {
    	if (!$this->actionExists()) {
			$this->forwardToTemplate();
		}
	}
	public function postDispatch() {
		/**
		 * @var InterSite $config Configuração geral do site, gerada pelo InterSite
		 */
		$config = Zend_Registry::get('config');
		/**
		 * @var Jp7_Locale $lang Idioma sendo utilizada no site
		 */
		$lang = Zend_Registry::get('lang');
		/**
		 * @var array $metas Metatags no formato $nome => $valor
		 */
		$metas = Zend_Registry::get('metas');
		/**
		 * @var array $scripts Arquivos de Javascript
		 */
		$scripts = Zend_Registry::get('scripts');
		/**
		 * @var array $links Arquivos de CSS
		 */
    	$links = Zend_Registry::get('links');
		
		// View
		$this->view->config = $config;
		$this->view->lang = $lang;
		$this->view->tipo = self::getTipo();
		
		// Layout
		// - Title
		$this->_prepareTitle();
		$this->view->headTitle($config->lang->title);
		// - Metas
		$this->view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=ISO-8859-1');
		foreach ($metas as $key => $value) {
			$this->view->headMeta()->appendName($key, $value);
		}
		// - Javascripts
		foreach ($scripts as $file) {
			$this->view->headScript()->appendFile($file);
		}
		// - CSS
		foreach ($links as $file) {
			$this->view->headLink()->appendStylesheet($file);
		}
	}
	
	protected function _prepareTitle() {
		$this->view->headTitle()->setSeparator(' | ');
		
		if ($this->record) {
			if ($titulo = $this->record->getFieldsValues('varchar_key')) {
				$this->view->headTitle($titulo);
			}
		}
		
		if ($secao = self::getTipo()) {
			if ($secao->getFieldsValues('nome') == 'Home' && !$secao->getParent()->id_tipo) {
				return; // Home
			}
			while ($secao->id_tipo) {
				$this->view->headTitle($secao->getFieldsValues('nome'));
				$secao = $secao->getParent();
			}
		}	
	}
	
	/**
	 * Trata as actions que não tem a função definida e passa para o template
	 * se existir.
	 * 
	 * @param string $method
	 * @param array $args
	 * @return void
	 */
	public function __call($method, $args)
	{
		if ($this->forwardToTemplate()) {
			return;	
		} else {
			return parent::__call($method, $args);
		}
	}
	/** 
	 * Forwards the request to the template of this InterAdminTipo.
	 * 
	 * @return bool TRUE if it has a template, FALSE otherwise.
	 */
	public function forwardToTemplate() {
		// TODO $this->getTipo() should be static::getTipo(). Available on PHP 5.3
		if ($tipo = $this->getTipo()) {
			if ($template = $tipo->getModel()->template) {
				$templateArr = explode('/', $template);
				if (count($templateArr) > 2) {
					list($module, $controller, $action) = $templateArr;
				} else {
					list($controller, $action) = $templateArr;
				}
				$this->_forward($action, $controller, $module);
				return true;
			}
		}
		return false;
	}
	/**
	 * Returns the InterAdminTipo pointed by the current Controller and Action.
	 * 
	 * @return InterAdminTipo
	 */
	public static function getTipo() {
		if (!isset(self::$tipo)) {
			$config = Zend_Registry::get('config');
						
			$customTipo = ucfirst($config->name_id) . '_InterAdminTipo';
			if (class_exists($customTipo)) {
				$parentTipo = new $customTipo();
			} else {
				$parentTipo = new InterAdminTipo();
			}
			
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$tipos = array();
			if ($request->getModuleName() != 'default') {
				$tipos[] = toId($request->getModuleName());
			}
			if ($request->getControllerName() != 'index') {
				$tipos[] = toId($request->getControllerName());
			}
			if ($request->getActionName() != 'index') {
				$tipos[] = toId($request->getActionName());
			}
			if (!$tipos) {
				$tipos[] = 'home';
			}
			
			foreach ($tipos as $id_tipo_string) {
				$tipo = $parentTipo->getFirstChild(array(
					'fields' => array('template'),
					'where' => array("id_tipo_string = '" . $id_tipo_string . "'")
				));
				// Caso action não exista no interadmin, mas o controller sim
				if (!$tipo) { 
					if ($parentTipo->id_tipo) {
						$tipo = $parentTipo;
					}
					break;
				}
				$parentTipo = $tipo;
			}
			self::$tipo = $tipo;
		}
		return self::$tipo;
	}
	/**
	 * Sets the InterAdminTipo for this controller.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public static function setTipo(InterAdminTipo $tipo = null) {
		self::$tipo = $tipo;
	}
	/**
	 * Checks if the request Action exists.
	 * @return bool
	 */	
	public function actionExists() {
		$request = $this->getRequest();
		$actionName = toId($request->getActionName());
		// Case insensitive
		return method_exists($this,  $actionName . $request->getActionKey());
	}
}
