<?php

namespace Jp7\Laravel;
use Route;

class Router {
	
	public function createRoutes($section) {
		if ($subsections = $section->getChildrenMenu()) {
			$closure = function() use ($section, $subsections) {
				foreach ($subsections as $subsection) {
					$this->createRoutes($subsection);
				}
			};
			if ($section->isRoot()) {
				$closure();
			} else {
				Route::group(['namespace' => $section->getStudly(), 'prefix' => $section->getSlug()], $closure);
			}
		}
		if (!$section->isRoot()) {
			// Somente para debug na barra do laravel
			$dynamic = $this->_checkTemplate($section) ? 'dynamic' : 'static';
			
			Route::group(['before' => 'setTipo:' . $section->id_tipo . '|' . $dynamic], function() use ($section) {
				Route::resource($section->getSlug(), $section->getControllerBasename(), ['only' => ['index', 'show']]);
			});
		}
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