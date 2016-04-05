<?php

namespace Jp7\Laravel\Url;

use Jp7\Interadmin\ClassMap;
use Jp7\Interadmin\Record;
use Jp7\Laravel\RouterFacade as Router;
use BadMethodCallException;
use URL;

trait TypeTrait
{
    /**
     * Returns the full url for this Type.
     *
     * @param string $action
     * @param array  $parameters
     *
     * @throws BadMethodCallException
     *
     * @return string
     */
    public function getUrl($action = 'index', array $parameters = [])
    {
        if (func_num_args() === 1 && is_array($action)) {
            list($action, $parameters) = [null, $action];
        }

        if ($this->getParent() instanceof Record) {
            array_unshift($parameters, $this->getParent());
        }

        $route = $this->getRoute($action);
        if (!$route) {
            throw new BadMethodCallException('There is no route for id_tipo: '.$this->id_tipo.
                ', action: '.$action.'. Called on '.get_class($this));
        }

        $variables = Router::getVariablesFromRoute($route);

        if (count($parameters) != count($variables)) {
            throw new BadMethodCallException('Route "'.$route->getUri().'" has '.count($variables).
                ' parameters, but received '.count($parameters).'. Called on '.get_class($this));
        }

        $parameters = array_map(function ($p) {
            if (is_object($p)) {
                return $p->id_slug ?: $p->id;
            } else {
                return $p;
            }
        }, $parameters);
        
        return URL::route($route->getName(), $parameters);
    }

    public function getRoute($action = 'index')
    {
        $validActions = ['index', 'show', 'create', 'store', 'update', 'destroy', 'edit'];
        if (!in_array($action, $validActions)) {
            throw new BadMethodCallException('Invalid action "'.$action.'", valid actions: '.implode(', ', $validActions));
        }

        return Router::getRouteByIdTipo($this->id_tipo, $action);
    }

    public function getRouteActions()
    {
        $class = ClassMap::getInstance()->getClass($this->id_tipo);

        if ($class && method_exists($class, 'getRouteActions')) {
            return call_user_func([$class, 'getRouteActions']);
        }

        return ['index', 'show'];
    }
}
