<?php

class Jp7_Model_TipoAbstract extends InterAdminTipo {
	public $isSubTipo = false;
	
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
					$child->nome = 'Modelo - ' . $model_id_tipo;
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
	
	/**
	 * Trigger executado após inserir um tipo com esse modelo.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function createChildren(InterAdminTipo $tipo) {
		
	}
	
	public function createBoxesSettingsAndIntroduction(InterAdminTipo $tipo) {
		if (!$tipo->getFirstChildByModel('Introduction')) {
			$introduction = $tipo->createChild('Introduction');
			$introduction->nome = 'Introdução';
			$introduction->ordem = -30;
	        $introduction->save();
		}
		if (!$tipo->getFirstChildByModel('Boxes')) {
			$boxes = $tipo->createChild('Boxes');
			$boxes->nome = 'Boxes';
			$boxes->ordem = -20;
	        $boxes->save();
		}
		if (!$tipo->getFirstChildByModel('Settings')) {
			$settings = $tipo->createChild('Settings');
			$settings->nome = 'Configurações';
			$settings->ordem = -10;
	        $settings->save();
		}
	}
	
	/**
	 * Returns the fields when editting the boxes.
	 * 
	 * @param Jp7_Box_BoxAbstract $box
	 * @return string	HTML
	 */
	public function getEditorFields(Jp7_Box_BoxAbstract $box) {
		// do nothing
	}
}