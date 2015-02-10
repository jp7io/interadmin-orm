<?php

namespace Jp7\Former;

class Extension {
	private $type;
	private $decorator;
	private $row;
	private $former;

	public function __construct(\Former\Former $former) {
		$this->former = $former;
	}

	public function __call($method, $arguments) {
		$result = call_user_func_array([$this->former, $method], $arguments);

		if ($result instanceof \Former\Traits\Field) {
			$this->decorateField($result);
		}

		return $result;
	}

	public function populate($values) {
		$this->type = $values->getType();

		$this->former->populate($values);
	}

	public function close() {
		$this->type = null;
		
		$this->former->close();
	}

	private function decorateField($field) {
		if ($this->decorator) {
			$this->decorator->_runOn($field);
		}

		if ($this->type) {
			if ($alias = $field->getName()) {
				$this->decorateInteradmin($alias, $field);
			}
		}
	}

	private function decorateInteradmin($alias, $field) {
		$campos = $this->type->getCampos();
		$aliases = array_flip($this->type->getCamposAlias());
				
		if (isset($aliases[$alias])) {
			$name = $aliases[$alias];

			$label = \InterAdminField::getCampoHeader($campos[$name]);
			$field->label($label);
			
			if (starts_with($name, 'select_')) {
				$campoType = $campos[$name]['nome'];
				$options = $campoType->records()->all();
				$field->options($options);
			}
		}
	}

	public function decorator() {
		$this->decorator = new Decorator;
		return $this->decorator;
	}

	public function closeDecorator() {
		$this->decorator = null;
	}

	public function row() {
		$this->row = new Row();
		return $this->row;
	}

	public function closeRow() {
		$html = $this->row->close();
		$this->row = null;
		return $html;
	}
}
	