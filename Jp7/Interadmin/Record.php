<?php

namespace Jp7\Interadmin;

use Jp7\Interadmin\Relation\HasMany;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use BadMethodCallException;
use Exception;
use DB;
use Request;

/**
 * Class which represents records on the table interadmin_{client name}.
 */
class Record extends RecordAbstract implements Arrayable
{
    use \Jp7\Laravel\Url\RecordTrait;
     
    /**
     * To be used temporarily with deprecated methods.
     */
    const DEPRECATED_METHOD = '54dac5afe1fcac2f65c059fc97b44a58';

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

    protected $_loadedRelationships;
    protected $_eagerLoad;

    /**
     * Username to be inserted in the log when saving this record.
     *
     * @var string
     */
    protected static $log_user = null;
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
     * Public Constructor.
     *
     * @param int $id This record's 'id'.
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes + ['id' => 0];
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
        if (array_key_exists($name, $this->attributes)) {
            $value = &$this->attributes[$name];
        } else {
            // returned as reference
            $value = $this->_lazyLoadAttribute($name);
        }
        
        // Mutators
        if ($name !== 'id') {
            $mutator = 'get' . Str::studly($name) . 'Attribute';
            if (method_exists($this, $mutator)) {
                $value = $this->$mutator($value);
            }
        }
        
        return $value;
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
        if (array_key_exists($name, $this->attributes) || $this->_lazyLoadAttribute($name)) {
            return true;
        }
        
