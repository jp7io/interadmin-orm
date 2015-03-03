<?php

namespace Jp7\Laravel\Controller;

trait DynamicViewTrait {

	/**
	 * @var Variables to send to view
	 */
	protected $_viewData = null;

	public function constructDynamicViewTrait() {
		if (is_null($this->_viewData)) {
			$this->_viewData = new \stdClass();
		}
	}
	
	public function &__get($key) {
		return $this->_viewData->$key;
	}
	
	public function __set($key, $value) {
		$this->_viewData->$key = $value;
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
		
		if (!is_null($response)) {
			return $response;
		}
		if ($method == 'show' && !$this->record) {
			throw new \Exception('Show action without record. You need to set $this->record inside your controller.');	
		}
		return $this->_makeView($method);
	}
	
	private function _makeView($method) {
		$viewName = $this->_getViewName($method);
		$viewContent = \View::make($viewName, (array) $this->_viewData);
		
		if ($this->layout) {
			$this->_viewData->content = $viewContent;
			$viewContent = \View::make($this->layout, (array) $this->_viewData);
		}
		return $viewContent;
	}

	/**
	 * Get the view name
	 * 
	 * @return string
	 */
	private function _getViewName($method) {
		$action = str_replace('_', '-', snake_case($method));
		
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
		$slugArray = array_map(function($string) {
			$string = snake_case($string);
			$string = str_replace('_', '-', $string);
			return str_replace('-controller', '', $string);
		}, $explodedClassName);
		return implode('.', $slugArray);
	}
}