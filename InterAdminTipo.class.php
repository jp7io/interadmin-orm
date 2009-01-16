<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdminTipo
 */
 
/**
 * Class which represents records on the table interadmin_{client name}_tipos.
 * 
 * @property string $interadminsOrderby SQL Order By for the records of this InterAdminTipo.
 * @property string $class Class to be instantiated for the records of this InterAdminTipo.
 * @package InterAdminTipo
 */
class InterAdminTipo{
	/**
	 * This record's 'id_tipo'.
	 * @var int
	 */
	public $id_tipo;
	/**
	 * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 */
	public $db_prefix;
	/**
	 * Caches the url retrieved by getUrl().
	 * @var string
	 */
	protected $_url;
	/**
	 * Caches the data retrieved by getCampos().
	 * @var array
	 */
	protected $_campos;
	/**
	 * Contains the parent InterAdminTipo object, i.e. the record with an 'id_tipo' equal to this record's 'parent_id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_parent;
	/**
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * 
	 * @param int $id_tipo This record's 'id_tipo'.
	 * @param array $options Default array of options. Available keys: db_prefix, fields.
	 */
	public function __construct($id_tipo = 0, $options = array()) {
		$this->id_tipo = $id_tipo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) $this->getFieldsValues($options['fields']);
	}
	/**
	 * Returns an InterAdminTipo instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id_tipo This record's 'id_tipo'.
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo Returns an InterAdminTipo or a child class in case it's defined on its 'class_tipo' property.
	 */
	public static function getInstance($id_tipo, $options = array()){
		if ($options['class']) {
			$class_name = (class_exists($options['class'])) ? $options['class'] : 'InterAdminTipo';
		} else {
			$instance = new InterAdminTipo($id_tipo, array_merge($options, array('fields' => array('model_id_tipo', 'class_tipo'))));
			if ($instance->class_tipo) $class_name = $instance->class_tipo;
			else $class_name = jp7_fields_values($instance->db_prefix . '_tipos', 'id_tipo', $instance->model_id_tipo, 'class_tipo');
			if (!class_exists($class_name)) {
				if ($options['fields']) $instance->getFieldsValues($options['fields']);
				return $instance;
			}
		}
		return new $class_name($id_tipo, $options);
	}
	/**
	 * String value of this record´s $id_tipo.
	 *
	 * @return string String value of the $id_tipo property.
	 */
	public function __toString() {
		return (string) $this->id_tipo;
	}
	/**
	 * Retrieves magic properties.
	 *
	 * @param string $var Magic property 'interadminsOrderby' or 'class'.
	 * @return mixed
	 */
	public function __get($var) {
		if ($var == 'interadminsOrderby') {
			$campos = $this->getCampos();
			if ($campos) {
				foreach ($campos as $key=>$row) {
					if ($row['orderby'])$tipo_orderby[$row['orderby']] = $key;
				}
				if ($tipo_orderby) {
					ksort($tipo_orderby);
					$tipo_orderby = implode(",", $tipo_orderby);
				}
				$this->$var = $tipo_orderby;
			}
			if (!count($tipo_orderby)) {
				$this->$var = 'date_publish DESC';
			}
			return $this->$var;
		} elseif ($var == 'class') {
			return ($this->getFieldsValues('class')) ? $this->class : $this->getModel()->getFieldsValues('class');	
		}
	}
	/**
	 * Gets values from this record on the database.
	 *
	 * @param array|string $fields Array (recommended) or string (an unique field) containning the names of the fields to be retrieved.
	 * @return mixed If $fields is an array an object will be returned, otherwise it will return the value retrieved.
	 */
	public function getFieldsValues($fields) {
		$fieldsValues = jp7_fields_values($this->db_prefix.'_tipos', 'id_tipo', $this->id_tipo, $fields, TRUE);
		foreach ((array) $fieldsValues as $field=>$value) {
			$this->$field = $value;
		}
		if (is_array($fields)) return $fieldsValues;
		elseif ($fields) return $fieldsValues->$fields;
	}
	/**
	 * Gets the parent InterAdminTipo object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo
	 */
	public function getParent($options = array()) {
		if ($this->_parent) return $this->_parent;
		if ($this->parent_id_tipo || $this->getFieldsValues('parent_id_tipo')) {
			return $this->_parent = InterAdminTipo::getInstance($this->parent_id_tipo, $options);
		}
	}
	/**
	 * Sets the parent InterAdminTipo or InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdminTipo|InterAdmin $parent
	 * @return void
	 */
	public function setParent($parent) {
		$this->_parent = $parent;
	}
	/**
	 * Retrieves the children of this InterAdminTipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, class.
	 * @return array Array of InterAdminTipo objects.
	 */
	public function getChildren($options = array()){
		global $db;
		global $jp7_app;
		$options['fields'] = array_merge(array('id_tipo'), (array) $options['fields']);
		$options['from'] = $this->db_prefix . "_tipos AS main";
		$options['where'] = "parent_id_tipo = " . $this->id_tipo . $options['where'];
	 	if (!$options['order']) $options['order'] = 'ordem, nome';

		$rs = $this->executeQuery($options);
		
		while ($row = $rs->FetchNextObj()) {
			$interAdminTipo = InterAdminTipo::getInstance($row->id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'class' => $options['class']
			));
			$interAdminTipo->setParent($this);
			$this->putOrmData($interAdminTipo, $row, $options);
			$interAdminTipos[] = $interAdminTipo;
		}
		$rs->Close();
		return $interAdminTipos;
	}
	/**
	 * Retrieves the children of this InterAdminTipo which have the given model_id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, class.
	 * @return Array of InterAdminTipo objects.
	 */
	public function getChildrenByModel($model_id_tipo, $options = array()) {
		$options['where'] .= " AND main.model_id_tipo = " . $model_id_tipo;
		return $this->getChildren($options);
	}
	/**
	 * Retrieves the records which have this InterAdminTipo's id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
	 * @return array Array of InterAdmin objects.
	 */
	public function getInterAdmins($options = array()) {
		global $lang;
		$model = $this->getModel();
		$table = ($model->getFieldsValues('tabela')) ? '_' . $model->tabela : '';

		if ($options['fields'] == '*') $options['fields'] = $this->getAllFieldsNames();
		$options['fields'] = array_merge(array('id'), (array) $options['fields']);
		$options['from'] = $this->db_prefix . $table . (($this->getFieldsValues('language')) ? $lang->prefix : '') . " AS main";
		$options['where'] = "main.id_tipo = " . $this->id_tipo . $options['where'];
		if ($this->_parent && $this->_parent instanceof InterAdmin) $options['where'] .= " AND main.parent_id = " . $this->_parent->id;	
		$options['order'] = (($options['order']) ? $options['order'] . ',' : '') . $this->interadminsOrderby;
		
		$rs = $this->executeQuery($options);
		
		$interAdmins = array();
		while ($row = $rs->FetchNextObj()) {
			$class_name = ($options['class']) ? $options['class'] : $this->class;
			$interAdmin = InterAdmin::getInstance($row->id, array(
				'db_prefix' => $this->db_prefix,
				'table' => $this->tabela,
				'class' => $class_name
			));
			$interAdmin->setTipo($this);
			
			if ($this->_parent && $this->_parent instanceof InterAdmin) $interAdmin->setParent($this->_parent);
			
			$this->putOrmData($interAdmin, $row, $options);
			
			$interAdmins[] = $interAdmin;
		}
		$rs->Close();
		return $interAdmins;
	}
	
	/**
	 * Returns the number of InterAdmins. It uses a SQL query with COUNT(id).
	 *
	 * @param array $options Default array of options. Available keys: where.
	 * @return int Count of InterAdmins found.
	 */
	public function getInterAdminsCount($options = array()) {
		$model = $this->getModel();
		$table = ($model->getFieldsValues('tabela')) ? '_' . $model->tabela : '';
		$options['count'] = "COUNT(main.id) AS count";
		$options['from'] = $this->db_prefix . $table . (($this->getFieldsValues('language')) ? $lang->prefix : '') . " AS main";
		$options['where'] = "main.id_tipo = " . $this->id_tipo . $options['where'];
		if ($this->_parent && $this->_parent instanceof InterAdmin) $options['where'] .= " AND main.parent_id = " . $this->_parent->id;
		$rs = $this->executeQuery($options);
		if ($row = $rs->FetchNextObj()) return $row->count;
		else return 0;
	}
	
	/**
	 * Retrieves the first records which have this InterAdminTipo's id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, group, class.
	 * @return InterAdmin First InterAdmin object found.
	 */
	public function getFirstInterAdmin($options = array()) {
		$options['limit'] = 1;
		$interAdmin = $this->getInterAdmins($options);
		return $interAdmin[0];
	}
	/**
	 * Returns the model identified by model_id_tipo, or the object itself if it has no model.
	 *
	 * @param array $options Default array of options. Available keys: db_prefix, fields.
	 * @return InterAdminTipo Model used by this InterAdminTipo.
	 */
	public function getModel($options = array()) {
		if ($this->model_id_tipo || $this->getFieldsValues('model_id_tipo')) {
			$model = new InterAdminTipo($this->model_id_tipo, $options);
			return $model->getModel($options);
		} else {
			return $this;
		}
	}
	/**
	 * Returns an array with data about the fields on this type, which is then cached on the $_campos property.
	 * 
	 * @return array
	 */
	public function getCampos() {
		if ($this->_campos) return $this->_campos;
		$model = $this->getModel();
		$campos = $model->getFieldsValues('campos');
		unset($model->campos);
		$campos_parameters = array('tipo', 'nome', 'ajuda', 'tamanho', 'obrigatorio', 'separador', 'xtra', 'lista', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissoes', 'default', 'nome_id');
		$campos	= split('{;}', $campos);
		$A = array();
		for ($i = 0; $i < count($campos); $i++) {
			$parameters = split("{,}", $campos[$i]);
			if ($parameters[0]) {
				$A[$parameters[0]]['ordem'] = ($i+1);
				$isSelect = (strpos($parameters[0], 'select_') !== FALSE);
				for ($j = 0 ; $j < count($parameters); $j++) {
					$A[$parameters[0]][$campos_parameters[$j]] = $parameters[$j];
				}
				if ($isSelect && $A[$parameters[0]]['nome'] != 'all') {
					$id_tipo = $A[$parameters[0]]['nome'];
					$Cadastro_r = new InterAdminTipo($id_tipo);
					$A[$parameters[0]]['nome'] = $Cadastro_r;
					//jp7_print_r($parameters[0]);
					//jp7_print_r($A[$parameters[0]]['nome']);
				}
			}
		}
		return $this->_campos = $A;
		//return interadmin_tipos_campos($this->getFieldsValues('campos'));
	}
	/**
	 * Returns an array with the names of all the fields available.
	 * 
	 * @return array
	 */
	public function getAllFieldsNames(){
		$fields = array();
		$invalid_fields = array('tit', 'func');
		$all_fields = array_keys($this->getCampos());
		foreach ($all_fields as $field) {
			$field_arr = explode('_', $field);
			if (!in_array($field_arr[0], $invalid_fields)) $fields[] = $field;
		}
		return $fields;
	}
	/**
	 * Associates the values on a SQL RecordSet with the fields, and then puts them on an object.
	 * 
	 * @param InterAdmin $object
	 * @param array $row Row of a SQL RecordSet.
	 * @param array $options Default array of options. Available keys: fields, fields_alias.
	 * @return void
	 */
	public function putOrmData(&$object, &$row, $options){
		$campos = $this->getCampos();
		$joinCount = 0;
		foreach((array)$options['fields'] as $join => $field){
			if (is_array($field)) {
				$field = $join;
				$joinCount++;
			}
			$alias = ($options['fields_alias']) ? $this->getCamposAlias($field) : $field;
			$object->$alias = $this->getByForeignKey($row->$field, $field, $campos[$field]['xtra']);

			if (is_object($object->$alias) && is_array($options['fields'][$field])) {
				foreach($options['fields'][$field] as $joinField) {
					$joinAlias = ($options['fields_alias']) ? $campos[$field]['nome']->getCamposAlias($joinField) : $joinField;
					$joinCampos = $campos[$field]['nome']->getCampos();
					$rowField = 'join' . $joinCount . '_' . $joinField;
					$object->$alias->$joinAlias = $this->getByForeignKey($row->$rowField, $joinField, $joinCampos[$joinField]['xtra']);
				}
			}
		}
	}
	/**
	 * Gets an object by its key, which may be its 'id' or 'id_tipo', and then returns it.
	 * 
	 * @param mixed $value Any value.
	 * @param string $field The name of the field.
	 * @param string $xtra Xtra value of the field.
	 * @return mixed The object created by the key or the value itself.
	 */
	public function getByForeignKey(&$value, $field, $xtra = ''){
		if (strpos($field, 'select_') === 0) {
			if (strpos($field, 'select_multi') === 0) {
				$value_arr = explode(',', $value);
				if (!$value_arr[0]) $value_arr = array();
				foreach ($value_arr as $key2 => $value2) {
					if ($xtra === 'S') {
						$value_arr[$key2] = InterAdminTipo::getInstance($value2);
					} else {
						$value_arr[$key2] = InterAdmin::getInstance($value2);
					}
				}
				$value = $value_arr;
			} elseif($value && is_numeric($value)) {
				if ($xtra === 'S') {
					$value = InterAdminTipo::getInstance($value);
				} else {
					$value = InterAdmin::getInstance($value);
				}
			}
		}
		return $value;
	}
	/**
	 * Executes a SQL Query based on the values passed by $options.
	 * 
	 * @param array $options  Default array of options. Available keys: count, fields, from, where, order, group, limit.
	 * @return ADORecordSet The resulting RecordSet.
	 */
	public function executeQuery($options){
		global $jp7_app, $db, $lang;
		$campos = $this->getCampos();
		// Join
		$joinsCount = 0;
		if (!is_array($options['from'])) $options['from'] = (array) $options['from'];
		foreach($options['fields'] as $key => $fields){
			if (is_array($fields)) {
				$join = 'join' . ++$joinsCount;
				$options['fields'][$key] = 'main.' . $key;
				$joinModel = $campos[$key]['nome']->getModel();
				if ($campos[$key]['xtra'] == 'S') {
					$options['from'][] = $this->db_prefix . "_tipos" .
						" AS " . $join . " ON "  . $options['fields'][$key] . " = " . $join . ".id_tipo";
				} else {
					$options['from'][] = $this->db_prefix .
						(($joinModel->getFieldsValues('tabela')) ? '_' . $joinModel->tabela : '') .
						(($campos[$key]['nome']->getFieldsValues('language')) ? $lang->prefix : '') .
						" AS " . $join . " ON "  . $options['fields'][$key] . " = " . $join . ".id";
				}
				
				foreach($fields as $joinField) {
					$options['fields'][] = $join . '.' . $joinField . " AS " . $join . '_' . $joinField;
				}
			} else {
				if (strpos($fields, '(') === FALSE && strpos($fields, '.') === FALSE) $options['fields'][$key] = 'main.' . $fields;
			}
		}
		
		// Order Fix
		$order_arr = jp7_explode(',', $options['order']);
		foreach ($order_arr as $key => &$value) {
			if (strpos($value, '(') === FALSE && strpos($value, '.') === FALSE) $value = 'main.' . $value;
		}
		$options['order'] = implode(',', $order_arr);
		
		if ($options['count']) $options['fields'] = (array) $options['count'];
		// Sql
		$sql = "SELECT " . (($options['fields']) ? implode(',', $options['fields']) : '') .
			" FROM " . implode(' LEFT JOIN ', $options['from']) .
			" WHERE " . $options['where'] .
			(($options['order']) ? " ORDER BY " . $options['order'] : '') .
			(($options['group']) ? " GROUP BY " . $options['group'] : '') .
			(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		
		if ($jp7_app) $rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		return $rs;
	}
	
	/**
	 * Gets the alias for a given field name.
	 * 
	 * @param string $field Field name.
	 * @return string Resulting alias.
	 */
	public function getCamposAlias($field) {
		$campos = $this->getCampos();
		if ($campos[$field]['nome_id']) return $campos[$field]['nome_id'];
		$alias = $campos[$field]['nome'];
		if (is_object($alias)) $alias = ($alias->nome) ? $alias->nome : $alias->getFieldsValues('nome');
		$alias = ($alias) ? toId($alias) : $field;
		return $alias;
	}
	/**
	 * Returns this object´s nome and all the fields marked as 'combo', if the field 
	 * is an InterAdminTipo such as a select_key, its getStringValue() method is used.
	 *
	 * @return string For the tipo 'City' with the field 'state' marked as 'combo' it would return: 'City - State'.
	 */
	public function getStringValue(/*$simple = FALSE*/) {
		$campos = $this->getCampos();
		$return[] = $this->getFieldsValues('nome');
		//if (!$simple) {
			foreach ($campos as $key => $row) {
				if (($row['combo'] || $key == 'varchar_key' || $key == 'select_key') && $key !== 'char_key') {
					if (is_object($row['nome'])) $return[] = $row['nome']->getStringValue();
					else $return[] = $row['nome'];
				}
			}
		//}
		return implode(' - ', $return);
	}
	/**
	 * Returns the full url for this InterAdminTipo.
	 * 
	 * @return string
	 */
	public function getUrl() {
		if ($this->_url) return $this->_url;
		global $c_url, $c_cliente_url, $c_cliente_url_path, $implicit_parents_names, $jp7_app, $seo, $lang;
		$url = '';
		$url_arr = '';
		$parent = $this;
		while ($parent) {
			if ($seo) {
				if (!in_array($parent->getFieldsValues('nome'), (array)$implicit_parents_names)) $url_arr[] = toSeo($parent->getFieldsValues('nome'));
			} else {
				$url_arr[] = toId($parent->getFieldsValues('nome'));
			}
			$parent = $parent->getParent();
		}
		$url_arr = array_reverse((array)$url_arr);

		if ($seo) {
			$url = $c_url . join("/", $url_arr);
		} else {
			$url = (($jp7_app) ? $c_cliente_url . $c_cliente_url_path : $c_url) . $lang->path_url . join("_", $url_arr);
			$pos = strpos($url, '_');
			if ($pos) $url = substr_replace($url, '/', $pos, 1);
			$url .= (count($url_arr) > 1) ? '.php' : '/';
		}
		
		return $this->_url = $url;
	}
	/**
	 * Returns the names of the parents separated by '/', e.g. 'countries/south-america/brazil'.
	 * 
	 * @return string
	 */
	public function getTreePath() {
		global $c_url, $implicit_parents_names, $seo, $lang;
		$url = '';
		$url_arr = '';
		$parent = $this;
		while ($parent) {
			if ($seo) {
				if (!in_array($parent->getFieldsValues('nome'), (array)$implicit_parents_names)) $url_arr[] = toSeo($parent->getFieldsValues('nome'));
			} else {
				$url_arr[] = $parent->getFieldsValues('nome');
			}
			$parent = $parent->getParent();
		}
		$url_arr = array_reverse((array)$url_arr);

			$url = $c_url . join("/", $url_arr);
		return $url;
	}
}
?>