<?php

namespace Jp7\Laravel\Url;

use Jp7\InterAdmin\ClassMap;
use BadMethodCallException;
use Route;
use URL;
use InterAdmin;

trait TypeTrait
{
    /**
     * Returns the full url for this InterAdminTipo.
     *
     * @param string $action
     * @param array  $parameters
     *
     * @throws BadMethodCallException
     *
     * @return string
     */
    public function getUrl($action = 'index', array $parameters = array())
    {
        if (func_num_args() === 1 && is_array($action)) {
            list($action, $parameters) = [null, $action];
        }

        if ($this->getParent() instanceof InterAdmin) {
            array_unshift($parameters, $this->getParent());
        }

        $route = $this->getRoute($action);
        if (!$route) {
            throw new BadMethodCallException('There is no route for id_tipo: '.$this->id_tipo.
                ', action: '.$action.'. Called on '.get_class($this));
        }

        $variables = Route::getVariablesFromRoute($route);

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

        return URL::route(null, $parameters, true, $route);
    }

    public function getRoute($action = 'index')
    {
        $validActions = array('index', 'show', 'create', 'store', 'update', 'destroy', 'edit');
        if (!in_array($action, $validActions)) {
            throw new BadMethodCallException('Invalid action "'.$action.'", valid actions: '.implode(', ', $validActions));
        }

        return Route::getRouteByIdTipo($this->id_tipo, $action);
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
