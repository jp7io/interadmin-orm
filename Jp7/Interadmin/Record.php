<?php

namespace Jp7\Interadmin;

use Jp7\CollectionUtil;
use Jp7\Interadmin\Relation\HasMany;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use BadMethodCallException;
use UnexpectedValueException;
use InvalidArgumentException;
use Exception;
use DB;
use Request;
use RecordUrl;

/**
 * Class which represents records on the table interadmin_{client name}.
 *
 * @method static Query where(string $column, string $operator = null, mixed $value = null)
 * @method static Query whereRaw(string $where)
 * @method static static build(array $attributes = [])
 * @method static static create(array $attributes = [])
 * @method static static find(int|string $id)
 * @method static static findOrFail(int|string $id)
 * @method Query\FileQuery arquivos
 */
class Record extends RecordAbstract implements Arrayable, Jsonable
{
    /**
     * Contains the Type, i.e. the record with an 'id_tipo' equal to this record´s 'id_tipo'.
     *
     * @var Type
     */
    protected $_tipo;
    /**
     * Contains the parent Record object, i.e. the record with an 'id' equal to this record's 'parent_id'.
     *
     * @var Record
     */
    protected $_parent;
    /**
     * Contains an array of objects (Record and Type).
     *
     * @var array
     */
    protected $_tags;

    /**
     * Cached aliases.
     *
     * @var array
     */
    protected $_aliases = [];

    protected $relations = [];

    /**
     * Username to be inserted in the log when saving this record.
     *
     * @var string
     */
    protected static $log_user = 'site';

    protected static $log_action = '';
    /**
     * If TRUE the records will be filtered using the method getPublishedFilters().
     *
     * @var bool
     */
    protected static $publish_filters_enabled = true;
    /**
     * Timestamp for testing filters with a different date.
     *
     * @var int
     */
    protected static $timestamp;
    /**
     * @var bool
     */
    protected static $lazy_loading_debug = false;

    protected static $lazy_loading_optimizer = true;

    protected static $_collections = [];

    protected $_collection_id;
    /**
     * Public Constructor.
     *
     * @param int $id This record's 'id'.
     */
    public function __construct(array $attributes = [], $type = null)
    {
        $this->attributes['id'] = 0; // initialize ID
        // id_tipo needs to be set first because of aliases
        if ($type) {
            $this->setType($type);
        } elseif (isset($attributes['id_tipo'])) {
            $this->setIdTipoAttribute($attributes['id_tipo']);
            unset($attributes['id_tipo']);
        } elseif ($type = static::type()) {
            $this->setType($type);
        }
        $this->setRawAttributes($attributes);
    }

