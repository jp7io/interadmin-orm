<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdminArquivo
 */
 
/**
 * Class which represents records on the table interadmin_{client name}_arquivos.
 *
 * @package InterAdminArquivo
 */
class InterAdminArquivo{
	/**
	 * This record's 'id_arquivo', which is its primary key.
	 * @var int
	 */	
	public $id_arquivo;
	/**
	 * This record's 'id', which is the parent of this InterAdminArquivo.
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
	 * 
	 * @param int $id_arquivo This record's 'id_arquivo'.
	 * @param array $options Default array of options. Available keys: db_prefix, fields.
	 */
	public function __construct($id_arquivo = 0, $options = array()) {
		$this->id_arquivo = $id_arquivo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) $this->getFieldsValues($options['fields']);
	}
	/**
	 * String value of this record´s $id_arquivo.
	 * 
	 * @return string String value of the $id_arquivo property.
	 */
	public function __toString(){
		return (string) $this->id_arquivo;
	}	
	/**
	 * Gets values from this record on the database.
	 *
	 * @param mixed $fields Array (recommended) or string (an unique field) containning the names of the fields to be retrieved.
	 * @return mixed If fields were an array an object will be returned, otherwise it will return the result as a string.
	 */
	function getFieldsValues($fields) {   
		global $lang, $db;
		if (!$this->_tipo) $this->getTipo();
		if ($lang->prefix) $tipoLanguage = $this->_tipo->getFieldsValues('language');
		if ($fields == '*') $fields = InterAdminArquivo::getAllFieldsNames();
		 
		$sql = "SELECT " . implode(',' , (array) $fields) . 
			" FROM " . $this->db_prefix . (($tipoLanguage) ? $lang->prefix : '') . "_arquivos" .
			" WHERE id_arquivo = " . $this->id_arquivo;
		$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		if ($fieldsValues = $rs->FetchNextObj()) {
			foreach ((array) $fieldsValues as $field=>$value) {
				$this->$field = $value;
			}
			if (is_array($fields)) return $fieldsValues;
			else return $fieldsValues->$fields;
		}
	}
	/**
	 * Updates the values into the database table. If this object has no 'id_arquivo', the data is inserted.
	 * 
	 * @param array $fields_values Array with the values, the keys are the fields names.
	 * @param bool $force_magic_quotes_gpc If TRUE the string will be quoted even if 'magic_quotes_gpc' is not active. 
	 * @return void
	 */
	public function setFieldsValues($fields_values, $force_magic_quotes_gpc = FALSE) {
		global $lang;
		$tipoLanguage = $this->getTipo()->getFieldsValues('language');
		if ($this->id_arquivo) {
			jp7_db_insert($this->db_prefix . (($tipoLanguage) ? $lang->prefix : '')  . '_arquivos', 'id_arquivo', $this->id_arquivo, $fields_values, TRUE, $force_magic_quotes_gpc);
		} else {
			$this->id_arquivo = jp7_db_insert($this->db_prefix . (($tipoLanguage) ? $lang->prefix : '')  . '_arquivos', 'id_arquivo', 0, $fields_values, TRUE, $force_magic_quotes_gpc);
		}
	}
	/**
	 * Returns an array with the names of all the fields.
	 * 
	 * @return array
	 */
	public static function getAllFieldsNames(){
		return array('id_arquivo', 'id_tipo', 'id', 'parte', 'url', 'url_thumb', 'url_zoom', 'url_mac', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted');
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
		if ($this->id || $this->getFieldsValues('id')) {
			return $this->_parent = InterAdmin::getInstance($this->id, $options);
		}
	}
	/**
	 * Sets the parent InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdmin $parent
	 * @return void
	 */
	public function setParent($parent) {
		$this->id = $parent->id;
		$this->_parent = $parent;
	}
	/**
	 * Returns the full url address of this file.
	 *
	 * @return string
	 */
	public function getUrl(){
		global $c_url;
		$url = ($this->url) ? $this->url : $this->getFieldsValues('url');
		$url = str_replace('../../', $c_url, $url);
		return $url; 
	}
}
?>