<?php

namespace Jp7\Laravel;

class Controller extends \Controller {

	static $tipo = null;
	static $record = null;

	protected $layout = 'templates.layout';

	/**
	 * @var Variables to send to view
	 */
	protected $view = null;
	
	public function __construct()
	{
		if (is_null($this->view))
		{
			$this->view = new \StdClass;
		}
		$this->beforeFilter('@setTipo');
		$this->beforeFilter('@setRecord', [
			'only' => ['show']
		]);
		$klass = \InterAdminTipo::getDefaultClass();
		$rootTipo = new $klass(0);

		$this->view->menuItens = $rootTipo->getChildrenMenu();

	}


	public function setTipo() {
		if (!static::$tipo && defined('static::TIPO_CLASS_NAME')) {
			$className = '\\' . static::TIPO_CLASS_NAME;
			
			if (class_exists($className)) {
				static::$tipo = new $className;
			}
		}

		$this->tipo = $this->view->tipo = static::$tipo;
	}

	public function setRecord() {
		if ($this->tipo) {
			$route = Route::getCurrentRoute();
			$resources = $route->parameterNames();

			$record = $this->tipo
				->fields('*');

			$resourceName 		= end($resources);
			$value    	  		= $route->getParameter($resourceName);
			$resourceName 		= str_singular($resourceName);
			
			if (count($resources) > 1) {
				$parentResource = $resources[count($resources) - 2];
				$parentValue 	= $route->getParameter($parentResource);
				$parentResource = str_singular($parentResource);

				$record->where($parentResource . ".id_slug = '{$parentValue}'");
			}
			
			$record = $record->findByIdSlug($value);
			
			$this->record 
				= static::$record 
				= $this->view->record
				= $this->view->{camel_case($resourceName)}
				= $record;

			if (count($resources) > 1) {
				$this->view->$parentResource = static::$record->$parentResource;
			}
		}
	}


	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}


	/**
	 * Execute an action on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function callAction($method, $parameters)
	{
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
	private function _getViewName($action)
	{
		$action = \Jp7_Inflector::underscore(str_replace(['get', 'any', 'post'], '', $action));
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
			$string =  \Jp7_Inflector::underscore($string);
			$string = str_replace('_', '-', $string);
			return str_replace('-controller', '', $string);
		}, $explodedClassName);
		return implode('.', $snakeArray);
	}

}
