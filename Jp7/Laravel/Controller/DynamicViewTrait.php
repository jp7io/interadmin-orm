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
        $viewName = $this->findViewName($this->action);
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
            $content = $this->view()->header('Vary', 'Accept');
        }
        return response($content);
    }

    /**
     * Find the view to be used.
     *
     * @return string
     */
    protected function findViewName($method)
    {
        $action = str_replace('_', '-', snake_case($method));
        
        $viewNames = [
            $this->makeViewName(get_class($this), $action),
            $this->makeViewName(get_parent_class($this), $action),
            "templates.{$action}"
        ];
        
        foreach ($viewNames as $viewName) {
            if ($this->viewExists($viewName)) {
                return $viewName;
            }
        }
        
        throw new Exception('View not found in: ' . implode(', ', $viewNames));
    }

    protected function makeViewName($controllerClass, $action)
    {
        $controllerPath = $this->studlyToSlug($controllerClass);
        # Remove -controller in the end
        $controllerPath = substr($controllerPath, 0, -strlen('-controller'));
        $controllerPath = str_replace('app.http.controllers.', '', $controllerPath);
        return "{$controllerPath}.{$action}";
    }

    protected function viewExists($viewName)
    {
        $filename = str_replace('.', '/', $viewName);

        foreach (config('view.paths') as $viewPath) {
            if (file_exists($viewPath.'/'.$filename.'.blade.php')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Turns Something\IndexController into something.index-controller
     *
     * @param string $class  Class name in studly case
     * @return string
     */
    protected function studlyToSlug($class, $separator = '.')
    {
        $toSlug = function ($string) {
            return str_replace('_', '-', snake_case($string));
        };
        $slugArray = array_map($toSlug, explode('\\', $class));
        
        return implode($separator, $slugArray);
    }
}
