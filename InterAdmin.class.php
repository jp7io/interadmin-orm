<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category Jp7
 * @package InterAdmin
 */
 
/**
 * Class which represents records on the table interadmin_{client name}.
 *
 * @package InterAdmin
 */
class InterAdmin extends InterAdminAbstract {
	/**
	 * DEPRECATED: Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 * @deprecated It will only use this property if there is no id_tipo yet
	 */
	public $db_prefix;
	/**
	 * DEPRECATED: Table suffix of this record. e.g.: the table 'interadmin_client_registrations' would have 'registrations' as $table.
	 * @var string
	 * @deprecated It will only use this property if there is no id_tipo yet
	 */
	public $table;
	/**
	 * Contains the InterAdminTipo, i.e. the record with an 'id_tipo' equal to this record´s 'id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_tipo;
	/**
	 * Contains the parent InterAdmin object, i.e. the record with an 'id' equal to this record's 'parent_id'.
	 * @var InterAdmin
	 */
	protected $_parent;
	/**
	 * Contains an array of objects (InterAdmin and InterAdminTipo).
	 * @var array
	 */
	protected $_tags;
	
	protected $_eagerLoad;
	
	/**
	 * Username to be inserted in the log when saving this record.
	 * @var string
	 */
	protected static $log_user = null;
	/**
	 * If TRUE the records will be filtered using the method getPublishedFilters()
	 * @var bool
	 */
	protected static $publish_filters_enabled = true;
	/**
	 * Timestamp for testing filters with a different date.
	 * @var int
	 */
	protected static $timestamp;
	/**
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * @param string $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias.
	 */
	public function __construct($id = '0', $options = array()) {
		global $config;
		
		$id = (string) $id;
		$this->id = is_numeric($id) ? $id : '0';
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $config->db->prefix;
		$this->table = ($options['table']) ? '_' . $options['table'] : '';
		$this->_db = $options['db'];
		
		if ($options['fields'] && $this->id) {
			$options = $options + array('fields_alias' => static::DEFAULT_FIELDS_ALIAS);
			$this->getFieldsValues($options['fields'], false, $options['fields_alias']);
		}
	}

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
			if (
				in_array($attributeName, $this->getTipo()->getCamposAlias()) || 
				in_array($attributeName, $this->getAdminAttributes())
			) {
				throw new Jp7_InterAdmin_Exception('Attribute "' . $attributeName . '" was not loaded for ' . get_class($this) . ' - ID: ' . $this->id);
			}
			return null;
		}
	}

	/**
	 * Returns an InterAdmin instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class, default_class.
	 * @param InterAdminTipo Set the record´s Tipo.
	 * @return InterAdmin Returns an InterAdmin or a child class in case it's defined on the 'class' property of its InterAdminTipo.
	 */
	public static function getInstance($id, $options = array(), InterAdminTipo $tipo = null) {
		$optionsWithoutFields = array_merge($options, array('fields' => array()));
		
		// Default Class
		if (!$options['default_class']) {
			$options['default_class'] = 'InterAdmin';
		}
		// Classe não foi forçada, descobrir a classe do Tipo
		if (!$options['class']) {
			if (!$tipo) {
				$instance = new $options['default_class']($id, $optionsWithoutFields);
				$tipo = $instance->getTipo();
			}
			$options['class'] = $tipo->class;
		}
		// Classe foi descoberta
		if ($instance && $options['class'] == get_class($instance)) {
			// Classe do objeto temporário já está correta
			$finalInstance = $instance;
		} else {
			// Classe é outra
			$class_name = class_exists($options['class']) ? $options['class'] : $options['default_class'];
			$finalInstance = new $class_name($id, $optionsWithoutFields);
		}
		if ($tipo) {
			$finalInstance->setTipo($tipo);
			$finalInstance->db_prefix = $tipo->db_prefix;
			$finalInstance->setDb($tipo->getDb());
		}
		// Fields		
		if ($options['fields']) {
			$finalInstance->_resolveWildcard($options['fields'], $finalInstance);
			$finalInstance->loadAttributes($options['fields'], $options['fields_alias']);
		}
		return $finalInstance;
	}
	/**
	 * Finds a Child Tipo by a camelcase keyword. 
	 * 
	 * @param 	string 	$nome_id 	CamelCase
	 * @return 	array 
	 */
	protected function _findChild($nome_id) {
		$children = $this->getTipo()->getInterAdminsChildren();
		if (!$children[$nome_id]) {
			$nome_id = explode('_', Jp7_Inflector::underscore($nome_id));
			$nome_id[0] = Jp7_Inflector::plural($nome_id[0]);
			$nome_id = Jp7_Inflector::camelize(implode('_', $nome_id));
		}
		if (!$children[$nome_id]) {
			$nome_id = Jp7_Inflector::plural($nome_id);
		}
		return $children[$nome_id];
	}
	
	public function getChildrenTipoByNome($nome_id) {
		$child = $this->_findChild($nome_id);
		if ($child) {
			return $this->getChildrenTipo($child['id_tipo']);
		}
	}
	
	/**
	 * Magic method calls
	 * 
	 * Available magic methods:
	 * - create{Child}(array $attributes = array())
	 * - get{Children}(array $options = array())
	 * - getFirst{Child}(array $options = array())
	 * - get{Child}ById(int $id, array $options = array())
	 * - get{Child}ByIdString(int $id, array $options = array())
	 * - delete{Children}(array $options = array())
	 * 
	 * @param string $methodName
	 * @return mixed
	 */
	public function __call($methodName, $args) {
		// childName() - relacionamento
		if ($child = $this->_findChild(ucfirst($methodName))) {
			$childrenTipo = $this->getChildrenTipo($child['id_tipo']);
			if (isset($this->_eagerLoad[$methodName])) {
				return new InterAdminEagerLoaded($childrenTipo, $this->_eagerLoad[$methodName]);
			}
			return $childrenTipo;
		// get{ChildName}, getFirst{ChildName} and get{ChildName}ById
		} elseif (strpos($methodName, 'get') === 0) {
			// getFirst{ChildName}
			if (strpos($methodName, 'getFirst') === 0) {
				$nome_id = substr($methodName, strlen('getFirst'));
				if ($child = $this->_findChild($nome_id)) {
					return $this->getFirstChild($child['id_tipo'], (array) $args[0]);
				}
			// get{ChildName}ById
			} elseif (substr($methodName, -4) == 'ById') {
				$nome_id = substr($methodName, strlen('get'), -strlen('ById'));
				if ($child = $this->_findChild($nome_id)) {
					$options = (array) $args[1];
					$options['where'][] = "id = " . intval($args[0]);
					return $this->getFirstChild($child['id_tipo'], $options);
				}
			// get{ChildName}ByIdString
			} elseif (substr($methodName, -10) == 'ByIdString') {
				$nome_id = substr($methodName, strlen('get'), -strlen('ByStringId'));
				if ($child = $this->_findChild($nome_id)) {
					$options = (array) $args[1];
					$options['where'][] = "id_string = '" . $args[0] . "'";
					return $this->getFirstChild($child['id_tipo'], $options);
				}
			// get{ChildName}Count
			} elseif (substr($methodName, -5) == 'Count') {
				$nome_id = substr($methodName, strlen('get'), -strlen('Count'));
				if ($child = $this->_findChild($nome_id)) {
					return $this->getChildrenCount($child['id_tipo'], (array) $args[0]);
				}
			// get{ChildName}
			} else {
				$nome_id = substr($methodName, strlen('get'));
				if ($child = $this->_findChild($nome_id)) {
					return $this->getChildren($child['id_tipo'], (array) $args[0]);
				}
			}
		// create{ChildName}
		} elseif (strpos($methodName, 'create') === 0) {
			$nome_id = substr($methodName, strlen('create')); 
			if ($child = $this->_findChild($nome_id)) {
				return $this->createChild($child['id_tipo'], (array) $args[0]);
			}
		// delete{ChildName}
		} elseif (strpos($methodName, 'delete') === 0) {
			$nome_id = substr($methodName, strlen('delete'));
			if ($child = $this->_findChild($nome_id)) {
				return $this->deleteChildren($child['id_tipo'], (array) $args[0]);
			}
		}
		// Default error when method doesn´t exist
		$message = 'Call to undefined method ' . get_class($this) . '->' . $methodName . '(). Available magic methods: ' . "\n";
		$children = $this->getTipo()->getInterAdminsChildren();
		$patterns = array(
			'get{ChildName}',
			'getFirst{ChildName}',
			'get{ChildName}ById',
			'get{ChildName}ByIdString',
			'get{ChildName}Count',
			'create{ChildName}',
			'delete{ChildName}'				
		);
		foreach (array_keys($children) as $childName) {
			foreach ($patterns as $pattern) {
				$message .= "\t\t- " . str_replace('{ChildName}', $childName, $pattern) . "\n";
			}
		}
		die(jp7_debug($message));
	}
	/**
	 * Gets fields values by their alias.
	 *  
	 * @param array|string $fields
	 * @see InterAdmin::getFieldsValues()
	 * @deprecated
	 * @return
	 */
	public function getByAlias($fields) {
		throw new Exception('getByAlias() was removed, load fields previously.');
	}
	/**
	 * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
	 * 
	 * @param array $options Default array of options. Available keys: class.
	 * @return InterAdminTipo
	 */
	public function getTipo($options = array()) {
		if (!$this->_tipo) {
			if (!$id_tipo = $this->id_tipo) {
				global $db;
				$sql = "SELECT id_tipo FROM " . $this->getTableName() . " WHERE id = " . intval($this->id);
				$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
				if ($row = $rs->FetchNextObj()) {
					$id_tipo = $row->id_tipo;
				}				
			}
			$this->setTipo(InterAdminTipo::getInstance($id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'db' => $this->_db,
				'class' => $options['class']
			)));
		}
		return $this->_tipo;
	}
	/**
	 * Sets the InterAdminTipo object for this record, changing the $_tipo property.
	 *
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function setTipo(InterAdminTipo $tipo = null) {
		$this->id_tipo = $tipo->id_tipo;
		$this->_tipo = $tipo;
	}
	/**
	 * Gets the parent InterAdmin object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class.
	 * @return InterAdmin
	 */
	public function getParent($options = array()) {
		if (!$this->_parent) {
			$this->loadAttributes(array('parent_id', 'parent_id_tipo'), false);
			
			$options = $options + array(
				'fields_alias' => static::DEFAULT_FIELDS_ALIAS,
				'fields' => static::DEFAULT_FIELDS
			);
			
			$parentTipo = null;
			if ($this->parent_id_tipo) {
				$parentTipo = InterAdminTipo::getInstance($this->parent_id_tipo);
			}
			$options['default_class'] = static::DEFAULT_NAMESPACE . 'InterAdmin';
			if ($this->parent_id) {
				$this->_parent = InterAdmin::getInstance($this->parent_id, $options, $parentTipo);
				if ($this->_parent->id) {
					$this->getTipo()->setParent($this->_parent);
				}
			}
		} elseif ($options['fields']) {
			$this->_parent->loadAttributes($options['fields'], $options['fields_alias']);
		}
		return $this->_parent;
	}
	/**
	 * Sets the parent InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdmin $parent
	 * @return void
	 */
	public function setParent(InterAdmin $parent = null) {
		if (isset($parent)) {
			if (!isset($parent->id)) {
				$parent->id = 0; // Necessário para que a referência funcione
			}
			if (!isset($parent->id_tipo)) {
				$parent->id_tipo = 0; // Necessário para que a referência funcione
			}
		}
		$this->attributes['parent_id'] = &$parent->id;
		$this->attributes['parent_id_tipo'] = &$parent->id_tipo;
		$this->_parent = $parent;
	}
	/**
	 * Creates and returns a child record. 
	 * 
	 * @param int $id_tipo
	 * @param array $attributes Attributes to be merged into the new record.
	 * @return 
	 */
	public function createChild($id_tipo, array $attributes = array()) {
		return $this->getChildrenTipo($id_tipo)->createInterAdmin($attributes);
	}
	/**
	 * Instantiates an InterAdminTipo object and sets this record as its parent.
	 * 
	 * @param int $id_tipo
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo
	 */
	public function getChildrenTipo($id_tipo, $options = array()) {
		if (!$options['db_prefix']) {
			$options['db_prefix'] = $this->getTipo()->db_prefix;
		}
		$options['default_class'] = static::DEFAULT_NAMESPACE . 'InterAdminTipo';
		$childrenTipo = InterAdminTipo::getInstance($id_tipo, $options);
		$childrenTipo->setParent($this);
		return $childrenTipo;
	}
	/**
	 * Retrieves this record´s children for the given $id_tipo.
	 * 
	 * @param int $id_tipo
	 * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
	 * @return array Array of InterAdmin objects.
	 */
	public function getChildren($id_tipo, $options = array()) {
		$children = array();
		if ($id_tipo) {
			$options = $options + array('fields_alias' => static::DEFAULT_FIELDS_ALIAS);
			$children = $this->getChildrenTipo($id_tipo)->find($options);
		}
		return $children;
	}
	/**
	 * Returns the number of children using COUNT(id).
	 * 
	 * @param int $id_tipo
	 * @param array $options Default array of options. Available keys: where.
	 * @return int Count of InterAdmins found.
	 */
	public function getChildrenCount($id_tipo, $options = array()) {
		$options['fields'] = array('COUNT(DISTINCT id)');
		$retorno = $this->getFirstChild($id_tipo, $options);
		return intval($retorno->count_distinct_id);
	}

	/**
	 * Returns siblings records
	 * 
	 * @return InterAdminOptions
	 */
	public function siblings() {
		return $this->getTipo()->whereNot(['id' => $this->id]);
	}

	/**
	 * Returns the first Child.
	 * 
	 * @param int $id_tipo
	 * @param array $options [optional]
	 * @return InterAdmin
	 */
	public function getFirstChild($id_tipo, $options = array()) {
		$retorno = $this->getChildren($id_tipo, array('limit' => 1) + $options);
		return $retorno[0];	
	}
	/**
	 * Returns the first Child by ID.
	 * 
	 * @param int $id_tipo
	 * @param int $id
	 * @param array $options [optional]
	 * @return InterAdmin
	 */
	public function getChildById($id_tipo, $id, $options = array()) {
		$options['limit'] = 1;
		$options['where'][] = "id = " . intval($id);
		$retorno = $this->getChildren($id_tipo, $options);
		return $retorno[0];	
	}
	/**
	 * Deletes all the children of a given $id_tipo.
	 * 
	 * @param int $id_tipo
	 * @param array $options [optional]
	 * @return int Number of deleted children.
	 */
	public function deleteChildren($id_tipo, $options = array()) {
		$children = $this->getChildren($id_tipo, $options);
		foreach ($children as $child) {
			$child->delete();
		}
		return count($children);
	}
	/**
	 *  Deletes the children of a given $id_tipo forever.
	 *  
	 * @param int $id_tipo
	 * @param array $options [optional]
	 * @return int Count of deleted InterAdmins.
	 */
	public function deleteChildrenForever($id_tipo, $options = array()) {
		if ($id_tipo) {
			$tipo = $this->getChildrenTipo($id_tipo);
			return $tipo->deleteInterAdminsForever($options);
		}
	}
	/**
	 * Creates a new InterAdminArquivo with id_tipo, id and mostrar set.
	 * 
	 * @param array $attributes [optional]
	 * @return InterAdminArquivo
	 */
	public function createArquivo(array $attributes = array()) {
		$className = static::DEFAULT_NAMESPACE . 'InterAdminArquivo';
		if (!class_exists($className)) {
			$className = 'InterAdminArquivo';
		}
		$arquivo = new $className();
		$arquivo->setParent($this);
		$arquivo->setTipo($this->getTipo());
		$arquivo->mostrar = 'S';
		$arquivo->setAttributes($attributes);
		return $arquivo;
	}
	/**
	 * Retrieves the uploaded files of this record.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, limit.
	 * @return array Array of InterAdminArquivo objects.
	 */
	public function getArquivos($options = array()) {
		$arquivos = array();
		
		$className = (class_exists($options['class'])) ? $options['class'] : static::DEFAULT_NAMESPACE . 'InterAdminArquivo';
		$arquivoModel = new $className(0);
		$arquivoModel->setTipo($this->getTipo());
		
		$this->_resolveWildcard($options['fields'], $arquivoModel);
		$this->_whereArrayFix($options['where']); // FIXME
		
		$options['fields'] = array_merge(array('id_arquivo'), (array) $options['fields']);
		$options['from'] = $arquivoModel->getTableName() . " AS main";
		$options['where'][] = "id_tipo = " . intval($this->id_tipo);
		$options['where'][] = "id = " . intval($this->id);
		$options['order'] = (($options['order']) ? $options['order'] . ',' : '') . ' ordem';
		// Internal use
		$options['aliases'] = $arquivoModel->getAttributesAliases();
		$options['campos'] = $arquivoModel->getAttributesCampos();
		
		$rs = $this->_executeQuery($options);
		
		$records = array();
		foreach ($rs as $row) {
			$arquivo = new $className($row->id_arquivo, array(
				'db_prefix' => $this->getTipo()->db_prefix,
				'db' => $this->_db
			));
			$arquivo->setTipo($this->getTipo());
			$arquivo->setParent($this);
			$this->_getAttributesFromRow($row, $arquivo, $options);
			$arquivos[] = $arquivo;
		}
		return $arquivos;
	}
	public function getFirstArquivo($options = array()) {
		$retorno = $this->getArquivos($options + array('limit' => 1));
		return $retorno[0];
	}
	/**
	 * Deletes all the InterAdminArquivo records related with this record.
	 * 
	 * @param array $options [optional]
	 * @return int Number of deleted arquivos.
	 */
	public function deleteArquivos($options = array()) {
		$arquivos = $this->getArquivos($options);
		foreach ($arquivos as $arquivo) {
			$arquivo->delete();
		}
		return count($arquivos);
	}
	
	public function createLog(array $attributes = array()) {
		$log = InterAdminLog::create($attributes);
		$log->setParent($this);
		$log->setTipo($this->getTipo());
		return $log;
	}
	
	/**
	 * Returns the full url for this record.
	 * 
	 * @return string
	 */
	public function getUrl($sep = null){
		global $seo, $seo_sep;
				
		if ($seo && $this->getParent()->id) {
			$link = $this->_parent->getUrl() . '/' . toSeo($this->getTipo()->getFieldsValues('nome'));
		} else {
			$link = $this->getTipo()->getUrl();
		}
		if ($seo) {
			$aliases = $this->getTipo()->getCamposAlias();
			if (array_key_exists('varchar_key', $aliases)) {
				$alias = $aliases['varchar_key'];
				if (isset($this->$alias)) {
					$nome = $this->$alias;
				} else {
					$nome = $this->getFieldsValues('varchar_key');
				}
			}
			if (is_null($sep)) {
				$sep = $seo_sep;
			}
			$link .= $sep . toSeo($nome);
		} else {
			$link .= '?id=' . $this->id;
		}
		return $link;
	}
	/**
	 * Sets only the editable attributes, prevents the user from setting $id_tipo, for example.
	 * 
	 * @param array $attributes
	 * @return void
	 */
	public function setAttributesSafely(array $attributes) {
		$editableFields = array_flip($this->getAttributesAliases());
		$filteredAttributes = array_intersect_key($attributes, $editableFields);
		return $this->setAttributes($filteredAttributes);
	}
	/**
	 * Sets the tags for this record. It DELETES the previous records.
	 * 
	 * @param array $tags Array of object to be saved as tags.
	 * @return void
	 */
	public function setTags(array $tags) {
		$db = $this->getDb();
		$sql = "DELETE FROM " . $this->db_prefix . "_tags WHERE parent_id = " .  $this->id;
		$db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		
		foreach ($tags as $tag) {
			$sql = "INSERT INTO " . $this->db_prefix . "_tags (parent_id, id, id_tipo) VALUES 
				(" . $this->id . "," .
				(($tag instanceof InterAdmin) ? $tag->id : 0) . "," .
				(($tag instanceof InterAdmin) ? $tag->getFieldsValues('id_tipo') : $tag->id_tipo) . ")";
			$db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		}
	}
	/**
	 * Returns the tags.
	 * 
	 * @param array $options Available keys: where, group, limit.
	 * @return array
	 */
	public function getTags($options = array()) {
		if (!$this->_tags || $options) {
			$db = $this->getDb();
			
			$options['where'][] = "parent_id = " . $this->id;	
			$sql = "SELECT * FROM " . $this->db_prefix . "_tags " .
				"WHERE " . implode(' AND ', $options['where']) .
				(($options['group']) ? " GROUP BY " . $options['group'] : '') .
				(($options['limit']) ? " LIMIT " . $options['limit'] : '');
			$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
			
			$this->_tags = array();
			while ($row = $rs->FetchNextObj()) {
				if ($tag_tipo = InterAdminTipo::getInstance($row->id_tipo)) {
					$tag_text = $tag_tipo->getFieldsValues('nome');
					if ($row->id) {
						$options = array(
							'fields' => array('varchar_key'),
							'where' => array('id = ' . $row->id)
						);
						if ($tag_registro = $tag_tipo->findFirst($options)) {
							$tag_text = $tag_registro->varchar_key . ' (' . $tag_tipo->nome . ')';
							$tag_registro->interadmin = $this;
							$retorno[] = $tag_registro;
						}
					} else {
						$tag_tipo->interadmin = $this;
						$retorno[] = $tag_tipo;
					}
				}
			}
			$rs->Close();
		} else {
			$retorno = $this->_tags;
		}
		if (!$options) {
			$this->_tags = $retorno; // cache somente para getTags sem $options
		}
		return (array) $retorno;
	}
	/**
	 * Checks if this object is published using the same rules used on interadmin_query().
	 * 
	 * @return bool
	 */
	public function isPublished() {
		global $config, $s_session;
		$this->getFieldsValues(array('date_publish', 'date_expire', 'char_key', 'publish', 'deleted'));
		return (
			strtotime($this->date_publish) <= InterAdmin::getTimestamp() &&
			(strtotime($this->date_expire) >= InterAdmin::getTimestamp() || $this->date_expire == '0000-00-00 00:00:00') &&
			$this->char_key &&
			($this->publish || $s_session['preview'] || !$config->interadmin_preview) &&
			!$this->deleted
		);
	}
	/**
	 * DEPRECATED: Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * 
	 * @param array $sqlRow
	 * @param string $tipoLanguage
	 * @deprecated Kept for backwards compatibility
	 * @return mixed
	 */
	protected function _getFieldsValuesAsString($sqlRow, $fields_alias) {
		global $lang;
		$campos = $this->getTipo()->getCampos();
		
		foreach((array) $sqlRow as $key => $value) {
			if (strpos($key, 'select_') === 0) {
				$tipoObj = $this->getCampoTipo($campos[$key]);
				$value_arr = explode(',', $value);
				$str_arr = array();
				foreach($value_arr as $value_id) {
					$str_arr[] = jp7_fields_values($tipoObj->getInterAdminsTableName(), 'id', $value_id, 'varchar_key');
				}
				$value = implode(', ', $str_arr);
			}
			if ($fields_alias) {
				$alias = $this->_tipo->getCamposAlias($key);
				unset($sqlRow->$key);
			} else {
				$alias = $key;
			}
			$this->$alias = $sqlRow->$alias = $value;
		}
	}
	/**
	 * Returns this object´s varchar_key and all the fields marked as 'combo', if the field 
	 * is an InterAdmin such as a select_key, its getStringValue() method is used.
	 *
	 * @return string For the city 'Curitiba' with the field 'state' marked as 'combo' it would return: 'Curitiba - Paraná'.
	 */
	public function getStringValue() {
		$campos = $this->getTipo()->getCampos();
		$camposCombo = array();
		if (key_exists('varchar_key', $campos)) {
			$campos['varchar_key']['combo'] = 'S';
		} elseif (key_exists('select_key', $campos)) {
			$campos['select_key']['combo'] = 'S';
		}
		foreach ($campos as $key => $campo) {
			if ($campo['combo']) {
				$camposCombo[] = $campo['tipo'];
			}
		}
		if ($camposCombo) {
			$valoresCombo = $this->getFieldsValues($camposCombo);
			$stringValue = array();
			foreach ($valoresCombo as $key => $value) {
				if ($value instanceof InterAdminFieldFile) {
					continue;
				} elseif ($value instanceof InterAdminAbstract) {
					 $value = $value->getStringValue();
				}
				$stringValue[] = $value;
			}
			return implode(' - ', $stringValue);
		}
	}
	/**
	 * Saves this record and updates date_modify.
	 * 
	 * @return void
	 */
	public function save() {
		// id_string
		if (isset($this->varchar_key)) {
			$this->id_string = toId($this->varchar_key);
			$this->id_slug = $this->generateSlug($this->varchar_key);
		} else {
			$alias_varchar_key = $this->getTipo()->getCamposAlias('varchar_key');
			if (isset($this->$alias_varchar_key)) {
				$this->id_string = toId($this->$alias_varchar_key);
				$this->id_slug = $this->generateSlug($this->$alias_varchar_key);
			}
		}
		// log
		$this->log = date('d/m/Y H:i') . ' - ' . self::getLogUser() . ' - ' . $_SERVER['REMOTE_ADDR'] . chr(13) . $this->log;
		// date_modify
		$this->date_modify = date('c');
		
		return parent::save();
	}

	public function generateSlug($string) {
		$this->getByAlias('id_slug');
		$newSlug = toSlug($string);
		if (is_numeric($newSlug)) {
			$newSlug = '--' . $newSlug;
		}
		if ($this->id_slug === $newSlug) {
			// Está igual, evitar query
			return $newSlug; 
		}
		$siblingSlugs = $this->siblings()->where('id_slug LIKE ?', "$newSlug%")->distinct('id_slug');
		
		$i = 2;
		$newSlugCopy = $newSlug;
		while (in_array($newSlug, $siblingSlugs)) {
			$newSlug = $newSlugCopy . $i;
			$i++;
		}
		return $newSlug;
	}
		
	public function getAttributesNames() {
		return $this->getTipo()->getCamposNames();
	}
	public function getAttributesCampos() {
		return $this->getTipo()->getCampos();
	}
	public function getCampoTipo($campo) {
		return $this->getTipo()->getCampoTipo($campo);
	}
	public function getAttributesAliases() {
		return $this->getTipo()->getCamposAlias();
	}
	public function getTableName() {
		if ($this->id_tipo) {
			return $this->getTipo()->getInterAdminsTableName();
		} else {
			// Compatibilidade, tenta encontrar na tabela global
			return $this->db_prefix . $this->table;
		}
	}
    /**
     * Returns $log_user. If $log_user is NULL, returns $s_user['login'] on 
     * applications and 'site' otherwise.
     * 
     * @see InterAdmin::$log_user
     * @return string
     */
    public static function getLogUser() {
    	global $jp7_app, $s_user;
    	if (is_null(self::$log_user)) {
    		return ($jp7_app) ? $s_user['login'] : 'site';	
		}
		return self::$log_user;
    }
  	/**
     * Sets $log_user and returns the old value.
     *
     * @see 	InterAdmin::$log_user
     * @param 	object 	$log_user
     * @return 	string	Old value.
     */
    public static function setLogUser($log_user) {
        $old_user = self::$log_user;
		self::$log_user = $log_user;
		return $old_user;
    }
	/**
	 * Enables or disables published filters.
	 * 
	 * @param bool $bool
	 * @return bool Returns the previous value.
	 */
	public static function setPublishedFiltersEnabled($bool) {
		$oldValue = self::$publish_filters_enabled;
		self::$publish_filters_enabled = (bool) $bool;
		return $oldValue;
	}
	/**
	 * Returns TRUE if published filters are enabled.
	 * 
	 * @return bool $bool
	 */
	public static function isPublishedFiltersEnabled() {
		return self::$publish_filters_enabled;
	}
	public static function getTimestamp() {
		return isset(self::$timestamp) ? self::$timestamp : time();
	}
	public static function setTimestamp($time) {
		self::$timestamp = $time;
	}	
	/**
	 * Merges two option arrays.
	 * 
	 * Values of 'where' will be merged
	 * Values of 'fields' will be merged
	 * Other values (such as 'limit') can be overwritten by the $extended array of options.
	 * 
	 * @param array $initial 	Initial array of options.
	 * @param array $extended 	Array of options that will extend the initial array.
	 * @return array 			Array of $options properly merged.
	 */
	public static function mergeOptions($initial, $extended) {
		if (!$extended) {
			return $initial;
		}
		if (isset($initial['fields']) && isset($extended['fields'])) {
			$extended['fields'] = array_merge($extended['fields'], $initial['fields']);
		}
		if (isset($initial['where']) && isset($extended['where'])) {
			if (!is_array($extended['where'])) {
				$extended['where'] = array($extended['where']);
			}
			$extended['where'] = array_merge($extended['where'], $initial['where']);
		}
		return $extended + $initial;
	}
	
	public function getTagFilters() {
		return "(tags.id = " . $this->id . " AND tags.id_tipo = '" . $this->getTipo()->id_tipo . "')";
	}
    
    /**
     * @see InterAdminAbstract::getAdminAttributes()
     */
    public function getAdminAttributes() {
		return $this->getTipo()->getInterAdminsAdminAttributes();
    }
	
    /**
     * Searches $value on the relationship and sets the $attribute  
     * @param string $attribute
     * @param string $searchValue
     * @param string $searchColumn
     * @throws Exception
     */
    public function setAttributeBySearch($attribute, $searchValue, $searchColumn = 'varchar_key') {
		$campos = $this->getTipo()->getCampos();
		$aliases = array_flip($this->getTipo()->getCamposAlias());
		$nomeCampo = $aliases[$attribute] ? $aliases[$attribute] : $attribute;
		
		if (!startsWith('select_', $nomeCampo)) {
			throw new Exception('The field ' . $attribute . ' is not a select. It was expected a select field on setAttributeBySearch.');
		}
		
		$campoTipo = $this->getCampoTipo($campos[$nomeCampo]);
		$record = $campoTipo->findFirst(array(
			'where' => array($searchColumn . " = '" . $searchValue . "'")
		));
		if (startsWith('select_multi_', $nomeCampo)) {
			$this->$attribute = array($record);
		} else {
			$this->$attribute = $record;
		}
    }
    
	/**
	 * @deprecated use setAttributeBySearch
	 */
	public function setFieldBySearch($attribute, $searchValue, $searchColumn = 'varchar_key') {
		return $this->setAttributeBySearch($attribute, $searchValue, $searchColumn);
	}
	
	public function getRelationshipData($relationship) {
		$aliases = $this->getAttributesAliases();
		
		$campoNome = array_search($relationship, $aliases);
		$alias = true;
		if ($campoNome === false) {
			$campoNome = $relationship;
			$alias = false;
		}
		$campos = $this->getAttributesCampos();
		$campoTipo = $this->getCampoTipo($campos[$campoNome]);
		if ($campoTipo instanceof InterAdminTipo) {
			return array(
				'type' => 'select',
				'tipo' => $campoTipo,
				'name' => $relationship,
				'alias' => $alias
			);
		}
		if ($childrenTipo = $this->getChildrenTipoByNome($relationship)) {
			return array(
				'type' => 'children',
				'tipo' => $childrenTipo,
				'name' => $relationship,
				'alias' => true
			);
		}
		throw new Exception('Unknown relationship: ' . $relationship);
	}
	
	public function setEagerLoad($key, $data) {
		$this->_eagerLoad[$key] = $data;
	}
}