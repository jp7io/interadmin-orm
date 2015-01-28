<?php

namespace Jp7\Former;

class Collection {
	protected $element;
	protected $options = [];
	protected $blank = 'Selecione';

	function __construct($element) {
		$this->element = $element;
	}

	function blank($text) {
		$this->blank = $text;
		return $this;
	}

	function options($list) {
		if ($list instanceof \Illuminate\Support\Collection) {
			$list = $list->lists('nome', 'id');
		}
		$this->options = $list;
		return $this;
	}

	public function __toString() {
		$this->options = ['' => $this->blank] + $this->options;
		return $this->element->options($this->options)->__toString();
	}

	function __call($method, $arguments) {
		call_user_func_array([$this->element, $method], $arguments);
		return $this;
	}
}