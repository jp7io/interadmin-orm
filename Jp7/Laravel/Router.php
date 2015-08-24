<?php

namespace Jp7\Laravel;

use Jp7\MethodForwarder;
use Jp7\Interadmin\ClassMap;
use Route;
use Cache;
use Closure;

class Router extends MethodForwarder
{
    /**
     * @var array [id_tipo => route.name]
     */
    protected $map = [];
    protected $cachefile = 'bootstrap/cache/routemap.cache';
    
    ////
    //// Cache
    ////
    
    public function __construct($target)
    {
        $this->cachefile = base_path($this->cachefile);
        
        if (is_file($this->cachefile)) {
            $this->loadCache();
        }
        
        parent::__construct($target);
    }
    
    public function clearCache()
    {
        $this->map = [];
        $this->saveCache();
    }
    
    public function saveCache()
    {
        file_put_contents($this->cachefile, serialize($this->map));
    }
    
    public function loadCache()
    {
        $this->map = unserialize(file_get_contents($this->cachefile));
    }
    
    ////
    //// Map functions
    ////
     
    private function addIdTipo($id_tipo, $slug)
    {
        if (!is_numeric($id_tipo)) {
            // Class => id_tipo
            $cm = ClassMap::getInstance();
            $id_tipo = $cm->getClassIdTipo($id_tipo);
        }
        
        // Saving routes for each id_tipo
        $groupRoute = str_replace('/', '.', trim($this->getLastGroupPrefix(), '/'));
        if (!array_key_exists($id_tipo, $this->map)) {
            $this->map[$id_tipo] = ($groupRoute ? $groupRoute . '.' : '') . $slug;
            return true; // map entry set
        }
        return false; // already existed route for this type
    }
    
    public function getRouteByIdTipo($id_tipo, $action = 'index')
    {
        if (!isset($this->map[$id_tipo])) {
            throw new \Exception('There is no route registered for id_tipo: ' . $id_tipo);
        }
        $mappedRoute = $this->map[$id_tipo];
        $routePrefix = ($mappedRoute != '/') ? $mappedRoute . '.' : '';
        
        return $this->target->getRoutes()->getByName($routePrefix . $action);
    }

    public function getIdTipoByRoute($route)
    {
        return array_search($route, $this->map);
    }

    public function getTypeByRoute($routeName)
    {
        if (!$id_tipo = $this->getIdTipoByRoute($routeName)) {
            return;
        }

        return \InterAdminTipo::getInstance($id_tipo);
    }
    
    public function getMapIdTipos()
    {
        return $this->map;
    }
    
    ////
    //// Route override
    ////
    
    //public function resource($name, $controller = null, array $options = array())
    public function resource($name, array $options = array())
    {
        /*
        if (is_array($controller) && empty($options)) {
            $options = $controller;
            $controller = null;
        }
        */
        if (empty($options['only'])) {
            $options['only'] = ['index', 'show'];
        }
        if (isset($options['id_tipo'])) {
            $this->addIdTipo($options['id_tipo'], $name);
        }
        //if (is_null($controller)) {
        if ($name === '/') {
            $controller = 'Index';
        } else {
            $parts = explode('.', $name);
            $parts = array_map('studly_case', $parts);
            $controller = implode('\\', $parts);
        }
        $controller .= 'Controller';
        //}
        return parent::resource($name, $controller, $options);
    }
    
    public function group(array $attributes, Closure $callback)
    {
        if (!empty($attributes['prefix']) && empty($attributes['namespace'])) {
            $attributes['namespace'] = studly_case($attributes['prefix']);
        }
        return parent::group($attributes, $callback);
    }
    
    ////
    //// Dynamic routes
    ////
    
    public function createDynamicRoutes($section, $currentPath = [])
    {
        $isRoot = $section->isRoot();
        
        if ($subsections = $section->getChildrenMenu()) {
            $closure = function () use ($subsections, $currentPath) {
                foreach ($subsections as $subsection) {
                    $this->createDynamicRoutes($subsection, $currentPath, false);
                }
            };
            
            if ($isRoot) {
                $closure();
            } else {
                Route::group([
                    'namespace' => $section->getStudly(),
                    'prefix' => $section->getSlug()
                ], $closure);
            }
        }
        if (!$isRoot) {
            $slug = $section->getSlug();
        
            if ($this->addIdTipo($section->id_tipo, $slug)) {
                // won't override type route
                Route::resource($slug, $section->getControllerBasename(), [
                    'only' => $section->getRouteActions()
                ]);
            }
        }
    }
    
    ////
    //// Helpers
    ////
        
    public function getVariablesFromRoute($route)
    {
        $matches = array();
        preg_match_all('/{(\w+)}/', $route->getUri(), $matches);

        return $matches[1] ?: array();
    }
    
    /**
     * Map URI to breadcrumb of objects
     *
     * @param string $uri
     * @param Closure $resolveParameter
     */
    public function uriToBreadcrumb($uri, $resolveParameter)
    {
        $breadcrumb = [];
        $uri = trim($uri, '/');
        if ($uri == '') {
            return $breadcrumb;
        }
        $parameter = null;
        $type = null;

        $segments = explode('/', $uri);
        $routeParts = [];

        foreach ($segments as $segment) {
            if (starts_with($segment, '{')) {
                $parameter = $resolveParameter($type, $segment);
                $breadcrumb[] = $parameter;
            } else {
                $routeParts[] = $segment;
                $routeName = implode('.', $routeParts);

                $type = $this->getTypeByRoute($routeName);
                if ($type && $parameter) {
                    $type->setParent($parameter);
                }
                $breadcrumb[] = $type;
            }
        }

        return $breadcrumb;
    }
}
