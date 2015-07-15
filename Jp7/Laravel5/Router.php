<?php

namespace Jp7\Laravel5;

use Jp7\MethodForwarder;
use Route;
use Cache;

class Router extends MethodForwarder
{
    /**
     * @var array [id_tipo => route.name]
     */
    protected $map = [];
    
    public function __construct($target)
    {
        $this->map = Cache::get('Interadmin.routeMap') ?: [];
        
        parent::__construct($target);
    }
    
    public function resource($name, $controller, array $options = array())
    {
        if (isset($options['id_tipo'])) {
            $this->addIdTipo($options['id_tipo'], $name);
        }
        return parent::resource($name, $controller, $options);
    }
    
    public function type($name, $id_tipo, array $options = array(), $nestedFunction = null)
    {
        if ($nestedFunction) {
            Route::group(['namespace' => studly_case($name), 'prefix' => $name], $nestedFunction);
        }
        
        if ($name === '/') {
            $controller = 'Index';
        } else {
            $controller = studly_case(str_replace('.', '\\ ', $name));
        }
        
        $controller .= 'Controller';
        
        $this->addIdTipo($id_tipo, $name);
        
        Route::resource($name, $controller, $options + [
            'only'=> ['index', 'show']
        ]);
    }
    
    private function addIdTipo($id_tipo, $slug)
    {
        if (!is_numeric($id_tipo)) {
            $id_tipo = call_user_func([$id_tipo, 'type'])->id_tipo;
        }
        
        // Saving routes for each id_tipo
        $groupRoute = str_replace('/', '.', trim($this->getLastGroupPrefix(), '/'));
        if (!array_key_exists($id_tipo, $this->map)) {
            $this->map[$id_tipo] = ($groupRoute ? $groupRoute . '.' : '') . $slug;
            return true; // map entry set
        }
        return false; // already existed route for this type
    }
    
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
    
    public function clearCache()
    {
        $this->map = [];
        Cache::forget('Interadmin.routeMap');
    }
    
    public function saveCache()
    {
        Cache::forever('Interadmin.routeMap', $this->map);
    }
}
