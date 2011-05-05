<?php

class Jp7_Model_TipoAbstract extends InterAdminTipo {
	/**
	 * $id_tipo não é inteiro
	 * @return 
	 */
	public function __construct() {
		
	}
	
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
		if (is_string($fields)) {
			return $this->attributes[$fields]; 
		} elseif (is_array($fields)) {
			return (object) array_intersect_key($this->attributes, array_flip($fields));
		}
	}
	
	protected function _findChildByModel($model_id_tipo) {
		$child = InterAdminTipo::findFirstTipo(array(
			'where' => array(
				"model_id_tipo = '" . $model_id_tipo . "'",
				"admin <> ''"
			)
		));
		if (!$child) {
			// Tenta criar o tipo filho caso ele não exista
			$sistemaTipo = InterAdminTipo::findFirstTipo(array(
				'where' => array(
					"nome = 'Sistema'",
					"admin <> ''"
				)
			));
			if ($sistemaTipo) {
				$classesTipo = $sistemaTipo->getFirstChildByNome('Classes');
				if ($classesTipo) {
					$child = new InterAdminTipo();
					$child->parent_id_tipo = $classesTipo->id_tipo;
					$child->model_id_tipo = $model_id_tipo;
					$child->nome = $model_id_tipo;
					$child->mostrar = 'S';
					$child->admin = 'S';
					$child->save();
					return $child;
				}
			}
			throw new Exception('Could not find a Tipo using the model "' . $model_id_tipo . '". You need to create one in Sistema/Classes.');
		} else {
			return $child;
		}
	}
}