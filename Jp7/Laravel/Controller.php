<?php

namespace Jp7\Laravel;

class Controller extends \Controller {

	static $tipo = null;

	protected $layout = 'layouts.master';

	/**
	 * @var Variables to send to view
	 */
	protected $view = null;
	protected $tipoClassName = null;
	
	public function __construct() {
		if (is_null($this->view)) {
			$this->view = new View;
		}
		$this->beforeFilter('@setTipo');
		$this->beforeFilter('@setRecord', ['only' => ['show']]);
		$this->beforeFilter('@setMenuItens');
	}

	public function getRootTipo() {
		//$klass = \getDefaultClass();
		return \InterAdminTipo::getInstance(0);
	}
        
	public function setMenuItens() {
		$this->view->menuItens = $this->getRootTipo()->getChildrenMenu();
	}
        
	public function setTipo() {
		if (!static::$tipo && $this->tipoClassName) {
			$className = '\\' . $this->tipoClassName;
			
			if (class_exists($className)) {
				static::$tipo = new $className;
			}
		}
		
		$this->tipo = static::$tipo;
	}

	public function setRecord() {
		$route = \Route::getCurrentRoute();
		$resources = $route->parameterNames();
		
		if ($this->tipo && count($resources) == 1) {
			$resourceName = end($resources);
			$value = $route->getParameter($resourceName);
			
			$this->record = $this->tipo->find($value);
		}
	}

	/**
	 * Execute an action on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function callAction($method, $parameters) {
		$response = call_user_func_array(array($this, $method), $parameters);
		
		if (is_null($response)) {
			if ($method == 'show' && !$this->record) {
				throw new \Exception('Show action without record. You need to set $this->record inside your controller.');	
			}
			$this->view->tipo = $this->tipo;
			$this->view->record = $this->record;
				
			$viewName = $this->_getViewName($method);
			$viewContent = \View::make($viewName, (array) $this->view);
			
			if (is_null($this->layout)) {
				$response = $viewContent;
			} else {
				$this->view->content = $viewContent;
				$response = \View::make($this->layout, (array) $this->view);
			}
		}
	
		return $response;
	}
	
	/**
	 * Get the view name
	 * 
	 * @return string
	 */
	private function _getViewName($action) {
		$action = str_replace('_', '-', snake_case($action));
		
		$viewRoute = $this->_viewRoute(get_class($this), $action);
		if ($this->_viewExists($viewRoute)) {
			return $viewRoute;
		}
		
		$viewRoute = $this->_viewRoute(get_parent_class($this), $action);
		if ($this->_viewExists($viewRoute)) {
			return $viewRoute;
		}
		
		return "templates.{$action}";
	}

	protected function _viewRoute($controllerClass, $action) {
		$controllerRoute = $this->_controllerRoute($controllerClass);
		return "{$controllerRoute}.{$action}";
		
	}
	
	protected function _viewExists($route) {
		$filename = str_replace('.', '/', $route);
		
		foreach (\Config::get('view.paths') as $viewPath) {
			if (file_exists($viewPath . '/' . $filename . '.blade.php')) {
				return true;
			}
		}
		return false;
	}
	
	protected function _controllerRoute($controllerClass) {
		$explodedClassName = explode('\\', $controllerClass);
		$snakeArray = array_map(function($string) {
			$string = snake_case($string);
			$string = str_replace('_', '-', $string);
			return str_replace('-controller', '', $string);
		}, $explodedClassName);
		return implode('.', $snakeArray);
	}

}
