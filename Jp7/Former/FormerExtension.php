<?php

namespace Jp7\Former;

use Former\Former as OriginalFormer;
use Debugbar, InterAdmin, InterAdminField;

/**
 * Add InterAdmin settings on former automatically
 */
class FormerExtension {
	use RowTrait, DecoratorTrait;
	
	private $model;
	private $former;
	
	public function __construct(OriginalFormer $former) {
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

			if ($this->model) {
				$this->decorateTypeField($result);
			}
		}
		return $result;
	}
	
	public function __get($property) {
		return $this->former->$property;
	}

	public function __set($property, $value) {
		$this->former->$property = $value;	
	}
	
	public function populate($model) {
		if ($model instanceof InterAdmin) {
			$this->model = $model;	
		}		
		return $this->former->populate($model);
	}
	
	public function open() {
		$form = $this->former->open();
		if ($this->model) {
			$form->rules($this->model->getRules());
			if ($this->model->getRoute('store')) {
				$form->action($this->model->getUrl('store'));
			}
		}
		return $form;
	}

	public function close() {
		$this->model = null;
		
		return $this->former->close();
	}

	private function decorateTypeField($field) {
		// Settings from InterAdmin
		if (!$alias = $field->getName()) {
			return;
		}
		
		$type = $this->model->getType();
		$campos = $type->getCampos();
		$aliases = array_flip($type->getCamposAlias());
		
		if (empty($aliases[$alias])) {
			return;
		}

		$name = $aliases[$alias];
		$campo = $campos[$name];

		// Set label
		$label = InterAdminField::getCampoHeader($campo);
		$field->label($label);
		
		// Populate options
		if (starts_with($name, 'select_')) {
			$this->populateOptions($field, $campo['nome']);
		}
	}

	private function populateOptions($field, $campoType) {
		if ($field->getType() === 'collection') {
			$field->options($campoType->records());
		} elseif ($field->getType() === 'radios') {
			$radios = [];
			foreach ($campoType->records()->all() as $record) {
				$radios[$record->getName()] = array('value' => $record->id);
			}
			$field->radios($radios);
		}
	}
}
	