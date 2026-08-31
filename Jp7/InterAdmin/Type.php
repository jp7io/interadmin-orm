<?php

namespace Jp7\InterAdmin;

use Illuminate\Support\Str;
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
 * Class which represents records on the table interadmin_{client name}_types.
 *
 * The @method block is what __callStatic() forwards to Query\TypeQuery -- it declares nothing and
 * adds nothing. first(), delete(), forceDelete() and restore() are absent on purpose: each is a
 * public INSTANCE method here, which PHP refuses to call statically rather than routing to
 * __callStatic(). build() and create() return Type rather than static, because TypeQuery
 * instantiates Type::getDefaultClass() and not the called class. MagicSurfaceTest holds both ends.
 *
 * @property string $interadminsOrderby SQL Order By for the records of this Type.
 * @property string $class Class to be instantiated for the records of this Type.
 * @property string $table_name Table of this Type, or of its Model, if it has no table.
 * @property int|string $type_id This Type's primary key.
 * @property string $children The child Types, as the '{,}'/'{;}' delimited blob the column stores.
 * @property ?string $deleted_at When this Type was soft-deleted, NULL while it is live.
 *
 * @method static Type build(array $attributes = [])
 * @method static bool chunk(int $count, callable $callback)
 * @method static int count()
 * @method static Type create(array $attributes = [])
 * @method static Query\TypeQuery debug(bool $debug = true)
 * @method static Query\TypeQuery doesntHave(string $relationship)
 * @method static bool exists()
 * @method static static|null find(int|string $id)
 * @method static static findOrFail(int|string $id)
 * @method static static findOrNew(int|string $id)
 * @method static static firstOrCreate(array $attributes, array $values = [])
 * @method static static firstOrFail()
 * @method static static firstOrNew(array $attributes, array $values = [])
 * @method static Query\TypeQuery forPage(int $page, int $perPage = 15)
 * @method static Collection get()
 * @method static array getOptionsArray(bool $replaceBinding = true)
 * @method static Query\TypeQuery groupBy(string $column)
 * @method static Query\TypeQuery groupByRaw(string $group)
 * @method static Query\TypeQuery has(string $relationship)
 * @method static Query\TypeQuery havingRaw(string $sql)
 * @method static Query\TypeQuery join(string $alias, string|Type $className, array|string $conditions, string $_joinType = 'INNER', bool $_typeless = false)
 * @method static array jsonList(string $column, string $key)
 * @method static Query\TypeQuery leftJoin(string $alias, string|Type $className, array|string $conditions)
 * @method static Query\TypeQuery limit(int $limit)
 * @method static Collection lists(string $column, ?string $key = null)
 * @method static Query\TypeQuery orWhere(string|callable $column, mixed $operator = null, mixed $value = null)
 * @method static Query\TypeQuery orderBy(string $column, string $direction = 'asc')
 * @method static Query\TypeQuery orderByRaw(string $order)
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(?int $perPage = null, array $columns = ['*'], string $pageName = 'page')
 * @method static Collection pluck(string $column, ?string $key = null)
 * @method static Query\TypeQuery published(bool|array $filters = true)
 * @method static Query\TypeQuery rightJoin(string $alias, string|Type $className, array|string $conditions)
 * @method static Query\TypeQuery select(string|array ...$columns)
 * @method static Query\TypeQuery skip(int $offset)
 * @method static Query\TypeQuery take(int $limit)
 * @method static Query\TypeQuery typelessJoin(string $alias, string|Type $className, array|string $conditions)
 * @method static Query\TypeQuery typelessLeftJoin(string $alias, string|Type $className, array|string $conditions)
 * @method static Query\TypeQuery typelessRightJoin(string $alias, string|Type $className, array|string $conditions)
 * @method static mixed value(string $column)
 * @method static Query\TypeQuery where(string|callable $column, mixed $operator = null, mixed $value = null)
 * @method static Query\TypeQuery whereDate(string $column, mixed $operator, mixed $value = null)
 * @method static Query\TypeQuery whereDay(string $column, mixed $operator, mixed $value = null)
 * @method static Query\TypeQuery whereDoesntHave(string $relationship, mixed $conditions = null)
 * @method static Query\TypeQuery whereFindInSet(string $column, mixed $value)
 * @method static Query\TypeQuery whereHas(string $relationship, mixed $conditions = null, bool $_not = false)
 * @method static Query\TypeQuery whereIn(string $column, array $values, bool $_not = false)
 * @method static Query\TypeQuery whereMonth(string $column, mixed $operator, mixed $value = null)
 * @method static Query\TypeQuery whereNotIn(string $column, array $values)
 * @method static Query\TypeQuery whereRaw(string $where)
 * @method static Query\TypeQuery whereNull(string $column)
 * @method static Query\TypeQuery whereNotNull(string $column)
 * @method static Query\TypeQuery whereYear(string $column, mixed $operator, mixed $value = null)
 * @method static Query\TypeQuery with(string ...$relationships)
 */
