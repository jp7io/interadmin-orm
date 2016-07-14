<?php

namespace Jp7\Laravel;

use Jp7\Interadmin\Type;
use Jp7\Interadmin\Record;
use Jp7\Laravel\RouterFacade as r;
use BadMethodCallException;
use URL;

class RecordUrl
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
    public static function getTypeUrl(Type $type, $action = 'index', $parameters = [])
    {
        if ($type->getParent() instanceof Record) {
            array_unshift($parameters, $type->getParent());
        }

        $route = $type->getRoute($action);
        if (!$route) {
            throw new BadMethodCallException('There is no route for id_tipo: '.$type->id_tipo.
                ', action: '.$action.'. Called on '.get_class($type));
        }

        $variables = r::getVariablesFromRoute($route);
        if (count($parameters) != count($variables)) {
            throw new BadMethodCallException('Route "'.$route->getUri().'" has '.count($variables).
                ' parameters, but received '.count($parameters).'. Called on '.get_class($type));
        }
        // object -> id_slug
        $parameters = array_map(function ($parameter) {
            if (is_object($parameter)) {
                return $parameter->id_slug ?: $parameter->id;
            } else {
                return $parameter;
            }
        }, $parameters);
        
        return URL::route($route->getName(), $parameters);
    }

    /**
     * Return URL from the route associated with this record.
     *
     * @param string $action Defaults to 'show'
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    public static function getRecordUrl(Record $record, $action = 'show')
    {
        $route = $record->getRoute($action);
        if (!$route) {
            throw new BadMethodCallException('There is no route for id_tipo: '.$record->id_tipo.
                 ', action: '.$action.'. Called on '.get_class($record));
        }

        $variables = r::getVariablesFromRoute($route);
        $hasSlug = in_array($action, ['show', 'edit', 'update', 'destroy']);

        if ($hasSlug) {
            $removedVar = array_pop($variables);
        }

        $parameters = $record->getUrlParameters($variables);

        if ($hasSlug) {
            $parameters[] = $record;
            array_push($variables, $removedVar);
        }
        // object -> id_slug
        $parameters = array_map(function ($p) {
            return $p->id_slug ?: $p->id;
        }, $parameters);

        if (count($parameters) != count($variables)) {
            throw new BadMethodCallException('Route "'.$route->getUri().'" has '.count($variables).
                    ' parameters, but received '.count($parameters).'. Called on '.get_class($record));
        }
        
        return URL::route($route->getName(), $parameters);
    }
}
