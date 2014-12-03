<?php
/**
 * Class for handling collections of objects.
 */
class Jp7_Collections {
 	/**
	 * Acts like a SELECT statement. Performs 'where', 'order', 'group' and 'limit'.
	 * 
	 * @param array $array
	 * @param array $options Available keys are 'where', 'order', 'group' and 'limit'.
	 * @return array Processed collection.
	 */
	public static function query($array, $options) {
		if ($array) {
			if ($options['where']) {
				$array = self::filter($array, $options['where']);
			}
			if ($options['group']) {
				$array = self::group($array, $options['group']);
			}
			if ($options['order']) {
				$array = self::sort($array, $options['order']);
			}
			if ($options['limit']) {
				$array = self::slice($array, $options['limit']);
			}
		}
		return $array;
	}
	/**
	 * Acts like a SQL GROUP BY  statement.
	 *
	 * @param array $array
	 * @param string $clause
	 * @return array
	 */
	public static function group($array, $clause) {
		$clause = preg_replace('/\s+/', ' ', $clause);
		$keys = explode(',', $clause);
		
		$novaArray = array();
		$hashExistente = array();
		
		foreach ($array as $item) {
			$hash = ':';
			foreach ($keys as $key) {
				$properties = explode('.', $key);
				$value = $item;
				foreach ($properties as $property) {
					$value = $value->$property;
				}

				$hash .= strtolower(jp7_normalize($value));
			}			
			if (!$hashExistente[$hash]) {
				$novaArray[] = $item;
				$hashExistente[$hash]++;
			}
		}
		return $novaArray;
	}
	/**
	 * Filters the array using SQL Where.
	 * 
	 * @param array $array
	 * @param string $clause Similar to SQL WHERE Clause, only supports simple comparations for now.
	 * @return array
	 */
	public static function filter($array, $clause, $debug = false) {
		return array_filter($array, self::_clauseToFunction($clause, $debug));
	}
	
	public static function detect($array, $clause, $debug = false) {
		$function = self::_clauseToFunction($clause);
		foreach ($array as $item) {
			if ($function($item)) {
				return $item;
			}
		}
	}
	
	private static function _clauseToFunction($clause, $debug = false) {
		if (is_array($clause)) {
			$clause = implode(' AND ', $clause);
		}
		$clause = preg_replace('/([^=!])(=)([^=])/', '\1\2=\3', $clause);
		$clause = preg_replace('/(?<!\')(\b[a-zA-Z_][a-zA-Z0-9_.]+\b)/', '$a->\1', $clause);
		// FIXME Fazer um parser melhor depois
		$clause = str_replace('.', '->', $clause);
		$clause = str_replace(' $a->OR ', ' OR ', $clause);
		$fnBody = 'return ' . $clause . ';';
		if ($debug) {
			krumo($fnBody);
		}
		return create_function('$a', $fnBody);
	}
	
