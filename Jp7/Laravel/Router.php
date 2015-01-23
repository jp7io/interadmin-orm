<?php

namespace Jp7\Laravel;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Jp7\MethodLogger;

class Router extends \Illuminate\Routing\Router {
	
	protected $mapIdTipos = [];
	protected $logger;

	public function __construct(Dispatcher $events, Container $container = null) {
		$this->logger = new MethodLogger($this);
		return parent::__construct($events, $container);
	}

	public function createDynamicRoutes($section, $currentPath = [], $firstCall = true) {
		if ($firstCall && \Cache::has('Interadmin.routes')) {
			$this->logger->_replay(\Cache::get('Interadmin.routes'));
			return;
		}
		if ($subsections = $section->getChildrenMenu()) {
			if ($section->isRoot()) {
				foreach ($subsections as $subsection) {
					$this->createDynamicRoutes($subsection, $currentPath, false);
				}
			} else {
				$this->logger->updateGroupStack(['namespace' => $section->getStudly(), 'prefix' => $section->getSlug()]);

				//$currentPath[] = $section->getSlug();
				foreach ($subsections as $subsection) {
					$this->createDynamicRoutes($subsection, $currentPath, false);
				}
				
				$this->logger->popGroupStack();
			}
		}
		if (!$section->isRoot()) {
			// Somente para debug na barra do laravel
			$this->logger->resource($section->getSlug(), $section->getControllerBasename(), [
				'only' => ['index', 'show'],
				'id_tipo' => $section->id_tipo,
				'dynamic' => $this->_checkTemplate($section)					
			]);
		}
		if ($firstCall) {
			// save cache after running all routes
			\Cache::put('Interadmin.routes', $this->logger->_getLog(), 60);
		}
	}
	
	public function updateGroupStack(array $attributes)  {
		return parent::updateGroupStack($attributes);
	}

	public function popGroupStack() {
		array_pop($this->groupStack);
	}

	public function resource($name, $controller, array $options = array()) {
		if (isset($options['id_tipo'])) {
			// Saving routes for each id_tipo
			$groupRoute = str_replace('/', '.', $this->getLastGroupPrefix());
			if (!array_key_exists($options['id_tipo'], $this->mapIdTipos)) {
				$this->mapIdTipos[$options['id_tipo']] = ($groupRoute ? $groupRoute . '.' : '') . $name;
			}
			
			$before = empty($options['dynamic']) ? '' : 'dynamic';
			
			$this->group(['before' => $before], function() use ($name, $controller, $options) {
				parent::resource($name, $controller, $options);
			});
		} else {
			parent::resource($name, $controller, $options);
		}
	}
	
	public function type($name, $id_tipo) {
		// $type = call_user_func([$className, 'type']);
		$controller = studly_case(str_replace('.', '\\ ', $name )) . 'Controller';
		$this->resource($name,  $controller, [
			'id_tipo' => $id_tipo,
			'only' => ['index', 'show']
		]);
	}
	
	public function getRouteByIdTipo($id_tipo, $action = 'index') {
		$mappedRoute = $this->mapIdTipos[$id_tipo];
		return $this->routes->getByName($mappedRoute . '.' . $action);
	}
	
	public function getIdTipoByRoute($route) {
		return array_search($route, $this->mapIdTipos);
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
	
	public function getTypeByRoute($routeName) {
		$id_tipo = $this->getIdTipoByRoute($routeName);
		if (!$id_tipo) {
			return null;
		}
		return \InterAdminTipo::getInstance($id_tipo);
	}
	
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
	
}