<?php

namespace Jp7\Laravel;
use Route;

class Router {
	
	public function createRoutes($section, $namespace = '') {
		if ($subsections = $section->getChildrenMenu()) {
			Route::group(['namespace' => $section->getNamespace(), 'prefix' => $section->getControllerUrl()], function() use ($section, $subsections) {
				foreach ($subsections as $subsection) {
					$this->createRoutes($subsection, $section->getNamespace());
				}
			});
		}
		if ($section->id_tipo > 0) {
			// Somente para debug na barra do laravel
			$dynamic = $this->_checkTemplate($section) ? 'dynamic' : 'static';
			
			Route::group(['before' => 'setTipo:' . $section->id_tipo . '|' . $dynamic], function() use ($section) {
				Route::resource($section->getControllerUrl(), $section->getControllerName(), ['only' => ['index', 'show']]);
			});
		}
	}
	
	protected function _checkTemplate($section) {
		$dynamic = false;
		if (!class_exists($section->getControllerNameWithNamespace())) {
			$dynamic = true;
			
			if (starts_with($section->template, '/')) {
				$section->template = substr($section->template, 1); //  Corrigir lasa assim que possivel
			}
			if ($section->template) {
				$templateParts = explode('/', $section->template);
				$templateParts = array_map('studly_case', $templateParts);
					
				$templateController = implode('\\', $templateParts) . 'Controller';
			} else {
				$templateController = '';
			}
		
			if ($templateController && class_exists($templateController)) {
				eval($section->getFullNamespace() . "class {$section->getControllerName()} extends \\$templateController { }");
			} else {
				eval($section->getFullNamespace() . "class {$section->getControllerName()} extends \\BaseController { public function index() { }}");
			}
		}
		return $dynamic;
	}
}