class Type extends RecordAbstract
{
    use \Jp7\Laravel\Routable;

    const TYPE_ID = 0;
    const CACHE_TAG = 'type';

    /**
     * Seconds. Cache::remember() has taken seconds since Laravel 5.8, and the 5 written here
     * meant five minutes; as five seconds it expired between two views of the same page.
     */
    const CACHE_TTL = 300;

    private static $inheritedFields = [
        'class', 'class_type', 'icon', 'layout', 'layout_records', 'table_name',
        'template', 'children', 'fields', 'language', 'edit', 'single', 'trigger_function',
        'editpage', 'template_view', 'template_insert', 'tags_list', 'hits', 'description',
        'xtra_disabledfields', 'xtra_disabledchildren', 'arquivos'
    ];
    private static $privateFields = ['children', 'fields'];

    protected static $_defaultClass = self::class;

    protected $_primary_key = 'type_id';

    /**
     * Contains the parent Type object, i.e. the record with an 'type_id' equal to this record's 'parent_type_id'.
     *
     * @var self
     */
    protected $_parent;

    /**
     * Cached aliases.
     *
     * @var array
     */
    protected $_interadminAliases = [];

    /**
     * Cached relationships.
     *
     * @var array
     */
    protected $_interadminRelationships;

    /**
     * Construct.
     *
     * @param int $type_id [optional] This record's 'type_id'.
     */
    public function __construct($type_id = null)
    {
        $this->type_id = $type_id ?? static::TYPE_ID;
    }

    public function &__get($name)
    {
        $value = null;
        if (array_key_exists($name, $this->attributes)) {
            $value = &$this->attributes[$name];
        } elseif (in_array($name, $this->getAttributesNames())) {
            $this->attributes += $this->getCache('attributes', function () {
                return (array) $this->getDb()
                    ->table('types')
                    ->where('type_id', $this->type_id)
                    ->first();
            });
            if (array_key_exists($name, $this->attributes)) {
                $value = &$this->attributes[$name];
            }
        } elseif ($query = $this->_getManyRelationship($name)) {
            $value = $query->get(); // not memoized, unlike Record implementation
        }
        $value = $this->getMutatedAttribute($name, $value);
        return $value;
    }

    public function __call($methodName, $args)
    {
        if ($query = $this->_getManyRelationship($methodName)) {
            return $query;
        }
        // Default error when method doesn´t exist
        $message = 'Call to undefined method '.get_class($this).'->'.
            $methodName.'(). Available magic methods: '."\n";

        foreach ($this->_getChildrenKeyBySlug() as $slug => $child) {
            $message .= "\t\t- ".lcfirst(Str::camel($slug))."()\n";
        }
        throw new BadMethodCallException($message);
    }

    protected function _getManyRelationship($name)
    {
        $childrenBySlug = $this->_getChildrenKeyBySlug();
        $childSlug = Str::snake($name, '-');
        if (array_key_exists($childSlug, $childrenBySlug)) {
            $childrenBySlug[$childSlug]->setParent($this);
            return $childrenBySlug[$childSlug]->records();
        }
    }

