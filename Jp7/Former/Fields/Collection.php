<?php

namespace Jp7\Former\Fields;

class Collection extends \Former\Form\Fields\Select {
	protected $blank = 'Selecione';
	protected $options = [];
	
	function blank($text) {
		$this->blank = $text;
		return $this;
	}
	
	function options($list, $selected = NULL, $valuesAsKeys = false) {
		if ($list instanceof \Jp7\Interadmin\Query\Base) {
			$list = $list->all();
		}
		if ($list instanceof \Illuminate\Support\Collection) {
			if ($first = $list->first()) {
				$varchar_key = $first->getType()->getCamposAlias('varchar_key');
				
				$list = $list->lists($varchar_key, 'id');
			}
		}
		$this->options = $list;
		return $this;
	}
	
	public function render() {
		$this->options = ['' => $this->blank] + $this->options;
		parent::options($this->options);
		return parent::render();
	}
}