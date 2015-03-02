<?php

namespace Jp7\Laravel;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Jp7\MethodLogger;
use Cache;
use InterAdminTipo;

class Router extends \Illuminate\Routing\Router {
	
	protected $mapIdTipos = [];
	protected $logger;

	public function __construct(Dispatcher $events, Container $container = null) {
		$this->logger = new MethodLogger($this);
		return parent::__construct($events, $container);
	}

	public function createDynamicRoutes($section, $currentPath = []) {
		if (Cache::has('Interadmin.routes')) {
			$this->logger->_replay(Cache::get('Interadmin.routes'));
			return;
		}
		
		// run routes without cache
		$this->_createDynamicRoutes($section, $currentPath);
		
		// save cache after running all routes
		Cache::put('Interadmin.routes', $this->logger->_getLog(), 60);
	}

	protected function _createDynamicRoutes($section, $currentPath = []) {
		$isRoot = $section->isRoot();

		if ($subsections = $section->getChildrenMenu()) {
			if (!$isRoot) {
				$this->logger->updateGroupStack([
					'namespace' => $section->getStudly(),
					'prefix' => $section->getSlug()
				]);
			}
			foreach ($subsections as $subsection) {
				$this->_createDynamicRoutes($subsection, $currentPath, false);
			}
			if (!$isRoot) {
				$this->logger->popGroupStack();
			}
		}
		if (!$isRoot) {
			$this->logger->resource($section->getSlug(), $section->getControllerBasename(), [
				'only' => $section->getRouteActions(),
				'id_tipo' => $section->id_tipo
			]);
		}
	}
	
	public function updateGroupStack(array $attributes)  {
		// Make it public for cache
		return parent::updateGroupStack($attributes);
	}

	public function popGroupStack() {
		// Make it public for cache
		array_pop($this->groupStack);
	}

	public function resource($name, $controller, array $options = array()) {
		if (isset($options['id_tipo'])) {
			// Saving routes for each id_tipo
			$groupRoute = str_replace('/', '.', $this->getLastGroupPrefix());
			if (!array_key_exists($options['id_tipo'], $this->mapIdTipos)) {
				$this->mapIdTipos[$options['id_tipo']] = ($groupRoute ? $groupRoute . '.' : '') . $name;
			}			
		}
		parent::resource($name, $controller, $options);
	}
	
	public function type($name, $className_Or_IdTipo, $function = null) {
		if ($function) {
			$this->group(['namespace' => studly_case($name), 'prefix' => $name], $function);
		}
		if (is_numeric($className_Or_IdTipo)) {
			$type = InterAdminTipo::getInstance($className_Or_IdTipo);
		} else {
			$type = call_user_func([$className_Or_IdTipo, 'type']);
		}

		$controller = studly_case(str_replace('.', '\\ ', $name )) . 'Controller';
		$this->resource($name,  $controller, [
			'id_tipo' => $type->id_tipo,
			'only' => $type->getRouteActions()
		]);
	}
		
	public function getRouteByIdTipo($id_tipo, $action = 'index') {
		if (!isset($this->mapIdTipos[$id_tipo])) {
			throw new \Exception('There is no route registered for id_tipo: ' . $id_tipo);
		}
		$mappedRoute = $this->mapIdTipos[$id_tipo];
		return $this->routes->getByName($mappedRoute . '.' . $action);
	}
	
	public function getIdTipoByRoute($route) {
		return array_search($route, $this->mapIdTipos);
	}

	public function getTypeByRoute($routeName) {
		$id_tipo = $this->getIdTipoByRoute($routeName);
		if (!$id_tipo) {
			return null;
		}
		return InterAdminTipo::getInstance($id_tipo);
	}

	public function getMapIdTipos() {
		return $this->mapIdTipos;
	}
	
	public function getVariablesFromRoute($route) {
		$matches = array();
		preg_match_all('/{(\w+)}/', $route->getUri(), $matches);
		return $matches[1] ?: array();
	}
	
	public function uriToBreadcrumb($uri, $resolveParameter) {
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
	
	/*	
	protected function _checkTemplate($section) {
		$dynamic = false;
		if (!class_exists($section->getControllerName())) {
			$dynamic = true;
			
			$templateController = '';
			if ($section->template) {
				$templateController = $this->_pathToNamespace($section->template) . 'Controller';
			}
			
			$namespace = $section->getNamespace();
			$namespaceCode = $namespace ? 'namespace ' . $namespace . ';' : '';
			
			if ($templateController && class_exists($templateController)) {
				eval($namespaceCode . "class {$section->getControllerBasename()} extends \\$templateController { }");
			} else {
				eval($namespaceCode . "class {$section->getControllerBasename()} extends \\BaseController { public function index() { }}");
			}
		}
		return $dynamic;
	}
	
	protected function _pathToNamespace($string) {
		if (starts_with($string, '/')) {
			// lasa está começando com /templates - Corrigir assim que possivel 
			$string = substr($string, 1);
		}
		
		$parts = explode('/', $string);
		$parts = array_map('studly_case', $parts);
		return implode('\\', $parts) . 'Controller';
	}
	*/
	
}