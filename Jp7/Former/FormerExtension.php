<?php

namespace Jp7\Former;
use Debugbar;

/**
 * Add InterAdmin settings on former automatically
 */
class FormerExtension {
	use RowTrait, DecoratorTrait;
	
	private $type;
	private $former;
	
	public function __construct(\Former\Former $former) {
		if ($errors = app()['session']->get('errors')) {
			if (class_exists('Debugbar')) {
				Debugbar::error($errors->all());
			}
		}
		
		$this->former = $former;
	}

	public function __call($method, $arguments) {
		$result = call_user_func_array([$this->former, $method], $arguments);

		if ($result instanceof \Former\Traits\Field) {
			$this->decorateField($result);

			if ($this->type) {
				$this->decorateTypeField($result);
			}
		}
		return $result;
	}

	public function type(\InterAdminTipo $type) {
		$this->type = $type;
	}
	
	public function open() {
		$form = $this->former->open();
		if ($this->type) {
			$form->rules($this->type->getRules());
			if ($this->type->getRoute('store')) {
				$form->action($this->type->getUrl('store'));
			}
		}
		return $form;
	}

	public function close() {
		$this->type = null;
		
		return $this->former->close();
	}

	private function decorateTypeField($field) {
		// Settings from InterAdmin
		if ($alias = $field->getName()) {
			$campos = $this->type->getCampos();
			$aliases = array_flip($this->type->getCamposAlias());
			
			if (isset($aliases[$alias])) {
				$name = $aliases[$alias];
				$campo = $campos[$name];

				// Set label
				$label = \InterAdminField::getCampoHeader($campo);
				$field->label($label);
				
				// Populate options
				if ($field->getType() === 'collection' && starts_with($name, 'select_')) {
					$campoType = $campo['nome'];
					$options = $campoType->records();
					$field->options($options);
				}
			}
		}
	}
}
	