    protected function _getChildrenKeyBySlug()
    {
        return $this->getCache('__call', function () {
            return $this->children()
                ->select('id_slug')
                ->get()
                ->each(function (Type $childType) {
                    $childType->setParent(null); // reduce cache size and recursive unserializing
                })
                ->keyBy('id_slug') // for faster key searches
                ->all(); // to plain array
        });
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]) || in_array($name, $this->getAttributesNames());
    }

    public static function __callStatic($name, array $arguments)
    {
        if ($query = static::query()) {
            return call_user_func_array([$query, $name], $arguments);
        }
        throw new BadMethodCallException('Call to undefined method '.get_called_class().'::'.$name);
    }

    public static function all()
    {
        return static::query()->get();
    }

    public static function query()
    {
        return new Query\TypeQuery;
    }

    /**
     * Returns an Type instance. If $options['class'] is passed,
     * it will be returned an object of the given class, otherwise it will search
     * on the database which class to instantiate.
     *
     * @param int   $type_id This record's 'type_id'.
     * @param array $options Default array of options. Available keys: class, default_class.
     *
     * @return static Returns an Type or a child class in case it's defined on its 'class_type' property.
     */
    public static function getInstance($type_id, $options = [])
    {
        if (isset($options['class'])) {
            // Classe foi forçada
            $classType = $options['class'];
        } else {
            // Classe não foi forçada, verificar classMap
            $classType = TypeClassMap::getInstance()->getClass($type_id);
            // As in Record::getInstance(): a `class_type` PHP cannot declare is no binding.
            if (!$classType || !DynamicLoader::isDeclarable($classType)) {
                if (isset($options['default_namespace'])) {
                    $classType = $options['default_namespace'].'Type';
                } else {
                    $classType = self::$_defaultClass;
                }
            }
        }
        // Classe foi encontrada, instanciar o objeto
        $type = new $classType($type_id);
        if (!empty($options['db'])) {
            $type->setDb($options['db']);
        }
        return $type;
    }
    /*
    public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
        if (!isset($this->attributes['model_type_id'])) {
            $eagerload = array('name', 'language', 'parent_type_id', 'fields', 'model_type_id', 'table_name', 'class', 'class_type', 'template', 'children');
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
     * @return Type|RecordAbstract|null Null for a root type, which has no parent_type_id.
     */
    public function getParent($options = [])
    {
        if ($this->_parent) {
            return $this->_parent;
        }
        if ($this->parent_type_id) {
            $options['default_namespace'] = static::DEFAULT_NAMESPACE;

            return $this->_parent = self::getInstance($this->parent_type_id, $options);
        }
    }

    public function hasLoadedParent()
    {
        return $this->_parent !== null;
    }

    public function getAncestors()
    {
        $parents = [];
        $parent = $this;

        while (($parent = $parent->getParent()) && $parent->type_id) {
            array_unshift($parents, $parent);
        }

        return $parents;
    }

    /**
     * Sets the parent Type or Record object for this record, changing the $_parent property.
     *
     * @param RecordAbstract $parent
     */
    public function setParent(?RecordAbstract $parent = null)
    {
        $this->_parent = $parent;
    }
    /**
     * Retrieves the children of this Type.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return Collection Array of Type objects.
     *
     * @deprecated Actually its being used by TypeQuery to find any type
     */
    public function deprecatedGetChildren($options = [])
    {
        $this->_whereArrayFix($options['where']); // FIXME
        $cacheKey = __METHOD__.serialize($options);

        if (empty($options['order'])) {
            $options['order'] = 'position, name';
        }
        if (empty($options['where'])) {
            $options['where'] = ['1=1'];
        }
        if (empty($options['fields'])) {
            $options['fields'] = $this->getAttributesNames();
        } else {
            $options['fields'] = array_merge(['type_id'], (array) $options['fields']);
        }
        // Internal use
        $options['from'] = $this->getTableName().' AS main';
        $options['aliases'] = $this->getAttributesAliases();
        $options['field_definitions'] = $this->getAttributesFields();

        $rs = self::getCacheRepository()->remember($cacheKey, self::CACHE_TTL, function () use ($options) {
            return $this->_executeQuery($options);
        });

        $types = [];
        foreach ($rs as $row) {
            $type = self::getInstance($row->type_id, [
                'db' => $this->_db,
                'class' => isset($options['class']) ? $options['class'] : null,
                'default_namespace' => static::DEFAULT_NAMESPACE,
            ]);
            if ($this->type_id) {
                $type->setParent($this);
            }
            $this->_getAttributesFromRow($row, $type, $options);
            $types[] = $type;
        }
        // $rs->Close();
        return new Collection($types);
    }

    public function children()
    {
        $query = new Query\TypeQuery($this);
        return $query->where('parent_type_id', $this->type_id);
    }

    public function childrenByModel($model_type_id)
    {
        return $this->children()->where('model_type_id', $model_type_id);
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
        $this->_prepareInterAdminsOptions($options, $optionsInstance, true);

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
//                if ($relationshipData['type'] == 'select' && !$relationshipData['multi']) {
//                    // Any eager load level missing?
//                    if ($relationshipData['levels']) {
//                        $selects = [];
//                        $attribute = $relationshipData['name'];
//                        foreach ($records as $item) {
//                            if ($item->$attribute) {
//                                $selects[] = $item->$attribute;
//                            }
//                        }
//                        Relation::eagerLoad($selects, $relationshipData['levels']);
//                    }
//                } else {
                    Relation::eagerLoad($records, $relationshipData['levels']);
//                }
            }
        }

        // // $rs->Close();
        return $options['model']->newCollection($records);
    }

    public function deprecated_distinct($column, $options = [])
    {
        return $this->deprecated_aggregate('DISTINCT', $column, $options);
    }

    public function deprecated_max($column, $options = [])
    {
        $result = $this->deprecated_aggregate('MAX', $column, $options);

        return $result[0];
    }

    public function deprecated_min($column, $options = [])
    {
        $result = $this->deprecated_aggregate('MIN', $column, $options);

        return $result[0];
    }

    public function deprecated_sum($column, $options = [])
    {
        $result = $this->deprecated_aggregate('SUM', $column, $options);

        return $result[0];
    }

    public function deprecated_avg($column, $options = [])
    {
        $result = $this->deprecated_aggregate('AVG', $column, $options);

        return $result[0];
    }

    public function deprecated_aggregate($function, $column, $options)
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance, true);

        $options['fields'] = $function.'('.$column.') AS values';

        if (isset($options['group'])) {
            throw new Exception('This method cannot be used with GROUP BY.');
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
    public function deprecatedCount($options = [], $_typeless = false)
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
            // A GROUP BY on any other field would return the wrong count.
            throw new Exception('GROUP BY is not supported when using count().');
        }
        if ($_typeless) {
            $rows = $this->deprecatedTypelessFind(['limit' => 2, 'skip' => 0, 'with' => []] + $options);
        } else {
            $rows = $this->deprecatedFind(['limit' => 2, 'skip' => 0, 'with' => []] + $options);
        }
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
     * Retrieves the record with this id, scoped to this Type's type_id.
     *
     * Lives here rather than only on the legacy InterAdminTipo shim: tenant Type
     * subclasses extend this class directly, so without it every one of their
     * ~80 findById() call sites hits __call() and fatals with BadMethodCallException.
     * Same body the shim had, so behaviour is unchanged for callers that had it.
     *
     * @param int   $id      Search value.
     * @param array $options Available keys: fields, where, order, group, class.
     *
     * @return Record|null First Record object found.
     */
    public function findById($id, $options = [])
    {
        $options['where'][] = 'id = '.intval((string) $id);

        return $this->deprecatedFindFirst($options);
    }

    /**
     * Retrieves the first records which have this Type's type_id.
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
     * Returns the model identified by model_type_id, or the object itself if it has no model.
     *
     * @param array $options Default array of options.
     *
     * @return Type Model used by this Type.
     */
    public function getModel()
    {
        if ($this->model_type_id) {
            if (is_numeric($this->model_type_id)) {
                $model = Type::getInstance($this->model_type_id, ['default_namespace' => static::DEFAULT_NAMESPACE]);
            } else {
                $className = 'Jp7_Model_'.$this->model_type_id.'Tipo';
                $model = new $className();
            }

            return $model->getModel();
        } else {
            return $this;
        }
    }
    /**
     * Returns an array with data about the fields on this type, which is then cached under the type's `field_definitions` cache key.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->getCacheUnlessEmpty('field_definitions', function () {
            // The blob is POSITIONAL, so these names are this decoder's invention, not stored
            // data. They must stay identical to InterAdmin's Field::FIELDS_ATTRIBUTES, which
            // encodes the same 16 slots back.
            $fieldAttributeNames = [
                'type', 'name', 'help', 'size', 'required', 'separator', 'xtra',
                'list', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissions',
                'default', 'name_id',
            ];
            $fieldRows = explode('{;}', (string) $this->fields);
            $A = [];
            for ($i = 0; $i < count($fieldRows); $i++) {
                $parameters = explode('{,}', $fieldRows[$i]);
                if ($parameters[0]) {
                    $A[$parameters[0]]['order'] = ($i+1);
                    $isSelect = strpos($parameters[0], 'select_') === 0;
                    for ($j = 0; $j < count($parameters); $j++) {
                        $A[$parameters[0]][$fieldAttributeNames[$j]] = $parameters[$j];
                    }
                    if ($isSelect && $A[$parameters[0]]['name'] != 'all') {
                        $type_id = $A[$parameters[0]]['name'];
                        $A[$parameters[0]]['name'] = self::getInstance($type_id, [
                            'db' => $this->_db,
                            'default_namespace' => static::DEFAULT_NAMESPACE,
                        ]);
                    }
                }
            }
            // Alias
            foreach ($A as $column => $array) {
                if (empty($array['name_id'])) {
                    // Generate name_id
                    $alias = $array['name'];
                    if (is_object($alias)) {
                        $alias = empty($array['label']) ? $alias->name : $array['label'];
                    }
                    if (!$alias) {
                        throw new UnexpectedValueException('An alias was expected.');
                        //$alias = $column;
                    }
                    $A[$column]['name_id'] = to_slug($alias, '_');
                }
                if (strpos($column, 'select_') === 0) {
                    if (strpos($column, 'select_multi_') === 0) {
                        $A[$column]['name_id'] .= '_ids';
                    } else {
                        $A[$column]['name_id'] .= '_id';
                    }
                } elseif (strpos($column, 'special_') === 0 && $array['xtra']) {
                    if (in_array($array['xtra'], FieldUtil::getSpecialMultiXtras())) {
                        $A[$column]['name_id'] .= '_ids';
                    } else {
                        $A[$column]['name_id'] .= '_id';
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
    public function getFieldNames()
    {
        $fields = array_keys($this->getFields());
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
    public function getFieldAliases($fields = null)
    {
        if (!$this->_interadminAliases) {
            $this->_interadminAliases = $this->getCache('field_definitions_alias', function () {
                $aliases = [];
                foreach ($this->getFields() as $column => $array) {
                    if (strpos($column, 'tit_') === 0 || strpos($column, 'func_') === 0) {
                        continue;
                    }
                    $aliases[$column] = $array['name_id'];
                }
                return $aliases;
            });
        }

        if (is_null($fields)) {
            return $this->_interadminAliases;
        }

        return isset($this->_interadminAliases[$fields]) ? $this->_interadminAliases[$fields] : null;
    }

    public function getComboFieldNames()
    {
        return array_keys(array_filter($this->getFields(), function ($field) {
            return (bool) $field['combo'] || $field['type'] === 'varchar_key';
        }));
    }

    public function getRelationships()
    {
        if ($this->_interadminRelationships === null) {
            // getFieldType might be different for each class
            $cacheKey = static::class.','.$this->getCacheKey('relationships');
            $this->_interadminRelationships = self::getCacheRepository()->remember($cacheKey, self::CACHE_TTL, function () {
                $relationships = [];

                foreach ($this->getFields() as $column => $array) {
                    if (strpos($column, 'tit_') === 0 || strpos($column, 'func_') === 0) {
                        continue;
                    }
                    if (strpos($column, 'select_') === 0) {
                        $multi = strpos($column, 'select_multi_') === 0;
                        $hasType = in_array($array['xtra'], FieldUtil::getSelectTypeXtras());
                        if ($multi) {
                            $relation = substr($array['name_id'], 0, -4); // _ids = 4 chars
                        } else {
                            $relation = substr($array['name_id'], 0, -3); // _id = 3 chars
                        }
                        $relationships[$relation] = [
                            'query' => $hasType ? $array['name'] : $array['name']->records(),
                            'type' => $hasType,
                            'multi' => $multi,
                        ];
                    } elseif (strpos($column, 'special_') === 0 && $array['xtra']) {
                        $multi = in_array($array['xtra'], FieldUtil::getSpecialMultiXtras());
                        $hasType = in_array($array['xtra'], FieldUtil::getSpecialTypeXtras());
                        if ($multi) {
                            $relation = substr($array['name_id'], 0, -4); // _ids = 4 chars
                        } else {
                            $relation = substr($array['name_id'], 0, -3); // _id = 3 chars
                        }
                        if ($specialType = $this->getFieldType($array)) {
                            $query = $specialType->records();
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
        return $this->_interadminRelationships;
    }

    /**
     * Returns the Type for a field.
     *
     * @param object $field
     *
     * @return Type|null Null when the field stores no type, which callers test for.
     */
    public function getFieldType($field)
    {
        if (is_object($field['name'])) {
            return $field['name'];
        } elseif ($field['name'] == 'all') {
            return new self;
        }
    }

    public function getFieldTypeByAlias($alias)
    {
        $fieldDefinitions = $this->getFields();
        $aliases = array_flip($this->getFieldAliases());

        $columnName = $aliases[$alias] ? $aliases[$alias] : $alias;

        return $this->getFieldType($fieldDefinitions[$columnName]);
    }
    /**
     * Returns this object´s name.
     *
     * @return string
     */
    public function getStringValue(/*$simple = FALSE*/)
    {
        return $this->name;
    }
    /**
     * Returns the name according to the $lang.
     *
     * @return string
     */
    public function getName()
    {
        $suffix = Lang::get('interadmin.suffix');

        return $this->{'name'.$suffix} ?: $this->name;
    }

    /**
     * Saves this Type.
     */
    public function save()
    {
        $this->type_id_string = toId($this->name);
        $this->id_slug = to_slug($this->name);

        // log
        $this->log = date('d/m/Y H:i').' - '.Record::getLogUser().' - '.
            Request::ip().chr(13).$this->log;
        $this->date_modify = date('c');
        // Inheritance
        $this->syncInheritance();
        $result = $this->saveRaw();

        // Inheritance - Types inheriting from this Type
        if ($this->type_id) {
            $inheritingTypes = $this->deprecatedGetChildren([
                'where' => ["model_type_id = '".$this->type_id."'"],
                'class' => self::class,
            ]);
            foreach ($inheritingTypes as $type) {
                $type->syncInheritance();
                $type->saveRaw();
            }
        }

        return $result;
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
        // A type that inherits nothing has `inherited` NULL, not ''.
        foreach (array_filter(explode(',', (string) $this->inherited)) as $inherited_var) {
            $this->attributes[$inherited_var] = '';
        }
        $this->inherited = [];
        // Atualizando cache com dados do modelo
        if ($this->model_type_id) {
            if (is_numeric($this->model_type_id)) {
                $model = new self($this->model_type_id);
            } else {
                $className = 'Jp7_Model_'.$this->model_type_id.'Tipo';
                $model = new $className();
            }
            foreach (self::$inheritedFields as $field) {
                if (($model->$field && !$this->$field) || in_array($field, self::$privateFields)) {
                    $this->inherited[] = $field;
                    $this->$field = $model->$field;
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
        $this->deleted_at = date('c');
        $this->save();
    }

    public function restore()
    {
        $this->deleted_at = null;
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
        $this->_prepareInterAdminsOptions($options, $optionsInstance, true);
        unset($options['order']);
        return $this->_executeQuery($options, 'DELETE');
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
        $this->_prepareInterAdminsOptions($options, $optionsInstance, true);
        unset($options['order']);

        $valuesToSave = $this->_convertForDatabase(
            $attributes + ['date_modify' => date('c')],
            array_flip($options['aliases'])
        );
        return $this->_executeQuery($options, 'UPDATE', $valuesToSave);
    }

    public function getAttributesNames()
    {
        return $this->getColumns();
    }

    public function getAttributesFields()
    {
        return [];
    }
    public function getAttributesAliases()
    {
        return [];
    }
    public function getTableName()
    {
        return $this->getDb()->getTablePrefix().'types';
    }
    public function getInterAdminsOrder()
    {
        return $this->getCache('order', function () {
            $order = [];
            $fieldDefinitions = $this->getFields();
            if ($fieldDefinitions) {
                foreach ($fieldDefinitions as $key => $row) {
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
        return $this->_getTableLang().($this->table_name ?: 'records');
    }
    /**
     * Returns the table name for the files.
     *
     * @return string
     */
    public function getArquivosTableName()
    {
        return $this->_getTableLang().'files';
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
            return str_replace('_', '\\', $this->class_type);
        }
        return $this->class_type;
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
        $cache = self::getCacheRepository();
        $cache->forget($this->getCacheKey('__call'));
        $cache->forget($this->getCacheKey('attributes'));
        $cache->forget($this->getCacheKey('field_definitions'));
        $cache->forget($this->getCacheKey('field_definitions_alias'));
        $cache->forget($this->getCacheKey('children'));
        $cache->forget($this->getCacheKey('order'));
        $cache->forget($this->getCacheKey('typesUsingThisModel'));

        // different values for getType() depending on class
        $cache->forget(static::class.','.$this->getCacheKey('relationships'));
    }

    protected function getCache($varname, $callback)
    {
        $cacheKey = $this->getCacheKey($varname);
        return self::getCacheRepository()->remember($cacheKey, self::CACHE_TTL, $callback);
    }

    /**
     * getCache(), minus the part where an empty result gets persisted.
     *
     * Only for values whose emptiness always means the read failed, rather than that the
     * answer is nothing. getFields() is that: `fields` is parsed out of an attribute, so an
     * empty field map is what you get whenever the types row did not load -- and a type with
     * no field map cannot be selected from at all, every relation field throwing out of
     * _resolveFieldsAlias(). Keeping one buys an explode() on '' and costs a wedged type.
     *
     * Do NOT widen this to getCache() itself. An empty children list or an empty __call map is
     * an ordinary answer for an ordinary type, and re-deriving those hits the database.
     */
    protected function getCacheUnlessEmpty($varname, $callback)
    {
        $cacheKey = $this->getCacheKey($varname);
        $cache = self::getCacheRepository();

        if ($value = $cache->get($cacheKey)) {
            return $value;
        }
        $value = $callback();
        if ($value) {
            $cache->put($cacheKey, $value, self::CACHE_TTL);
        }
        return $value;
    }

    /**
     * No static memo here, and the reason is that a repository is not always interchangeable
     * with a later one for the same tag.
     *
     * Cache::tag() hands back a FileStore rooted at a per-tag directory, and one of those is as
     * good as the next -- the state lives on disk, so anyone's flush() is everyone's. But on a
     * store whose state lives in the OBJECT (the `array` store the test suites ask for) the
     * repository belongs to the application instance that built it, and Laravel builds a new
     * application between tests. A memo taken in one test then reads a store that nobody else
     * writes to: an explicit Cache::tag(self::CACHE_TAG)->flush() resolves the CURRENT store,
     * empties that, and leaves this one holding the values it cached before. Types then answer
     * from a cache no flush can reach, for the rest of the process.
     *
     * That is not hypothetical -- it is why DashboardRecentItemsTest saw a type's `icone` as the
     * empty string an earlier test had cached, after updating it and flushing. It stayed hidden
     * because the container exports CACHE_DRIVER=file, which beats phpunit.xml's <env>, so local
     * runs never used the store the config asks for. CI has no such variable and does.
     *
     * Cache::tag() already memoizes the FileStore case, so the cost of dropping this is nil
     * where it was actually buying anything.
     */
    protected static function getCacheRepository()
    {
        return Cache::tag(self::CACHE_TAG);
    }

    protected function getCacheKey($varname)
    {
        return $varname.','.$this->_db.','.$this->type_id;
    }

    /**
     * Check cache for types is not stale or changed outside the App.
     *
     * @return void;
     */
    public static function checkCache()
    {
        $cache = self::getCacheRepository();
        // don't query too often
        if ($cache->get('modified:check') > time() - 10) {
            return; // too soon
        }
        $cache->forever('modified:check', time());

        // check if types changed
        $modified = strtotime(DB::table('types')
            ->select(DB::raw('MAX(date_modify) AS modified'))
            ->value('modified'));
        if ($modified === $cache->get('modified')) {
            return; // not changed
        }
        // flush tagged cache
        $cache->flush();
        $cache->forever('modified', $modified);

        // check inheritance of types
        $unsyncedTypes = DB::table('types AS child')
            ->select('child.*')
            ->join('types AS model', function ($join) {
                $join->on('model.type_id', '=', 'child.model_type_id')
                    ->on(function ($q) {
                        $q->on('model.fields', '<>', 'child.fields')
                            ->orOn('model.children', '<>', 'child.children');
                    });
            })
            ->get();
        if (is_array($unsyncedTypes)) {
            $unsyncedTypes = collect($unsyncedTypes); // <= Laravel 5.2
        }
        \Log::notice('Resyncing '.count($unsyncedTypes).' types: '.$unsyncedTypes->implode('type_id', ','));
        foreach ($unsyncedTypes as $unsyncedType) {
            $type = new self($unsyncedType->type_id);
            $type->setRawAttributes(get_object_vars($unsyncedType));
            $type->syncInheritance();
            $type->saveRaw();
        }
    }

    /**
     * Returns metadata about the children types that the Records have.
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
                if (count($childrenArrParts) < 4) { // 4 = 'type_id', 'nome', 'ajuda', 'netos'
                    // Fix for types with an old, outdated structure
                    $childrenArrParts = array_pad($childrenArrParts, 4, '');
                }
                $child = array_combine(['type_id', 'nome', 'ajuda', 'netos'], $childrenArrParts);
                $name_id = Str::studly(to_slug($child['nome']));
                $children[$name_id] = $child;
            }
            return $children;
        });
    }

    /**
     * Returns a Type if the $name_id is found in getInterAdminsChildren().
     *
     * @param string $name_id Camel Case name, e.g.: DadosPessoais
     *
     * @return Type|null Null when $name_id is not among getInterAdminsChildren().
     */
    public function getInterAdminsChildrenType($name_id)
    {
        $childrenTypes = $this->getInterAdminsChildren();
        if (isset($childrenTypes[$name_id])) {
            $type_id = $childrenTypes[$name_id]['type_id'];

            return self::getInstance($type_id, [
                'db' => $this->_db,
                'default_namespace' => static::DEFAULT_NAMESPACE,
            ]);
        }
    }

    public function getInterAdminsChildrenData($type_id)
    {
        foreach ($this->getInterAdminsChildren() as $metadata) {
            if ($metadata['type_id'] == $type_id) {
                return $metadata;
            }
        }
    }

    public function getInterAdminsChildrenTypes()
    {
        $types = [];
        foreach ($this->getInterAdminsChildren() as $name_id => $metadata) {
            $types[] = $this->getInterAdminsChildrenType($name_id);
        }
        return $types;
    }

    public function getRelationshipData($relationship)
    {
        $relationships = $this->getRelationships();

        if (isset($relationships[$relationship])) {
            $data = $relationships[$relationship];
            return [
                'type' => 'select',
                'query' => $data['query'],
                'tipo' => is_object($data['query']) ? $data['query']->type() : $data['query'],
                'name' => $relationship,
                'alias' => true,
                'multi' => $data['multi'],
                'has_type' => $data['type'],
            ];
        }
        // As children
        $studlyCased = ucfirst($relationship);
        if ($childrenType = $this->getInterAdminsChildrenType($studlyCased)) {
            return [
                'type' => 'children',
                'tipo' => $childrenType,
                'name' => $relationship,
                'alias' => true,
                'multi' => true,
                'has_type' => false,
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
     * Creates a record with type_id, mostrar, date_insert and date_publish filled.
     *
     * @param array $attributes Attributes to be merged into the new record.
     *
     * @return Record
     */
    public function deprecated_createInterAdmin(array $attributes = [])
    {
        $options = ['default_namespace' => static::DEFAULT_NAMESPACE];
        $record = Record::getInstance(0, $options, $this);
        if ($mostrar = $this->getFieldAliases('char_key')) {
            $record->$mostrar = 'S';
        }
        $record->date_publish = date('c');
        $record->date_insert = date('c');
        $record->publish = 'S';
        $record->log = '';

        if ($this->_parent instanceof Record) {
            $record->setParent($this->_parent);
        }

        $record->fill($attributes);
        $record->exists = false;
        return $record;
    }

    /**
     * Returns all Type's using this Type as a model (model_type_id).
     *
     * @param array $options [optional]
     *
     * @return Type[] Array of Types indexed by their type_id.
     */
    public function getTypesUsingThisModel($options = [])
    {
        $typesUsingThisModel = $this->getCache('typesUsingThisModel', function () {
            $options2 = [
                'fields' => 'type_id',
                'from' => $this->getTableName().' AS main',
                'where' => [
                    "model_type_id = '".$this->type_id."'",
                ],
            ];
            $rs = $this->_executeQuery($options2);

            $options['default_namespace'] = static::DEFAULT_NAMESPACE;
            $typesUsingThisModel = [];
            foreach ($rs as $row) {
                $typesUsingThisModel[$row->type_id] = Type::getInstance($row->type_id, $options);
            }
            return $typesUsingThisModel;
        });
        $typesUsingThisModel[$this->type_id] = $this;
        return $typesUsingThisModel;
    }

    protected function _prepareInterAdminsOptions(&$options, &$optionsInstance, $filterType = false)
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

        if (count($options['fields']) != 1 || strpos($options['fields'][0] ?? '', 'COUNT(') === false) {
            $requiredFields = array_intersect(['id', 'type_id', 'id_slug'], $recordModel->getColumns());
            $options['fields'] = array_merge($requiredFields, (array) $options['fields']);
        }

        $options['from'] = $recordModel->getTableName().' AS main';
        $options['order'] = (isset($options['order']) ? $options['order'].', ' : '').$this->getInterAdminsOrder();

        // Internal use
        $options['aliases'] = $recordModel->getAttributesAliases();
        $options['field_definitions'] = $recordModel->getAttributesFields();
        $options['model'] = $recordModel;
        $options['eager_load'] = [];

        if (!$options['field_definitions']) {
            \Log::notice('Querying a type without field definitions - type_id: '.$this->type_id);
        }

        if (isset($options['with'])) {
            foreach ($options['with'] as $withRelationship) {
                // Isso aqui é mais uma validação
                // O código mesmo é rodado depois
                $levels = explode('.', $withRelationship);

                if ($relationshipData = $this->getRelationshipData($levels[0])) {
//                    if ($relationshipData['type'] === 'select') {
//                        // select.* - Esse carregamento é feito com join para aproveitar código existente
//                        // E também porque join é mais rápido para hasOne() do que um novo select
//                        if (!$relationshipData['multi']) {
//                            $options['fields'][$levels[0]] = ['*'];
//                            array_shift($levels);
//                        }
//                    }
                    $options['eager_load'][] = $relationshipData + [
                        'levels' => $levels,
                    ];
                } else {
                    throw new Exception('Unknown relationship: '.$levels[0]);
                }
            }
        }
        if ($filterType) {
            $options['where'][] = 'type_id = '.$this->type_id;
            if ($this->_parent instanceof Record) {
                // NULL to avoid finding children for invalid parents without ID
                $options['where'][] =  'parent_id = '.($this->_parent->id ?: 'NULL');
                if ($this->_parent->type_id) {
                    $options['where'][] = 'parent_type_id = '.$this->_parent->type_id;
                }
            }
        }
    }

    public function getInterAdminsAdminAttributes()
    {
        return ['id_slug', 'id_string', 'parent_id', 'parent_type_id', 'date_publish', 'date_insert', 'date_expire', 'date_modify', 'log', 'publish', 'deleted', 'hits'];
    }

    public function getFillable()
    {
        return $this->getAttributesNames();
    }

    /**
     * Returns all records having an Type that uses this as a model (model_type_id).
     *
     * @param array $options [optional]
     *
     * @return Record[]
     */
    public function modelRecords()
    {
        $types = $this->getTypesUsingThisModel();

        $query = new TypelessQuery($this);

        return $query->whereIn('type_id', $types);
    }

    public function deprecatedTypelessFind($options = [])
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);

        $rs = $this->_executeQuery($options);
        $records = [];
        $types = [];
        foreach ($rs as $row) {
            if (isset($row->type_id)) {
                if (empty($types[$row->type_id])) {
                    $types[$row->type_id] = Type::getInstance($row->type_id, ['default_namespace' => static::DEFAULT_NAMESPACE]);
                }
                $type = $types[$row->type_id];
            } else {
                $type = $this;
            }
            $record = Record::getInstance($row->id ?? null, $optionsInstance, $type);
            $this->_getAttributesFromRow($row, $record, $options);
            $records[] = $record;
        }

        return $options['model']->newCollection($records);
    }

    public function getTagFilters()
    {
        return [
            'type_id' => $this->type_id,
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

    /**
     * Parameters to be used with URL::route().
     *
     * @param array $variables
     *
     * @return array
     */
    public function getUrlParameters(array $variables)
    {
        $parameters = [];
        if ($this->getParent() instanceof Record) {
            array_unshift($parameters, $this->getParent());
        }
        return $parameters;
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

        return r::getRouteByTypeId($this->type_id, $action);
    }
}
