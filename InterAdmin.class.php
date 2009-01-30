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
class InterAdmin{
	/**
	 * This record's 'id'.
	 * @var int
	 */
	public $id;
	/**
	 * This record's 'id_tipo', this value can be used to get the InterAdminTipo for a InterAdmin object.
	 * @var int
	 */
	public $id_tipo;
	/**
	 * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 */
	public $db_prefix;
	/**
	 * Table suffix of this record. e.g.: the table 'interadmin_client_registrations' would have 'registrations' as $table.
	 * @var string
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
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * @param int $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias.
	 */
	public function __construct($id = 0, $options = array()) {
		$this->id = $id;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		$this->table = ($options['table']) ? '_' . $options['table'] : '';
		if ($options['fields']) $this->getFieldsValues($options['fields'], FALSE, $options['fields_alias']);
	}
	/**
	 * Returns an InterAdmin instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class.
	 * @return InterAdmin Returns an InterAdmin or a child class in case it's defined on the 'class' property of its InterAdminTipo.
	 */
	public static function getInstance($id, $options = array()) {
		if ($options['class']) {
			$class_name = (class_exists($options['class'])) ? $options['class'] : 'InterAdmin';
		} else {
			$instance = new InterAdmin($id, array_merge($options, array('fields' => array())));
			$class_name = $instance->getTipo()->class;
			if (!class_exists($class_name)) {
				if ($options['fields']) $instance->getFieldsValues($options['fields'], FALSE, $options['fields_alias']);
				return $instance;
			}
		}
		return new $class_name($id, $options);
	}
	/**
	 * String value of this record´s $id.
	 * 
	 * @return string String value of the $id property.
	 */
	public function __toString(){
		return (string) $this->id;
	}

