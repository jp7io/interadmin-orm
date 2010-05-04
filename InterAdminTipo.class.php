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
 * Class which represents records on the table interadmin_{client name}_tipos.
 * 
 * @property string $interadminsOrderby SQL Order By for the records of this InterAdminTipo.
 * @property string $class Class to be instantiated for the records of this InterAdminTipo.
 * @property string $tabela Table of this Tipo, or of its Model, if it has no table.
 * @package InterAdmin
 */
class InterAdminTipo extends InterAdminAbstract {
	const ID_TIPO = 0;
	
	/**
	 * Stores metadata to be shared by instances with the same $id_tipo.
	 * @var array 
	 */
	protected static $_metadata;
	protected $_primary_key = 'id_tipo';
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
	 * Contains the parent InterAdminTipo object, i.e. the record with an 'id_tipo' equal to this record's 'parent_id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_parent;
	
	// @todo Remove this
	protected $_loadedfrommodel;
	protected $_tiposUsingThisModel;
	
	/**
	 * Public Constructor. If $options['fields'] is passed the method $this->getFieldsValues() is called.
	 * This method has 4 possible calls:
	 * 
	 * __construct()
	 * __construct(int $id_tipo)
	 * __construct(array $options)
	 * __construct(int $id_tipo, array $options)
	 * 
	 * @param array $id_tipo This record's 'id_tipo'.
	 * @param array $options [optional]	Default array of options. Available keys: db_prefix, fields.
	 * @todo Modificar staticConst(...) por static::ID_TIPO no PHP 5.3
	 */
	public function __construct($id_tipo = null, $options = array()) {
		if (is_null($id_tipo) || is_array($id_tipo)) {
			$options = (array) $id_tipo;
			$id_tipo = $this->staticConst('ID_TIPO');
		}
		// Constructor
		if (is_object($id_tipo)) {
			$id_tipo = (string) $id_tipo;
		}
		$this->id_tipo = intval($id_tipo);
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) {
			$this->getFieldsValues($options['fields']);
		}
	}
	
	/**
	 * Returns an InterAdminTipo instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id_tipo This record's 'id_tipo'.
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class, default_class.
	 * @return InterAdminTipo Returns an InterAdminTipo or a child class in case it's defined on its 'class_tipo' property.
	 */
	public static function getInstance($id_tipo, $options = array()) {
		if (!$options['default_class']) {
			$options['default_class'] = 'InterAdminTipo';
		}
		if ($options['class']) {
			$class_name = (class_exists($options['class'])) ? $options['class'] : $options['default_class'];
		} else {
			$instance = new $options['default_class']($id_tipo, array_merge($options, array('fields' => array('model_id_tipo', 'class_tipo'))));
			if ($instance->class_tipo) {
				 $class_name = $instance->class_tipo;
			} else {
				// @todo Store class_tipo on metadatas do save queries
				if ($instance->model_id_tipo) {
					$class_name = jp7_fields_values($instance->db_prefix . '_tipos', 'id_tipo', $instance->model_id_tipo, 'class_tipo');
				}
			}
			if (!class_exists($class_name)) {
				if ($options['fields']) {
					$instance->getFieldsValues($options['fields']);
				}
				return $instance;
			}
		}
		return new $class_name($id_tipo, $options);
	}
	public function getFieldsValues($fields) {
		if (!isset($this->attributes['model_id_tipo'])) {
			$eagerload = array('nome', 'parent_id_tipo', 'campos', 'model_id_tipo', 'tabela', 'class', 'class_tipo', 'template', 'children');
			$neededFields = array_unique(array_merge((array) $fields, $eagerload));
			$values = parent::getFieldsValues($neededFields);
			if (is_array($fields)) {
				return $values;
			} else {
				return $values->$fields;
			}
		}
		return parent::getFieldsValues($fields);
	}
	/**
	 * Retrieves magic properties.
	 *
	 * @param string $var Magic property 'interadminsOrderby' or 'class'.
	 * @return mixed
	 */
	public function __get($var) {
		if ($var == 'class' || $var == 'tabela') {
			if (!isset($this->attributes[$var]) || !isset($this->_loadedfrommodel[$var])) {
				if (!$this->$var && !$this->getFieldsValues($var)) {
					$this->$var = $this->getModel()->getFieldsValues($var);
					$this->_loadedfrommodel[$var] = true;
				}
			}
		}
		return parent::__get($var);
	}
	/**
	 * Gets the parent InterAdminTipo object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo|InterAdminAbstract
	 */
	public function getParent($options = array()) {
		if ($this->_parent) {
			return $this->_parent;
		}
		if ($this->parent_id_tipo || $this->getFieldsValues('parent_id_tipo')) {
			$options['default_class'] = $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdminTipo';
			return $this->_parent = InterAdminTipo::getInstance($this->parent_id_tipo, $options);
		}
	}
	/**
	 * Sets the parent InterAdminTipo or InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdminAbstract $parent
	 * @return void
	 */
	public function setParent(InterAdminAbstract $parent = null) {
		$this->_parent = $parent;
	}
	/**
	 * Retrieves the children of this InterAdminTipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, class.
	 * @return array Array of InterAdminTipo objects.
	 */
	public function getChildren($options = array()) {
		$this->_whereArrayFix($options['where']); // FIXME
				
		$options['fields'] = array_merge(array('id_tipo'), (array) $options['fields']);
		$options['from'] = $this->getTableName() . " AS main";
		$options['where'][] = "parent_id_tipo = " . $this->id_tipo;
	 	if (!$options['order']) {
	 		$options['order'] = 'ordem, nome';
		}
		// Internal use
		$options['aliases'] = $this->getAttributesAliases();
		$options['campos'] = $this->getAttributesCampos();
		
		$rs = $this->_executeQuery($options);
		
		$tipos = array();
		while ($row = $rs->FetchNextObj()) {
			$tipo = InterAdminTipo::getInstance($row->id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'class' => $options['class'],
				'default_class' => $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdminTipo'
			));
			$tipo->setParent($this);
			$this->_getAttributesFromRow($row, $tipo, $options);
			$tipos[] = $tipo;
		}
		return $tipos;
	}
	/**
	 * Gets the first child.
	 * 
	 * @param array $options [optional]
	 * @return InterAdminTipo
	 */
	public function getFirstChild($options = array()) {
		return reset($this->getChildren(array('limit' => 1) + $options));
	}
	/**
	 * Retrieves the first child of this InterAdminTipo which have the given model_id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, class.
	 * @return InterAdminTipo
	 */
	public function getFirstChildByModel($model_id_tipo, $options = array()) {
		$options['where'][] = "model_id_tipo = " . $model_id_tipo;
		$options['limit'] = 1;
		return reset($this->getChildren($options));
	}
	/**
	 * Retrieves the children of this InterAdminTipo which have the given model_id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, class.
	 * @return Array of InterAdminTipo objects.
	 */
	public function getChildrenByModel($model_id_tipo, $options = array()) {
		$options['where'][] = "model_id_tipo = " . $model_id_tipo;
		return $this->getChildren($options);
	}
	/**
	 * Retrieves the records which have this InterAdminTipo's id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
	 * @return array Array of InterAdmin objects.
	 */
	public function getInterAdmins($options = array()) {
		$this->_whereArrayFix($options['where']); // FIXME
		
		$options['where'][] = "id_tipo = " . $this->id_tipo;
		if ($this->_parent instanceof InterAdmin) {
			$options['where'][] =  "parent_id = " . intval($this->_parent->id);
		}
		
		$this->_prepareInterAdminsOptions($options, $optionsInstance);
		
		$rs = $this->_executeQuery($options);
		$records = array();
		while ($row = $rs->FetchNextObj()) {
			$record = InterAdmin::getInstance($row->id, $optionsInstance, $this);
			if ($this->_parent instanceof InterAdmin) {
				$record->setParent($this->_parent);
			}
			$this->_getAttributesFromRow($row, $record, $options);
			$records[] = $record;
		}
		return $records;
	}
	/**
	 * Returns the number of InterAdmins using COUNT(id).
	 *
	 * @param array $options Default array of options. Available keys: where.
	 * @return int Count of InterAdmins found.
	 */
	public function getInterAdminsCount($options = array()) {
		$options['fields'] = array('COUNT(DISTINCT id)');
		$retorno = $this->getFirstInterAdmin($options);
		return intval($retorno->count_distinct_id);
	}
	/**
	 * Retrieves the first records which have this InterAdminTipo's id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, group, class.
	 * @return InterAdmin First InterAdmin object found.
	 */
	public function getFirstInterAdmin($options = array()) {
		return reset($this->getInterAdmins(array('limit' => 1) + $options));
	}
	/**
	 * Retrieves the unique record which have this id
	 * 
	 * @param int $id Search value.
	 * @return InterAdmin First InterAdmin object found.
	 */
	public function getInterAdminById($id, $options = array()) {
		$options['where'][] = "id = " . intval($id);
		return $this->getFirstInterAdmin($options);
	}
	/**
	 * Retrieves the first record which have this id_string
	 * 
	 * @param string $id_string Search value.
	 * @return InterAdmin First InterAdmin object found.
	 */
	public function getInterAdminByIdString($id_string, $options = array()) {
		$options['where'][] = "id_string = '" . $id_string . "'";
		return $this->getFirstInterAdmin($options);
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
		if (!$A = $this->_getMetadata('campos')) {
			$model = $this->getModel();
			
			$campos = $model->getFieldsValues('campos');
			unset($model->campos);
			$campos_parameters = array(
				'tipo', 'nome', 'ajuda', 'tamanho', 'obrigatorio', 'separador', 'xtra',
				'lista', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissoes',
				'default', 'nome_id'
			);
			$campos	= explode('{;}', $campos);
			$A = array();
			for ($i = 0; $i < count($campos); $i++) {
				$parameters = explode("{,}", $campos[$i]);
				if ($parameters[0]) {
					$A[$parameters[0]]['ordem'] = ($i+1);
					$isSelect = (strpos($parameters[0], 'select_') !== false);
					for ($j = 0 ; $j < count($parameters); $j++) {
						$A[$parameters[0]][$campos_parameters[$j]] = $parameters[$j];
					}
					if ($isSelect && $A[$parameters[0]]['nome'] != 'all') {
						$id_tipo = $A[$parameters[0]]['nome'];
						$A[$parameters[0]]['nome'] = InterAdminTipo::getInstance($id_tipo, array(
							'default_class' => $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdminTipo'
						));
					}
				}
			}
			$this->_setMetadata('campos', $A);
		}
		return $A;
	}
	/**
	 * Returns an array with the names of all the fields available.
	 * 
	 * @return array
	 */
	public function getCamposNames(){
		$invalid_fields = array('tit', 'func');
		$fields = array_keys($this->getCampos());
		foreach ($fields as $key => $field) {
			$field_arr = explode('_', $field);
			if (in_array($field_arr[0], $invalid_fields)) {
				unset($fields[$key]);
			}
		}
		return $fields;
	}
	/**
	 * Gets the alias for a given field name.
	 * 
	 * @param array|string $fields Fields names, defaults to all fields.
	 * @return array|string Resulting alias(es).
	 */
	public function getCamposAlias($fields = null) {
		$campos = $this->getCampos();
		if (is_null($fields)) {
			$fields = array_keys($campos);
		}
		$aliases = array();
		foreach ((array) $fields as $field) {
			if ($campos[$field]['nome_id']) {
				$aliases[$field] = $campos[$field]['nome_id'];
			} else {
				$alias = $campos[$field]['nome'];
				if (is_object($alias)) {
					if ($campos[$field]['label']) {
					 	$alias = $campos[$field]['label'];
					} else { 
						$alias = $alias->getFieldsValues('nome');	
					}
				}
				$alias = ($alias) ? toId($alias) : $field;
				$aliases[$field] = $alias;
				// Cache
				$campos[$field]['nome_id'] = $alias; 
				$this->_setMetadata('campos', $campos);
			}
		}
		if (is_array($fields)) {
			return $aliases;
		} else {
			return reset($aliases);
		}
	}
	/**
	 * Returns the InterAdminTipo for a field.
	 * 
	 * @param object $campo
	 * @return InterAdminTipo 
	 */
	protected function _getCampoTipo($campo) {
		if (is_object($campo['nome'])) {
			return $campo['nome'];
		} elseif ($campo['nome'] == 'all') {
			return new InterAdminTipo();
		}
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
		if ($this->_url) {
			return $this->_url;
		}
		global $config, $implicit_parents_names, $seo, $lang;
		$url = '';
		$url_arr = '';
		$parent = $this;
		while ($parent) {
			if (!isset($parent->nome)) {
				$parent->getFieldsValues('nome');
			}
			if ($seo) {
				if (!in_array($parent->nome, (array) $implicit_parents_names)) {
					$url_arr[] = toSeo($parent->nome);
				}
			} else {
				if (toId($parent->nome)) {
					$url_arr[] = toId($parent->nome);
				}
			}
			$parent = $parent->getParent();
			if ($parent instanceof InterAdmin) {
				$parent = $parent->getTipo();
			}
		}
		$url_arr = array_reverse((array) $url_arr);
		
		if ($seo) {
			$url = $config->url . $lang->path . jp7_implode("/", $url_arr);
		} else {
			$url = $config->url . $lang->path_url . implode("_", $url_arr);
			$pos = strpos($url, '_');
			if ($pos) {
				$url = substr_replace($url, '/', $pos, 1);
			}
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
		global $config, $implicit_parents_names, $seo, $lang;
		$url = '';
		$url_arr = '';
		$parent = $this;
		while ($parent) {
			if ($seo) {
				if (!in_array($parent->getFieldsValues('nome'), (array)$implicit_parents_names)) {
					$url_arr[] = toSeo($parent->getFieldsValues('nome'));
				}
			} else {
				$url_arr[] = $parent->getFieldsValues('nome');
			}
			$parent = $parent->getParent();
		}
		$url_arr = array_reverse((array)$url_arr);

			$url = $config->url . join("/", $url_arr);
		return $url;
	}
	/**
	 * Sets this row as deleted as saves it.
	 * 
	 * @return
	 */
	public function delete() {
		$this->deleted_tipo = 'S';
		$this->save();
	}
	
	/**
	 * Deletes all the InterAdmins.
	 * 
	 * @param array $options [optional]
	 * @return int Count of deleted InterAdmins.
	 */
	public function deleteInterAdmins($options = array()) {
		$records = $this->getInterAdmins($options);
		foreach ($records as $record) {
			$record->delete();
		}
		return count($records);
	}
	
	/**
	 * Deletes all the InterAdmins forever.
	 * 
	 * @param array $options [optional]
	 * @return int Count of deleted InterAdmins.
	 */
	public function deleteInterAdminsForever($options = array()) {
		global $db;
		
		if ($this->id_tipo) {
			$sql = "DELETE FROM " . $this->getInterAdminsTableName() . 
				" WHERE id_tipo = " . $this->id_tipo;
			$db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		}
		
		return $db->Affected_Rows();
	}
	
	public function getAttributesNames() {
		return array('id_tipo', 'model_id_tipo', 'parent_id_tipo', 'redirect_id_tipo',
			'nome', 'nome_en', 'texto', 'class', 'class_tipo', 'template', 'editpage', 
			'template_inserir', 'tabela', 'disparo', 'campos', 'arquivos', 'arquivos_ajuda',
			'arquivos_2', 'arquivos_2_ajuda', 'arquivos_3', 'arquivos_3_ajuda', 'arquivos_4',
			'arquivos_4_ajuda', 'links', 'links_ajuda', 'children', 'mostrar', 'language',
			'menu', 'busca', 'restrito', 'admin', 'editar', 'unico', 'publish_tipo', 'versoes',
			'hits', 'tags', 'tags_tipo', 'tags_registros', 'visualizar', 'ordem', 'log', 'deleted_tipo'
		);
	}
	public function getAttributesCampos() {
		return array();
	}
	public function getAttributesAliases() {
		return array();
	}
	public function getTableName() {
		return $this->db_prefix . '_tipos';
	}
	public function getInterAdminsOrder($order = '') {
		if (!$interadminsOrderBy = $this->_getMetadata('interadmins_order')) {
			$interadminsOrderBy = array();
			$campos = $this->getCampos();
			if ($campos) {
				foreach ($campos as $key => $row) {
					if ($row['orderby']) {
						if ($row['orderby'] < 0) {
							$key .= " DESC";
						}
						$interadminsOrderBy[$row['orderby']] = $key;
					}
				}
				if ($interadminsOrderBy) {
					ksort($interadminsOrderBy);
				}
			}
			$interadminsOrderBy[] = 'date_publish DESC';
			$this->_setMetadata('interadmins_order', $interadminsOrderBy);
		}
		if ($order) {
			$order = explode(',', $order);
			$interadminsOrderBy = array_unique(array_merge($order, $interadminsOrderBy));
		}
		return implode(',', $interadminsOrderBy);
	}
	/**
	 * Returns the table name for the InterAdmins.
	 * 
	 * @return string
	 */
	public function getInterAdminsTableName() {
		global $lang;
		$table = $this->db_prefix .	(($this->tabela) ? '_' . $this->tabela : '');
		if (!isset($this->language)) {
			$this->getFieldsValues('language');
		}
		if ($this->language) {
			$table .= $lang->prefix;
		}
		return $table;
	}
	/**
	 * Returns the table name for the files.
	 * 
	 * @return string
	 */
	public function getArquivosTableName() {
		global $lang;
		$table = $this->db_prefix;
		if (!isset($this->language)) {
			$this->getFieldsValues('language');
		}
		if ($this->language) {
			$table .= $lang->prefix;
		}
		return $table . '_arquivos';
	}
	protected function _setMetadata($varname, $value) {
		self::$_metadata[$this->db_prefix][$this->id_tipo][$varname] = $value;
	}
	protected function _getMetadata($varname) {
		return self::$_metadata[$this->db_prefix][$this->id_tipo][$varname];
	}
	/**
	 * @return array
	 */
	public function getInterAdminsChildren() {
		if (!$children = $this->_getMetadata('children')) {
			$model = $this->getModel();
			
			$children = array();
			$childrenArr = explode("{;}", $model->getFieldsValues('children'));
			for ($i = 0; $i < count($childrenArr) - 1; $i++) {
				$child = array_combine(array('id_tipo', 'nome', 'ajuda', 'netos'), explode('{,}', $childrenArr[$i]));
				$nome_id = Jp7_Inflector::camelize($child['nome']);
				$children[$nome_id] = $child;
			}
			$this->_setMetadata('children', $children);
		}
		return $children;
	}
	/**
	 * Creates a record with id_tipo, mostrar, date_insert and date_publish filled.
	 * 
	 * @param array $attributes Attributes to be merged into the new record.
	 * @return InterAdmin
	 */
	public function createInterAdmin(array $attributes = array()) {
		$options = array('default_class' => $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdmin');
		$record = InterAdmin::getInstance(0, $options, $this);
		$mostrar = $this->getCamposAlias('char_key');
		$record->$mostrar = 'S';
		$record->date_publish = date('c');
		$record->date_insert = date('c');
		if ($this->_parent instanceof InterAdmin) {
			$record->setParent($this->_parent);
			// Childs are published by default on InterAdmin.
			$record->publish = 'S';
		}
		$record->setAttributes($attributes);
		return $record;
	}
	
	/**
	 * Returns the InterAdmins having the given tags.
	 * 
	 * @param InterAdmin[] $tags
	 * @param array $options [optional]
	 * @return InterAdmin[]
	 */
	public function getInterAdminsByTags($tags, $options = array()) {
		if (!is_array($tags)) {
			$tags = array($tags);
		}
		$tagsWhere = array();
		foreach ($tags as $tag) {
			if ($tag instanceof InterAdminTipo) {
				$tagsWhere[] = "(tags.id_tipo = " . $tag->id_tipo . " AND tags.id = 0)";
			} elseif ($tag instanceof InterAdmin) {
				$tagsWhere[] = "(tags.id = " . $tag->id . " AND tags.id_tipo = '" . $tag->getTipo()->id_tipo . "')";
			} elseif (is_numeric($tag)) {
				$tagsWhere[] = "(tags.id_tipo = " . $tag . " AND tags.id <> 0)";
			}
		}
		if (!$tagsWhere) {
			return array();
		}
		
		$options['where'][] = '(' . implode(' OR ', $tagsWhere) . ')';
		return $this->getInterAdmins($options);
	}
	
	/**
	 * Returns all InterAdminTipo's using this InterAdminTipo as a model (model_id_tipo).
	 * 
	 * @param array $options [optional]
	 * @return InterAdminTipo[] Array of Tipos indexed by their id_tipo.
	 */
	public function getTiposUsingThisModel($options = array()) {
		if (!isset($this->_tiposUsingThisModel)) {
			$rs = $this->_executeQuery(array(
				'fields' => 'id_tipo',
				'from' => $this->getTableName() . ' AS main',
				'where' => array(
					'model_id_tipo = ' . $this->id_tipo
				)
			));
			
			$options['default_class'] = $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdminTipo';		
			$this->_tiposUsingThisModel = array();
			while ($row = $rs->FetchNextObj()) {
				$this->_tiposUsingThisModel[$row->id_tipo] = InterAdminTipo::getInstance($row->id_tipo, $options);;
			}
			$this->_tiposUsingThisModel[$this->id_tipo] = $this;
		}
		return $this->_tiposUsingThisModel;
	}
	
	protected function _prepareInterAdminsOptions(&$options, &$optionsInstance) {
		$optionsInstance = array(
			'class' => $options['class'],
			'default_class' => $this->staticConst('DEFAULT_NAMESPACE') . 'InterAdmin'
		);
		
		$recordModel = InterAdmin::getInstance(0, $optionsInstance, $this);
		
		$this->_resolveWildcard($options['fields'], $recordModel);
		if (count($options['fields']) != 1 || strpos($options['fields'][0], 'COUNT(') === false) {
			$options['fields'] = array_merge(array('id', 'id_tipo'), (array) $options['fields']);
		}
		$options['from'] = $recordModel->getTableName() . " AS main";
		$options['order'] = $this->getInterAdminsOrder($options['order']);
		// Internal use
		$options['aliases'] = $recordModel->getAttributesAliases();
		$options['campos'] = $recordModel->getAttributesCampos();
		$options = $options + array('fields_alias' => $this->staticConst('DEFAULT_FIELDS_ALIAS'));
	}
	
	/**
	 * Returns all records having an InterAdminTipo that uses this as a model (model_id_tipo).
	 * 
	 * @param array $options [optional]
	 * @return InterAdmin[]
	 */
	public function getInterAdminsUsingThisModel($options = array()) {
		$tipos = $this->getTiposUsingThisModel();
		$options['where'][] = "id_tipo IN (" . implode(',', $tipos) . ')';
		
		$this->_prepareInterAdminsOptions($options, $optionsInstance);
		
		$rs = $this->_executeQuery($options);
		$records = array();
		while ($row = $rs->FetchNextObj()) {
			$record = InterAdmin::getInstance($row->id, $optionsInstance, $tipos[$row->id_tipo]);
			$this->_getAttributesFromRow($row, $record, $options);
			$records[] = $record;
		}
		return $records;
	}
}
