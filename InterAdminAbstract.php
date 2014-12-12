<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdmin
 */

/**
 * Class which represents records on the table interadmin_{client name}.
 *
 * @package InterAdmin
 */
abstract class InterAdminAbstract implements Serializable {
	const DEFAULT_FIELDS_ALIAS = false;
	const DEFAULT_NAMESPACE = '';
	const DEFAULT_FIELDS = '*';
	
	private static $_cache = false;
	
	protected $_primary_key = 'id';
	/**
	 * Array of all the attributes with their names as keys and the values of the attributes as values.
	 * @var array 
	 */
	public $attributes = array();
	/**
	 * Used to know if the values were updated through setFieldsValues(). 
	 * Hack for compatibility with legacy sites.
	 * 
	 * @var bool
	 */
	protected $_updated = false;
	protected $_deleted = false;
	/**
	 * @var ADOConnection
	 */
	protected $_db = null;
	
	/**
	 * Magic get acessor.
	 * 
	 * @param string $attributeName
	 * @return mixed
	 */
	public function &__get($attributeName) {
		if (isset($this->attributes[$attributeName])) {
			return $this->attributes[$attributeName];
		} else {
			return null;
		}
	}
	/**
	 * Magic set acessor.
	 * 
	 * @param string $attributeName
	 * @param string $attributeValue
	 * @return void
	 */
	public function __set($attributeName, $attributeValue) {
		$this->attributes[$attributeName] = $attributeValue;
	}
	/**
	 * Magic unset acessor.
	 * 
	 * @param string $attributeName
	 * @return void
	 */
	public function __unset($attributeName) {
		unset($this->attributes[$attributeName]);
	}
	/**
	 * Magic isset acessor.
	 * 
	 * @param string $attributeName
	 * @return bool
	 */
	public function __isset($attributeName) {
		return isset($this->attributes[$attributeName]);
	}
	/**
	 * String value of this record's primary_key.
	 * 
	 * @return string String value of the primary_key property.
	 */
	public function __toString() {
		$pk = $this->_primary_key;
		return (string) $this->$pk;
	}
	
	public function serialize() {
		$vars = get_object_vars($this);
		unset($vars['_db']);
        return serialize($vars);
    }
	
    public function unserialize($data) {
    	global $db;
		$vars = unserialize($data);
    	foreach ($vars as $key => $value) {
    		$this->$key = $value;
    	}
    	$this->_db = $db;
    }
	
	/** 
	 * Gets values from this record on the database.
	 *
	 * @param array|string $fields Array of fields or name of the field to be retrieved, '*' to get all the fields.
	 * @param bool $forceAsString Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * @param bool $fieldsAlias If <tt>TRUE</tt> the names of the fields are replaced by the Alias that were inserted on the InterAdmin.
	 * @return mixed If $fields is an array an object will be returned, otherwise it will return the value retrieved.
	 * @todo Multiple languages - When there is no id_tipo yet, the function is unable to decide which language table it should use.
	 * @deprecated Use loadAttributes() or just set fields() with all().
	 */
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
		throw new Exception('getFieldsValues() has been removed.');
		if ($this->_deleted) {
			throw new Exception('This record has been deleted.');
		}
		$this->_resolveWildcard($fields, $this);
		
