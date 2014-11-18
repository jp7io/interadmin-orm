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
		// $this->beforeFilter('@setTipo');
		// $this->beforeFilter('@setRecord', [
		// 	'only' => ['show']
		// ]);
		$klass = \InterAdminTipo::getDefaultClass();
		$rootTipo = new $klass(0);

		$this->view->menuItens = $rootTipo->getChildrenMenu();

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
		$action = snake_case(str_replace(['get', 'any', 'post'], '', $action));
		$action = str_replace('_', '-', $action);
		
		$explodedClassName = explode('\\', get_class($this));
		$className 		   = array_map(function($string) {
			return str_replace(['_', '-controller'], ['-', ''], snake_case($string));
		}, $explodedClassName);
		$className 		   = implode('.', $className);
		
		$url = str_replace('.', '/', "{$className}.{$action}");

		if (file_exists(\Config::get('view.paths')[0] . '/' . $url . '.blade.php')) {
			return "{$className}.{$action}";
		} elseif(file_exists(\Config::get('view.paths')[0] . '/' . self::$tipo->template . '/' . $action . '.blade.php')) {
			return trim(str_replace('/', '.', self::$tipo->template) . '.' . $action, '.');
		} else {
			return "templates.{$action}";
		}
		
	}

}
