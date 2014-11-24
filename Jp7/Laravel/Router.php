<?php

namespace Jp7\Laravel;

class Router extends \Illuminate\Routing\Router {
	
	protected $mapIdTipos = [];
	
	public function createDynamicRoutes($section, $currentPath = []) {
		if ($subsections = $section->getChildrenMenu()) {
			if ($section->isRoot()) {
				foreach ($subsections as $subsection) {
					$this->createDynamicRoutes($subsection, $currentPath);
				}
			} else {
				$this->group(['namespace' => $section->getStudly(), 'prefix' => $section->getSlug()], function() use ($section, $subsections, $currentPath) {
					//$currentPath[] = $section->getSlug();
					foreach ($subsections as $subsection) {
						$this->createDynamicRoutes($subsection, $currentPath);
					}
				});
			}
		}
		if (!$section->isRoot()) {
			// Somente para debug na barra do laravel
			$this->resource($section->getSlug(), $section->getControllerBasename(), [
				'only' => ['index', 'show'],
				'id_tipo' => $section->id_tipo,
				'dynamic' => $this->_checkTemplate($section) ? '|dynamic' : ''						
			]);
		}
	}
	
	public function resource($name, $controller, array $options = array()) {
		if ($options['id_tipo']) {
			$groupRoute = str_replace('/', '.', $this->getLastGroupPrefix()); 
			$this->mapIdTipos[$options['id_tipo']] = ($groupRoute ? $groupRoute . '.' : '') . $name;
			
			$this->group(['before' => 'setTipo:' . $options['id_tipo'] . $options['dynamic']], function() use ($name, $controller, $options) {
				parent::resource($name, $controller, $options);
			});
		} else {
			parent::resource($name, $controller, $options);
		}
	}	
	
	public function getRouteByIdTipo($id_tipo, $action = 'index') {
		$mappedRoute = $this->mapIdTipos[$id_tipo];
		return $this->routes->getByName($mappedRoute . '.' . $action);
	}
	
	public function getMapIdTipos() {
		return $this->mapIdTipos;
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