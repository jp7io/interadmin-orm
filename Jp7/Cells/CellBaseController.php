<?php

namespace Jp7\Cells;
use Illuminate\View\Factory;
use Jp7\Laravel\Controller;

abstract class CellBaseController extends \Torann\Cells\CellBaseController {

	public function __construct(Factory $view, $caching_disabled) {
		parent::__construct($view, $caching_disabled);
		// CoC - name is always snake_case of the class name
		$this->name = snake_case(substr(get_called_class(),4), '-');
		// Current section
		$this->tipo = Controller::getCurrentController()->tipo;
	}

	public function init() {
		// make it not required
	}

	public function initCell( $viewAction = 'display' )	{
		$this->viewAction = $viewAction;

		$this->init();

		$this->$viewAction();

		// Use data on $this
		$this->data = array_merge($this->attributes, (array) $this);
	}

	public function isCached() {
		return \Cache::has($this->getCacheKey());
	}

	public function getCacheKey() {
		$path = "$this->name.$this->viewAction";
		return "Cells.{$path}.{$this->uniqueCacheId}";
	}
}