		if ($forceAsString || $this->_updated) {
			// reload
			$fieldsToLoad = $fields;
		} elseif (is_string($fields) && isset($this->attributes[$fields])) {
			// Performance for 1 field only, does the same thing as the last line
			return $this->attributes[$fields]; 
		} else {
			// use cache
			$fieldsToLoad = array_diff((array) $fields, array_keys($this->attributes));
		}
		// Retrieving data
		if ($fieldsToLoad) {
			$options = array(
				'fields' => (array) $fieldsToLoad,
				'fields_alias' => $fieldsAlias,
				'from' => $this->getTableName() . ' AS main',
				'where' => array($this->_primary_key . ' = ' . intval($this->{$this->_primary_key})),
				// Internal use
				'aliases' => $this->getAttributesAliases(),
				'campos' => $this->getAttributesCampos()
				//'skip_published_filters' => array('main')
			);
			$rs = $this->_executeQuery($options);
			if ($row = $rs[0]) {
				if ($forceAsString) {
					$this->_getFieldsValuesAsString($row, $fieldsAlias);
				} else {
					$this->_getAttributesFromRow($row, $this, $options);
				}
			}
			//$rs->Close();
		}
		if (is_array($fields)) {
			// returns only the fields requested on $fields
			foreach ($fields as $key => $value) {
				if (is_array($value)) {
					$fields[$key] = $key;
				}
			}
			return (object) array_intersect_key($this->attributes, array_flip($fields));
		} else {
			return $this->attributes[$fields];
		}
	}

	/**
	 * Loads attributes if they are not set yet.
	 * 
	 * @param array $attributes
	 * @return null
	 */
	public function loadAttributes($attributes, $fieldsAlias = true) {
		if ($this->_deleted) {
			throw new Exception('This record has been deleted.');
		}
		
		$fieldsToLoad = array_diff($attributes, array_keys($this->attributes));
		// Retrieving data
		if ($fieldsToLoad) {
			$options = array(
				'fields' => (array) $fieldsToLoad,
				'fields_alias' => $fieldsAlias,
				'from' => $this->getTableName() . ' AS main',
				'where' => array($this->_primary_key . ' = ' . intval($this->{$this->_primary_key})),
				// Internal use
				'aliases' => $this->getAttributesAliases(),
				'campos' => $this->getAttributesCampos(),
				'skip_published_filters' => array('main')
			);
			$rs = $this->_executeQuery($options);
			if ($row = array_shift($rs)) {
				$this->_getAttributesFromRow($row, $this, $options);
			}
			//$rs->Close();
		}
	}

	/**
	 * DEPRECATED: Updates the values into the database table. If this object has no 'id', the data is inserted.
	 * 
	 * @param array $fields_values Array with the values, the keys are the fields names.
	 * @param bool $force_magic_quotes_gpc If TRUE the string will be quoted even if 'magic_quotes_gpc' is not active.
	 * @return void
	 * @todo Verificar necessidade de $force_magic_quotes_gpc no jp7_db_insert
	 * @deprecated Utilizar save() ou updateAttributes()
	 */
	public function setFieldsValues($fields_values, $force_magic_quotes_gpc = false) {
		$pk = $this->_primary_key;
		if ($this->$pk) {
			jp7_db_insert($this->getTableName(), $this->_primary_key, $this->$pk, $fields_values, true, $force_magic_quotes_gpc);
		} else {
			$this->$pk = jp7_db_insert($this->getTableName(), $this->_primary_key, 0, $fields_values, true, $force_magic_quotes_gpc);
		}
		$this->_updated = true; // FIXME Hack tempor�rio
	}
	/**
	 * Updates all the attributes from the passed-in array and saves the record.
	 * 
	 * @param array $attributes Array with fields names and values.
	 * @return void
	 */
	public function updateAttributes($attributes) {
		$this->setAttributes($attributes);
		$this->_update($attributes);
	}
	/**
	 * Saves this record.
	 * 
	 * @return void
	 */
	public function save() {
		$this->_update($this->attributes);
	}
	/**
	 * Increments a numeric attribute
	 * 
	 * @param string $attribute
	 * @param integer $by
	 */
	public function increment($attribute, $by = 1) {
		$this->$attribute += $by;
		$this->_update(array($attribute => $this->$attribute));
	}
	/**
	 * Updates using SQL.
	 * 
	 * @param array $attributes
	 * @return void
	 */
	protected function _update($attributes) {
		$db = $this->getDb();
		
		$valuesToSave = array();
		$aliases = array_flip($this->getAttributesAliases());
		
		foreach ($attributes as $key => $value) {
			$key = ($aliases[$key]) ? $aliases[$key] : $key;
			switch (gettype($value)) {
				case 'object':
					$valuesToSave[$key] = (string) $value;
					if ($value instanceof InterAdminFieldFile) {
						$valuesToSave[$key . '_text'] = $value->text;
					}
					break;
				case 'array':
					$valuesToSave[$key] = implode(',', $value);
					break;
				case 'NULL':
					$valuesToSave[$key] = '';
					break;
				default:
					$valuesToSave[$key] = $value;
					break;
			}
		}
		
		$pk = $this->_primary_key;
		if ($this->$pk) {
			DB::table($this->getTableName())
				->where($pk, $this->$pk)
				->update($valuesToSave) 
			or die(jp7_debug('Error while updating values in `' . $this->getTableName() .  '` ' . $db->ErrorMsg(), print_r($valuesToSave, true)));

			//$db->AutoExecute($this->getTableName(), $valuesToSave, 'UPDATE', $pk . ' = ' .  $this->$pk) 
			//	or die(jp7_debug('Error while updating values in `' . $this->getTableName() .  '` ' . $db->ErrorMsg(), print_r($valuesToSave, true)));
		} else {
			DB::table($this->getTableName())
				->insert($valuesToSave) 
			or die(jp7_debug('Error while inserting data into `' . $this->getTableName() . '` ' . $db->ErrorMsg(), print_r($valuesToSave, true)));

			// $db->AutoExecute($this->getTableName(), $valuesToSave, 'INSERT') 
			// 	or die(jp7_debug('Error while inserting data into `' . $this->getTableName() . '` ' . $db->ErrorMsg(), print_r($valuesToSave, true)));
			
			$this->$pk = DB::getPdo()->lastInsertId();
		}
	}
	/**
	 * Gets an object by its key, which may be its 'id' or 'id_tipo', and then returns it.
	 * 
	 * @param mixed $value Any value.
	 * @param string $field The name of the field.
	 * @param string $campos Value from getCampos().
	 * @param InterAdminAbstract Object which will receive the attribute.
	 * @return mixed The object created by the key or the value itself.
	 */
	protected function _getByForeignKey(&$value, $field, $campo = '', $object) {
		$interAdminClass = static::DEFAULT_NAMESPACE . 'InterAdmin';
		
		$options = array();
		if (strpos($field, 'date_') === 0) {
			return new Jp7_Date($value);
		}
		if (strpos($field, 'select_') === 0) {
			$isMulti = (strpos($field, 'select_multi') === 0);
			$isTipo = in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());
		} elseif (strpos($field, 'special_') === 0 && $campo['xtra']) {
			$isMulti = in_array($campo['xtra'], InterAdminField::getSpecialMultiXtras());
			$isTipo = in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras());
		} elseif (strpos($field, 'file_') === 0 && strpos($field, '_text') === false && $value) {
			$class_name = $interAdminClass . 'FieldFile';
			if (!class_exists($class_name)) {
				$class_name = 'InterAdminFieldFile';
			}
			$file = new $class_name($value);
			$file->setParent($object);
			return $file;
		} else {
			return $value;
		}
		
		$options['default_class'] =  $interAdminClass . (($isTipo) ? 'Tipo' : '');
		if ($object instanceof InterAdminAbstract) {
			$tipo = $object->getCampoTipo($campo);
		}
		
		if ($isMulti) {
			$value_arr = jp7_explode(',', $value);
			foreach ($value_arr as $key2 => $value2) {
				if ($value2 && is_numeric($value2)) {
					if ($isTipo) {
						$value_arr[$key2] = InterAdminTipo::getInstance($value2, $options);
					} else {
						$value_arr[$key2] = InterAdmin::getInstance($value2, $options, $tipo);
					}
				} else {
					//FIXME Retirar quando 7.form.lib parar de salvar N no special
					unset($value_arr[$key2]);
				}
			}
			$value = $value_arr;
		} elseif ($value && is_numeric($value)) {
			if ($isTipo) {
				$value = InterAdminTipo::getInstance($value, $options);
			} else {
				$value = InterAdmin::getInstance($value, $options, $tipo);
			}
		}
		
		return $value;
	}
	/**
	 * Executes a SQL Query based on the values passed by $options.
	 * 
	 * @param array $options Default array of options. Available keys: fields, fields_alias, from, where, order, group, limit, all, campos and aliases.
	 * @return ADORecordSet
	 */
	protected function _executeQuery($options, &$select_multi_fields = array()) {
		//global $debugger;
		$db = $this->getDb();
		
		// Type casting 
		if (!is_array($options['from'])) {
    		$options['from'] = (array) $options['from'];
		}
		if (!is_array($options['where'])) {
		    $options['where'] = (array) $options['where'];
		}
		$options['where'] = implode(' AND ', $options['where']);
		if (!is_array($options['fields'])) {
			$options['fields'] = (array) $options['fields'];
		}
		if (empty($options['fields_alias'])) {
			$options['aliases'] = array();
		} else {
			$options['aliases'] = array_flip($options['aliases']);
		}
		if (array_key_exists('use_published_filters', $options)) {
			$use_published_filters = $options['use_published_filters'];
		} else {
			$use_published_filters = InterAdmin::isPublishedFiltersEnabled();
		}
		/*
		$cache = null;
		if (self::$_cache) {
			$cache = new Jp7_Cache_Recordset($options);
		}
		if (!$cache || !($rs = $cache->load())) {
		*/
			// Resolve Alias and Joins for 'fields' and 'from'
			$this->_resolveFieldsAlias($options);
			// Resolve Alias and Joins for 'where', 'group' and 'order';
			$clauses = $this->_resolveSqlClausesAlias($options, $use_published_filters);
			
			$filters = '';
			if ($use_published_filters) {
				foreach ($options['from'] as $key => $from) {
					list($table, $alias) = explode(' AS ', $from);
					if ($alias == 'main') {
						if (empty($options['skip_published_filters']) || !in_array('main', $options['skip_published_filters'])) {
							$filters = static::getPublishedFilters($table, 'main');
						}			
					} else {
						$joinArr = explode(' ON', $alias);
						if (empty($options['skip_published_filters']) || !in_array($joinArr[0], $options['skip_published_filters'])) {
							$options['from'][$key] = $table . ' AS ' . $joinArr[0] . ' ON ' . static::getPublishedFilters($table, $joinArr[0]) . $joinArr[1];
						}
					}
				}
			}
			
		    $joins = '';
		    if (isset($options['joins']) && $options['joins']) {
			    foreach ($options['joins'] as $alias => $join) {
				    list($joinType, $tipo, $on) = $join;
				    $table = $tipo->getInterAdminsTableName();
				    $joins .= ' ' . $joinType . ' JOIN ' . $table . ' AS ' . $alias . ' ON ' . 
					    ($use_published_filters ? static::getPublishedFilters($table, $alias) : '') . 
					    $alias . '.id_tipo = ' . $tipo->id_tipo . ' AND ' . $this->_resolveSql($on, $options, $use_published_filters);
			    }
		    }
		    
			// Sql
			$sql = "SELECT " . implode(',', $options['fields']) .
				" FROM " . implode(' LEFT JOIN ', $options['from']) .
			    $joins .
				" WHERE " . $filters . $clauses .
				((!empty($options['limit'])) ? " LIMIT " . $options['limit'] : '');


			$rs = DB::select($sql);

			if (!$rs && !is_array($rs)) {
				$erro = $db->ErrorMsg();
				if (strpos($erro, 'Unknown column') === 0 && $options['aliases']) {
					$erro .= ". Available fields: \n\t\t- " . implode("\n\t\t- ", array_keys($options['aliases']));
				}			
				
				throw new Exception($erro . ' - SQL: ' . $sql);
			}
			
			if (!empty($options['debug'])) {
				// $time = $debugger->getTime($options['debug']);
				echo Jp7_Debugger::syntaxHighlightSql($sql);
			}
			$select_multi_fields = isset($options['select_multi_fields']) ? $options['select_multi_fields'] : null;		
			
			/*
			if ($cache) {
				$rs = $cache->save($rs);
			}
		}
		*/
		return $rs;
	}
	/**
	 * Resolves the aliases on clause using regex
	 * 
	 * @param string $clause
	 * @return 
	 */
	protected function _resolveSqlClausesAlias(&$options = array(), $use_published_filters) {
		$resolvedWhere = $this->_resolveSql($options['where'], $options, $use_published_filters); 
		
		// Group by para wheres com children, DISTINCT é usado para corrigir COUNT() com children
		$firstField = reset($options['fields']);
		if (!empty($options['group']) && strpos($firstField, 'DISTINCT') === false) {
			foreach ($options['from'] as $join) {
				// Isso pode ser feito por flag depois
				if (strpos($join, '.parent_id = main.id') !== false || strpos($join, 'AS tags ON') !== false) {
					$options['group'] = 'main.id';
				}
			}
		}
		
		$clause = ((!empty($options['group'])) ? " GROUP BY " . $options['group'] : '') .
			((!empty($options['having'])) ? " HAVING " . implode(' AND ', $options['having']) : '') .
			((!empty($options['order'])) ? " ORDER BY " . $options['order'] : '');
		
		return $resolvedWhere . $this->_resolveSql($clause, $options, $use_published_filters);
	}
	protected function _resolveSql($clause, &$options = array(), $use_published_filters) {
		$campos = &$options['campos'];
		$aliases = &$options['aliases'];
		
		$quoted = '(\'((?<=\\\\)\'|[^\'])*\')';
		$keyword = '\b[a-zA-Z0-9_.]+\b';
		// not followed by "(" or " (", so it won't match "CONCAT(" or "IN ("
		$not_function = '(?![ ]?\()';
		$reserved = array(
			'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'NOT', 'LIKE', 'IS',
			'NULL', 'DESC', 'ASC', 'BETWEEN', 'REGEXP', 'HAVING', 'DISTINCT', 'UNSIGNED', 'AS',
			'INTERVAL', 'DAY', 'WEEK', 'MONTH', 'YEAR', 'CASE', 'WHEN', 'THEN', 'END'
		);
		
		$offset = 0;
		$ignoreJoinsUntil = -1;
			
		while(preg_match('/(' . $quoted . '|' . $keyword . $not_function . '|EXISTS)/', $clause, $matches, PREG_OFFSET_CAPTURE, $offset)) {
			list($termo, $pos) = $matches[1];
			// Resolvendo true e false para char
			if (strtolower($termo) == 'true' || strtolower($termo) == 'false') {
				$negativas = array('', '!');
				if (strtolower($termo) == 'false') {
					$negativas = array_reverse($negativas);
				}
				$inicio = substr($clause, 0, $pos + strlen($termo));
				$inicioRep = preg_replace('/(\.char_[[:alnum:] ]*)(<>|!=)([ ]*)' . $termo . '$/i', "$1" . $negativas[0] . "=$3''", $inicio, 1, $count);
				if (!$count) {
					$inicioRep = preg_replace('/(\.char_[^=]*)=([ ]*)' . $termo . '/i', "$1" . $negativas[1] . "=$2''", $inicio, 1);
				}
				$clause = $inicioRep . substr($clause, $pos + strlen($termo));
				$offset = strlen($inicioRep);
				continue;
			}
				
			// Joins com EXISTS
			if ($termo == 'EXISTS') {
				$inicio = substr($clause, 0, $pos + strlen($termo));
				$existsClause = substr($clause, $pos + strlen($termo));
		
				if (preg_match('/^([\( ]+)(' . $keyword . ')([ ]+)(WHERE)?/', $existsClause, $existsMatches)) {
					$table = $existsMatches[2];
					// TODO unificar l�gica
					if ($table == 'tags') {
						$existsMatches[2] = 'SELECT id_tag FROM ' . $this->db_prefix . "_tags AS " . $table .
						' WHERE ' . $table . '.parent_id = main.id' . (($existsMatches[4]) ? ' AND ' : '');
					} elseif (strpos($table, 'children_') === 0) {
						$joinNome = Jp7_Inflector::camelize(substr($table, 9));
						$childrenArr = $this->getInterAdminsChildren();
						if (!$childrenArr[$joinNome]) {
							throw new Exception('The field "' . $table . '" cannot be used as a join on $options.' .
									'Expected a child named "' . $joinNome . '". Found: ' . implode(', ', array_keys($childrenArr)));
						}
						$joinTipo = InterAdminTipo::getInstance($childrenArr[$joinNome]['id_tipo'], array(
							'db_prefix' => $this->db_prefix,
							'db' => $this->_db,
							'default_class' => static::DEFAULT_NAMESPACE . 'InterAdminTipo'
						));
		
						$joinFilter = ($use_published_filters) ? $this->getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';
						$existsMatches[2] = 'SELECT id FROM ' . $joinTipo->getInterAdminsTableName() . " AS " . $table .
						' WHERE ' . $joinFilter . $table . '.parent_id = main.id AND ' . $table . '.id_tipo = ' . $joinTipo->id_tipo . '' .
						(($existsMatches[4]) ? ' AND ' : '');
					} elseif ($options['joins'][$table]) {
						$joinTipo = $options['joins'][$table][1];
						$onClause = array(
							'joins' => $options['joins'],
							'where' => $options['joins'][$table][2]
						);
						$joinFilter = ($use_published_filters) ? $this->getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';
						$existsMatches[2] = 'SELECT id FROM ' . $joinTipo->getInterAdminsTableName() . " AS " . $table .
						' WHERE ' . $joinFilter . $this->_resolveSqlClausesAlias($onClause, $use_published_filters) . (($existsMatches[4]) ? ' AND ' : '');
					}
						
					$inicioRep = $inicio . $existsMatches[1] . $existsMatches[2] . $existsMatches[3];
					$clause = $inicioRep . substr($clause, strlen($inicio . $existsMatches[0]));
					$offset = strlen($inicioRep);
					
					$ignoreJoinsUntil = $offset;
					continue;
				}
			}
				
			if ($termo[0] != "'" && !is_numeric($termo) && !in_array($termo, $reserved)) {
				$len = strlen($termo);
				$table = 'main';
				if (strpos($termo, '.') !== false) {
					list($table, $termo, $subtermo) = explode('.', $termo);
				}
				if ($table === 'main') {
					$campo = isset($aliases[$termo]) ? $aliases[$termo] : $termo;
				} else {
					$childrenArr = $childrenArr ?: $this->getInterAdminsChildren();
					
					// Joins com children
					if (strpos($table, 'children_') === 0) {
						$joinNome = Jp7_Inflector::camelize(substr($table, 9));
					} else {
						$joinNome = Jp7_Inflector::camelize($table);
					}
					if ($childrenArr[$joinNome]) {
						$joinTipo = InterAdminTipo::getInstance($childrenArr[$joinNome]['id_tipo'], array(
							'db_prefix' => $this->db_prefix,
							'db' => $this->_db,
							'default_class' => static::DEFAULT_NAMESPACE . 'InterAdminTipo'
						));
						if ($offset > $ignoreJoinsUntil && !in_array($table, (array) $options['from_alias'])) {
							$options['from_alias'][] = $table;
							$options['from'][] = $joinTipo->getInterAdminsTableName() .
							' AS ' . $table . ' ON ' . $table . '.parent_id = main.id AND ' . $table . '.id_tipo = ' . $joinTipo->id_tipo;
						}
						$joinAliases = array_flip($joinTipo->getCamposAlias());
					// Joins com tags @todo Verificar jeito mais modularizado de fazer esses joins
					} elseif ($table == 'tags') {
						if ($offset > $ignoreJoinsUntil && !in_array($table, (array) $options['from_alias'])) {
							$options['from_alias'][] = $table;
							$options['from'][] = $this->db_prefix . "_tags AS " . $table .
							" ON " . $table . ".parent_id = main.id";
						}
						$joinAliases = array();
					// Joins normais
					} else {
						$joinNome = ($aliases[$table]) ? $aliases[$table] : $table;
						// Permite utilizar relacionamentos no where sem ter usado o campo no fields
						if ($options['joins'] && $options['joins'][$table]) {
							$joinTipo = $options['joins'][$table][1];
						} else {
							if ($offset > $ignoreJoinsUntil && !in_array($table, (array) $options['from_alias'])) {
								$this->_addJoinAlias($options, $table, $campos[$joinNome]);
							}
							$joinTipo = $this->getCampoTipo($campos[$joinNome]);
						}
						$joinAliases = array_flip($joinTipo->getCamposAlias());
					}
					// TEMPORARIO FIXME, necessario melhor maneira
					if ($subtermo) {
						$subtable = $table . '__' . $termo;
						$subCampos = $joinTipo->getCampos();
						$subJoinTipo = $joinTipo->getCampoTipo($subCampos[$joinAliases[$termo]]);
		
						// Permite utilizar relacionamentos no where sem ter usado o campo no fields
						if (!in_array($subtable, (array) $options['from_alias'])) {
							$options['from_alias'][] = $subtable;
							$options['from'][] = $subJoinTipo->getInterAdminsTableName() .
							' AS ' . $subtable . ' ON ' . $subtable . '.id = ' . $table . '.' . $joinAliases[$termo] . ' AND ' . $subtable . '.id_tipo = ' . $subJoinTipo->id_tipo;
						}
						$table = $subtable;
						$termo = $subtermo;
						$joinAliases = array_flip($subJoinTipo->getCamposAlias());
					}
					$campo = ($joinAliases[$termo]) ? $joinAliases[$termo] : $termo;
				}
				$termo = $table . '.' . $campo;
				$clause = substr_replace($clause, $termo, $pos, $len);
			}
			$offset = $pos + strlen($termo);
		}
		return $clause;		
	}
	
	/**
	 * Resolves Aliases on $options fields.
	 * 
	 * @param array $options Same syntax as $options
	 * @param array $campos 
	 * @param array $aliases     'alias' => 'field'
	 * @param string $table Table alias for the fields.
	 * @return array Revolved $fields.
	 */
	protected function _resolveFieldsAlias(&$options = array(), $table = 'main.') {
		$campos = &$options['campos'];
		$aliases = &$options['aliases'];
		$fields = $options['fields'];
		
		foreach ($fields as $key => $campo) {
			// Traduzindo 'join.campo' para 'join' => array('campo')
			if (is_string($campo) && strpos($campo, '.') !== false && strpos($campo, '(') === false) {
				list($join, $nome) = explode('.', $campo);
				$fields[$join][] = $nome;
				unset($fields[$key]);
			}
		}
		
		foreach ($fields as $join => $campo) {
			// Com join
			if (is_array($campo)) {
				$nome = ($aliases[$join]) ? $aliases[$join] : $join;
				if ($nome) {
					// Join e Recursividade
					if (isset($options['joins']) && $options['joins'][$join]) {
						$joinTipo = $options['joins'][$join][1];
					} elseif (strpos($campos[$nome]['tipo'], 'select_multi_') === 0) {
						$fields[] = $table . $nome . (($table != 'main.') ? ' AS `' . $table . $nome . '`' : '');
						// Processamento dos campos do select_multi � feito depois
						$joinTipo = null;
						$options['select_multi_fields'][$join] = array(
							'fields' => $fields[$join],
							'fields_alias' => $options['fields_alias'],
						);
					} else {
					    $fields[] = $table . $nome . (($table != 'main.') ? ' AS `' . $table . $nome . '`' : '');
					    // Join e Recursividade
					    if (empty($options['from_alias']) || !in_array($join, (array) $options['from_alias'])) {
							$joinClasse = $this->_addJoinAlias($options, $join, $campos[$nome]);
							if ($joinClasse !== 'tipo') {
								$fields[$join][] = 'id_slug';
							}
					    }
					    $joinTipo = $this->getCampoTipo($campos[$nome]);
					}
					if ($joinTipo) {
						$wildcardPos = array_search('*', $fields[$join]);
						if ($wildcardPos !== false) {
							unset($fields[$join][$wildcardPos]);
							$fields[$join] = array_merge($fields[$join], $joinTipo->getCamposNames(), $joinTipo->getInterAdminsAdminAttributes());
						}
						$joinOptions = array(
							'fields' => $fields[$join],
							'fields_alias' => $options['fields_alias'],
							'campos' => $joinTipo->getCampos(),
							'aliases' => array_flip($joinTipo->getCamposAlias())
						);
						$this->_resolveFieldsAlias($joinOptions, $join . '.');
						foreach ($joinOptions['fields'] as $joinField) {
							array_push($fields, $joinField);
						}
					}
					unset($fields[$join]);
				}
			// Com função
			} elseif (strpos($campo, '(') !== false || strpos($campo, 'CASE') !== false) {
				if (strpos($campo, ' AS ') === false) {
					$aggregateAlias = trim(strtolower(preg_replace('/[^[:alnum:]]/', '_', $campo)), '_');
				} else {
					$parts = explode(' AS ', $campo);
					$aggregateAlias = array_pop($parts);
					$campo = implode(' AS ', $parts);
				}
				$fields[$join] = $this->_resolveSql($campo, $options, true) . ' AS `' . $table . $aggregateAlias . '`';
			// Sem join
			} else {
				$nome = isset($aliases[$campo]) ? $aliases[$campo] : $campo;
				if (strpos($nome, 'file_') === 0 && strpos($nome, '_text') === false) {
					if (strpos($campo, 'file_') === 0) {
						// necessário para quando o parametro fields está sem alias, mas o retorno está com alias
						$file_campo = array_search($campo, $aliases);
					} else {
						$file_campo = $campo;
					}
					$fields[] = $table . $nome . '_text  AS `' . $file_campo . '.text`';
				}
				
				$fields[$join] = $table . $nome . (($table != 'main.') ? ' AS `' . $table . $nome . '`' : '');
			}
		}
		$options['fields'] = $fields;
	}
	/**
	 * Helper function to add a join.
	 * 
	 * @return void 
	 */
	protected function _addJoinAlias(&$options = array(), $alias, $campo, $table = 'main') {
		$joinTipo = $this->getCampoTipo($campo);
		if (!$joinTipo || strpos($campo['tipo'], 'select_multi_') === 0) {
			die(jp7_debug('The field "' . $alias . '" cannot be used as a join (' . get_class($this) . ' - PK: ' . $this->__toString() . ').'));
		}
		$options['from_alias'][] = $alias; // Used as cache when resolving Where
		
		if (in_array($campo['xtra'], InterAdminField::getSelectTipoXtras()) || in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras())) {
            $options['from'][] = $joinTipo->getTableName() . 
                ' AS ' . $alias . ' ON '  . $table . '.' . $campo['tipo'] . ' = ' . $alias . '.id_tipo';
            
            return 'tipo';
        } else {
            $options['from'][] = $joinTipo->getInterAdminsTableName() .
                ' AS ' . $alias . ' ON '  . $table . '.' . $campo['tipo'] . ' = ' . $alias . '.id';
            return 'interadmin';
        }
	}
	/**
	 * Associates the values on a SQL RecordSet with the fields and insert them on the attributes array.
	 * 
	 * @param array $row Row of a SQL RecordSet.
	 * @param bool $fieldsAlias
	 * @param array $attributes If not provided it will populate an empty array.
	 * @return void
	 */
	protected function _getAttributesFromRow($row, $object, $options) {
		$campos = &$options['campos'];
		$aliases = &$options['aliases'];
		if (empty($options['fields_alias'])) {
			$aliases = array();
		}
		if ($aliases) {
			$fields = array_flip($aliases);
		}
		$attributes = &$object->attributes;

		foreach ($row as $key => $value) {
			@list($table, $field) = explode('.', $key);
			
			if (!$field) {
				$field = $table;
				$table = 'main';
			} 
			if ($table == 'main') {
				$alias = isset($aliases[$field]) ? $aliases[$field] : $field;
				$value = $this->_getByForeignKey($value, $field, @$campos[$field], $object);
				if (isset($attributes[$alias]) && is_object($attributes[$alias])) {
					continue;
				}
				if (!empty($options['select_multi_fields'])) {
					if (strpos($campos[$field]['tipo'], 'select_multi_') === 0) {
						$multi_options = $options['select_multi_fields'][$alias];
						if ($multi_options) {
							Jp7_Collections::getFieldsValues($value, $multi_options['fields'], $multi_options['fields_alias']);
						}
					}
				}
				$attributes[$alias] = $value;
			} else {
				$joinAlias = '';
				$join = ($fields[$table]) ? $fields[$table] : $table;
				$joinTipo = $this->getCampoTipo($campos[$join]);
				
				if (!$joinTipo && $options['joins'][$table]) {
					// Joins no options
					list($_joinType, $joinTipo, $_on) = $options['joins'][$table];
					if (!is_object($attributes[$table])) {
						$attributes[$table] =  InterAdmin::getInstance(0, array(), $joinTipo);
					}
				}
				
				if ($joinTipo) {
					$joinCampos = $joinTipo->getCampos();
					if ($joinTipo->id_tipo == '0') {
						$joinAlias = '';
					} else {
						$joinAlias = $joinTipo->getCamposAlias($field);
					}
				}
				
				if (is_object($attributes[$table])) {
					$subobject = $attributes[$table];
					$alias = ($aliases && $joinAlias) ? $joinAlias : $field;
					$value = $this->_getByForeignKey($value, $field, @$joinCampos[$field], $subobject);
					
					if (isset($subobject->$alias) && is_object($subobject->$alias)) {
						continue;
					}
					$subobject->$alias = $value;
				}
			}
		}
	}
	/**
	 * Resolves '*'
	 * 
	 * @param array $fields
	 * @param InterAdminAbstract $object
	 * @return void
	 */
	protected function _resolveWildcard(&$fields, InterAdminAbstract $object) {
		if ($fields == '*' || (is_array($fields) && in_array('*', $fields))) {
			$fields = (array) $fields;
			unset($fields[array_search('*', $fields)]);
			$fields = array_merge($object->getAttributesNames(), $object->getAdminAttributes(), $fields);
		}
	}
	/**
	 * Sets this object's attributes with the given array keys and values.
	 * 
	 * @param array $attributes
	 * @return void
	 */
	public function setAttributes(array $attributes) {
		foreach ($attributes as $key => $value) {
			$this->$key = $value;
		}
	}
	/**
	 * Reloads all the attributes.
	 * 
	 * @todo Not implemented yet. Won't work with recursive objects and alias.
	 * @return void
	 */
	public function reload($fields = null) {
		if (is_null($fields)) {
			$fields = array_keys($this->attributes);
			$existingFields = array_merge($this->getAttributesAliases(), $this->getAttributesNames(), $this->getAdminAttributes());
			$fields = array_intersect($fields, $existingFields);
		}
		// Esvaziando valores para forçar atualização
		foreach ($fields as $key) {
			unset($this->attributes[$key]);
		}
		$isAliased = static::DEFAULT_FIELDS_ALIAS;
		$this->getFieldsValues($fields, false, $isAliased);
	}
	/**
	 * Creates a object of the given Class name with the same attributes.
	 * 
	 * @param object $className
	 * @return InterAdminAbstract An instance of the given Class name.
	 */
	public function becomes($className) {
		$newobject = new $className();
		$newobject->attributes = $this->attributes;
		return $newobject;
	}
	/**
	 * Sets this row as deleted as saves it.
	 * 
	 * @return void
	 */
	public function delete() {
		$this->deleted = 'S';
		$this->save();
	}
	/**
	 * Deletes this row from the table.
	 * 
	 * @return 
	 */
	public function deleteForever() {
		$db = $this->getDb();
		$pk = $this->_primary_key;
		if ($this->$pk) {
			$sql = "DELETE FROM " . $this->getTableName() . 
				" WHERE " . $this->_primary_key . " = " . $this->$pk;
			$db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		}
		$this->attributes = array();
		$this->_deleted = true;
	}
	
	/**
	 * @param array $where
	 * FIXME temporário para wheres que eram com string 
	 */
	protected function _whereArrayFix(&$where) {
		if (is_string($where)) {
			$where = jp7_explode(' AND ', $where);
		} elseif (!$where) {
			$where = array();
		}
	}
		
	/**
	 * Returns the InterAdminTipo for a field.
	 * 
	 * @param object $campo
	 * @return InterAdminTipo 
	 */
	abstract public function getCampoTipo($campo);
	abstract function getAttributesCampos();
	abstract function getAttributesNames();
	abstract function getAttributesAliases();
	abstract function getAdminAttributes();
	abstract function getTableName();
	
	public static function getPublishedFilters($table, $alias) {
		global $db, $s_session;
		$config = InterSite::config();
		
		// Tipos
		if (strpos($table, '_tipos') === (strlen($table) - strlen('_tipos'))) {
			return $alias . ".mostrar <> '' AND " . $alias . ".deleted_tipo = '' AND ";
		// Tags
		} elseif (strpos($table, '_tags') === (strlen($table) - strlen('_tags'))) {
			// do nothing
		// Arquivos
		} elseif (strpos($table, '_arquivos') === (strlen($table) - strlen('_arquivos'))) {
			return $alias . ".mostrar <> '' AND " . $alias . ".deleted = '' AND ";
		// Registros
		} else {
			$return = $alias . ".date_publish <= '" . date("Y-m-d H:i:59", InterAdmin::getTimestamp()) . "'" .
				" AND (" . $alias . ".date_expire > '" . date("Y-m-d H:i:00", InterAdmin::getTimestamp()) . "' OR " . $alias . ".date_expire = '0000-00-00 00:00:00')" .
				" AND " . $alias . ".char_key <> ''" .
				" AND " . $alias . ".deleted = ''".
				" AND ";
				if ($config->interadmin_preview && !$s_session['preview']) {
					$return .= "(" . $alias . ".publish <> '' OR " . $alias . ".parent_id > 0) AND ";	
				} 
			return $return;
		}
	}
	
	/**
	 * Returns the SQL WHERE for filtering this as a tag.
	 * 
	 * @return string
	 */
	abstract public function getTagFilters();
	
	/**
	 * Returns the database object.
	 * 
	 * @return ADOConnection
	 */
	public function getDb() {
		return $this->_db ?: DB::connection();
	}
	/**
	 * Sets the database object.
	 * 
	 * @param ADOConnection $db
	 * @return void
	 */
	public function setDb(\Illuminate\Database\ConnectionInterface $db) {
		$this->_db = $db;
	}
}