    /**
     * Magic get acessor.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function &__get($name)
    {
        $value = null;
        // Attributes
        if (array_key_exists($name, $this->attributes)) {
            $value = &$this->attributes[$name];
            $value = $this->getMutatedAttribute($name, $value);
            return $value;
        }
        // Mutators
        $mutator = 'get'.Str::studly($name).'Attribute';
        if (method_exists($this, $mutator)) {
            $value = $this->$mutator($value);
            return $value;
        }
        // Aliases
        $aliases = $this->_aliases;
        $column = array_search($name, $aliases);
        // Fixes fields that have alias
        if ($column !== false && array_key_exists($column, $this->attributes)) {
            // column is present, alias requested
            $value = &$this->attributes[$column];
            $value = $this->getMutatedAttribute($column, $value);
            return $value;
        }
        // FIXME remove when old code is validated
        if (isset($aliases[$name]) && array_key_exists($aliases[$name], $this->attributes)) {
            throw new UnexpectedValueException('$this->attributes must not use alias: '.$aliases[$name]);
        }
        // Relations / Lazy Loading
        $value = $this->_lazyLoadAttribute($name);
        return $value; // returned as reference
    }
    /**
     * Magic set acessor.
     *
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value)
    {
        // Mutators
        $mutator = 'set' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $mutator)) {
            return $this->$mutator($value);
        }
        if ($this->_aliases) {
            $aliases = $this->_aliases;
            $column = array_search($name, $aliases);
            if ($column) {
                $name = $column;
            } elseif (!array_key_exists($name, $this->attributes) && array_key_exists($name, $this->getType()->getRelationships())) {
                // FIXME remove when old code is validated
                $column = $this->_aliasToColumn($name, array_flip($aliases));
                if ($column === $name) {
                    $data = $this->getType()->getRelationships()[$name];
                    throw new Exception($name.' is a relation, use '.$name.($data['multi'] ? '_ids' : '_id')); // laravel code
                }
                $name = $column;
            }
        }
        $this->attributes[$name] = $value;
    }

    public function setAttributesAttribute($value)
    {
        throw new Exception("attributes is protected"); // FIXME remove when old code is validated
    }

    public function getAttributesAttribute()
    {
        throw new Exception("attributes is protected"); // FIXME remove when old code is validated
    }

    public function setIdAttribute($value)
    {
        $this->attributes['id'] = $value;
    }

    public function setIdTipoAttribute($value)
    {
        $this->attributes['id_tipo'] = $value;
        if (!$this->_tipo) {
            $this->getType(); // Set Type and Aliases
        }
    }

    /**
     * Magic isset acessor.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        // Attributes or Mutators
        if (array_key_exists($name, $this->attributes) || method_exists($this, 'get' . Str::studly($name) . 'Attribute')) {
            return true;
        }
        // Aliases
        $column = array_search($name, $this->_aliases);
        if ($column !== false && array_key_exists($column, $this->attributes)) {
            return true;
        }
        // Relations / Lazy Loading
        if ($this->_lazyLoadAttribute($name)) {
            return true;
        }
        return false;
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->attributes[$name], $this->relations[$name]);
    }

    private function _lazyLoadAttribute($name)
    {
        // relationships -> select and select_multi
        $relationships = $this->getType()->getRelationships();
        if (isset($relationships[$name])) {
            $related = $this->_loadRelationship($relationships, $name);
            return $related; // returned as reference
        }
        if (!$this->attributes['id']) {
            return; // data below depends on an ID
        }
        // children most likely
        if ($query = $this->_getManyRelationship($name)) {
            if ($query instanceof Query && $this->_collection_id && array_key_exists($this->_collection_id, self::$_collections)) {
                // Lazy loading optimizer
                CollectionUtil::eagerLoad(self::$_collections[$this->_collection_id], $name);
                $manyRecords = $this->relations[$name];
            } else {
                $manyRecords = $query->get(); // EagerLoadedQuery or Query
                if (!$query instanceof EagerLoadedQuery) { // if EagerLoadedQuery, don't overwrite
                    $this->relations[$name] = $manyRecords;
                }
            }
            return $manyRecords;
        }

        // Lazy loading
        $columns = $this->getColumns();
        $aliases = $this->_aliases;
        if (!in_array($name, $columns) && !in_array($name, $aliases)) {
            return;
        }
        if (getenv('APP_DEBUG')) {
            $this->_debugLazyLoading('N+1 query: Loading attribute', $name, debug_backtrace(false, 2)[1]);
        }
        // not all columns are loaded by default / most types use same table
        $attributes = array_keys($aliases);
        // Fixes lazy loading of fields that are aliases
        if ($column = array_search($name, $aliases)) {
            $name = $column;
        }
        if (!in_array($name, $attributes)) {
            $attributes[] = $name;
        }
        $this->loadAttributes($attributes);
        $this->attributes[$name] = $this->getMutatedAttribute($name, $this->attributes[$name]);
        return $this->attributes[$name];
    }

    private function _debugLazyLoading($msg, $name, $caller)
    {
        $msg = $msg.' "'.$name.'".'.PHP_EOL.
            '- Class: '.get_class($this).PHP_EOL.
            '- ID: '.$this->id.PHP_EOL.
            '- File: '.($caller['file'] ?? '').' - Line: '.($caller['line'] ?? '');
        if (self::$lazy_loading_debug){
            throw new \Exception($msg);
        } else {
            \Log::notice($msg);
        }
    }

    protected function _loadRelationship($relationships, $name)
    {
        $data = $relationships[$name];
        // select_multi
        if ($data['multi']) {
            $fks = $this->{$name.'_ids'};
            if (!$fks) {
                return jp7_collect([]);
            }
            $loaded = &$this->relations[$name];
            if (!$loaded) {
                $loaded = (object) ['fks' => null];
            }
            if ($loaded->fks != $fks) {
                // stale data or not loaded
                $loaded->fks = $fks;
                $fksArray = is_array($fks) ? $fks : array_filter(explode(',', $fks));
                if ($data['type']) {
                    $multi = jp7_collect([]);
                    foreach ($fksArray as $fk) {
                        $multi[] = Type::getInstance($fk, ['default_namespace' => static::DEFAULT_NAMESPACE]);
                    }
                    $loaded->values = $multi;
                } elseif ($this->_collection_id && array_key_exists($this->_collection_id, self::$_collections)) {
                    // Optimize lazy loading
                    unset($this->relations[$name]);
                    CollectionUtil::eagerLoad(self::$_collections[$this->_collection_id], $name);
                    $loaded = &$this->relations[$name];
                } else {
                    if (getenv('APP_DEBUG') && self::$lazy_loading_debug) {
                        $this->_debugLazyLoading('N+1 query: Loading relation', $name, debug_backtrace(false, 2)[1]);
                    }
                    $query = clone $data['query'];
                    $loaded->values = $query->findMany($fksArray);
                }
            }
            return $loaded->values;
        }
        // select
        $fk = $this->{$name.'_id'};
        if (!$fk) {
            return null;
        }
        $keyExists = array_key_exists($name, $this->relations);
        $loaded = &$this->relations[$name];
        if (!$keyExists || ($keyExists && $loaded && $loaded->id != $fk)) {
            /// stale data or not loaded
            if ($data['type']) {
                $loaded = Type::getInstance($fk, ['default_namespace' => static::DEFAULT_NAMESPACE]);
            } elseif ($this->_collection_id && array_key_exists($this->_collection_id, self::$_collections)) {
                // Optimize lazy loading
                unset($this->relations[$name]);
                CollectionUtil::eagerLoad(self::$_collections[$this->_collection_id], $name);
                $loaded = &$this->relations[$name];
            } else {
                if (getenv('APP_DEBUG') && self::$lazy_loading_debug) {
                    $this->_debugLazyLoading('N+1 query: Loading relation', $name, debug_backtrace(false, 2)[1]);
                }
                $query = clone $data['query'];
                $loaded = $query->find($fk);
            }
        }
        return $loaded;
    }

    public function relationFromColumn($column)
    {
        $alias = $this->_aliases[$column];
        if (starts_with($column, 'select_multi_')) {
            $relation = substr($alias, 0, -4); // _ids = 4 chars
        } elseif (starts_with($column, 'select_')) {
            $relation = substr($alias, 0, -3); // _id = 3 chars
        } else {
            throw new InvalidArgumentException('$column must start with select_ or select_multi_.');
        }
        return $this->$relation;
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
        if ($type = static::type()) {
            return new Query($type);
        }
    }
    // called by EloquentUserProvider
    public function newQuery()
    {
        return static::query();
    }

    // called by FactoryBuilder
    public function newCollection(array $models = [])
    {
        $collection = new Collection($models);
        if (self::$lazy_loading_optimizer && count($models) > 2) {
            $collection_id = microtime();
            self::$_collections[$collection_id] = $collection;
            foreach ($models as $record) {
                $record->_collection_id = $collection_id;
            }
            if (count(self::$_collections) > 50) {
                // don't increase memory too much
                array_splice(self::$_collections, 0, 10);
            }
        }
        return $collection;
    }

    /**
     * @return Type
     */
    public static function type()
    {
        if ($id_tipo = RecordClassMap::getInstance()->getClassIdTipo(get_called_class())) {
            return Type::getInstance($id_tipo, ['default_namespace' => static::DEFAULT_NAMESPACE]);
        }
    }

