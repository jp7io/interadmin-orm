<?php

namespace Jp7\Laravel;

use Jp7\MethodForwarder;
use Jp7\Interadmin\ClassMap;
use Jp7\Interadmin\Type;
use LaravelLocalization;
use Route;
use Cache;
use Closure;
use App;

class Router extends MethodForwarder
{
    /**
     * @var array [id_tipo => route basename]
     */
    protected $map = [];
    protected $cachefile = 'bootstrap/cache/routemap.cache';
    protected $locale;
    
////
//// Cache functions: Type map will work even when Laravel routes are cached
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
//// Map functions: Read/write to the type map
////
     
    private function addType($id_tipo, $slug)
    {
        $map = &$this->map[$this->getLocale()];
        $map = $map ?: [];
        if (!is_numeric($id_tipo)) {
            // Get id_tipo from class
            $id_tipo = ClassMap::getInstance()->getClassIdTipo($id_tipo);
        }
        // Saving routes for each id_tipo
        $groupRoute = str_replace('/', '.', trim($this->getLastGroupPrefix(), '/'));
        // Avoid setting same id_tipo twice
        if (!array_key_exists($id_tipo, $map)) {
            $map[$id_tipo] = ($groupRoute ? $groupRoute . '.' : '') . $slug;
            return true; // map entry set
        }
        return false; // already existed route for this type
    }
    
    /**
     * @param  int $id_tipo
     * @param  string $action
     * @return Route
     */
    public function getRouteByTypeId($id_tipo, $action = 'index')
    {
        $map = &$this->map[$this->getLocale()];
        $map = $map ?: [];
        if (!isset($map[$id_tipo])) {
            throw new RouteException('There is no route registered for id_tipo: ' . $id_tipo);
        }
        $mappedRoute = $map[$id_tipo];
        $routePrefix = ($mappedRoute != '/') ? $mappedRoute . '.' : '';
        
        return $this->target->getRoutes()->getByName($routePrefix . $action);
    }
    /**
     * @param  string $routeBasename
     *
     * @return Type
     */
    public function getTypeByRouteBasename($routeBasename)
    {
        $map = &$this->map[$this->getLocale()];
        $map = $map ?: [];
        $id_tipo = array_search($routeBasename, $map);
        if ($id_tipo) {
            return Type::getInstance($id_tipo);
        }
    }
    /**
     * @param  Route $route
     * @return Type
     */
    public function getTypeByRoute($route)
    {
        $basename = $this->getRouteBasename($route);
        return $this->getTypeByRouteBasename($basename);
    }
    
    /**
     * @return array [id_tipo => route basename]
     */
    public function getTypeMap()
    {
        return $this->map;
    }
    
////
//// Route override: Adds default values for methods
////
    
    /**
     * Adds conventions for controllers and 'only' option:
     *     r::resource('places')
     * Will behave like:
     *     r::resource('places', 'PlacesController', ['only' => ['index', 'show']])
     *
     * @param  string $name         Resource name
     * @param  string $controller   Controller class
     * @param  array  $options      Array of options
     */
    public function resource($name, $controller = null, array $options = [])
    {
        if (!is_string($controller) && empty($options)) {
            // Called like resource($name, $options)
            $options = $controller;
            $controller = null;
        }
        if (is_null($controller)) {
            $controller = $this->getControllerClass($name);
        }
        if (empty($options['only'])) {
            $options['only'] = $this->getControllerActions($controller);
        }
        if (isset($options['id_tipo'])) {
            $this->addType($options['id_tipo'], $name); // Maps [id_tipo => route basename]
        }
        return parent::resource($name, $controller, $options);
    }
    
    /**
     * @param  string $name Resource name such as 'places'
     * @return string       Controller name such as 'PlacesController'
     */
    protected function getControllerClass($name)
    {
        if ($name === '/') {
            $controller = 'Index';
        } else {
            $parts = explode('.', $name);
            $parts = array_map('studly_case', $parts);
            $controller = implode('\\', $parts);
        }
        $controller .= 'Controller';
        return $controller;
    }
    
