<?php

class Jp7_InterAdmin_Util {
	
	protected static $_default_vars = array('parent_id', 'date_publish', 'date_insert', 'date_expire', 'date_modify', 'log', 'publish', 'deleted');
	
	/**
	 * Exports records and their children.
	 * 
	 * @param 	InterAdminTipo 	$tipoObj	InterAdminTipo where the records are.
	 * @param 	array         	$ids		Array de IDs.
	 * @return 	InterAdmin[]
	 */
	public static function export(InterAdminTipo $tipoObj, array $ids, $use_id_string = false) {
		$options = array(
			'fields' => array_merge(array('*'), self::$_default_vars),
			'fields_alias' => false
		);
		
		$optionsRegistros = $options;
		if ($use_id_string) {
			$optionsRegistros = self::_prepareOptionsForIdString($optionsRegistros, $tipoObj);
		}		
		$exports = $tipoObj->getInterAdmins($optionsRegistros + array(
			'where' => "id IN(" . implode(',', $ids) . ')'
		));
		
		$tiposChildren = $tipoObj->getInterAdminsChildren();
		foreach ($exports as $export) {
			$export->_children = array();
			foreach ($tiposChildren as $tipoChildren) {
				$optionsChildren = $options;
				$tipoChildren = $export->getChildrenTipo($tipoChildren['id_tipo']);
				if ($use_id_string) {
					$optionsChildren = self::_prepareOptionsForIdString($optionsChildren, $tipoChildren);
				}
				$export->_children[$tipoChildren->id_tipo] = $tipoChildren->getInterAdmins($optionsChildren);
			}
		}
		return $exports;
	}
	
	protected static function _prepareOptionsForIdString($options, $tipo) {
		$campos = $tipo->getCampos();
		foreach ($campos as $campo) {
			if (strpos($campo['tipo'], 'select_') === 0 && strpos($campo['tipo'], 'select_multi_') !== 0 && !in_array($campo['xtra'], InterAdminField::getSelectTipoXtras())) {
				$options['fields'][$campo['tipo']] = array('id_string');
			}
		}
		return $options;
	}
	
	protected static function _importAttributeFromIdString($record) {
		foreach ($record->attributes as $attributeName => $attribute) {
			if ($attribute instanceof InterAdmin && $attribute->id_string) {
				if ($attributeTipo = $attribute->getTipo()) {
					$record->$attributeName = $attributeTipo->getInterAdminByIdString($attribute->id_string);
				}
			}
		}
	}
	
	/**
	 * Imports records and their children with a new ID.
	 * 
	 * @param 	array	$records
	 * @param 	int 	$id_tipo
	 * @param 	int 	$parent_id 			[optional] defaults to 0
	 * @param 	bool 	$import_children 	[optional] defaults to TRUE
	 * @return 	void
	 */
	public static function import(array $records, $id_tipo, $parent_id = 0, $import_children = true, $use_id_string = false) {
		foreach ($records as $record) {
			unset($record->id);
			
			$record->parent_id = $parent_id;
			$record->id_tipo = $id_tipo;

			if ($use_id_string) {
				self::_importAttributeFromIdString($record);
			}
			
			$record->save();
			
			if ($import_children) {
				foreach ($record->_children as $tipo_children) {
					foreach ($tipo_children as $child) {
						unset($child->id);
						$child->parent_id = $record->id;
						
						if ($use_id_string) {
							self::_importAttributeFromIdString($child);
						}
						
						$child->save();
					}
				}
			}
		}
	}
		
}