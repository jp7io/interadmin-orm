<?php

abstract class Jp7_Tag {
	protected $value;
	protected $attrs;
	
	public function __construct($value, $attrs = array()) {
		$this->value = $value;
		$this->attrs = $attrs;
	}
	public function __toString() {
		$tagName = $this->getTagName();
		return '<' . $tagName . $this->parseAttrs($this->attrs) . '>' . $this->val() . '</' . $tagName . '>';
	}
	
	public function val($value = null){
		if (is_null($value)) {
			return $this->value;
		} else {
			$this->value = $value;
			return $this;
		}
	}	
	
	/**
	 * @return array
	 */
	public function getAttrs(){
		return $this->attrs;
	}
	/**
	 * @param array $attrs
	 */
	public function setAttrs($attrs){
		$this->attrs = $attrs;
	}
	
	public function attr($name, $value = null) {
		if (is_null($value)) {
			return $this->attrs[$name];
		} else {
			$this->attrs[$name] = $value;
			return $this;
		}
	}
	
	public function css($property, $value = null) {
		if (is_null($value)) {
			return $this->attrs['style'][$property];
		} else {
			$this->attrs['style'][$property] = $value;
			return $this;
		}
	}
	
	public function parseAttrs($array) {
		$attrs = '';
		foreach ($array as $key => $value) {
			if (!is_scalar($value)) {
				$value = $this->parseStyle($value);
			}
			$attrs .= ' ' . $key . '="' . $value . '"';
		}
		return $attrs;
	}
	
	public function parseStyle($array) {
		$attrs = '';
		foreach ($array as $key => $value) {
			$attrs .= $key . ':' . $value . ';';
		}
		return $attrs;
	}
	
	/**
	 * @return string
	 */
	public function getTagName() {
		return strtolower(end(explode('_', get_called_class())));
	}
}