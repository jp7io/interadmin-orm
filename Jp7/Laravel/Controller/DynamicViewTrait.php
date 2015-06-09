<?php

namespace Jp7\Laravel\Controller;

use View;
use Response;
use stdClass;
use Exception;
use Config;

trait DynamicViewTrait
{
    /**
     * @var Variables to send to view
     */
    protected $_view;

    public function constructDynamicViewTrait()
    {
        $this->_view = new stdClass();
    }

    public function &__get($key)
    {
        return $this->_view->$key;
    }

    public function __set($key, $value)
    {
        $this->_view->$key = $value;
    }

    /**
     * Execute an action on the controller.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        $response = call_user_func_array(array($this, $method), $parameters);

        if (!is_null($response)) {
            return $response;
        }
        if ($method == 'show' && !$this->record) {
            throw new Exception('Show action without record. You need to set $this->record inside your controller.');
        }

        return $this->makeView();
    }

    public function makeView()
    {
        $viewName = $this->_getViewName($this->action);
        $data = (array) $this->_view;
        $view = View::make($viewName, $data);

        if ($this->layout) {
            $view = View::make($this->layout, ['content' => $view] + $data);
        }

        return $view;
    }

    /**
     * Get the view name.
     *
     * @return string
     */
    private function _getViewName($method)
    {
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

    protected function _viewRoute($controllerClass, $action)
    {
        $controllerRoute = $this->_controllerRoute($controllerClass);

        return "{$controllerRoute}.{$action}";
    }

    protected function _viewExists($route)
    {
        $filename = str_replace('.', '/', $route);

        foreach (Config::get('view.paths') as $viewPath) {
            if (file_exists($viewPath.'/'.$filename.'.blade.php')) {
                return true;
            }
        }

        return false;
    }

    protected function _controllerRoute($controllerClass)
    {
        $explodedClassName = explode('\\', $controllerClass);
        $slugArray = array_map(function ($string) {
            $string = snake_case($string);
            $string = str_replace('_', '-', $string);

            return str_replace('-controller', '', $string);
        }, $explodedClassName);

        return implode('.', $slugArray);
    }
}