        return method_exists($this, 'get' . Str::studly($name) . 'Attribute');
    }
    
    private function _lazyLoadAttribute($name)
    {
        if ($this->id) {
            // relationship
            $relationships = $this->getType()->getRelationships();
            if (isset($relationships[$name])) {
                $related = $this->_loadRelationship($relationships, $name);

                return $related; // returned as reference
            }

            // Lazy loading
            $columns = $this->getColumns();
            if (in_array($name, $columns) || in_array($name, $this->getAttributesAliases())) {
                if (class_exists('Debugbar')) {
                    $caller = debug_backtrace(false, 2)[1];
                    
                    \Debugbar::warning('N+1 query: Attribute "'.$name.'" was not loaded for '
                        .get_class($this)
                        .' - ID: '.$this->id
                        .' - File: '. $caller['file'] . ' - Line: ' . $caller['line']);
                }

                $attributes = array_intersect($columns, array_merge(
                    $this->getAttributesNames(),
                    $this->getAdminAttributes()
                ));

                $this->loadAttributes($attributes);

                return $this->attributes[$name];
            }
        }
    }
        
    private function _loadRelationship($relationships, $name)
    {
        $data = $relationships[$name];

        if ($data['special']) {
            throw new Exception('Not implemented: '.$name);
        }

        if ($data['multi']) {
            if ($fks = $this->{$name.'_ids'}) {
                $loaded = &$this->_loadedRelationships[$name.'_ids'];
                if (!$loaded) {
                    $loaded = (object) ['fks' => null];
                }
                if ($loaded->fks != $fks) {
                    $loaded->fks = $fks;
                    if ($data['type']) {
                        $multi = [];
                        foreach (array_filter(explode(',', $fks)) as $fk) {
                            $multi[] = Type::getInstance($fk);
                        }

                        $loaded->values = $multi;
                    } else {
                        $loaded->values = $data['provider']->records()
                            ->findMany(array_filter(explode(',', $fks)));
                    }
                }
                return $loaded->values;
            }
        } elseif ($fk = $this->{$name.'_id'}) {
            $loaded = &$this->_loadedRelationships[$name.'_id'];
            if (!$loaded) {
                $loaded = (object) ['fk' => null];
            }
            if ($loaded->fk != $fk) {
                $loaded->fk = $fk;
                if ($data['type']) {
                    $loaded->value = Type::getInstance($fk);
                } else {
                    $loaded->value = $data['provider']->records()->find($fk);
                }
            }
            return $loaded->value;
        }
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

    public static function type()
    {
        if ($id_tipo = RecordClassMap::getInstance()->getClassIdTipo(get_called_class())) {
            return Type::getInstance($id_tipo);
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
    public static function getInstance($id, $options = [], Type $tipo)
    {
        // Classe foi forçada
        if (isset($options['class'])) {
            $class_name = $options['class'];
        } else {
            $class_name = RecordClassMap::getInstance()->getClass($tipo->id_tipo);
            if (!$class_name) {
                $class_name = isset($options['default_class']) ? $options['default_class'] : self::class;
            }
        }

        $instance = new $class_name(['id' => $id]);
        $instance->setType($tipo);
        $instance->setDb($tipo->getDb());

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
        // childName() - relacionamento
        if ($child = $this->_findChild(ucfirst($methodName))) {
            $childrenTipo = $this->getChildrenTipo($child['id_tipo']);
            if (isset($this->_eagerLoad[$methodName])) {
                return new EagerLoaded($childrenTipo, $this->_eagerLoad[$methodName]);
            }

            return new Query($childrenTipo);
        } elseif ($methodName === 'arquivos' && $this->getType()->arquivos) {
            return new Query\FileQuery($this);
        }
        // Default error when method doesn´t exist
        $message = 'Call to undefined method '.get_class($this).'->'.$methodName.'(). Available magic methods: '."\n";
        $children = $this->getType()->getInterAdminsChildren();

        foreach (array_keys($children) as $childName) {
            $message .= "\t\t- ".lcfirst($childName)."()\n";
        }
        if ($this->getType()->arquivos) {
            $message .= "\t\t- arquivos()\n";
        }

        throw new BadMethodCallException($message);
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

        if (empty($this->attributes['id_tipo'])) {
            $this->id_tipo = 0;
        }
        
        $tipo = static::type();
        if (!$tipo || $this->id_tipo != $tipo->id_tipo) {
            if (!$this->id_tipo) {
                $db = $this->getDb();
                $table = str_replace($db->getTablePrefix(), '', $this->getTableName()); // FIXME

                $data = DB::table($table)
                    ->select('id_tipo')
                    ->where('id', $this->id)
                    ->first();

                if (!$data || !$data->id_tipo) {
                    throw new Exception('Could not find id_tipo for record with id: ' . $this->id);
                }

                $this->id_tipo = $data->id_tipo;
            }
            $tipo = Type::getInstance($this->id_tipo, [
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
        $this->id_tipo = $tipo->id_tipo;
        $this->_tipo = $tipo;
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
                $parentTipo = Type::getInstance($this->parent_id_tipo);
                $this->_parent = $parentTipo->records()->find($this->parent_id);
                if ($this->_parent) {
                    $this->getType()->setParent($this->_parent);
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
        $options['default_class'] = static::DEFAULT_NAMESPACE.'Type';
        $childrenTipo = Type::getInstance($id_tipo, $options);
        $childrenTipo->setParent($this);

        return $childrenTipo;
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
    public function getArquivos($deprecated, $options = [])
    {
        if ($deprecated != self::DEPRECATED_METHOD) {
            throw new Exception('Use arquivos()->get() instead.');
        }

        $arquivos = [];
        if (isset($options['class'])) {
            $className = $options['class'];
        } else {
            $className = static::DEFAULT_NAMESPACE.'FileRecord';
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
        $arquivos = $this->getArquivos($options);
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
        kd('not implemented');
        $db = $this->getDb();
        $sql = 'DELETE FROM '.$this->getDb()->getTablePrefix().'tags WHERE parent_id = '.$this->id;
        if (!$db->Execute($sql)) {
            throw new Exception($db->ErrorMsg().' - SQL: '.$sql);
        }

        foreach ($tags as $tag) {
            $sql = 'INSERT INTO '.$this->getDb()->getTablePrefix().'tags (parent_id, id, id_tipo) VALUES
                ('.$this->id.','.
                (($tag instanceof Record) ? $tag->id : 0).','.
                (($tag instanceof Record) ? $tag->getFieldsValues('id_tipo') : $tag->id_tipo).')';
            if (!$db->Execute($sql)) {
                throw new Exception($db->ErrorMsg().' - SQL: '.$sql);
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
        kd('not implemented');
        if (!$this->_tags || $options) {
            $db = $this->getDb();

            $options['where'][] = 'parent_id = '.$this->id;
            $sql = 'SELECT * FROM '.$this->getDb()->getTablePrefix().'tags '.
                'WHERE '.implode(' AND ', $options['where']).
                (($options['group']) ? ' GROUP BY '.$options['group'] : '').
                (($options['limit']) ? ' LIMIT '.$options['limit'] : '');
            if (!$rs = $db->Execute($sql)) {
                throw new Exception($db->ErrorMsg().' - SQL: '.$sql);
            }

            $this->_tags = [];
            while ($row = $rs->FetchNextObj()) {
                if ($tag_tipo = Type::getInstance($row->id_tipo)) {
                    $tag_text = $tag_tipo->getFieldsValues('nome');
                    if ($row->id) {
                        $options = [
                            'fields' => ['varchar_key'],
                            'where' => ['id = '.$row->id],
                        ];
                        if ($tag_registro = $tag_tipo->findFirst($options)) {
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
    public function isPublished()
    {
        global $s_session;
        
        $this->getFieldsValues(['date_publish', 'date_expire', 'char_key', 'publish', 'deleted']);

        return (
            strtotime($this->date_publish) <= Record::getTimestamp() &&
            (strtotime($this->date_expire) >= Record::getTimestamp() || $this->date_expire == '0000-00-00 00:00:00') &&
            $this->char_key &&
            ($this->publish || config('interadmin.preview') || $s_session['preview']) &&
            !$this->deleted
        );
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
        $this->log = date('d/m/Y H:i').' - '.self::getLogUser().' - '.Request::ip().
            chr(13).$this->log;

        // date_modify
        $this->date_modify = date('c');

        return parent::save();
    }

    /**
     * Saves without logs and triggers.
     */
    public function saveRaw()
    {
        return parent::save();
    }

    public function generateSlug()
    {
        if (isset($this->attributes['varchar_key'])) {
            $alias_varchar_key = 'varchar_key';
        } else {
            $alias_varchar_key = $this->getType()->getCamposAlias('varchar_key');
        }
        if (empty($this->attributes[$alias_varchar_key])) {
            return;
        }

        $id_slug = to_slug($this->$alias_varchar_key);
        if (is_numeric($id_slug)) {
            $id_slug = '--'.$id_slug;
        }

        $query = function () {
            return $this->siblings()->published(true);
        };

        if ($query()->where('id_slug', $id_slug)->exists()) {
            // Add an index if it already exists
            $max = $query()
                ->where('id_slug', 'REGEXP', '^'.$id_slug.'[0-9]*$')
                ->max('id_slug');

            $next = preg_replace('~^'.$id_slug.'~', '', $max);
            $next = max($next, 1) + 1;

            $id_slug = $id_slug.$next;
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
    public function getCampoTipo($campo)
    {
        return $this->getType()->getCampoTipo($campo);
    }
    public function getAttributesAliases()
    {
        return $this->getType()->getCamposAlias();
    }
    public function getTableName()
    {
        if (empty($this->attributes['id_tipo'])) {
            // Compatibilidade, tenta encontrar na tabela global
            throw new Exception('Not implemented');
            return $this->getDb()->getTablePrefix(). (isset($this->attributes['table']) ? $this->attributes['table'] : '');
        } else {
            return $this->getType()->getInterAdminsTableName();
        }
    }
    /**
     * Returns $log_user. If $log_user is NULL, returns $s_user['login'] on
     * applications and 'site' otherwise.
     *
     * @see Record::$log_user
     *
     * @return string
     */
    public static function getLogUser()
    {
        global $jp7_app, $s_user;
        if (is_null(self::$log_user)) {
            return ($jp7_app) ? $s_user['login'] : 'site';
        }

        return self::$log_user;
    }
    /**
     * Sets $log_user and returns the old value.
     *
     * @see     Record::$log_user
     *
     * @param object $log_user
     *
     * @return string Old value.
     */
    public static function setLogUser($log_user)
    {
        $old_user = self::$log_user;
        self::$log_user = $log_user;

        return $old_user;
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
        $aliases = array_flip($this->getType()->getCamposAlias());
        $nomeCampo = $aliases[$attribute] ? $aliases[$attribute] : $attribute;

        if (!starts_with($nomeCampo, 'select_')) {
            throw new Exception('The field '.$attribute.' is not a select. It was expected a select field on setAttributeBySearch.');
        }

        $campoTipo = $this->getCampoTipo($campos[$nomeCampo]);
        $record = $campoTipo->findFirst([
            'where' => [$searchColumn." = '".$searchValue."'"],
        ]);
        if (starts_with($nomeCampo, 'select_multi_')) {
            $this->$attribute = [$record];
        } else {
            $this->$attribute = $record;
        }
    }

    public function setEagerLoad($key, $data)
    {
        $this->_eagerLoad[$key] = $data;
    }

    /**
     * Returns varchar_key using its alias, without loading it from DB again.
     */
    public function getName()
    {
        $varchar_key_alias = $this->getType()->getCamposAlias('varchar_key');

        return $this->$varchar_key_alias;
    }

    public function toArray()
    {
        $array = $this->attributes;
        foreach ($array as &$value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }
        }

        return $array;
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
                if ($campo['xtra'] === 'email') {
                    $rules[$alias][] = 'email';
                }
                if (starts_with($campo['tipo'], 'int_')) {
                    $rules[$alias][] = 'integer';
                }
                if (starts_with($campo['tipo'], 'date_')) {
                    $rules[$alias][] = 'date';
                }
            }
        }

        return $rules;
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
}
