<?php

namespace Jp7\Interadmin;

use Jp7\CollectionUtil;
use Jp7\Laravel\RouterFacade as r;
use BadMethodCallException;
use InvalidArgumentException;
use UnexpectedValueException;
use Exception;
use Lang;
use Request;
use App;
use Cache;
use RecordUrl;
use DB;

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Class which represents records on the table interadmin_{client name}_tipos.
 *
 * @property string $interadminsOrderby SQL Order By for the records of this Type.
 * @property string $class Class to be instantiated for the records of this Type.
 * @property string $tabela Table of this Type, or of its Model, if it has no table.
 */
class Type extends RecordAbstract
{
    use \Jp7\Laravel\Routable;

    const ID_TIPO = 0;
    const CACHE_TAG = 'type';

    private static $inheritedFields = [
        'class', 'class_tipo', 'icone', 'layout', 'layout_registros', 'tabela',
        'template', 'children', 'campos', 'language', 'editar', 'unico', 'disparo',
        'editpage', 'visualizar', 'template_inserir', 'tags_list', 'hits', 'texto',
        'xtra_disabledfields', 'xtra_disabledchildren', 'arquivos'
    ];
    private static $privateFields = ['children', 'campos'];

    protected static $_defaultClass = self::class;

    protected $_primary_key = 'id_tipo';

    /**
     * Contains the parent Type object, i.e. the record with an 'id_tipo' equal to this record's 'parent_id_tipo'.
     *
     * @var self
     */
    protected $_parent;

    protected $_tiposUsingThisModel;

    /**
     * Construct.
     *
     * @param int $id_tipo [optional] This record's 'id_tipo'.
     */
    public function __construct($id_tipo = null)
    {
        if (is_null($id_tipo)) {
            $id_tipo = static::ID_TIPO;
        }
        $this->id_tipo = $id_tipo;
    }

    public function &__get($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        } elseif (in_array($name, $this->getAttributesNames())) {
            $this->attributes += $this->getCache('attributes', function () {
                return (array) $this->getDb()
                    ->table('tipos')
                    ->where('id_tipo', $this->id_tipo)
                    ->first();
            });
            return $this->attributes[$name];
        }

