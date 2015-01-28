<?php

namespace Jp7\Cells;
use Illuminate\View\Factory;
use Jp7\Laravel\Controller;

abstract class CellBaseController extends \Torann\Cells\CellBaseController {

	private $_returned;
	public $type;
	public $record;

	// Multiple calls to a cell will run __construct only once
	public function __construct(Factory $view, $caching_disabled) {
		parent::__construct($view, $caching_disabled);
		// CoC - name is always snake_case of the class name
		$this->name = snake_case(substr(get_called_class(),4), '-');
				
		if (class_exists('Debugbar')) \Debugbar::startMeasure('Cell ' . $this->name);
	}

	public function setSharedVariables() {
		// Current section
		$this->type = Controller::getCurrentController()->type;
		$this->record = Controller::getCurrentController()->record;
	}

	public function init() {
		// it makes init optional
	}

	public function initCell( $viewAction = 'display' )	{
		$this->viewAction = $viewAction;

		$this->setSharedVariables();
		$this->init();

		$this->_returned = $this->$viewAction();
		
		// Use data on $this
		$this->data = array_merge($this->attributes, (array) $this);
		
		if (class_exists('Debugbar')) \Debugbar::stopMeasure('Cell ' . $this->name);
	}
	
	public function displayView() {
		if ($this->_returned) {
			return $this->_returned;
		} else {
			return parent::displayView();
		}
	}
	
	public function isCached() {
		return $this->cache && \Cache::has($this->getCacheKey());
	}

	public function getCacheKey() {
		$path = "$this->name.$this->viewAction";
		return "Cells.{$path}.{$this->uniqueCacheId}";
	}
}