	/**
	 * Gets values from this record on the database.
	 *
	 * @param array|string $fields Array of fields or name of the field to be retrieved, '*' to get all the fields.
	 * @param bool $forceAsString Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * @param bool $fields_alias If <tt>TRUE</tt> the names of the fields are replaced by the Alias that were inserted on the InterAdmin.
	 * @return mixed If $fields is an array an object will be returned, otherwise it will return the value retrieved.
	 * @todo (FIXME - Multiple languages) When $fields_alias is <tt>TRUE</tt> and there is no id_tipo yet, the function is unable to decide which language table it should use.
	 */
	public function getFieldsValues($fields, $forceAsString = FALSE, $fields_alias = FALSE) {   
		global $lang;
		if (!$this->_tipo) $this->getTipo();
		if ($lang->prefix) $tipoLanguage = $this->_tipo->getFieldsValues('language');
		if ($fields == '*') $fields = $this->_tipo->getAllFieldsNames(); 
		
		$rs = $this->_tipo->executeQuery(array(
			'fields' => (array) $fields,
			'from' => $this->db_prefix . $this->table . (($tipoLanguage) ? $lang->prefix : '') . " AS main",
			'where' => "main.id = " . (($this->id) ? $this->id : 0)
		));
		
		$fieldsValues = $rs->FetchNextObj();
		
		// Force As String - Kept for backwards compatibility
		if ($forceAsString) {
			foreach((array)$fieldsValues as $key=>$value) {
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
					unset($fieldsValues->$key);
				} else {
					$alias = $key;
				}
				$this->$alias = $fieldsValues->$alias = $value;
			}
		} else {
			$this->_tipo->putOrmData($this, $fieldsValues, array(
				'fields' => $fields,
				'fields_alias' => $fields_alias
			));
		}
		if (is_array($fields)) return $fieldsValues;
		else return $fieldsValues->$fields;
	}
	/**
	 * Returns this object´s varchar_key and all the fields marked as 'combo', if the field 
	 * is an InterAdmin such as a select_key, its getStringValue() method is used.
	 *
	 * @return string For the city 'Curitiba' with the field 'state' marked as 'combo' it would return: 'Curitiba - Paraná'.
	 */
	public function getStringValue() {
		$campos = $this->getTipo()->getCampos();
		//jp7_print_r($campos);
		foreach ($campos as $key => $row) {
			if (($row['combo'] || $key == 'varchar_key' || $key == 'select_key') && $key !== 'char_key') {
				$return[] = $row['tipo'];
			}
		}
		$return_str = (array) $this->getFieldsValues($return);
		foreach ($return_str as $key=>$value) {
			if (strpos($key, 'select_') === 0 && $value) $value = $value->getStringValue();
			$return_final[] = $value;
		}
		return implode(' - ', (array) $return_final);
	}
	/**
	 * Updates the values into the database table. If this object has no 'id', the data is inserted.
	 * 
	 * @param array $fields_values Array with the values, the keys are the fields names.
	 * @param bool $force_magic_quotes_gpc If TRUE the string will be quoted even if 'magic_quotes_gpc' is not active. 
	 * @return void
	 */
	public function setFieldsValues($fields_values, $force_magic_quotes_gpc = FALSE){
		global $lang;
		$tipoLanguage = $this->getTipo()->getFieldsValues('language');
		if ($this->id) {
			jp7_db_insert($this->db_prefix . $this->table . (($tipoLanguage) ? $lang->prefix : ''), 'id', $this->id, $fields_values, TRUE, $force_magic_quotes_gpc);
		} else {
			$this->id = jp7_db_insert($this->db_prefix . $this->table . (($tipoLanguage) ? $lang->prefix : ''), 'id', 0, $fields_values, TRUE, $force_magic_quotes_gpc);
		}
	}
	/**
	 * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
	 * 
	 * @param array $options Default array of options. Available keys: class.
	 * @return InterAdminTipo
	 */
	public function getTipo($options = array()) {
		if (!$this->_tipo) {
			if (!$this->id_tipo) $this->id_tipo = jp7_fields_values($this->db_prefix . $this->table, 'id', $this->id, 'id_tipo');
			$this->_tipo = InterAdminTipo::getInstance($this->id_tipo, array('db_prefix' => $this->db_prefix, 'class' => $options['class']));
		}
		return $this->_tipo;
	}
	/**
	 * Sets the InterAdminTipo object for this record, changing the $_tipo property.
	 *
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function setTipo($tipo) {
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
		if ($this->_parent) return $this->_parent;
		if (!$this->parent_id) $this->getFieldsValues('parent_id');
		return $this->_parent = InterAdmin::getInstance($this->parent_id, $options);
	}
	/**
	 * Sets the parent InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdmin $parent
	 * @return void
	 */
	public function setParent($parent) {
		$this->_parent = $parent;
	}
	/**
	 * Instantiates an InterAdminTipo object and sets this record as its parent.
	 * 
	 * @param int $id_tipo
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo
	 */
	public function getChildrenTipo($id_tipo, $options = array()) {
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
		if ($id_tipo) $children = $this->getChildrenTipo($id_tipo)->getInterAdmins($options); 
		return $children;
	}
	/**
	 * Retrieves the uploaded files of this record.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, limit.
	 * @return array Array of InterAdminArquivo objects.
	 */
	public function getArquivos($options = array()) {
		global $db;
		global $lang;
		global $jp7_app;
		$arquivos = array();
	
		if ($options['fields'] == '*')  $options['fields'] = InterAdminArquivo::getAllFieldsNames();

		$sql = "SELECT id_arquivo" . (($options['fields']) ? ',' . implode(',', (array)$options['fields']) : '') . 
			" FROM " . $this->db_prefix .(($this->getTipo()->getFieldsValues('language')) ? $lang->prefix : '') . '_arquivos' .
			" WHERE id=" . $this->id .
			(($options['where']) ? $options['where'] : '') .
			" ORDER BY " . (($options['order']) ? $options['order'] . ',' : '') . ' ordem' .
			(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		if ($jp7_app) $rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$arquivo = new InterAdminArquivo($row->id_arquivo, array('db_prefix' => $this->db_prefix));
			$arquivo->setTipo($this->getTipo());
			$arquivo->setParent($this);
			foreach((array)$options['fields'] as $field) {
				$arquivo->$field = $row->$field;
			}
			$arquivos[] = $arquivo;
		}
		$rs->Close();
		return $arquivos;
	}
	/**
	 * Returns the full url for this record.
	 * 
	 * @return string
	 */
	public function getUrl(){
		return $this->getTipo()->getUrl() . '?id=' . $this->id;
	}
}
?>