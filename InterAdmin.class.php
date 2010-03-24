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
class InterAdmin extends InterAdminAbstract {
	/**
	 * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 * @deprecated It will only use this property if there is no id_tipo yet
	 */
	public $db_prefix;
	/**
	 * Table suffix of this record. e.g.: the table 'interadmin_client_registrations' would have 'registrations' as $table.
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
	/**
	 * Username to be inserted in the log when saving this record.
	 * @var string
	 */
	protected static $log_user = null;
	/**
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * @param int $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias.
	 */
	public function __construct($id = 0, $options = array()) {
		if (is_object($id)) {
			$id = (string) $id;
		}
		$this->id = intval($id);
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		$this->table = ($options['table']) ? '_' . $options['table'] : '';
		if ($options['fields'] && $id) {
			$options = $options + array('fields_alias' => $this->staticConst('DEFAULT_FIELDS_ALIAS'));
			$this->getFieldsValues($options['fields'], false, $options['fields_alias']);
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
		// Classe não foi forçada
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
			$finalInstance->setTipo($tipo);
		}
		// Fields		
		if ($options['fields']) {
			$finalInstance->getFieldsValues($options['fields'], false, $options['fields_alias']);
		}
		return $finalInstance;
	}
	/**
	 * Magic method calls
	 * 
	 * Available magic methods:
	 * - create{Child}(array $attributes = array())
	 * - get{Children}(array $options = array())
	 * - getFirst{Child}(array $options = array())
	 * - get{Child}ById(int $id, array $options = array())
	 * - delete{Children}(array $options = array())
	 * 
	 * @param string $methodName
	 * @return mixed
	 */
	public function __call($methodName, $args) {
		$children = $this->getTipo()->getInterAdminsChildren();
		// get*
		if (strpos($methodName, 'get') === 0) {
			// getFirst*
			if (strpos($methodName, 'getFirst') === 0) {
				$nome_id = Jp7_Inflector::tableize(substr($methodName, 8));
				if ($child = $children[$nome_id]) {
					return $this->getFirstChild($child['id_tipo'], (array) $args[0]);
				}
			// get*ById
			} elseif (substr($methodName, -4) == 'ById') {
				$nome_id = Jp7_Inflector::tableize(substr($methodName, 3, -4));
				if ($child = $children[$nome_id]) {
					$options = (array) $args[1];
					$options['where'][] = "id = " . intval($args[0]);
					return $this->getFirstChild($child['id_tipo'], $options);
				}
			} else {
				$nome_id = Jp7_Inflector::underscore(substr($methodName, 3)); 
				if ($child = $children[$nome_id]) {
					return $this->getChildren($child['id_tipo'], (array) $args[0]);
				}
			} 
		// create*
		} elseif (strpos($methodName, 'create') === 0) {
			$nome_id = Jp7_Inflector::tableize(substr($methodName, strlen('create'))); 
			if ($child = $children[$nome_id]) {
				return $this->createChild($child['id_tipo'], (array) $args[0]);
			}
		// delete *
		} elseif (strpos($methodName, 'delete') === 0) {
			$nome_id = Jp7_Inflector::underscore(substr($methodName, strlen('delete')));
			if ($child = $children[$nome_id]) {
				return $this->deleteChildren($child['id_tipo'], (array) $args[0]);
			}
		} 
		// Default error when method doesn´t exist
		trigger_error('Call to undefined method ' . get_class($this) . '->' . $methodName . '()', E_USER_ERROR);
	}
	/**
	 * Gets fields values by their alias.
	 *  
	 * @param array|string $fields
	 * @see InterAdmin::getFieldsValues()
	 * @return 
	 */
	public function getByAlias($fields) {
		if (func_num_args() > 1) {
			throw new Exception('Only 1 argument is expected and it should be an array.');
		}
		return $this->getFieldsValues($fields, false, true);
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
				$id_tipo = jp7_fields_values($this->getTableName(), 'id', $this->id, 'id_tipo');
			}
			$this->setTipo(InterAdminTipo::getInstance($id_tipo, array(
				'db_prefix' => $this->db_prefix,
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
		if (!$this->parent_id) {
			$this->getFieldsValues('parent_id');
		}
		if (!$this->_parent && $this->parent_id) {
			$options['default_class'] = $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdmin';
			$this->_parent = InterAdmin::getInstance($this->parent_id, $options);
			if ($this->_parent->id) {
				$this->getTipo()->setParent($this->_parent);
			}
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
		if (!isset($parent->id)) {
			$parent->id = 0; // Necessário para que a referência funcione
		}
		$this->attributes['parent_id'] = &$parent->id;
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
		$options['default_class'] = $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdminTipo';
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
		global $db;
		$children = array();
		if ($id_tipo) {
			$options = $options + array('fields_alias' => $this->staticConst('DEFAULT_FIELDS_ALIAS'));
			$children = $this->getChildrenTipo($id_tipo)->getInterAdmins($options);
		}
		return $children;
	}
	/**
	 * Returns the first Child.
	 * 
	 * @param int $id_tipo
	 * @param array $options [optional]
	 * @return InterAdmin
	 */
	public function getFirstChild($id_tipo, $options = array()) {
		return reset($this->getChildren($id_tipo, array('limit' => 1) + $options));	
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
	 * Creates a new InterAdminArquivo with id_tipo, id and mostrar set.
	 * 
	 * @param array $attributes [optional]
	 * @return InterAdminArquivo
	 */
	public function createArquivo(array $attributes = array()) {
		$arquivo = new InterAdminArquivo();
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
		
		$className = (class_exists($options['class'])) ? $options['class'] : $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdminArquivo';
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
		while ($row = $rs->FetchNextObj()) {
			$arquivo = new $className($row->id_arquivo, array('db_prefix' => $this->getTipo()->db_prefix));
			$arquivo->setTipo($this->getTipo());
			$arquivo->setParent($this);
			$this->_getAttributesFromRow($row, $arquivo, $options);
			$arquivos[] = $arquivo;
		}
		return $arquivos;
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
			$alias = $this->getTipo()->getCamposAlias('varchar_key');
			if (isset($this->$alias)) {
				$nome = $this->$alias;
			} else {
				$nome = $this->getFieldsValues('varchar_key');
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
	 * Sets the tags for this record. It DELETES the previous records.
	 * 
	 * @param array $tags Array of object to be saved as tags.
	 * @return void
	 */
	public function setTags(array $tags) {
		global $db;
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
			global $db;
			
			$options['where'][] = "parent_id = " . $this->id;	
			$sql = "SELECT * FROM " . $this->db_prefix . "_tags " .
				"WHERE " . implode(' AND ', $options['where']) .
				(($options['group']) ? " GROUP BY " . $options['group'] : '') .
				(($options['limit']) ? " LIMIT " . $options['limit'] : '');
			$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
			
			$this->_tags = array();
			while ($row = $rs->FetchNextObj()) {
				$tag_tipo = InterAdminTipo::getInstance($row->id_tipo);
				$tag_text = $tag_tipo->getFieldsValues('nome');
				if ($row->id) {
					$options = array(
						'fields' => array('varchar_key'),
						'where' => array('id = ' . $row->id)
					);
					$tag_registro = $tag_tipo->getFirstInterAdmin($options);
					$tag_text = $tag_registro->varchar_key . ' (' . $tag_tipo->nome . ')';
					$tag_registro->interadmin = $this;
					$retorno[] = $tag_registro;
				} else {
					$tag_tipo->interadmin = $this;
					$retorno[] = $tag_tipo;
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
		global $c_publish, $s_session;
		$this->getFieldsValues(array('date_publish', 'date_expire', 'char_key', 'publish', 'deleted'));
		return (
			strtotime($this->date_publish) <= time() &&
			(strtotime($this->date_expire) >= time() || $this->date_expire == '0000-00-00 00:00:00') &&
			$this->char_key &&
			($this->publish || $s_session['preview'] || !$c_publish) &&
			!$this->deleted
		);
	}
	/**
	 * Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * 
	 * @param array $sqlRow
	 * @param string $tipoLanguage
	 * @deprecated Kept for backwards compatibility
	 * @return mixed
	 */
	protected function _getFieldsValuesAsString($sqlRow, $tipoLanguage) {
		global $lang;
		foreach((array) $sqlRow as $key => $value) {
			if (strpos($key, 'select_') === 0) {
				$value_arr = explode(',', $value);
				$str_arr = array();
				foreach($value_arr as $value_id) {
					$str_arr[] = jp7_fields_values($this->db_prefix . (($tipoLanguage) ? $lang->prefix : ''), 'id', $value_id, 'varchar_key');
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
		
		if (is_array($fields)) {
			return $sqlRow;
		} else {
			return $sqlRow->$fields;
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
		foreach ($campos as $key => $campo) {
			if (($campo['combo'] || $key == 'varchar_key' || $key == 'select_key') && $key !== 'char_key') {
				$camposCombo[] = $campo['tipo'];
			}
		}
		$valoresCombo = $this->getFieldsValues($camposCombo);
		$stringValue = array();
		foreach ($valoresCombo as $key => $value) {
			if (is_object($value)) {
				 $value = $value->getStringValue();
			}
			$stringValue[] = $value;
		}
		return implode(' - ', $stringValue);
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
		} else {
			$alias_varchar_key = ($this->getTipo()->getCamposAlias('varchar_key'));
			if (isset($this->$alias_varchar_key)) {
				$this->id_string = toId($this->$alias_varchar_key);
			}
		}
		// log
		if ($this->id && !isset($this->log)) {
			$this->getFieldsValues('log');
		}
		$this->log = date('d/m/Y H:i') . ' - ' . self::getLogUser() . ' - ' . $_SERVER['REMOTE_ADDR'] . chr(13) . $this->log;
		// date_modify
		$this->date_modify = date('c');
				
		return parent::save();
	}
	public function getAttributesNames() {
		return $this->getTipo()->getCamposNames();
	}
	public function getAttributesCampos() {
		return $this->getTipo()->getCampos();
	}
	protected function _getCampoTipo($campo) {
		return $this->getTipo()->_getCampoTipo($campo);
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
     * Sets $log_user.
     *
     * @see InterAdmin::$log_user
     * @param object $log_user
     * @return void
     */
    public static function setLogUser($log_user) {
        self::$log_user = $log_user;
    }
}