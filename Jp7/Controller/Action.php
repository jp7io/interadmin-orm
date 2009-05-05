<?php

class Jp7_Controller_Action extends Zend_Controller_Action
{
	public $id_tipo, $tipoObj, $parentObj, $config, $baseUrl, $title;

	/**
	 * Trata as actions que não tem a função definida e passa para o template
	 * se existir.
	 * 
	 * @param $method
	 * @param $args
	 * @return void
	 */
	public function __call($method, $args)
	{
		$rootTipo = new Jp7_InterAdmin_Tipo(0);

		$controllerTipo = reset($rootTipo->getChildren(array(
			'fields' => array('template'),
			'where' => " AND " . toSeoSearch('nome', str_replace('-', '', $this->getRequest()->getControllerName()))
		)));

		if ($controllerTipo) {
			if ($this->getRequest()->getActionName() == 'index') {
				$actionTipo = $controllerTipo;
			} else {
				$actionTipo = reset($controllerTipo->getChildren(array(
					'fields' => array('template'),
					'where' => " AND " . toSeoSearch('nome', str_replace('-', '', $this->getRequest()->getActionName()))
				)));
			}
		}

		if ($actionTipo) {
			if (!$actionTipo->template) {
				$actionTipo->template = $actionTipo->getModel()->getFieldsValues('template');
			}
			if ($actionTipo->template) {
				$template = explode('/', $actionTipo->template);
				$controller = $template[0];
				$action = $template[1];

				$this->_forward($action, $controller, false, array('id_tipo' => $actionTipo->id_tipo));
				return;
			}
		}
		return parent::__call($method, $args);
	}
	
	/**
	 * Inicializa a configuração padrão a todas as actions.
	 *
	 * @param int $id_tipo
	 * @return void
	 */
	public function prepare($id_tipo)
	{
		global $lang;
		
		if (!$id_tipo) {
			throw new Zend_Controller_Action_Exception('O $id_tipo não foi definido', 404);
		}
		$this->id_tipo = $id_tipo;
		$this->view->id_tipo = $this->id_tipo;
		
		$this->tipoObj = new Jp7_InterAdmin_Tipo($this->id_tipo);
		$this->view->tipoObj = $this->tipoObj;
		$this->parentObj = $this->tipoObj->getParent();
		
		// TODO Usar Zend_Config
		$this->config = Zend_Registry::get('config');
		
		// TODO Usar o Zend_Locale e remover global
		$lang = new Jp7_Locale($this->getRequest()->getParam('lang'));
		$this->view->lang = $lang;
		
		$this->baseUrl = $this->getFrontController()->getBaseUrl();
		$this->view->baseUrl = $this->getFrontController()->getBaseUrl();
		$this->view->client = $this->config->name_id;
		$this->view->controller = $this->getRequest()->getControllerName();
		$this->view->action = $this->getRequest()->getActionName();
		
		// metas, css, js
		$this->view->headMeta()->setHttpEquiv('Content-Type', 'text/html; charset=iso-8859-1');
		$this->view->headMeta()->appendName('language', $lang->lang);
		$this->view->headMeta()->appendName('description', '');
		$this->view->headMeta()->appendName('keywords', '');
		$this->view->headMeta()->appendName('copyright', date('Y') . ' ' . $this->config->name);
		$this->view->headMeta()->appendName('robots', 'all');
		$this->view->headMeta()->appendName('author', 'JP7 - http://jp7.com.br');
		$this->view->headMeta()->appendName('generator', 'JP7 InterAdmin');
		$this->view->headLink()->appendStylesheet('/_default/css/7_w3c.css');
		$this->view->headLink()->appendStylesheet($this->baseUrl . '/css/default.css');
		$this->view->headScript()->appendFile('/_default/js/swfobject.js', 'text/javascript');
		$this->view->headScript()->appendFile('/_default/js/jquery/jquery-1.2.6.pack.js', 'text/javascript');
		$this->view->headScript()->appendFile($this->baseUrl . '/js/functions.js', 'text/javascript');
		
		// titulos
		$this->title['client'] = $this->config->langs[$lang->lang]->title;
		if ($this->parentObj) {
			$this->title['controller'] = $this->parentObj->getFieldsValues('nome' . $lang->prefix);
			$this->title['action'] = $this->tipoObj->getFieldsValues('nome' . $lang->prefix);
		} else {
			$this->title['controller'] = $this->tipoObj->getFieldsValues('nome' . $lang->prefix);
		}
		$this->title['page'] = implode(' | ', $this->title);
		$this->view->title = $this->title;
		
		// fix para usar o 7.head
		// TODO Usar o layout_new.phtml e remover esse bloco
		$this->view->config = $this->config;
		$this->view->p_title = $this->title['client'];
		$this->view->secaoTitle = $this->title['controller'];
		$this->view->secaoTitle = $this->title['action'];
		$this->view->c_site = toId($this->view->p_title);
		$this->view->c_path = jp7_path(Zend_Controller_Front::getInstance()->getBaseUrl());
		$viewPaths = $this->view->getScriptPaths();
		$this->view->setScriptPath('../inc/');
		$this->view->head = $this->view->render('7.head.php');
		$this->view->setScriptPath($viewPaths);
		
		// fix para usar os arquivos de idioma
		// TODO Usar gettext e mudar para Jp7_Controller_Action::prepare
		if (file_exists('inc/lang_' . $lang->lang . '.php')) {
			include 'inc/lang_' . $lang->lang . '.php';
		} else {
			include 'inc/lang_pt-br.php';
		}
	}
}