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
 * Class representing records on the table interadmin_{client name}_logs.
 *
 * @package InterAdmin
 */
class InterAdminLog extends InterAdminAbstract {
	const ACTION_VIEW = 'view';
	const ACTION_LOGIN = 'login';
	const ACTION_INSERT = 'insert';
	const ACTION_MODIFY = 'modify';
	
	protected $_primary_key = 'id_log';
	
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
	 * @param int $id_log This record's 'id_log'.
	 * @param array $options Default array of options. Available keys: db_prefix, db, fields.
	 */
	public function __construct($id_log = 0, $options = array()) {
		$this->id_log = $id_log;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		$this->_db = $options['db'] ? $options['db'] : $GLOBALS['db'];
		
		if ($options['fields']) {
			$this->getFieldsValues($options['fields']);
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
			if (!$this->id_tipo) {
				$this->id_tipo = jp7_fields_values($this->getTableName(), 'id_log', $this->id_log, 'id_tipo');
			}
			$this->_tipo = InterAdminTipo::getInstance($this->id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'db' => $this->_db,
				'class' => $options['class']
			));
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
		if (!$this->_parent) {
			$tipo = $this->getTipo();
			if ($this->id || $this->getFieldsValues('id')) {
				$this->_parent = InterAdmin::getInstance($this->id, $options, $tipo);
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
	public function setParent($parent) {
		$this->id = $parent->id;
		$this->_parent = $parent;
	}
	
    function getAttributesAliases() {
       return array();
    }
    function getAttributesCampos() {
		return array();
    }
    function getAttributesNames() {
		return array('id_log', 'id', 'id_tipo', 'lang', 'action', 'ip', 'data', 'select_user', 'date_insert');
    }
	function getTableName() {
    	return $this->db_prefix . '_logs';
    }
    /**
     * @see InterAdminAbstract::getCampoTipo()
     */
    public function getCampoTipo($campo) {
       return;
    }
 	public function getTagFilters() {
		return '';
	} 
 	/**
     * @see InterAdminAbstract::getAdminAttributes()
     */
    public function getAdminAttributes() {
        return array();
    }
	public static function create($attributes = array()) {
		global $s_user, $lang;
		$log = new InterAdminLog();
		
		$log->lang = $lang->lang;
		$log->ip = $_SERVER['REMOTE_ADDR'];
		$log->select_user = $s_user['id'];
		$log->date_insert = date('c');
		
		$log->setAttributes($attributes);
		return $log;	
	}
	
	public static function countLogs($options = array()) {
		$logs = InterAdminLog::findLogs(array(
			'fields' => 'count(id)'
		) + $options);
		return $logs[0]->count_id;
	}
	
	public static function findLogs($options = array()) {
		$instance = new self();
		if ($options['db']) {
			$instance->setDb($options['db']);
		}
		if ($options['db_prefix']) {
			$instance->db_prefix = $options['db_prefix'];
		}
		
		$options['fields'] = array_merge(array('id_log'), (array) $options['fields']);
		$options['from'] = $instance->getTableName() . ' AS main';
		
		if (!$options['where']) {
			$options['where'][] = '1 = 1';
		}
	 	if (!$options['order']) {
	 		$options['order'] = 'date_insert DESC';
		}
		// Internal use
		$options['aliases'] = $instance->getAttributesAliases();
		$options['campos'] = $instance->getAttributesCampos();
		
		$rs = $instance->_executeQuery($options);
		$logs = array();
		
		while ($row = $rs->FetchNextObj()) {
			$log = new InterAdminLog($row->id_tipo, array(
				'db_prefix' => $instance->db_prefix,
				'db' => $instance->getDb()
			));
			$instance->_getAttributesFromRow($row, $log, $options);
			$logs[] = $log;
		}
		return $logs;
	}
	
	public static function getPublishedFilters($table, $alias) {
		// Não precisa
	}	
}
