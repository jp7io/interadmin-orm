<?php

namespace Jp7\Laravel;

class Controller extends \Illuminate\Routing\Controller {
	
	protected static $current = null;

	protected $layout = 'layouts.master';

	/**
	 * @var Variables to send to view
	 */
	protected $_viewData = null;
	protected $scope = null;
	
	public function __construct() {
		static::$current = $this;

		if (is_null($this->_viewData)) {
			$this->_viewData = new \stdClass();
		}
		$this->beforeFilter('@setScope');
		$this->beforeFilter('@setType');
		$this->beforeFilter('@setRecord', ['only' => ['show']]);
	}
	
	public function &__get($key) {
		return $this->_viewData->$key;
	}
	
	public function __set($key, $value) {
		$this->_viewData->$key = $value;
	}

	/* Temporary solution - Avoid using this as much as possible */
	public static function getCurrentController() {
		return static::$current;
	}
	
	// FIXME Where is it Used??
	public function getRootType() {
		//$klass = \getDefaultClass(); 
		return \InterAdminTipo::getInstance(0);
	}
    
	public function setScope($route) {
		$uri = $route->getUri();
		
		$action = explode('@', $route->getActionName())[1];
		if (in_array($action, array('show', 'edit', 'update', 'destroy'))) {
			$uri = dirname($uri); // Do not resolve $record yet
		}
		
		$breadcrumb = \Route::uriToBreadcrumb($uri, function($type, $segment) use ($route) {
			$slug = $route->getParameter(trim($segment, '{}'));
			return $type->records()->find($slug);
		});
		
		if ($type = end($breadcrumb)) {
			$parent = $type->getParent();
			if ($parent instanceof \InterAdmin && !$parent->hasChildrenTipo($type->id_tipo)) {
				throw new \Jp7_InterAdmin_Exception('It seems this route has a'
					. ' special structure. You need to define a custom '
					. 'setScope() to handle this.');
			}
			$this->scope = $type->records();
		}
	}
	
	public function setType() {
		if (!$this->scope) {
			throw new \Jp7_InterAdmin_Exception('setScope() could not resolve the'
				. ' type associated with this URI. You need to map it on routes.php.'
				. ' You can also define a custom setScope() or setType()');
		}
		$this->type = $this->scope->type();
	}
	
	public function setRecord($route) {
		if ($this->scope) {
			$parameters = $route->parameterNames();
			if (count($parameters) > 0) {
				$slug = $route->getParameter(end($parameters));
				
				$this->record = $this->scope->find($slug);
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
		$snakeArray = array_map(function($string) {
			$string = snake_case($string);
			$string = str_replace('_', '-', $string);
			return str_replace('-controller', '', $string);
		}, $explodedClassName);
		return implode('.', $snakeArray);
	}

}