	/**
	 * Flips an array of $items->subitem into an array of $subitem->items; 
	 * 
	 * @param array $array Array of objects
	 * @param string $subitemsProperty Name of the property to be flipped.
	 * @param string $itemsProperty Name of the property to be created with items. 
	 * @return array
	 */
	public static function flip($array, $subitemsProperty, $itemsProperty = '') {
		if (func_num_args() == 2) {
			// BC support: flip(compact('array'), 'subitemsProperty')
			$itemsProperty = key($array);
			$array = current($array);
		}
			
		$flipped = array();
		foreach ($array as $item) {
			$subitem = $item->$subitemsProperty;
			
			if (is_object($subitem)) {
				$key = (string) $subitem;
				
				if (!array_key_exists($key, $flipped)) {
					$subitem->$itemsProperty = array();
					$flipped[$key] = $subitem;
				}
				
				$flipped[$key]->{$itemsProperty}[] = $item;
			}
		}
		// Returning values with reindexed keys
		return array_values($flipped);
	}
	
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
				$key = $key->$property;
			}
			$separated[$key][] = $item;
		}
		return $separated;
	}
	
	/**
 	 * Acts like an order by on an SQL.
 	 * 
     * @param array $array The array we want to sort.
     * @param string $clause A string specifying how to sort the array similar to SQL ORDER BY clause.
     * @return array 
     */ 
    public static function sort($array, $clause, $debug = false) {
        $dirMap = array('desc' => 1, 'asc' => -1); 
		
		$clause = preg_replace('/\s+/', ' ', $clause);
        $keys = explode(',', $clause); 
       	
	    $retorno = 'return 0;';
		
        for ($i = count($keys) - 1; $i >= 0; $i--) {
            $parts = explode(' ', trim($keys[$i]));
        	$dir = array_pop($parts);
        	
			if (strtolower($dir) == 'asc' || strtolower($dir) == 'desc') {
				$t = $dirMap[strtolower($dir)];
			} else {
				$parts[] = $dir;
				$t = $dirMap['asc'];
			}
			$k = implode(' ', $parts);
            $f = -1 * $t;
			
			if ($k == 'RAND()') {
            	$aStr = $bStr = 'rand()';
			} else {
				$k = str_replace('.', '->', $k);
				$aStr = '$a->' . $k;
            	$bStr = '$b->' . $k;
			}
			
			// Checagem de string para usar collate correto
			if (strpos($k, '=') !== false) {
				$valor = null;
				$aStr = '(' . preg_replace('/([^=!])(=)([^=])/', '\1\2=\3', $aStr) . ')';
				$bStr = '(' . preg_replace('/([^=!])(=)([^=])/', '\1\2=\3', $bStr) . ')';
			} else {
				$attr = explode('->', $k);
				$valor = reset($array)->{$attr[0]};
				if ($attr[2]) {
					$valor = $valor->{$attr[1]}->{$attr[2]};
				} elseif ($attr[1]) {
					$valor = $valor->{$attr[1]};
				}
			}
			if (is_string($valor) && !is_numeric($valor)) {
				 $fnBody = '$cmp = strcoll(' . $aStr . ', ' . $bStr . ');' .
				 	' if ($cmp == 0) {' .
						$retorno .
					' } ' .
					'return ($cmp < 0) ? ' . $t . ' : ' . $f . ';';
			} else {
	            $fnBody = 'if (' . $aStr . ' == ' . $bStr . ') {' .
						$retorno .
					' } ' .
					'return (' . $aStr . ' < ' . $bStr . ') ? ' . $t . ' : ' . $f . ';';
			}
			$retorno = &$fnBody;
        }
		if ($debug) {
			krumo($fnBody);
		}
		if ($fnBody) {
            usort($array, create_function('$a,$b', $fnBody));        
        }
		return $array;
    }
	/**
	 * Acts like a LIMIT statement on SQL.
	 * 
	 * @param array &$array
	 * @param string $clause Similar to SQL LIMIT clause.
	 * @return array
	 */
	public static function slice($array, $clause) {
		if (strpos($clause, ',')) {
			$l = explode(',', $clause);
			$offset = trim($l[0]);
			$length = trim($l[1]);
		} else {
			$offset = 0;
			$length = trim($clause);
		}
		return array_slice($array, $offset, $length);
	}
	/**
	 * Implodes the properties of an array of objects.
	 * It uses getFieldsValues() for InterAdminTipo and getByAlias() for InterAdmin. 
	 * 
	 * @param 	string	$separator		
	 * @param 	array	$array					Array of objects.
	 * @param 	string 	$propertyName 			[optional] Defaults to 'nome'.
	 * @param 	bool 	$discardEmptyValues		If TRUE empty values won´t be imploded.
	 * @return 	string 	Values of $propertyName imploded using $separator.
	 */
	public static function implode($separator, $array, $propertyName = 'nome', $discardEmptyValues = true) {
		$stringArr = array();
		foreach ($array as $item) {
			if ($item instanceof InterAdminTipo) {
				$stringArr[] = $item->getFieldsValues($propertyName);
			} elseif ($item instanceof InterAdmin) {
				$stringArr[] = $item->getByAlias($propertyName);
			} else {
				$stringArr[] = $item->$propertyName;
			}
		}
		if ($discardEmptyValues) {		
			return jp7_implode($separator, $stringArr);
		} else {
			return implode($separator, $stringArr);
		}
	}
	
	/**
	 * Transforms prefixed keys into arrays. Ex: 
	 * $_POST['foto_id'][] = 1; 
	 * $_POST['foto_name'][] = 'Teste';
	 * 
	 * $foto = prefixToArray($_POST, 'foto');
	 * 
	 * Returns:
	 * $foto[0] = array('id' => 1, 'name' => 'Teste'); 
	 *  
	 * @param array $multiarray Such as $_POST.
	 * @param string $prefix Such as 'foto' for 'foto_'
	 * @return array
	 */
	public static function prefixToArray($multiarray, $prefix) {
		$newarray = array();
		$preflen = strlen($prefix) + 1;
		foreach ($multiarray as $name => $array) {
			$namekey = substr($name, $preflen);
			if (strpos($name, $prefix . '_') === 0) {
				foreach ($array as $key => $value) {
					$newarray[$key][$namekey] = $value;
				}
			}
		}
		return $newarray;
	}
	
	public static function getFieldsValues($array, $fields, $fields_alias) {
		if (count($array) > 0) {
			$first = reset($array);
			
			$tipo = $first->getTipo();
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

	public static function lists($records, $column, $blankText = 'Selecione', $idColumn = 'id') {
		$list = [
			'' => $blankText,
		];
		
		$properties = explode('.', $column);
		$idProperties = explode('.', $idColumn);
		
		foreach ($records as $record) {
			if (!is_object($record)) {
				continue;
			}
			
			$value = $id = $record;
			foreach ($properties as $property) {
				$value = $value->$property;
			}
			foreach ($idProperties as $property) {
				$id = $id->$property;
			}
			
			if (!$value || !$id) {
				continue;
			}
			
			$list[$id] = $value;
		}
		
		ksort($list, SORT_STRING);

		return $list;
	}

	public static function extractFields($interadmins, $column, $order = true, $distinct = true) {
		$novoArray = [];

		$properties = explode('.', $column);

		if ($interadmins) {
			foreach ($interadmins as $interadmin) {
				$value = $interadmin;
				foreach ($properties as $property) {
					$value = $value->$property;
				}

				if (!$value || ($distinct && in_array($value, $novoArray))) { 
					continue;
				}

				$novoArray[] = $value;
			}
		}
		
		if ($order) {
			sort($novoArray);
		}

		return $novoArray;
	}

	public static function makeArray($fromArrays, $closure) {
		$newArray = [];

		foreach ($fromArrays AS $key => $value) {
			$newArray += $closure($value, $key);
		}

		return $newArray;
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
		
		if ($data = $model->getTipo()->getRelationshipData($relationship)) {
			if ($data['type'] == 'select') {
				// select.id = record.select_id
				$indexed = self::separate($records, $relationship . '.id');
				
				$rows = $data['tipo']->find(array(
					'fields' => '*',
					'fields_alias' => $data['alias'],
					'where' => array('id IN (' . implode(',', array_keys($indexed)) . ')')
				));
				
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
				$children = $data['tipo']->where(['parent_id' => $records])->all();
				if ($relationships) {
					self::eagerLoad($children, $relationships);
				}
				$children = self::separate($children, 'parent_id');
				
				foreach ($records as $record) {
					$record->setEagerLoad($relationship, $children[$record->id] ?: array());
				}
			} else {
				throw new Jp7_InterAdmin_Exception('Unsupported relationship type: "' . $data['type'] . '" for class ' . get_class($model) . ' - ID: ' . $model->id);
			}
		} else {
			throw new Jp7_InterAdmin_Exception('Unknown relationship: "' . $relationship . '" for class ' . get_class($model) . ' - ID: ' . $model->id);
		}		
	}
}