    public function hasMany($className, $foreign_key, $local_key = 'id')
    {
        return new HasMany($this, $className, $foreign_key, $local_key);
    }

    /**
     * Returns an Record instance. If $options['class'] is passed,
     * it will be returned an object of the given class, otherwise it will search
     * on the database which class to instantiate.
     *
     * @param int   $id      This record's 'id'.
     * @param array $options Default array of options. Available keys: fields, fields_alias, class, default_class.
     * @param Type Set the record´s Tipo.
     *
     * @return Record Returns an Record or a child class in case it's defined on the 'class' property of its Type.
     */
    public static function getInstance($id, $options, Type $tipo)
    {
        // Classe foi forçada
        if (isset($options['class'])) {
            $className = $options['class'];
        } else {
            $className = RecordClassMap::getInstance()->getClass($tipo->id_tipo);
            if (!$className) {
                $className = (isset($options['default_namespace']) ? $options['default_namespace'] : static::DEFAULT_NAMESPACE).'Record';
            }
        }

        $instance = new $className(['id' => $id], $tipo);
        $instance->setDb($tipo->getDbName());

        return $instance;
    }
    /**
     * Finds a Child Tipo by a camelcase keyword.
     *
     * @param string $nome_id CamelCase
     *
     * @return array
     */
    protected function _findChild($nome_id)
    {
        $children = $this->getType()->getInterAdminsChildren();

        if (isset($children[$nome_id])) {
            return $children[$nome_id];
        }
    }

