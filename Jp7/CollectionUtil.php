 <?php
 
 namespace Jp7;
/**
 * Class for handling collections of objects.
 *
 */
class CollectionUtil {
 	
	/**
	 * Keys are strings.
	 * 
	 * @param array $array
	 * @param string $clause
	 * @return array 
	 */
	public static function separate($array, $clause) {
		$separated = array();

		$properties = explode('.', $clause);
		foreach ($array as $item) {
			$key = $item;
			foreach ($properties as $property) {
				$key = @$key->$property;
			}
			$separated[$key][] = $item;
		}
		return $separated;
	}
	
	public static function getFieldsValues($array, $fields, $fields_alias) {
		if (count($array) > 0) {
			$first = reset($array);
			
			$tipo = $first->getType();
			$retornos = $tipo->find(array(
				'class' => 'InterAdmin',
				'fields' => $fields,
				'fields_alias' => $fields_alias,
				'where' => array('id IN (' . implode(',', $array) . ')'),
				'order' => 'FIELD(id,' . implode(',', $array) . ')'
				//'debug' => true
			));
			foreach ($retornos as $key => $retorno) {
				$array[$key]->attributes = $retorno->attributes + $array[$key]->attributes;
			}
		}
	}
	
	public static function eagerLoad($records, $relationships) {
		if (!is_array($relationships)) {
			$relationships = array($relationships);
		}
		$relationship = array_shift($relationships);
		
		if (!$records) {
			return false;
		}
		$model = reset($records);
		
		if ($data = $model->getType()->getRelationshipData($relationship)) {
			if ($data['type'] == 'select') {
				// select.id = record.select_id
				$indexed = self::separate($records, $relationship . '.id');
				
				$rows = $data['tipo']
					->records()
					->whereIn('id', array_keys($indexed))
					->all();
				
				if ($relationships) {
					self::eagerLoad($rows, $relationships);
				}
				foreach ($rows as $row) {
					foreach ($indexed[$row->id] as $record) {
						$record->$relationship = $row;
					}
					unset($row);
				}
			} elseif ($data['type'] == 'children') {
				// child.parent_id = parent.id
				$data['tipo']->setParent(null);
				$children = $data['tipo']
					->whereIn('parent_id', $records)
					->all();
				if ($relationships) {
					self::eagerLoad($children, $relationships);
				}
				$children = self::separate($children, 'parent_id');
				
				foreach ($records as $record) {
					$record->setEagerLoad($relationship, $children[$record->id] ?: array());
				}
			} else {
				throw new Exception('Unsupported relationship type: "' . $data['type'] . '" for class ' . get_class($model) . ' - ID: ' . $model->id);
			}
		} else {
			throw new Exception('Unknown relationship: "' . $relationship . '" for class ' . get_class($model) . ' - ID: ' . $model->id);
		}		
	}
}