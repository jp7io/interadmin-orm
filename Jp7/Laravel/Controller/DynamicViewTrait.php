<?php

namespace Jp7\Laravel\Controller;

use Symfony\Component\HttpFoundation\Response;
use View;
use stdClass;
use Exception;
use Request;

trait DynamicViewTrait
{
    /**
     * @var Variables to send to view
     */
    protected $_view;
    protected $layout = 'layouts.master';

    public function constructDynamicViewTrait()
    {
        $this->_view = new stdClass();

        $this->beforeFilter('@checkAjax');
    }
    
    public function checkAjax()
    {
        $this->remote = null;
        if (Request::ajax()) {
            $this->layout = 'layouts.ajax';
            $this->remote = true;
        }
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
        $content = call_user_func_array(array($this, $method), $parameters);
        if ($content instanceof Response) {
            return $content;
        }
        return $this->response($content);
    }

    protected function view()
    {
        $viewName = $this->_getViewName($this->action);
        $data = (array) $this->_view;
        $view = View::make($viewName, $data);

        if ($this->layout) {
            $view = View::make($this->layout, ['content' => $view] + $data);
        }

        return $view;
    }

    protected function response($content = null)
    {
        if (is_null($content)) {
            $content = $this->view();
        }
        return response($content);
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
        $controllerRoute = str_replace('app.http.controllers.', '', $controllerRoute);
        return "{$controllerRoute}.{$action}";
    }

    protected function _viewExists($route)
    {
        $filename = str_replace('.', '/', $route);

        foreach (config('view.paths') as $viewPath) {
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
