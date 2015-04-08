<?php

namespace Jp7\Laravel\Controller;

use Jp7_InterAdmin_Exception as InterAdminException;
use Route, InterAdminTipo, InterAdmin;

trait RecordTrait {
	/**
	 * @var \Jp7\Interadmin\Query\Base
	 */
	protected $scope = null;
	
	public function constructRecordTrait() {
		$this->beforeFilter('@setScope');
		$this->beforeFilter('@setType');
		$this->beforeFilter('@setRecord', ['only' => ['show', 'edit', 'update', 'destroy']]);
	}
		    
	public function setScope($route) {
		$uri = $this->_getResourceUri($route);

		$breadcrumb = Route::uriToBreadcrumb($uri, function($type, $segment) use ($route) {
			$slug = $route->getParameter(trim($segment, '{}'));
			return $type->records()->find($slug);
		});
		
		if ($type = end($breadcrumb)) {
			$parent = $type->getParent();
			if ($parent instanceof InterAdmin && !$parent->hasChildrenTipo($type->id_tipo)) {
				throw new InterAdminException('It seems this route has a'
					. ' special structure. You need to define a custom '
					. 'setScope() to handle this.');
			}
			$this->scope = $type->records();
		}
	}
	
	protected function _getResourceUri($route) {
		$uri = $route->getUri();
		
		if (!in_array($this->action, array('index', 'store', 'show', 'update', 'destroy'))) {
			$uri = dirname($uri); // Remove extra directory
		}
		if ($this->isRecordAction()) {
			$uri = dirname($uri); // Do not resolve $record yet
		}
		return $uri;
	}

	public function setType() {
		if (!$this->scope) {
			throw new InterAdminException('setScope() could not resolve the'
				. ' type associated with this URI. You need to map it on routes.php.'
				. ' You can also define a custom setScope() or setType()');
		}
		$this->type = $this->scope->type();
	}
	
	public function setRecord($route) {
		$reflection = new \ReflectionMethod($this, $this->action);
		if (count($reflection->getParameters()) > 0)  {
			return; // tem parametros -> achar record no controller
		}
		if ($this->scope) {
			$parameters = $route->parameters();
			if (count($parameters) > 0) {
				$slug = end($parameters);				
				$query = clone $this->scope;
				$this->record = $query->findOrFail($slug);
			}
		}	
	}

	protected function isRecordAction() {
		return in_array($this->action, array('show', 'edit', 'update', 'destroy'));
	}
}