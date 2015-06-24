<?php

namespace Jp7\Laravel5\Url;

use BadMethodCallException;
use URL;
use Jp7\Laravel5\RouterFacade as Router;

trait RecordTrait
{
    /**
     * Return URL from the route associated with this record.
     *
     * @param string $action Defaults to 'show'
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    public function getUrl($action = 'show')
    {
        $route = $this->getRoute($action);
        if (!$route) {
            throw new BadMethodCallException('There is no route for id_tipo: '.$this->id_tipo.
                 ', action: '.$action.'. Called on '.get_class($this));
        }

        $variables = Router::getVariablesFromRoute($route);
        $hasSlug = in_array($action, array('show', 'edit', 'update', 'destroy'));

        if ($hasSlug) {
            $removedVar = array_pop($variables);
        }

        $parameters = $this->getUrlParameters($variables);

        if ($hasSlug) {
            $parameters[] = $this;
            array_push($variables, $removedVar);
        }

        $parameters = array_map(function ($p) {
            return $p->id_slug ?: $p->id;
        }, $parameters);

        if (count($parameters) != count($variables)) {
            throw new BadMethodCallException('Route "'.$route->getUri().'" has '.count($variables).
                    ' parameters, but received '.count($parameters).'. Called on '.get_class($this));
        }
        
        return URL::route($route->getName(), $parameters);
    }

    public function getRoute($action = 'index')
    {
        return $this->getType()->getRoute($action);
    }

    /**
     * Parameters to be used with URL::route().
     *
     * @param array $variables
     *
     * @return array
     */
    public function getUrlParameters(array $variables)
    {
        $parameters = [];
        $parent = $this;
        foreach ($variables as $variable) {
            if (!$parent = $parent->getParent()) {
                break;
            }
            $parameters[] = $parent;
        }

        return $parameters;
    }
}