        return $null; // Needs to be variable to be returned as reference
    }

    public function __call($methodName, $args)
    {
        $childrenType = $this->children()
            ->where('id_slug', snake_case($methodName, '-'))
            ->first();

        if ($childrenType) {
            return $childrenType->records();
        }
        // Default error when method doesn´t exist
        $message = 'Call to undefined method '.get_class($this).'->'.
            $methodName.'(). Available magic methods: '."\n";

        foreach ($this->children()->get() as $child) {
            $message .= "\t\t- ".lcfirst(camel_case($child->id_slug))."()\n";
        }
        throw new BadMethodCallException($message);
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]) || in_array($name, $this->getAttributesNames());
    }

    /**
     * Returns an Type instance. If $options['class'] is passed,
     * it will be returned an object of the given class, otherwise it will search
     * on the database which class to instantiate.
     *
     * @param int   $id_tipo This record's 'id_tipo'.
     * @param array $options Default array of options. Available keys: class, default_class.
     *
     * @return static Returns an Type or a child class in case it's defined on its 'class_tipo' property.
     */
    public static function getInstance($id_tipo, $options = [])
    {
        if (isset($options['class'])) {
            // Classe foi forçada
            $classTipo = $options['class'];
        } else {
            // Classe não foi forçada, verificar classMap
            $classTipo = TypeClassMap::getInstance()->getClass($id_tipo);
            if (!$classTipo) {
                if (isset($options['default_namespace'])) {
                    $classTipo = $options['default_namespace'].'Type';
                } else {
                    $classTipo = self::$_defaultClass;
                }
            }
        }
        // Classe foi encontrada, instanciar o objeto
        $tipo = new $classTipo($id_tipo);
        if (!empty($options['db'])) {
            $tipo->setDb($options['db']);
        }
        return $tipo;
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
     * Gets the parent Type object for this record, which is then cached on the $_parent property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return Type|RecordAbstract
     */
    public function getParent($options = [])
    {
        if ($this->_parent) {
            return $this->_parent;
        }
        if ($this->parent_id_tipo) {
            $options['default_namespace'] = static::DEFAULT_NAMESPACE;

            return $this->_parent = self::getInstance($this->parent_id_tipo, $options);
        }
    }
    public function getAncestors()
    {
        $parents = [];
        $parent = $this;

        while (($parent = $parent->getParent()) && $parent->id_tipo) {
            array_unshift($parents, $parent);
        }

        return $parents;
    }

    /**
     * Sets the parent Type or Record object for this record, changing the $_parent property.
     *
     * @param RecordAbstract $parent
     */
    public function setParent(RecordAbstract $parent = null)
    {
        $this->_parent = $parent;
    }
    /**
     * Retrieves the children of this Type.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return array Array of Type objects.
     *
     * @deprecated Actually its being used by TypeQuery to find any type
     */
    public function deprecatedGetChildren($options = [])
    {
        $this->_whereArrayFix($options['where']); // FIXME

        if (empty($options['order'])) {
            $options['order'] = 'ordem, nome';
        }
        if (empty($options['where'])) {
            $options['where'] = ['1=1'];
        }
        if (empty($options['fields'])) {
            $options['fields'] = $this->getAttributesNames();
        } else {
            $options['fields'] = array_merge(['id_tipo'], (array) $options['fields']);
        }
        // Internal use
        $options['from'] = $this->getTableName().' AS main';
        $options['aliases'] = $this->getAttributesAliases();
        $options['campos'] = $this->getAttributesCampos();

        $rs = $this->_executeQuery($options);

        $tipos = [];
        foreach ($rs as $row) {
            $tipo = self::getInstance($row->id_tipo, [
                'db' => $this->_db,
                'class' => isset($options['class']) ? $options['class'] : null,
                'default_namespace' => static::DEFAULT_NAMESPACE,
            ]);
            $tipo->setParent($this);
            $this->_getAttributesFromRow($row, $tipo, $options);
            $tipos[] = $tipo;
        }
        // $rs->Close();
        return new Collection($tipos);
    }

    public function children()
    {
        $query = new Query\TypeQuery($this);
        return $query->where('parent_id_tipo', $this->id_tipo);
    }

    public function childrenByModel($model_id_tipo)
    {
        return $this->children()->where('model_id_tipo', $model_id_tipo);
    }

    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
     *
     * @return Record[] Array of Record objects.
     *
     * @deprecated
     */
    public function deprecatedFind($options = [])
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);
        $options['where'][] = 'id_tipo = '.$this->id_tipo;
        if ($this->_parent instanceof Record) {
            // NULL to avoid finding children for invalid parents without ID
            $options['where'][] =  'parent_id = '.($this->_parent->id ?: 'NULL');
            if ($this->_parent->id_tipo) {
                $options['where'][] = 'parent_id_tipo = '.$this->_parent->id_tipo;
            }
        }

        $rs = $this->_executeQuery($options);

        $records = [];
        foreach ($rs as $row) {
            $_id = isset($row->id) ? $row->id : null;
            $record = Record::getInstance($_id, $optionsInstance, $this);
            if ($this->_parent instanceof Record) {
                $record->setParent($this->_parent);
            }
            $this->_getAttributesFromRow($row, $record, $options);
            $records[] = $record;
        }
        if ($options['eager_load']) {
            foreach ($options['eager_load'] as $relationshipData) {
                if ($relationshipData['type'] == 'select') {
                    // Any eager load level missing?
                    if ($relationshipData['levels']) {
                        $selects = [];
                        $attribute = $relationshipData['name'];
                        foreach ($records as $item) {
                            if ($item->$attribute) {
                                $selects[] = $item->$attribute;
                            }
                        }
                        CollectionUtil::eagerLoad($selects, $relationshipData['levels']);
                    }
                } else {
                    CollectionUtil::eagerLoad($records, $relationshipData['levels']);
                }
            }
        }
        // // $rs->Close();
        return new Collection($records);
    }

    public function deprecated_distinct($column, $options = [])
    {
        return $this->deprecated_aggregate('DISTINCT', $column, $options);
    }

    public function deprecated_max($column, $options = [])
    {
        $retorno = $this->deprecated_aggregate('MAX', $column, $options);

        return $retorno[0];
    }

    public function deprecated_min($column, $options = [])
    {
        $retorno = $this->deprecated_aggregate('MIN', $column, $options);

        return $retorno[0];
    }

    public function deprecated_sum($column, $options = [])
    {
        $retorno = $this->deprecated_aggregate('SUM', $column, $options);

        return $retorno[0];
    }

    public function deprecated_avg($column, $options = [])
    {
        $retorno = $this->deprecated_aggregate('AVG', $column, $options);

        return $retorno[0];
    }

    public function deprecated_aggregate($function, $column, $options)
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);

        $options['fields'] = $function.'('.$column.') AS values';
        $options['where'][] = 'id_tipo = '.$this->id_tipo;

        if (isset($options['group'])) {
            throw new Exception('This method cannot be used with GROUP BY.');
        }

        if ($this->_parent instanceof Record) {
            $options['where'][] =  'parent_id = '.intval($this->_parent->id);
        }

        $rs = $this->_executeQuery($options);
        $array = [];
        foreach ($rs as $row) {
            $array[] = $row->{'main.values'};
        }

        return $array;
    }

    /**
     * Returns the number of Records using COUNT(id).
     *
     * @param array $options Default array of options. Available keys: where.
     *
     * @return int Count of Records found.
     */
    public function deprecatedCount($options = [])
    {
        if (empty($options['group'])) {
            $options['fields'] = ['COUNT(id) AS count_id'];
        } elseif ($options['group'] == 'id') {
            // O COUNT() precisa trazer a contagem total em 1 linha
            // Caso exista GROUP BY id, ele traria em várias linhas
            // Esse é um tratamento especial apenas para o ID
            $options['fields'] = ['COUNT(DISTINCT id) AS count_id'];
            unset($options['group']);
        } else {
            // Se houver GROUP BY com outro campo, retornará a contagem errada
            throw new Exception('GROUP BY is not supported when using count().');
        }

        $rows = $this->deprecatedFind(['limit' => 2] + $options);

        if (count($rows) > 1) {
            throw new Exception('Could not resolve groupBy() before count().');
        }

        return isset($rows[0]->count_id) ? intval($rows[0]->count_id) : 0;
    }

    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, class.
     *
     * @return Record First Record object found.
     */
    public function deprecatedFindFirst($options = [])
    {
        return $this->deprecatedFind(['limit' => 1] + $options)->first();
    }

    /**
     * Retrieves the first records which have this Type's id_tipo.
     *
     * @return Record First Record object found.
     */
    public function first()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->limit(1)->get()->first();
    }

    /**
     * Returns the model identified by model_id_tipo, or the object itself if it has no model.
     *
     * @param array $options Default array of options.
     *
     * @return Type Model used by this Type.
     */
    public function getModel()
    {
        if ($this->model_id_tipo) {
            if (is_numeric($this->model_id_tipo)) {
                $model = Type::getInstance($this->model_id_tipo, ['default_namespace' => static::DEFAULT_NAMESPACE]);
            } else {
                $className = 'Jp7_Model_'.$this->model_id_tipo.'Tipo';
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
    public function getCampos()
    {
        return $this->getCache('campos', function () {
            $campos_parameters = [
                'tipo', 'nome', 'ajuda', 'tamanho', 'obrigatorio', 'separador', 'xtra',
                'lista', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissoes',
                'default', 'nome_id',
            ];
            $campos    = explode('{;}', $this->campos);
            $A = [];
            for ($i = 0; $i < count($campos); $i++) {
                $parameters = explode('{,}', $campos[$i]);
                if ($parameters[0]) {
                    $A[$parameters[0]]['ordem'] = ($i+1);
                    $isSelect = strpos($parameters[0], 'select_') === 0;
                    for ($j = 0; $j < count($parameters); $j++) {
                        $A[$parameters[0]][$campos_parameters[$j]] = $parameters[$j];
                    }
                    if ($isSelect && $A[$parameters[0]]['nome'] != 'all') {
                        $id_tipo = $A[$parameters[0]]['nome'];
                        $A[$parameters[0]]['nome'] = self::getInstance($id_tipo, [
                            'db' => $this->_db,
                            'default_namespace' => static::DEFAULT_NAMESPACE,
                        ]);
                    }
                }
            }
            // Alias
            foreach ($A as $campo => $array) {
                if (empty($array['nome_id'])) {
                    // Gerar nome_id
                    $alias = $array['nome'];
                    if (is_object($alias)) {
                        $alias = $array['label'] ?: $alias->nome;
                    }
                    if (!$alias) {
                        throw new UnexpectedValueException('An alias was expected.');
                        //$alias = $campo;
                    }
                    $A[$campo]['nome_id'] = to_slug($alias, '_');
                }
                if (strpos($campo, 'select_') === 0) {
                    if (strpos($campo, 'select_multi_') === 0) {
                        $A[$campo]['nome_id'] .= '_ids';
                    } else {
                        $A[$campo]['nome_id'] .= '_id';
                    }
                } elseif (strpos($campo, 'special_') === 0 && $array['xtra']) {
                    if (in_array($array['xtra'], FieldUtil::getSpecialMultiXtras())) {
                        $A[$campo]['nome_id'] .= '_ids';
                    } else {
                        $A[$campo]['nome_id'] .= '_id';
                    }
                }
            }
            return $A;
        });
    }
    /**
     * Returns an array with the names of all the fields available.
     *
     * @return array
     */
    public function getCamposNames()
    {
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
     *
     * @return array|string Resulting alias(es).
     */
    public function getCamposAlias($fields = null)
    {
        $aliases = $this->getCache('campos_alias', function () {
            $aliases = [];
            foreach ($this->getCampos() as $campo => $array) {
                if (strpos($campo, 'tit_') === 0 || strpos($campo, 'func_') === 0) {
                    continue;
                }
                $aliases[$campo] = $array['nome_id'];
            }
            return $aliases;
        });

        if (is_null($fields)) {
            return $aliases;
        }

        return isset($aliases[$fields]) ? $aliases[$fields] : null;
    }

    public function getCamposCombo()
    {
        return array_keys(array_filter($this->getCampos(), function ($campo) {
            return (bool) $campo['combo'] || $campo['tipo'] === 'varchar_key';
        }));
    }

    public function getRelationships()
    {
        // getCampoTipo might be different for each class
        $cacheKey = static::class.','.$this->getCacheKey('relationships');
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, 5, function () {
            $relationships = [];

            foreach ($this->getCampos() as $campo => $array) {
                if (strpos($campo, 'tit_') === 0 || strpos($campo, 'func_') === 0) {
                    continue;
                }
                if (strpos($campo, 'select_') === 0) {
                    $multi = strpos($campo, 'select_multi_') === 0;
                    $hasType = in_array($array['xtra'], FieldUtil::getSelectTipoXtras());
                    if ($multi) {
                        $relation = substr($array['nome_id'], 0, -4); // _ids = 4 chars
                    } else {
                        $relation = substr($array['nome_id'], 0, -3); // _id = 3 chars
                    }
                    $relationships[$relation] = [
                        'query' => $hasType ? $array['nome'] : $array['nome']->records(),
                        'type' => $hasType,
                        'multi' => $multi,
                    ];
                } elseif (strpos($campo, 'special_') === 0 && $array['xtra']) {
                    $multi = in_array($array['xtra'], FieldUtil::getSpecialMultiXtras());
                    $hasType = in_array($array['xtra'], FieldUtil::getSpecialTipoXtras());
                    if ($multi) {
                        $relation = substr($array['nome_id'], 0, -4); // _ids = 4 chars
                    } else {
                        $relation = substr($array['nome_id'], 0, -3); // _id = 3 chars
                    }
                    if ($specialTipo = $this->getCampoTipo($array)) {
                        $query = $specialTipo->records();
                    } else {
                        $query = new TypelessQuery(static::getInstance(0));
                    }
                    $relationships[$relation] = [
                        'query' => $query,
                        'type' => $hasType,
                        'multi' => $multi,
                    ];
                }
            }
            return $relationships;
        });
    }

    /**
     * Returns the Type for a field.
     *
     * @param object $campo
     *
     * @return Type
     */
    public function getCampoTipo($campo)
    {
        if (is_object($campo['nome'])) {
            return $campo['nome'];
        } elseif ($campo['nome'] == 'all') {
            return new self;
        }
    }

    public function getCampoTipoByAlias($alias)
    {
        $campos = $this->getCampos();
        $aliases = array_flip($this->getCamposAlias());

        $nomeCampo = $aliases[$alias] ? $aliases[$alias] : $alias;

        return $this->getCampoTipo($campos[$nomeCampo]);
    }
    /**
     * Returns this object´s nome.
     *
     * @return string
     */
    public function getStringValue(/*$simple = FALSE*/)
    {
        return $this->nome;
    }
    /**
     * Returns the nome according to the $lang.
     *
     * @return string
     */
    public function getName()
    {
        $suffix = Lang::get('interadmin.suffix');

        return $this->{'nome'.$suffix} ?: $this->nome;
    }

    /**
     * Saves this Type.
     */
    public function save()
    {
        $this->id_tipo_string = toId($this->nome);
        $this->id_slug = to_slug($this->nome);

        // log
        $this->log = date('d/m/Y H:i').' - '.Record::getLogUser().' - '.
            Request::ip().chr(13).$this->log;
        $this->date_modify = date('c');
        // Inheritance
        $this->syncInheritance();
        $retorno = $this->saveRaw();

        // Inheritance - Tipos inheriting from this Tipo
        if ($this->id_tipo) {
            $inheritingTipos = self::findTiposByModel($this->id_tipo, [
                'class' => self::class,
            ]);
            foreach ($inheritingTipos as $tipo) {
                $tipo->syncInheritance();
                $tipo->saveRaw();
            }
        }

        return $retorno;
    }

    protected function _update($attributes)
    {
        parent::_update($attributes);
        $this->clearCache();
        return $this;
    }

    public function syncInheritance()
    {
        // Retornando ao valor real
        foreach (array_filter(explode(',', $this->inherited)) as $inherited_var) {
            $this->attributes[$inherited_var] = '';
        }
        $this->inherited = [];
        // Atualizando cache com dados do modelo
        if ($this->model_id_tipo) {
            if (is_numeric($this->model_id_tipo)) {
                $modelo = new self($this->model_id_tipo);
            } else {
                $className = 'Jp7_Model_'.$this->model_id_tipo.'Tipo';
                $modelo = new $className();
            }
            foreach (self::$inheritedFields as $field) {
                if (($modelo->$field && !$this->$field) || in_array($field, self::$privateFields)) {
                    $this->inherited[] = $field;
                    $this->$field = $modelo->$field;
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
    public function delete()
    {
        $this->deleted_tipo = 'S';
        $this->save();
    }

    public function restore()
    {
        $this->deleted_tipo = '';
        $this->save();
    }

    /**
     * Deletes all the Records.
     *
     * @param array $options [optional]
     *
     * @return int Count of deleted Records.
     */
    public function deprecated_deleteInterAdmins($options = [])
    {
        $records = $this->deprecatedFind($options);
        foreach ($records as $record) {
            $record->delete();
        }

        return count($records);
    }

    /**
     * Deletes all the Records forever.
     *
     * @param array $options [optional]
     *
     * @return int Count of deleted Records.
     */
    public function deprecated_deleteInterAdminsForever($options = [])
    {
        $records = $this->deprecatedFind($options);
        foreach ($records as $record) {
            $record->forceDelete();
        }

        return count($records);
    }

    /**
     * Updates all the Records.
     *
     * @param array $attributes Attributes to be updated
     * @param array $options    [optional]
     *
     * @return int Count of updated Records.
     */
    public function deprecated_updateInterAdmins($attributes, $options = [])
    {
        $records = $this->deprecatedFind($options);
        foreach ($records as $record) {
            $record->updateAttributes($attributes);
        }

        return count($records);
    }

    public function getAttributesNames()
    {
        return $this->getColumns();
    }

    public function getAttributesCampos()
    {
        return [];
    }
    public function getAttributesAliases()
    {
        return [];
    }
    public function getTableName()
    {
        return $this->getDb()->getTablePrefix().'tipos';
    }
    public function getInterAdminsOrder()
    {
        return $this->getCache('order', function () {
            $order = [];
            $campos = $this->getCampos();
            if ($campos) {
                foreach ($campos as $key => $row) {
                    if (!$row['orderby'] || strpos($key, 'func_') !== false) {
                        continue;
                    }
                    if ($row['orderby'] < 0) {
                        $key .= ' DESC';
                    }
                    $order[$row['orderby']] = $key;
                }
                if ($order) {
                    ksort($order);
                }
            }
            $order[] = 'date_publish DESC';
            return implode(',', $order);
        });
    }
    /**
     * Returns the table name for the Records.
     *
     * @return string
     */
    public function getInterAdminsTableName()
    {
        return $this->_getTableLang().($this->tabela ?: 'registros');
    }
    /**
     * Returns the table name for the files.
     *
     * @return string
     */
    public function getArquivosTableName()
    {
        return $this->_getTableLang().'arquivos';
    }

    public function getRecordClass()
    {
        if (config('interadmin.psr-4')) {
            return str_replace('_', '\\', $this->class);
        }
        return $this->class;
    }

    public function getTypeClass()
    {
        if (config('interadmin.psr-4')) {
            return str_replace('_', '\\', $this->class_tipo);
        }
        return $this->class_tipo;
    }

    /**
     * Returns $db_prefix OR $db_prefix + $lang->prefix.
     *
     * @return string
     */
    protected function _getTableLang()
    {
        $table = $this->getDb()->getTablePrefix();
        if ($this->language) {
            if (!Lang::has('interadmin.prefix')) {
                throw new Exception('You need to add interadmin.prefix to app/lang/'.App::getLocale().'/interadmin.php');
            }
            $table .= Lang::get('interadmin.prefix');
        }

        return $table;
    }

    protected function clearCache()
    {
        // clear only this instance's cache
        $cache = Cache::tag(self::CACHE_TAG);
        $cache->forget($this->getCacheKey('attributes'));
        $cache->forget($this->getCacheKey('campos'));
        $cache->forget($this->getCacheKey('campos_alias'));
        $cache->forget($this->getCacheKey('children'));
        $cache->forget($this->getCacheKey('order'));
        // different values for getTipo() depending on class
        $cache->forget(static::class.','.$this->getCacheKey('relationships'));
    }

    protected function getCache($varname, $callback)
    {
        $cacheKey = $this->getCacheKey($varname);
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, 5, $callback);
    }

    protected function getCacheKey($varname)
    {
        return $varname.','.$this->_db.','.$this->id_tipo;
    }

    public static function checkCache()
    {
        $cache = Cache::tag(self::CACHE_TAG);
        // don't query too often
        if ($cache->get('modified:check') > time() - 10) {
            return; // too soon
        }
        $cache->forever('modified:check', time());
        // check if types changed
        $modified = strtotime(DB::table('tipos')
            ->select(DB::raw('MAX(date_modify) AS modified'))
            ->value('modified'));

        if ($modified !== $cache->get('modified')) {
            $cache->flush(); // flush tagged cache
            $cache->forever('modified', $modified);
        }
    }

    /**
     * Returns metadata about the children tipos that the Records have.
     *
     * @return array
     */
    public function getInterAdminsChildren()
    {
        return $this->getCache('children', function () {
            $children = [];
            $childrenArr = explode('{;}', $this->children);
            for ($i = 0; $i < count($childrenArr) - 1; $i++) {
                $childrenArrParts = explode('{,}', $childrenArr[$i]);
                if (count($childrenArrParts) < 4) { // 4 = 'id_tipo', 'nome', 'ajuda', 'netos'
                    // Fix para tipos com estrutura antiga e desatualizada
                    $childrenArrParts = array_pad($childrenArrParts, 4, '');
                }
                $child = array_combine(['id_tipo', 'nome', 'ajuda', 'netos'], $childrenArrParts);
                $nome_id = studly_case(to_slug($child['nome']));
                $children[$nome_id] = $child;
            }
            return $children;
        });
    }

    /**
     * Returns a Type if the $nome_id is found in getInterAdminsChildren().
     *
     * @param string $nome_id Camel Case name, e.g.: DadosPessoais
     *
     * @return Type
     */
    public function getInterAdminsChildrenTipo($nome_id)
    {
        $childrenTipos = $this->getInterAdminsChildren();
        if (isset($childrenTipos[$nome_id])) {
            $id_tipo = $childrenTipos[$nome_id]['id_tipo'];

            return self::getInstance($id_tipo, [
                'db' => $this->_db,
                'default_namespace' => static::DEFAULT_NAMESPACE,
            ]);
        }
    }

    public function getInterAdminsChildrenData($id_tipo)
    {
        foreach ($this->getInterAdminsChildren() as $metadata) {
            if ($metadata['id_tipo'] == $id_tipo) {
                return $metadata;
            }
        }
    }

    public function getInterAdminsChildrenTipos()
    {
        $tipos = [];
        foreach ($this->getInterAdminsChildren() as $nome_id => $metadata) {
            $tipos[] = $this->getInterAdminsChildrenTipo($nome_id);
        }
        return $tipos;
    }

    public function getRelationshipData($relationship)
    {
        $relationships = $this->getRelationships();

        if (isset($relationships[$relationship])) {
            $data = $relationships[$relationship];
            return [
                'type' => 'select',
                'tipo' => $data['query']->type(),
                'name' => $relationship,
                'alias' => true,
                'multi' => $data['multi']
            ];
        }
        // As children
        $studlyCased = ucfirst($relationship);
        if ($childrenTipo = $this->getInterAdminsChildrenTipo($studlyCased)) {
            return [
                'type' => 'children',
                'tipo' => $childrenTipo,
                'name' => $relationship,
                'alias' => true,
                'multi' => true
            ];
        }
        // As method
        $optionsInstance = ['default_namespace' => static::DEFAULT_NAMESPACE];
        $recordModel = Record::getInstance(0, $optionsInstance, $this);
        if (method_exists($recordModel, $relationship)) {
            return $recordModel->$relationship()->getRelationshipData();
        }
        throw new InvalidArgumentException('Unknown relationship: '.$relationship);
    }

    /**
     * Creates a record with id_tipo, mostrar, date_insert and date_publish filled.
     *
     * @param array $attributes Attributes to be merged into the new record.
     *
     * @return Record
     */
    public function deprecated_createInterAdmin(array $attributes = [])
    {
        $options = ['default_namespace' => static::DEFAULT_NAMESPACE];
        $record = Record::getInstance(0, $options, $this);
        if ($mostrar = $this->getCamposAlias('char_key')) {
            $record->$mostrar = 'S';
        }
        $record->date_publish = date('c');
        $record->date_insert = date('c');
        $record->publish = 'S';
        $record->log = '';

        if ($this->_parent instanceof Record) {
            $record->setParent($this->_parent);
        }

        return $record->fill($attributes);
    }

    /**
     * Returns all Type's using this Type as a model (model_id_tipo).
     *
     * @param array $options [optional]
     *
     * @return Type[] Array of Tipos indexed by their id_tipo.
     */
    public function getTiposUsingThisModel($options = [])
    {
        if (!isset($this->_tiposUsingThisModel)) {
            $options2 = [
                'fields' => 'id_tipo',
                'from' => $this->getTableName().' AS main',
                'where' => [
                    "model_id_tipo = '".$this->id_tipo."'",
                ],
            ];
            $rs = $this->_executeQuery($options2);

            $options['default_namespace'] = static::DEFAULT_NAMESPACE;
            $this->_tiposUsingThisModel = [];
            foreach ($rs as $row) {
                $this->_tiposUsingThisModel[$row->id_tipo] = Type::getInstance($row->id_tipo, $options);
            }
            $this->_tiposUsingThisModel[$this->id_tipo] = $this;
        }

        return $this->_tiposUsingThisModel;
    }
    /**
     * Retrieves the first Type from the database.
     *
     * @param array $options [optional]
     *
     * @return Type
     */
    public static function findFirstTipo($options = [])
    {
        $tipos = self::findTipos(['limit' => 1] + $options);

        return empty($tipos) ? null : $tipos[0];
    }
    /**
     * Retrieves the first Type with the given "model_id_tipo".
     *
     * @param string|int $model_id_tipo
     * @param array      $options       [optional]
     *
     * @return Type
     */
    public static function findFirstTipoByModel($model_id_tipo, $options = [])
    {
        return self::findTiposByModel($model_id_tipo, ['limit' => 1] + $options)[0];
    }
    /**
     * Retrieves all the Type with the given "model_id_tipo".
     *
     * @param string|int $model_id_tipo
     * @param array      $options       [optional]
     *
     * @return array
     */
    public static function findTiposByModel($model_id_tipo, $options = [])
    {
        $options['where'][] = "model_id_tipo = '".$model_id_tipo."'";
        if ($model_id_tipo != '0') {
            // Devido à mudança de int para string do campo model_id_tipo, essa linha é necessária
            $options['where'][] = "model_id_tipo != '0'";
        }

        return self::findTipos($options);
    }
    /**
     * Retrieves multiple Type's from the database.
     *
     * @param array $options [optional]
     *
     * @return Type[]
     */
    public static function findTipos($options = [])
    {
        $instance = new self();
        if (isset($options['db'])) {
            $instance->setDb($options['db']);
        }
        if (!isset($options['fields'])) {
            $options['fields'] = [];
        }
        $options['fields'] = array_merge(['id_tipo'], (array) $options['fields']);

        $options['from'] = $instance->getTableName().' AS main';
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
        $tipos = [];

        foreach ($rs as $row) {
            $tipo = self::getInstance($row->id_tipo, [
                'db' => isset($options['db']) ? $options['db'] : null,
                'class' => isset($options['class']) ? $options['class'] : null,
            ]);
            $instance->_getAttributesFromRow($row, $tipo, $options);
            $tipos[] = $tipo;
        }

        return $tipos;
    }

    protected function _prepareInterAdminsOptions(&$options, &$optionsInstance)
    {
        $this->_whereArrayFix($options['where']); // FIXME

        $optionsInstance = [
            'class' => isset($options['class']) ? $options['class'] : null,
            'default_namespace' => static::DEFAULT_NAMESPACE,
        ];

        $recordModel = Record::getInstance(0, $optionsInstance, $this);
        if ($this->_parent instanceof Record) {
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
            $requiredFields = array_intersect(['id', 'id_tipo', 'id_slug'], $recordModel->getColumns());
            $options['fields'] = array_merge($requiredFields, (array) $options['fields']);
        }

        $options['from'] = $recordModel->getTableName().' AS main';
        $options['order'] = (isset($options['order']) ? $options['order'].', ' : '').$this->getInterAdminsOrder();

        // Internal use
        $options['aliases'] = $recordModel->getAttributesAliases();
        $options['campos'] = $recordModel->getAttributesCampos();
        $options['model'] = $recordModel;
        $options['eager_load'] = [];

        if (!$options['campos']) {
            \Log::notice('Querying a type without "campos" - id_tipo: '.$this->id_tipo);
        }

        if (isset($options['with'])) {
            foreach ($options['with'] as $withRelationship) {
                // Isso aqui é mais uma validação
                // O código mesmo é rodado depois
                $levels = explode('.', $withRelationship);

                if ($relationshipData = $this->getRelationshipData($levels[0])) {
                    if ($relationshipData['type'] === 'select') {
                        // select.* - Esse carregamento é feito com join para aproveitar código existente
                        // E também porque join é mais rápido para hasOne() do que um novo select
                        if (!$relationshipData['multi']) {
                            // select_multi resolve eager_load depois no _loadRelationship()
                            $options['fields'][$levels[0]] = ['*'];
                        }
                        array_shift($levels);
                    }
                    $options['eager_load'][] = $relationshipData + [
                        'levels' => $levels,
                    ];
                } else {
                    throw new Exception('Unknown relationship: '.$levels[0]);
                }
            }
        }
    }

    public function getInterAdminsAdminAttributes()
    {
        return ['id_slug', 'id_string', 'parent_id', 'parent_id_tipo', 'date_publish', 'date_insert', 'date_expire', 'date_modify', 'log', 'publish', 'deleted', 'hits'];
    }

    public function getFillable()
    {
        return $this->getAttributesNames();
    }

    /**
     * Returns all records having an Type that uses this as a model (model_id_tipo).
     *
     * @param array $options [optional]
     *
     * @return Record[]
     */
    public function modelRecords()
    {
        $tipos = $this->getTiposUsingThisModel();

        $query = new TypelessQuery($this);

        return $query->whereIn('id_tipo', $tipos);
    }

    public function deprecatedTypelessFind($options = [])
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);

        $rs = $this->_executeQuery($options);
        $records = [];
        foreach ($rs as $row) {
            $type = Type::getInstance($row->id_tipo, ['default_namespace' => static::DEFAULT_NAMESPACE]);

            $record = Record::getInstance($row->id, $optionsInstance, $type);
            $this->_getAttributesFromRow($row, $record, $options);
            $records[] = $record;
        }

        return new Collection($records);
    }

    public function getTagFilters()
    {
        return [
            'id_tipo' => $this->id_tipo,
            'id' => 0,
        ];
    }

    /**
     * Returns $_defaultClass.
     *
     * @see Type::$_defaultClass
     */
    public static function getDefaultClass()
    {
        return self::$_defaultClass;
    }

    /**
     * Sets $_defaultClass.
     *
     * @param object $_defaultClass
     *
     * @see Type::$_defaultClass
     */
    public static function setDefaultClass($defaultClass)
    {
        self::$_defaultClass = $defaultClass;
    }

    /**
     * @see RecordAbstract::getAdminAttributes()
     */
    public function getAdminAttributes()
    {
        return [];
    }

    public function records()
    {
        return new Query($this);
    }

    public function getUrl() // $action = 'index', array $parameters = []
    {
        $args = func_get_args();
        array_unshift($args, $this);
        return call_user_func_array([RecordUrl::class, 'getTypeUrl'], $args);
    }

    /**
     * Gets the route for this type.
     * @param  string $action Default to 'index'
     * @return
     */
    public function getRoute($action = 'index')
    {
        $validActions = ['index', 'show', 'create', 'store', 'update', 'destroy', 'edit'];
        if (!in_array($action, $validActions)) {
            throw new BadMethodCallException('Invalid action "'.$action.'", valid actions: '.implode(', ', $validActions));
        }

        return r::getRouteByTypeId($this->id_tipo, $action);
    }
}
