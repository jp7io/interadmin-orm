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
		
		$this->tipo = $this->view->tipo = static::$tipo;
	}

	public function setRecord() {
		if ($this->tipo) {
			$route = \Route::getCurrentRoute();
			$resources = $route->parameterNames();
			
			$query = $this->tipo;
			
			$resourceName 		= end($resources);
			$value    	  		= $route->getParameter($resourceName);
			$resourceName 		= str_singular($resourceName);
			
			if (count($resources) > 1) {
				$parentResource = $resources[count($resources) - 2];
				$parentValue 	= $route->getParameter($parentResource);
				$parentResource = str_singular($parentResource);

				$query->where($parentResource . ".id_slug = '{$parentValue}'");
			}
			
			$record = $query->find($value);
			
			$this->record 
 				= $this->view->record
				= $record;
			//= static::$record
			//= $this->view->{camel_case($resourceName)}

			if (count($resources) > 1) {
				$this->view->$parentResource = $record->$parentResource;
			}
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
	
		if (is_null($response))
		{
			$viewName		= $this->_getViewName($method);
			$viewContent    = \View::make($viewName, (array)$this->view);
			
			if (is_null($this->layout))
			{
				$response = $viewContent;
			}
			else
			{
				$this->view->content = $viewContent;
				$response = \View::make($this->layout, (array)$this->view);
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
		$action = snake_case(str_replace(['get', 'any', 'post'], '', $action));
		$action = str_replace('_', '-', $action);
		
		if ($viewFile = $this->_getViewFile(get_class($this), $action)) {
			return $viewFile;
		} elseif ($viewFile = $this->_getViewFile(get_parent_class($this), $action)) {
			return $viewFile;
		} else {
			return "templates.{$action}";
		}
	}

	protected function _getViewFile($controllerClass, $action) {
		$routeName = $this->_routeName($controllerClass);
		
		$url = str_replace('.', '/', "{$routeName}.{$action}");

		$viewPath = \Config::get('view.paths')[0];

		if (file_exists($viewPath . '/' . $url . '.blade.php')) {
			return "{$routeName}.{$action}";
		}
	}

	protected function _routeName($controllerClass) {
		$explodedClassName = explode('\\', $controllerClass);
		$snakeArray = array_map(function($string) {
			$string = snake_case($string);
			$string = str_replace('_', '-', $string);
			return str_replace('-controller', '', $string);
		}, $explodedClassName);
		return implode('.', $snakeArray);
	}

}