    public function getChildrenTipoByNome($nome_id)
    {
        $child = $this->_findChild($nome_id);
        if ($child) {
            return $this->getChildrenTipo($child['id_tipo']);
        }
    }

    /**
     * Magic method calls.
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
     *
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        if ($query = $this->_getManyRelationship($methodName)) {
            return $query;
        }
        $relationships = $this->getType()->getRelationships();
        if (isset($relationships[$methodName])) {
            return $relationships[$methodName]['query'];
        }
        // Default error when method doesn´t exist
        $message = 'Call to undefined method '.get_class($this).'->'.$methodName.'(). Available magic methods: '."\n";
        $children = $this->getType()->getInterAdminsChildren();

        foreach (array_keys($children) as $childName) {
            $message .= "\t\t- ".lcfirst($childName)."()\n";
        }
        if ($this->hasArquivosTab()) {
            $message .= "\t\t- arquivos()\n";
        }

        throw new BadMethodCallException($message);
    }

    /**
     * @param $name
     * @return EagerLoadedQuery|Query|Query\FileQuery
     */
    protected function _getManyRelationship($name)
    {
        // childName() - relacionamento
        if ($child = $this->_findChild(ucfirst($name))) {
            $childrenTipo = $this->getChildrenTipo($child['id_tipo']);
            if (array_key_exists($name, $this->relations)) {
                return new EagerLoadedQuery($childrenTipo, $this->relations[$name]);
            }
            return new Query($childrenTipo);
        } elseif ($name === 'arquivos' && $this->hasArquivosTab()) {
            return new Query\FileQuery($this);
        }
    }

    /**
     * Gets the Type object for this record, which is then cached on the $_tipo property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return Type
     */
    public function getType($options = [])
    {
        if ($this->_tipo) {
            return $this->_tipo;
        }
        // Instance was not brought from DB, id_tipo is empty
        if (empty($this->attributes['id_tipo'])) {
            // Record::type() -> Classes that have name
            $tipo = static::type();
            if (!$tipo) {
                throw new UnexpectedValueException('Could not find id_tipo for record. Class: '.get_class($this).' - ID: ' . $this->id);
            }
        } else {
            $tipo = Type::getInstance($this->attributes['id_tipo'], [
                'db' => $this->_db,
                'class' => empty($options['class']) ? null : $options['class'],
            ]);
        }
        $this->setType($tipo);
        return $this->_tipo;
    }