    protected function getControllerActions($classBasename)
    {
        $stack = $this->getGroupStack();
        $namespace = end($stack)['namespace'];
        $class = $namespace.'\\'.$classBasename;
        if (!class_exists($class)) {
            echo 'Controller not found: '.$class.PHP_EOL;
            /*
            Create all controllers:
            \Artisan::call('make:controller', [
                'name' => str_replace('App\Http\Controllers\\', '', $class)
            ]);
            */
            return [];
        }
        $validActions = ['index', 'show', 'create', 'store', 'update', 'destroy', 'edit'];
        return array_intersect(get_class_methods($class), $validActions);
    }
    
    /**
     * Adds conventions for 'namespace':
     *     r::group(['prefix' => 'contact'], $callback)
     * Will behave like:
     *     r::group(['prefix' => 'contact', 'namespace' => 'Contact'], $callback)
     *
     * @param  array   $attributes
     * @param  Closure $callback
     */
    public function group(array $attributes, Closure $callback)
    {
        if (!empty($attributes['prefix']) && !array_key_exists('namespace', $attributes)) {
            $attributes['namespace'] = studly_case($attributes['prefix']);
        }
        return parent::group($attributes, $callback);
    }
    

////
//// Localization: allows caching routes with localization
////
    
    protected function getLocale()
    {
        // route creation: $this->locale
        // route resolution: App::getLocale()
        return is_null($this->locale) ? App::getLocale() : $this->locale;
    }
    
    public function languages(Closure $callback)
    {
        foreach (LaravelLocalization::getSupportedLanguagesKeys() as $locale) {
            $this->locale = $locale; // Used as map key
            if ($locale === LaravelLocalization::getDefaultLocale()) {
                $prefix = '';
            } else {
                $prefix = $locale;
            }
            $this->group(['prefix' => $prefix, 'namespace' => null], $callback);
        }
        $this->locale = null;
    }
    
////
//// Dynamic routes: Creates routes automatically from InterAdmin's sections
////
    
    /**
     * Creates routes automatically from InterAdmin's sections.
     * Only creates routes if Type has 'menu' checked.
     *
     * @param  Type     $section        Should use trait Jp7\Laravel\Routable
     * @param  array    $currentPath    Used for recursivity
     * @return void
     */
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
        
            if ($this->addType($section->id_tipo, $slug)) {
                // won't enter here if there is already a route for this type
                $controllerClass = $section->getControllerBasename();
                Route::resource($slug, $controllerClass, [
                    'only' => $this->getControllerActions($controllerClass)
                ]);
            }
        }
    }
    
////
//// Helpers: Get extra information from Laravel routes
////

    /**
     * Returns a list of variable placeholders from routes.
     * Route: schools/{schools}/courses/{courses}
     * Variables: ['schools', 'courses']
     *
     * @param  Route $route
     * @return array
     */
    public function getVariablesFromRoute($route)
    {
        $matches = [];
        preg_match_all('/{(\w+)}/', $route->getUri(), $matches);

        return $matches[1] ?: [];
    }
    
    /**
     * Parses route basename
     *
     * Route: 'services.contact.index'
     * Basename: 'services.contact'
     *
     * @param Route $route
     * @return string
     */
    public function getRouteBasename($route)
    {
        $parts = explode('.', $route->getName());
        array_pop($parts);
        return implode('.', $parts);
    }
    
    /**
     * Map URI to breadcrumb of objects
     * Allows custom resolution of {placeholder} to Objects.
     *
     * @param string $uri
     * @param Closure $resolveParameter     Closure will be called each time a {placeholder} is found
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

                $type = $this->getTypeByRouteBasename($routeName);
                if ($type && $parameter) {
                    $type->setParent($parameter);
                }
                $breadcrumb[] = $type;
            }
        }

        return $breadcrumb;
    }
}
