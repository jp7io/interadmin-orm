<?php
use Jp7\Interadmin\Collection;
use Jp7\Interadmin\TipoCache;

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
	
	private static $inheritedFields = array(
		'class', 'class_tipo', 'icone', 'layout', 'layout_registros', 'tabela',
		'template', 'children', 'campos', 'language', 'editar', 'unico', 'disparo', 'xtra_disabledfields', 'xtra_disabledchildren'
	);
	private static $privateFields = array('children', 'campos');
		
	protected static $_defaultClass = 'InterAdminTipo';
	
	protected $_primary_key = 'id_tipo';
	
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
	
	protected $_tiposUsingThisModel;
	
	/**
	 * Construct
	 * 
	 * @param int 		$id_tipo 	[optional] This record's 'id_tipo'.
	 */
	public function __construct($id_tipo = null) {
		if (is_null($id_tipo)) {
			$id_tipo = static::ID_TIPO;
		}
		$this->id_tipo = $id_tipo;
	}
	
	public function &__get($attributeName) {
		if (isset($this->attributes[$attributeName])) {
			return $this->attributes[$attributeName];
		} elseif (in_array($attributeName, $this->getAttributesNames())) {
			$this->loadAttributes($this->getAttributesNames(), false);
			return $this->attributes[$attributeName];
		}
		return $null; // Needs to be variable to be returned as reference
	}
	
	public function __call($methodName, $args) {
		$childrenType = $this->children()
			->where('id_slug', snake_case($methodName, '-'))
			->first();
		
		if ($childrenType) {
			return $childrenType->records();
		}
		// Default error when method doesn´t exist
		$message = 'Call to undefined method ' . get_class($this) . '->' . 
			$methodName . '(). Available magic methods: ' . "\n";
		
		foreach ($this->children()->all() as $child) {
			$message .= "\t\t- " . lcfirst(camel_case($child->id_slug)) . "()\n";
		}
		throw new BadMethodCallException($message);
	}
		
	/**
	 * Returns an InterAdminTipo instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id_tipo This record's 'id_tipo'.
	 * @param array $options Default array of options. Available keys: class, default_class.
	 * @return InterAdminTipo Returns an InterAdminTipo or a child class in case it's defined on its 'class_tipo' property.
	 */
	public static function getInstance($id_tipo, $options = array()) {
		if (isset($options['class'])) {
			// Classe foi forçada
			$classTipo = $options['class'];
		} else {
			// Classe não foi forçada, verificar classMap
			$cm = \Jp7\InterAdmin\ClassMap::getInstance();
			$classTipo = $cm->getClassTipo($id_tipo);
			if (!$classTipo) {
				$classTipo = isset($options['default_class']) ? $options['default_class'] : self::$_defaultClass;
			}
		}
		// Classe foi encontrada, instanciar o objeto
		return new $classTipo($id_tipo);
	}
	/*
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
		if (!isset($this->attributes['model_id_tipo'])) {
			$eagerload = array('nome', 'language', 'parent_id_tipo', 'campos', 'model_id_tipo', 'tabela', 'class', 'class_tipo', 'template', 'children');
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
	*/
	/**
	 * Gets the parent InterAdminTipo object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: class.
	 * @return InterAdminTipo|InterAdminAbstract
	 */
	public function getParent($options = array()) {
		if ($this->_parent) {
			return $this->_parent;
		}
		if ($this->parent_id_tipo) {
			$options['default_class'] = static::DEFAULT_NAMESPACE . 'InterAdminTipo';
			return $this->_parent = InterAdminTipo::getInstance($this->parent_id_tipo, $options);
		}
	}
	public function getAncestors() {
		$parents = array();
		$parent = $this;
		
		while (($parent = $parent->getParent()) && $parent->id_tipo) {
			array_unshift($parents, $parent);
		}
		
		return $parents;
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
	 * @deprecated
	 */
	public function getChildren($deprecated, $options = array()) {
		if ($deprecated != InterAdmin::DEPRECATED_METHOD) {
			throw new Exception("Use children()->all() instead.");			
		}
		
		$this->_whereArrayFix($options['where']); // FIXME
		
		if (empty($options['fields'])) {
			$options['fields'] = $this->getAttributesNames();
		} else {
			$options['fields'] = array_merge(array('id_tipo'), (array) $options['fields']);
		}
		$options['from'] = $this->getTableName() . " AS main";
		$options['where'][] = "parent_id_tipo = " . $this->id_tipo;
	 	if (empty($options['order'])) {
	 		$options['order'] = 'ordem, nome';
		}
		// Internal use
		$options['aliases'] = $this->getAttributesAliases();
		$options['campos'] = $this->getAttributesCampos();
		
		$rs = $this->_executeQuery($options);
		
		$tipos = array();
		foreach ($rs as $row) {
			$tipo = InterAdminTipo::getInstance($row->id_tipo, array(
				'db' => $this->_db,
				'class' => isset($options['class']) ? $options['class'] : null,
				'default_class' => static::DEFAULT_NAMESPACE . 'InterAdminTipo'
			));
			$tipo->setParent($this);
			$this->_getAttributesFromRow($row, $tipo, $options);
			$tipos[] = $tipo;
		}
		// $rs->Close();
		return new Collection($tipos);
	}

	public function children() {
		return new \Jp7\Interadmin\Query\Type($this);
	}
	
	public function childrenByModel($model_id_tipo) {
		return $this->children()->where('model_id_tipo', $model_id_tipo);
	}
	
	/**
	 *
	 * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
	 * @return InterAdmin[] Array of InterAdmin objects.
	 * @deprecated
	 */
	public function deprecatedFind($options = array()) {
		$this->_prepareInterAdminsOptions($options, $optionsInstance);
		
		$options['where'][] = "id_tipo = " . $this->id_tipo;
		if ($this->_parent instanceof InterAdmin) {
			$options['where'][] =  "parent_id = " . intval($this->_parent->id);
		}
		
		$rs = $this->_executeQuery($options, $select_multi_fields);
		$options['select_multi_fields'] = $select_multi_fields;
		
		$records = array();
		foreach ($rs as $row) {
			$_id = isset($row->id) ? $row->id : null;
			$record = InterAdmin::getInstance($_id, $optionsInstance, $this);
			if ($this->_parent instanceof InterAdmin) {
				$record->setParent($this->_parent);
			}
			$this->_getAttributesFromRow($row, $record, $options);
			$records[] = $record;
		}
		if ($options['eager_load']) {
			foreach ($options['eager_load'] as $relationshipData) {
				if ($relationshipData['type'] == 'select') {
					if ($relationshipData['levels']) {
						$selects = array();
						$property = $relationshipData['name'];
						foreach ($records as $item) {
							if ($item->$property) {
								$selects[] = $item->$property;
							}
						}
						Jp7_Collections::eagerLoad($selects, $relationshipData['levels']);
					}
				} else {
					Jp7_Collections::eagerLoad($records, $relationshipData['levels']);
				}
			}
		}
		// // $rs->Close();
		return new Collection($records);
	}

	public function deprecated_distinct($column, $options = array()) {
		return $this->_aggregate('DISTINCT', $column, $options);
	}
	
	public function deprecated_max($column, $options = array()) {
		$retorno = $this->_aggregate('MAX', $column, $options);
		return $retorno[0];
	}
	
	public function deprecated_min($column, $options = array()) {
		$retorno = $this->_aggregate('MIN', $column, $options);
		return $retorno[0];
	}
	
	public function deprecated_sum($column, $options = array()) {
		$retorno = $this->_aggregate('SUM', $column, $options);
		return $retorno[0];
	}
	
	public function deprecated_avg($column, $options = array()) {
		$retorno = $this->_aggregate('AVG', $column, $options);
		return $retorno[0];
	}
	
	protected function _aggregate($function, $column, $options) {
		$this->_prepareInterAdminsOptions($options, $optionsInstance);
		
		$options['fields'] = $function . '(' . $column . ') AS values';
		$options['where'][] = "id_tipo = " . $this->id_tipo;
		
		if (isset($options['group'])) {
			throw new Exception('This method cannot be used with GROUP BY.');	
		}
		
		if ($this->_parent instanceof InterAdmin) {
			$options['where'][] =  "parent_id = " . intval($this->_parent->id);
		}
		
		$rs = $this->_executeQuery($options);
		$array = array();
		foreach ($rs as $row) {
			$array[] = $row->{'main.values'};
		}
		return $array;	
	}
		
	/**
	 * Returns the number of InterAdmins using COUNT(id).
	 *
	 * @param array $options Default array of options. Available keys: where.
	 * @return int Count of InterAdmins found.
	 */
	public function count($deprecated, $options = array()) {
		if ($deprecated != InterAdmin::DEPRECATED_METHOD) {
			throw new Exception("Use records()->count() instead.");
		}
		if (empty($options['group'])) {
			$options['fields'] = array('COUNT(id) AS count_id');
		} elseif ($options['group'] == 'id') {
			// O COUNT() precisa trazer a contagem total em 1 linha
			// Caso exista GROUP BY id, ele traria em várias linhas
			// Esse é um tratamento especial apenas para o ID
			$options['fields'] = array('COUNT(DISTINCT id) AS count_id');
			unset($options['group']);
		} else {
			// Se houver GROUP BY com outro campo, retornará a contagem errada
			throw new Exception("GROUP BY is not supported when using count().");
		}

		$retorno = $this->findFirst(InterAdmin::DEPRECATED_METHOD, $options);
		return intval($retorno->count_id);
	}
	
	/**
	 * @param array $options Default array of options. Available keys: fields, where, order, group, class.
	 * @return InterAdmin 	First InterAdmin object found.
	 */
	public function findFirst($deprecated, $options = array()) {
		if ($deprecated != InterAdmin::DEPRECATED_METHOD) {
			throw new Exception("Use records()->first() instead.");
		}
		return $this->deprecatedFind(array('limit' => 1) + $options)->first();
	}

	/**
	 * Retrieves the first records which have this InterAdminTipo's id_tipo.
	 * 
	 * @return InterAdmin 	First InterAdmin object found.
	 */
	public function first() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		
		return $this->limit(1)->all()->first();
	}

	/**
	 * Returns the model identified by model_id_tipo, or the object itself if it has no model.
	 *
	 * @param array $options Default array of options.
	 * @return InterAdminTipo Model used by this InterAdminTipo.
	 */
	public function getModel() {
		if ($this->model_id_tipo) {
			if (is_numeric($this->model_id_tipo)) {
				$model = new InterAdminTipo($this->model_id_tipo);
			} else {
				$className = 'Jp7_Model_' . $this->model_id_tipo . 'Tipo';
				$model = new $className();
			}
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
			//unset($model->campos);
			$campos_parameters = array(
				'tipo', 'nome', 'ajuda', 'tamanho', 'obrigatorio', 'separador', 'xtra',
				'lista', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissoes',
				'default', 'nome_id'
			);
			$campos	= explode('{;}', $this->campos);
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
							'db' => $this->_db,
							'default_class' => static::DEFAULT_NAMESPACE . 'InterAdminTipo'
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
		$fields = array_keys($this->getCampos());
		foreach ($fields as $key => $field) {
			if (strpos($field, 'tit_') === 0 || strpos($field, 'func_') === 0) {
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
		if (is_null($fields)) {
			if (!$aliases = $this->_getMetadata('camposAlias')) {
				$allFields = array_keys($this->getCampos());
				$aliases = $this->getCamposAlias($allFields);
				$this->_setMetadata('camposAlias', $aliases);
			}
			return $aliases;
		}
		
		$campos = $this->getCampos();
		$aliases = array();
		$update = false;
		
		foreach ((array) $fields as $field) {
			if (isset($campos[$field])) {
				if (empty($campos[$field]['nome_id'])) {
					$alias = $campos[$field]['nome'];
					if (is_object($alias)) {
						if ($campos[$field]['label']) {
						 	$alias = $campos[$field]['label'];
						} else {
							$alias = $alias->nome;	
						}
					}
					$alias = ($alias) ? to_slug($alias, '_') : $field;
					$aliases[$field] = $alias;
					// Cache
					$update = true;
					
					$campos[$field]['nome_id'] = $alias;
				} else {
					$aliases[$field] = $campos[$field]['nome_id'];
				}
			}
		}
		if ($update) {
			$this->_setMetadata('campos', $campos);
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
	public function getCampoTipo($campo) {
		if (is_object($campo['nome'])) {
			return $campo['nome'];
		} elseif ($campo['nome'] == 'all') {
			return new InterAdminTipo();
		}
	}	
	
	public function getCampoTipoByAlias($alias) {
		$campos = $this->getCampos();
		$aliases = array_flip($this->getCamposAlias());
		
		$nomeCampo = $aliases[$alias] ? $aliases[$alias] : $alias;
		return $this->getCampoTipo($campos[$nomeCampo]);
		
	}
	/**
	 * Returns this object´s nome and all the fields marked as 'combo', if the field 
	 * is an InterAdminTipo such as a select_key, its getStringValue() method is used.
	 *
	 * @return string For the tipo 'City' with the field 'state' marked as 'combo' it would return: 'City - State'.
	 */
	public function getStringValue(/*$simple = FALSE*/) {
		$campos = $this->getCampos();
		$return[] = $this->nome;
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
	 * Returns the nome according to the $lang.
	 * 
	 * @return string 
	 */
	public function getName() {
		$affix = Lang::get('interadmin.affix');
		return $this->{'nome' . $affix} ?: $this->nome;
	}
	
	/**
	 * Saves this InterAdminTipo.
	 * @return void
	 */
	public function save() {
		$this->id_slug = to_slug($this->nome);
		
		// log
		$this->log = date('d/m/Y H:i') . ' - ' . InterAdmin::getLogUser() . ' - ' . $_SERVER['REMOTE_ADDR'] . chr(13) . $this->log;
		
		// Inheritance
		$this->syncInheritance();
		$retorno = parent::save();
		
		// Inheritance - Tipos inheriting from this Tipo
		if ($this->id_tipo) {
			$inheritingTipos = InterAdminTipo::findTiposByModel($this->id_tipo, array(
				'class' => 'InterAdminTipo'
			));
			foreach ($inheritingTipos as $tipo) {
				$tipo->syncInheritance();
				$tipo->updateAttributes($tipo->attributes);
			}
		}
		return $retorno;
	}
	
	public function syncInheritance() {
		// Retornando ao valor real
		foreach (array_filter(explode(',', $this->inherited)) as $inherited_var) {
			$this->attributes[$inherited_var] = '';
		}
		$this->inherited = array();
		// Atualizando cache com dados do modelo
		if ($this->model_id_tipo) {
			if (is_numeric($this->model_id_tipo)) {
				$modelo = new InterAdminTipo($this->model_id_tipo);
				$modelo->loadAttributes(self::$inheritedFields, false);
			} else {
				$className = 'Jp7_Model_' . $this->model_id_tipo . 'Tipo';
				if (class_exists($className)) {
					$modelo = new $className();
				} else {
					echo 'Erro: Class ' . $className . ' not found';
				}
			}
			if ($modelo) {
				foreach (self::$inheritedFields as $field) {
					if ($modelo->$field) {
						if (!$this->$field || in_array($field, self::$privateFields)) {
							$this->inherited[] = $field;
							$this->$field = $modelo->$field;
						}
					}
				}
			}
		}
		$this->inherited = implode(',', $this->inherited);
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
	public function deprecated_deleteInterAdmins($options = array()) {
		$records = $this->deprecatedFind($options);
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
	public function deprecated_deleteInterAdminsForever($options = array()) {
		$records = $this->deprecatedFind($options);
		foreach ($records as $record) {
			$record->forceDelete();
		}
		return count($records);
	}
	
	/**
	 * Updates all the InterAdmins.
	 * 
	 * @param array $attributes Attributes to be updated
	 * @param array $options [optional]
	 * @return int Count of updated InterAdmins.
	 */
	public function deprecated_updateInterAdmins($attributes, $options = array()) {
		$records = $this->deprecatedFind($options);
		foreach ($records as $record) {
			$record->updateAttributes($attributes);
		}
		return count($records);
	}
	
	public function getAttributesNames() {
		return $this->getColumns();
	}
		
	public function getAttributesCampos() {
		return array();
	}
	public function getAttributesAliases() {
		return array();
	}
	public function getTableName() {
		return $this->getDb()->getTablePrefix() . '_tipos';
	}
	public function getInterAdminsOrder() {
		if (!$interadminsOrderBy = $this->_getMetadata('interadmins_order')) {
			$interadminsOrderBy = array();
			$campos = $this->getCampos();
			if ($campos) {
				foreach ($campos as $key => $row) {
					if ($row['orderby'] && strpos($key, 'func_') === false) {
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
		return implode(',', $interadminsOrderBy);
	}
	/**
	 * Returns the table name for the InterAdmins.
	 * 
	 * @return string
	 */
	public function getInterAdminsTableName() {
		return $this->_getTableLang() . (($this->tabela) ? '_' . $this->tabela : '');
	}
	/**
	 * Returns the table name for the files.
	 * 
	 * @return string
	 */
	public function getArquivosTableName() {
		return $this->_getTableLang() . '_arquivos';
	}
	/**
	 * Returns $db_prefix OR $db_prefix + $lang->prefix.
	 * @return string
	 */	
	protected function _getTableLang() {
		$table = $this->getDb()->getTablePrefix();
		if ($this->language) {
			if (!Lang::has('interadmin.affix')) {
				throw new Exception('You need to add interadmin.affix to app/lang/' . App::getLocale() . '/interadmin.php');
			}
			$table .= Lang::get('interadmin.affix');
		}
		return $table;
	}	
	protected function _setMetadata($varname, $value) {
		$db_identifier = $this->getDb()->getDatabaseName();
		
		$cache = TipoCache::getInstance($db_identifier, $this->getDb()->getTablePrefix(), $this->id_tipo);
		$cache->set($varname, $value);
	}
	protected function _getMetadata($varname) {
		$db_identifier = $this->getDb()->getDatabaseName();
		
		$cache = TipoCache::getInstance($db_identifier, $this->getDb()->getTablePrefix(), $this->id_tipo);
		return $cache->get($varname);
	}
	/**
	 * Returns metadata about the children tipos that the InterAdmins have.
	 *  
	 * @return array
	 */
	public function getInterAdminsChildren() {
		if (!$children = $this->_getMetadata('children')) {
			//$model = $this->getModel();
			
			$children = array();
			$childrenArr = explode("{;}", $this->children);
			for ($i = 0; $i < count($childrenArr) - 1; $i++) {
				$childrenArrParts = explode('{,}', $childrenArr[$i]);
				if (count($childrenArrParts) < 4) { // 4 = 'id_tipo', 'nome', 'ajuda', 'netos'
					// Fix para tipos com estrutura antiga e desatualizada
					$childrenArrParts = array_pad($childrenArrParts, 4, '');
				}
				$child = array_combine(array('id_tipo', 'nome', 'ajuda', 'netos'), $childrenArrParts);
				$nome_id = studly_case(to_slug($child['nome']));
				$children[$nome_id] = $child;
			}
			$this->_setMetadata('children', $children);
		}
		return $children;
	}
	
	/**
	 * Returns a InterAdminTipo if the $nome_id is found in getInterAdminsChildren().
	 * 
	 * @param string $nome_id	Camel Case name, e.g.: DadosPessoais
	 * @return InterAdminTipo
	 */
	public function getInterAdminsChildrenTipo($nome_id) {
		$childrenTipos = $this->getInterAdminsChildren();
		if (isset($childrenTipos[$nome_id])) {
			$id_tipo = $childrenTipos[$nome_id]['id_tipo'];
			return InterAdminTipo::getInstance($id_tipo, array(
				'db' => $this->_db,
				'default_class' => static::DEFAULT_NAMESPACE . 'InterAdminTipo'
			));
		}
	}
	
	public function getRelationshipData($relationship) {
		$aliases = $this->getCamposAlias();
		
		$campoNome = array_search($relationship, $aliases);
		$alias = true;
		if ($campoNome === false) {
			$campoNome = $relationship;
			$alias = false;
		}
		$campos = $this->getCampos();
		$campoTipo = isset($campos[$campoNome]) ? $this->getCampoTipo($campos[$campoNome]) : null;
		if ($campoTipo instanceof InterAdminTipo) {
			return array(
				'type' => 'select',
				'tipo' => $campoTipo,
				'name' => $relationship,
				'alias' => $alias
			);
		}
		// As children
		$studlyCased = ucfirst($relationship);
		if ($childrenTipo = $this->getInterAdminsChildrenTipo($studlyCased)) {
			return array(
				'type' => 'children',
				'tipo' => $childrenTipo,
				'name' => $relationship,
				'alias' => true
			);
		}
		// As method
		$optionsInstance = ['default_class' => static::DEFAULT_NAMESPACE . 'InterAdmin'];
		$recordModel = InterAdmin::getInstance(0, $optionsInstance, $this);
		if (method_exists($recordModel, $relationship)) {
			return $recordModel->$relationship()->getRelationshipData();
		}
		throw new Exception('Unknown relationship: ' . $relationship);
	}
	
	/**
	 * Creates a record with id_tipo, mostrar, date_insert and date_publish filled.
	 * 
	 * @param array $attributes Attributes to be merged into the new record.
	 * @return InterAdmin
	 */
	public function deprecated_createInterAdmin(array $attributes = array()) {
		$options = array('default_class' => static::DEFAULT_NAMESPACE . 'InterAdmin');
		$record = InterAdmin::getInstance(0, $options, $this);
		if ($mostrar = $this->getCamposAlias('char_key')) {
			$record->$mostrar = 'S';
		}
		$record->date_publish = date('c');
		$record->date_insert = date('c');
		$record->publish = 'S';
		$record->log = '';
		
		return $record->fill($attributes);
	}
	
	/**
	 * Returns all InterAdminTipo's using this InterAdminTipo as a model (model_id_tipo).
	 * 
	 * @param array $options [optional]
	 * @return InterAdminTipo[] Array of Tipos indexed by their id_tipo.
	 */
	public function getTiposUsingThisModel($options = array()) {
		if (!isset($this->_tiposUsingThisModel)) {
			
			$options2 = array(
				'fields' => 'id_tipo',
				'from' => $this->getTableName() . ' AS main',
				'where' => array(
					"model_id_tipo = '" . $this->id_tipo . "'"
				)
			);
			$rs = $this->_executeQuery($options2);
			
			$options['default_class'] = static::DEFAULT_NAMESPACE . 'InterAdminTipo';		
			$this->_tiposUsingThisModel = array();
			foreach ($rs as $row) {
				$this->_tiposUsingThisModel[$row->id_tipo] = InterAdminTipo::getInstance($row->id_tipo, $options);;
			}
			$this->_tiposUsingThisModel[$this->id_tipo] = $this;
		}
		return $this->_tiposUsingThisModel;
	}
	/**
	 * Retrieves the first InterAdminTipo from the database.
	 * 
	 * @param 	array $options [optional]
	 * @return 	InterAdminTipo
	 */
	public static function findFirstTipo($options = array()) {
		$tipos = self::findTipos(array('limit' => 1) + $options);
		return empty($tipos) ? null : $tipos[0]; 
	}
	/**
	 * Retrieves the first InterAdminTipo with the given "model_id_tipo".
	 * 
	 * @param	string|int	$model_id_tipo	
	 * @param	array 		$options [optional]
	 * @return	InterAdminTipo
	 */
	public static function findFirstTipoByModel($model_id_tipo, $options = array()) {
		return self::findTiposByModel($model_id_tipo, array('limit' => 1) + $options)[0];
	}
	/**
	 * Retrieves all the InterAdminTipo with the given "model_id_tipo".
	 * @param string|int 	$model_id_tipo
	 * @param array			$options [optional]
	 * @return array
	 */
	public static function findTiposByModel($model_id_tipo, $options = array()) {
		$options['where'][] = "model_id_tipo = '" . $model_id_tipo . "'";
		if ($model_id_tipo != '0') {
			// Devido à mudança de int para string do campo model_id_tipo, essa linha é necessária
			$options['where'][] = "model_id_tipo != '0'";
		}
		return self::findTipos($options); 
	}
	/**
	 * Retrieves multiple InterAdminTipo's from the database.
	 * 
	 * @param 	array $options [optional]
	 * @return 	InterAdminTipo[]
	 */
	public static function findTipos($options = array()) {
		$instance = new self();
		if (isset($options['db'])) {
			$instance->setDb($options['db']);
		}
		if (!isset($options['fields'])) {
			$options['fields'] = [];
		}		
		$options['fields'] = array_merge(array('id_tipo'), (array) $options['fields']);
		
		$options['from'] = $instance->getTableName() . ' AS main';
		if (empty($options['where'])) {
			$options['where'][] = '1 = 1';
		}
	 	if (empty($options['order'])) {
	 		$options['order'] = 'ordem, nome';
		}
		// Internal use
		$options['aliases'] = $instance->getAttributesAliases();
		$options['campos'] = $instance->getAttributesCampos();
		
		$rs = $instance->_executeQuery($options);
		$tipos = array();
		
		foreach ($rs as $row) {
			$tipo = InterAdminTipo::getInstance($row->id_tipo, array(
				'db' => $instance->getDb(),
				'class' => isset($options['class']) ? $options['class'] : null
			));
			$instance->_getAttributesFromRow($row, $tipo, $options);
			$tipos[] = $tipo;
		}
		return $tipos;
	}
	
	protected function _prepareInterAdminsOptions(&$options, &$optionsInstance) {
		$this->_whereArrayFix($options['where']); // FIXME
		
		$optionsInstance = array(
			'class' => isset($options['class']) ? $options['class'] : null,
			'default_class' => static::DEFAULT_NAMESPACE . 'InterAdmin'
		);
		
		$recordModel = InterAdmin::getInstance(0, $optionsInstance, $this);
		if ($this->_parent instanceof InterAdmin) {
			$recordModel->setParent($this->_parent);
		}
		
		if (empty($options['fields'])) {
			$defaultFields = static::DEFAULT_FIELDS;
			if (strpos($defaultFields, ',') !== false) {
				$defaultFields = explode(',', $defaultFields);
			}
			$options['fields'] = $defaultFields;
		}
		if (!array_key_exists('fields_alias', $options)) {
			$options['fields_alias'] = static::DEFAULT_FIELDS_ALIAS;
		}
		
		$this->_resolveWildcard($options['fields'], $recordModel);
		
		if (count($options['fields']) != 1 || strpos($options['fields'][0], 'COUNT(') === false) {
			$requiredFields = array_intersect(array('id', 'id_tipo', 'id_slug'), $recordModel->getColumns());
			$options['fields'] = array_merge($requiredFields, (array) $options['fields']);
		}
		
		$options['from'] = $recordModel->getTableName() . " AS main";
		if (empty($options['order'])) {
			$options['order'] = $this->getInterAdminsOrder();
		}
		
		// Internal use
		$options['aliases'] = $recordModel->getAttributesAliases();
		$options['campos'] = $recordModel->getAttributesCampos();
		$options['model'] = $recordModel;
		$options['eager_load'] = array();
		
		if (isset($options['with'])) {
			foreach ($options['with'] as $withRelationship) {
				// Isso aqui é mais uma validação
				// O código mesmo é rodado depois
				$levels = explode('.', $withRelationship);
				
				if ($relationshipData = $this->getRelationshipData($levels[0])) {
					if ($relationshipData['type'] === 'select') {
						// select.* - Esse carregamento é feito com join para aproveitar código existente
						// E também porque join é mais rápido para hasOne() do que um novo select
						$options['fields'][$levels[0]] = array('*');
						array_shift($levels);
					}
					$options['eager_load'][] = $relationshipData + array(
						'levels' => $levels
					);
				} else{
					throw new Exception('Unknown relationship: ' . $levels[0]);
				}
			}
		}
	}
		
	public function getInterAdminsAdminAttributes() {
		return array('id_slug', 'id_string', 'parent_id', 'parent_id_tipo', 'date_publish', 'date_insert', 'date_expire', 'date_modify', 'log', 'publish', 'deleted');
	}
		
	public function getFillable() {
		return $this->getAttributesNames();
	}

	/**
	 * Returns all records having an InterAdminTipo that uses this as a model (model_id_tipo).
	 * 
	 * @param array $options [optional]
	 * @return InterAdmin[]
	 */
	public function modelRecords() {
		$tipos = $this->getTiposUsingThisModel();

		$query = new \Jp7\Interadmin\TypelessQuery($this);
		return $query->whereIn('id_tipo', $tipos);
	}
	
	public function deprecatedTypelessFind($options = array()) {
		$this->_prepareInterAdminsOptions($options, $optionsInstance);
		
		$rs = $this->_executeQuery($options);
		$records = array();
		foreach ($rs as $row) {
			$type = InterAdminTipo::getInstance($row->id_tipo);

			$record = InterAdmin::getInstance($row->id, $optionsInstance, $type);
			$this->_getAttributesFromRow($row, $record, $options);
			$records[] = $record;
		}
		return new Collection($records);
	}

	public function getTagFilters() {
		return [
			'tags.id_tipo' => $this->id_tipo,
			'tags.id' => 0
		];
	}
	
    /**
     * Returns $_defaultClass.
     * 
     * @see InterAdminTipo::$_defaultClass
     */
    public static function getDefaultClass() {
        return self::$_defaultClass;
    }
    
    /**
     * Sets $_defaultClass.
     * 
     * @param object $_defaultClass
     * @see InterAdminTipo::$_defaultClass
     */
    public static function setDefaultClass($defaultClass) {
        self::$_defaultClass = $defaultClass;
    }
	
    /**
     * @see InterAdminAbstract::getAdminAttributes()
     */
    public function getAdminAttributes() {
        return array();
    }

    public function records() {
    	return new \Jp7\Interadmin\Query($this);
    }    
}