    /**
     * Sets the Type object for this record, changing the $_tipo property.
     *
     * @param Type $tipo
     */
    public function setType(Type $tipo = null)
    {
        $this->attributes['id_tipo'] = $tipo->id_tipo;
        $this->_tipo = $tipo;
        $this->_aliases = $tipo ? $this->getAttributesAliases() : [];
    }
    /**
     * Gets the parent Record object for this record, which is then cached on the $_parent property.
     *
     * @param array $options Default array of options. Available keys: fields, fields_alias, class.
     *
     * @return Record
     */
    public function getParent($options = [])
    {
        if (!$this->_parent) {
            $this->loadAttributes(['parent_id', 'parent_id_tipo'], false);

            if ($this->parent_id) {
                if (!$this->parent_id_tipo) {
                    throw new Exception('Field parent_id_tipo is required. Id: '.$this->id);
                }
                $this->_parent = Type::getInstance($this->parent_id_tipo)
                    ->records()
                    ->find($this->parent_id);
                if ($this->_parent) {
                    if (!$this->getType()->hasLoadedParent() || !$this->_tipo->getParent() instanceof Record) {
                        // Dangerous side effect changing Type's parent record, clone to mitigate side effect
                        $this->_tipo = clone $this->_tipo;
                    }
                    $this->_tipo->setParent($this->_parent);
                }
            }
        }

        return $this->_parent;
    }
    /**
     * Sets the parent Record object for this record, changing the $_parent property.
     *
     * @param Record $parent
     */
    public function setParent(Record $parent = null)
    {
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
     * Instantiates an Type object and sets this record as its parent.
     *
     * @param int   $id_tipo
     * @param array $options Default array of options. Available keys: class.
     *
     * @return Type
     */
    public function getChildrenTipo($id_tipo, $options = [])
    {
        $options['default_namespace'] = static::DEFAULT_NAMESPACE;
        $options['db'] = $this->_db;
        $childrenTipo = Type::getInstance($id_tipo, $options);
        $childrenTipo->setParent($this);

        return $childrenTipo;
    }

    /**
     * @return Type[]
     */
    public function getChildrenTipos()
    {
        $childrenTypes = $this->getType()->getInterAdminsChildrenTipos();
        foreach ($childrenTypes as $childType) {
            $childType->setParent($this);
        }
        return $childrenTypes;
    }

    public function hasChildrenTipo($id_tipo)
    {
        foreach ($this->getType()->getInterAdminsChildren() as $childrenArr) {
            if ($childrenArr['id_tipo'] == $id_tipo) {
                return true;
            }
        }

        return false;
    }

    public function hasArquivosTab()
    {
        return $this->getType()->arquivos || $this->getType()->arquivos_2;
    }

    /**
     * Returns siblings records.
     *
     * @return Query
     */
    public function siblings()
    {
        return $this->getType()->records()->where('id', '<>', $this->id);
    }

    /**
     * Creates a new FileRecord with id_tipo, id and mostrar set.
     *
     * @param array $attributes [optional]
     *
     * @return FileRecord
     */
    public function deprecated_createArquivo(array $attributes = [])
    {
        $className = static::DEFAULT_NAMESPACE.'FileRecord';
        if (!class_exists($className)) {
            $className = 'Jp7\\Interadmin\\FileRecord';
        }
        $arquivo = new $className();
        $arquivo->setParent($this);
        $arquivo->setType($this->getType());
        $arquivo->mostrar = 'S';

        return $arquivo->fill($attributes);
    }
    /**
     * Retrieves the uploaded files of this record.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, limit.
     *
     * @return array Array of FileRecord objects.
     *
     * @deprecated
     */
    public function deprecated_getArquivos($options = [])
    {
        $arquivos = [];
        if (isset($options['class'])) {
            $className = $options['class'];
        } else {
            $className = static::DEFAULT_NAMESPACE.'FileRecord';
        }
        if (!class_exists($className)) {
            $className = 'Jp7\\Interadmin\\FileRecord';
        }
        $arquivoModel = new $className(0);
        $arquivoModel->setType($this->getType());

        if (empty($options['fields'])) {
            $options['fields'] = '*';
        }

        $this->_resolveWildcard($options['fields'], $arquivoModel);
        $this->_whereArrayFix($options['where']); // FIXME

        $options['fields'] = array_merge(['id_arquivo'], (array) $options['fields']);
        $options['from'] = $arquivoModel->getTableName().' AS main';
        $options['where'][] = 'id_tipo = '.intval($this->id_tipo);
        $options['where'][] = 'id = '.intval($this->id);
        $options['order'] = (isset($options['order']) ? $options['order'].',' : '').' ordem';
        // Internal use
        $options['aliases'] = $arquivoModel->getAttributesAliases();
        $options['campos'] = $arquivoModel->getAttributesCampos();

        $rs = $this->_executeQuery($options);

        $records = [];
        foreach ($rs as $row) {
            $arquivo = new $className($row->id_arquivo, [
                'db' => $this->_db,
            ]);
            $arquivo->setType($this->getType());
            $arquivo->setParent($this);
            $this->_getAttributesFromRow($row, $arquivo, $options);
            $arquivos[] = $arquivo;
        }

        return new Collection($arquivos);
    }

    /**
     * Deletes all the FileRecord records related with this record.
     *
     * @param array $options [optional]
     *
     * @return int Number of deleted arquivos.
     */
    public function deprecated_deleteArquivos($options = [])
    {
        $arquivos = $this->deprecated_getArquivos($options);
        foreach ($arquivos as $arquivo) {
            $arquivo->delete();
        }

        return count($arquivos);
    }

    public function deprecated_createLog(array $attributes = [])
    {
        $log = Type::create($attributes);
        $log->setParent($this);
        $log->setType($this->getType());

        return $log;
    }

    /**
     * Sets the tags for this record. It DELETES the previous records.
     *
     * @param array $tags Array of object to be saved as tags.
     */
    public function deprecated_setTags(array $tags)
    {
        $db = $this->getDb();
        $sql = 'DELETE FROM '.$db->getTablePrefix().'tags WHERE parent_id = '.$this->id;
        foreach ($tags as $tag) {
            $sql = 'INSERT INTO '.$db->getTablePrefix().'tags (parent_id, id, id_tipo) VALUES
                ('.$this->id.','.
                ($tag instanceof self ? $tag->id : 0).','.
                ($tag instanceof self ? $tag->id_tipo : $tag->id_tipo).')';
            if (!$db->insert($sql)) {
                throw new Jp7_Interadmin_Exception($db->ErrorMsg());
            }
        }
    }
    /**
     * Returns the tags.
     *
     * @param array $options Available keys: where, group, limit.
     *
     * @return array
     */
    public function deprecated_getTags($options = [])
    {
        $retorno = [];
        if (!$this->_tags || $options) {
            $db = $this->getDb();
            $options['where'][] = 'parent_id = '.$this->id;
            $sql = 'SELECT * FROM '.$db->getTablePrefix().'tags '.
                'WHERE '.implode(' AND ', $options['where']).
                (!empty($options['group']) ? ' GROUP BY '.$options['group'] : '').
                (!empty($options['limit']) ? ' LIMIT '.$options['limit'] : '');
            $rs = $db->select($sql);
            $this->_tags = [];
            foreach ($rs as $row) {
                if ($tag_tipo = Type::getInstance($row->id_tipo)) {
                    $tag_text = $tag_tipo->nome;
                    if ($row->id) {
                        $options = [
                            'fields' => ['varchar_key'],
                            'where' => ['id = '.$row->id],
                        ];
                        if ($tag_registro = $tag_tipo->deprecatedFindFirst($options)) {
                            $tag_text = $tag_registro->varchar_key.' ('.$tag_tipo->nome.')';
                            $tag_registro->interadmin = $this;
                            $retorno[] = $tag_registro;
                        }
                    } else {
                        $tag_tipo->interadmin = $this;
                        $retorno[] = $tag_tipo;
                    }
                }
            }
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
    public function isPublished()
    {
        return $this->char_key &&
            !$this->deleted &&
            ($this->parent_id || $this->publish || !config('interadmin.preview')) &&
            $this->date_publish->getTimestamp() <= Record::getTimestamp() &&
            ($this->date_expire->getTimestamp() >= Record::getTimestamp() || $this->date_expire->format('Y') < 1);
    }

    /**
     * Saves this record and updates date_modify.
     */
    public function save()
    {
        if (empty($this->attributes['id_tipo'])) {
            throw new Exception('Saving a record without id_tipo.');
        }
        if (empty($this->attributes['id_slug']) && in_array('id_slug', $this->getColumns())) {
            $this->id_slug = $this->generateSlug();
        }

        // log
        $this->log = date('d/m/Y H:i').' - '.
            self::$log_user.' - '.
            (self::$log_action ? self::$log_action.' - ' : '').
            Request::ip().
            chr(13).
            $this->log;

        // date_modify
        $this->date_modify = date('c');

        return $this->saveRaw();
    }

    public function generateSlug()
    {
        if (!array_key_exists('varchar_key', $this->_aliases) || !$this->varchar_key) {
            return '';
        }

        $id_slug = to_slug($this->varchar_key);
        if (is_numeric($id_slug)) {
            $id_slug = '--'.$id_slug;
        }

        if ($this->siblings()->where('id_slug', $id_slug)->exists()) {
            // Add 1 if it already exists
            // REGEXP used to avoid multiple queries
            $max = $this->siblings()
                ->where('id_slug', 'REGEXP', '^'.$id_slug.'[0-9]*$')
                ->orderByRaw('LENGTH(id_slug) DESC, id_slug DESC')
                ->value('id_slug');

            $max = replace_prefix($id_slug, '', $max) ?: 1;
            $id_slug .= $max + 1;
        }

        return $id_slug;
    }

    public function getAttributesNames()
    {
        return $this->getType()->getCamposNames();
    }
    public function getAttributesCampos()
    {
        return $this->getType()->getCampos();
    }
    final public function getCampoTipo($campo)
    {
        return $this->getType()->getCampoTipo($campo);
    }
    public function getAttributesAliases()
    {
        return $this->getType()->getCamposAlias();
    }
    public function getTableName()
    {
        return $this->getType()->getInterAdminsTableName();
    }
    public function getAliasedAttributes()
    {
        $return = [];
        $aliases = $this->_aliases;
        foreach ($this->attributes as $column => $value) {
            $alias = isset($aliases[$column]) ? $aliases[$column] : $column;
            $return[$alias] = $this->getMutatedAttribute($column, $value);
        }
        return $return;
    }

    /**
     * Returns $log_user. If $log_user is NULL, returns 'site'.
     *
     * @see Record::$log_user
     *
     * @return string
     */
    public static function getLogUser()
    {
        return self::$log_user;
    }

    /**
     * Sets $log_user and returns the old value.
     *
     * @see     Record::$log_user
     *
     * @param string $log_user
     *
     * @return string Old value.
     */
    public static function setLogUser($log_user)
    {
        $old_user = self::$log_user;
        self::$log_user = $log_user;

        return $old_user;
    }

    public static function getLogAction()
    {
        return self::$log_action;
    }

    public static function setLogAction($log_action)
    {
        $old_action = self::$log_action;
        self::$log_action = $log_action;

        return $old_action;
    }

    /**
     * Enables or disables published filters.
     *
     * @param bool $bool
     *
     * @return bool Returns the previous value.
     */
    public static function setPublishedFiltersEnabled($bool)
    {
        $oldValue = self::$publish_filters_enabled;
        self::$publish_filters_enabled = (bool) $bool;

        return $oldValue;
    }
    /**
     * Returns TRUE if published filters are enabled.
     *
     * @return bool $bool
     */
    public static function isPublishedFiltersEnabled()
    {
        return self::$publish_filters_enabled;
    }
    public static function debugLazyLoading(bool $bool = true) {
        self::$lazy_loading_debug = $bool;
    }

    public static function setLazyLoadingOptimizerEnabled(bool $bool) {
        $oldValue = self::$lazy_loading_optimizer;
        self::$lazy_loading_optimizer = $bool;

        return $oldValue;
    }

    public static function getTimestamp()
    {
        return isset(self::$timestamp) ? self::$timestamp : time();
    }
    public static function setTimestamp($time)
    {
        self::$timestamp = $time;
    }
    /**
     * Merges two option arrays.
     *
     * Values of 'where' will be merged
     * Values of 'fields' will be merged
     * Other values (such as 'limit') can be overwritten by the $extended array of options.
     *
     * @param array $initial  Initial array of options.
     * @param array $extended Array of options that will extend the initial array.
     *
     * @return array Array of $options properly merged.
     */
    public static function mergeOptions($initial, $extended)
    {
        if (!$extended) {
            return $initial;
        }
        if (isset($initial['fields']) && isset($extended['fields'])) {
            $extended['fields'] = array_merge($extended['fields'], $initial['fields']);
        }
        if (isset($initial['where']) && isset($extended['where'])) {
            if (!is_array($extended['where'])) {
                $extended['where'] = [$extended['where']];
            }
            $extended['where'] = array_merge($extended['where'], $initial['where']);
        }

        return $extended + $initial;
    }

    public function getTagFilters()
    {
        return [
            'id' => $this->id,
            'id_tipo' => intval($this->getType()->id_tipo),
        ];
    }

    /**
     * @see RecordAbstract::getAdminAttributes()
     */
    public function getAdminAttributes()
    {
        return $this->getType()->getInterAdminsAdminAttributes();
    }

    /**
     * Searches $value on the relationship and sets the $attribute.
     *
     * @param string $attribute
     * @param string $searchValue
     * @param string $searchColumn
     *
     * @throws Exception
     */
    public function setAttributeBySearch($attribute, $searchValue, $searchColumn = 'varchar_key')
    {
        $campos = $this->getType()->getCampos();
        $aliases = array_flip($this->_aliases);
        $nomeCampo = $aliases[$attribute] ? $aliases[$attribute] : $attribute;

        if (!starts_with($nomeCampo, 'select_')) {
            throw new Exception('The field '.$attribute.' is not a select. It was expected a select field on setAttributeBySearch.');
        }

        $campoTipo = $this->getCampoTipo($campos[$nomeCampo]);
        $record = $campoTipo->deprecatedFindFirst([
            'where' => [$searchColumn." = '".$searchValue."'"],
        ]);
        if (starts_with($nomeCampo, 'select_multi_')) {
            $this->$attribute = [$record];
        } else {
            $this->$attribute = $record;
        }
    }

    /**
     * Set the specific relationship in the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    public function hasLoadedRelation($relation)
    {
        return array_key_exists($relation, $this->relations);
    }

    /**
     * Returns varchar_key using its alias, without loading it from DB again.
     */
    public function getName()
    {
        return $this->varchar_key;
    }

    /**
     * Returns this object´s varchar_key and all the fields marked as 'combo', if the field
     * is an InterAdmin such as a select_key, its getStringValue() method is used.
     *
     * @return string For the city 'Curitiba' with the field 'state' marked as 'combo' it would return: 'Curitiba - Paraná'.
     */
    public function getStringValue()
    {
        $camposCombo = $this->getType()->getCamposCombo();
        if (!$camposCombo) {
            return $this->id;
        }
        $stringValue = [];
        foreach ($camposCombo as $campoCombo) {
            if (starts_with($campoCombo, 'file_')) {
                continue;
            }
            if (starts_with($campoCombo, 'select_')) {
                if ($relation = $this->relationFromColumn($campoCombo)) {
                    $stringValue[] = $relation->getName();
                }
                continue;
            }
            $stringValue[] = $this->$campoCombo;
        }

        return implode(' - ', array_filter($stringValue));
    }

    public function toArray()
    {
        $array = $this->getAliasedAttributes();
        foreach ($array as &$value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }
        }

        return $array;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function __debugInfo()
    {
        return array_merge((array) $this, $this->getAliasedAttributes());
    }

    public function getRules()
    {
        $rules = [];
        foreach ($this->getType()->getCampos() as $campo) {
            if ($campo['form']) {
                $alias = $campo['nome_id'];

                if ($campo['obrigatorio']) {
                    $rules[$alias][] = 'required';
                }
                if ($campo['xtra'] === 'email' || $campo['xtra'] === 'id_email') {
                    $rules[$alias][] = 'email';
                }
                if (starts_with($campo['tipo'], 'int_')) {
                    $rules[$alias][] = 'integer';
                }
                if (starts_with($campo['tipo'], 'date_')) {
                    $rules[$alias][] = 'date_format:Y-m-d';
                }
            }
        }

        return $rules;
    }

    public function getUniqueRule($column, array $whereHash = [])
    {
        $aliases = $this->_aliases;
        $params = [
            // unique:table
            str_replace($this->getDb()->getTablePrefix(), '', $this->getTableName()),
            // column
            array_search($column, $aliases) ?: $column,
            // except
            $this->id,
            // idColumn
            'id',
            // WHERE:
            'id_tipo', $this->id_tipo,
        ];
        foreach ($whereHash as $column => $value) {
            $params[] = $column;
            $params[] = $value;
        }
        return 'unique:'.implode(',', $params);
    }

    public function getFillable()
    {
        $fillable = [];

        foreach ($this->getType()->getCampos() as $campo) {
            if ($campo['form']) {
                $fillable[] = $campo['nome_id'];
            }
        }

        return $fillable;
    }

    public function getRoute($action = 'index')
    {
        return $this->getType()->getRoute($action);
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
        $parent = $this;
        foreach ($variables as $variable) {
            if (!$parent = $parent->getParent()) {
                break;
            }
            $parameters[] = $parent;
        }

        return array_reverse($parameters);
    }

    public function getUrl($action = 'show')
    {
        $args = func_get_args();
        array_unshift($args, $this);
        return call_user_func_array([RecordUrl::class, 'getRecordUrl'], $args);
    }
}
