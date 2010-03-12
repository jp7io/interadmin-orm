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
	public static function export(InterAdminTipo $tipoObj, array $ids) {
		
		$options = array(
			'fields' => array_merge(array('*'), self::$_default_vars),
			'fields_alias' => false
		);
		/*
		$selects = array_filter($tipoObj->getCamposNames(), create_function('$a', ' return strpos($a, \'select_\') === 0 && strpos($a, \'select_multi\') !== 0;'));
		foreach ($selects as $select) {
			$optionsRegistros['fields'][$select] = array('id_string');
		}
		*/
		$exports = $tipoObj->getInterAdmins($options + array('where' => "id IN(" . implode(',', $ids) . ')'));
		
		$tiposChildren = $tipoObj->getInterAdminsChildren();
		foreach ($exports as $export) {
			$export->_children = array();
			foreach ($tiposChildren as $tipoChildren) {
				$tipoChildren = $export->getChildrenTipo($tipoChildren['id_tipo']);
				/*
				$selects = array_filter($tipoChildren->getCamposNames(), create_function('$a', ' return strpos($a, \'select_\') === 0 && strpos($a, \'select_multi\') !== 0;'));
				foreach ($selects as $select) {
					$optionsChildren['fields'][$select] = array('id_string');
				}
				*/
				$export->_children[$tipoChildren->id_tipo] = $tipoChildren->getInterAdmins($options);
			}
		}
		return $exports;
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
	public static function import(array $records, $id_tipo, $parent_id = 0, $import_children = true) {
		foreach ($records as $record) {
			unset($record->id);
			
			$record->parent_id = $parent_id;
			$record->id_tipo = $id_tipo;
			
			/*
			if ($_POST['use_id_string']) {
				$selects = array_filter($record->getTipo()->getCamposNames(), create_function('$a',  'return strpos($a, \'select_\') === 0 && strpos($a, \'select_multi\') !== 0;'));
				foreach ($selects as $select) {
					if ($record->$select && $record->$select) {
					}
				}
			}
			*/
						
			$record->save();
			
			if ($import_children) {
				foreach ($record->_children as $tipo_children) {
					foreach ($tipo_children as $child) {
						unset($child->id);
						$child->parent_id = $record->id;							
						$child->save();
					}
				}
			}
		}
	}
		
}