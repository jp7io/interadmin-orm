<?php

namespace Jp7\InterAdmin;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\Query\Expression;
use Jp7\TryMethod;
use Exception;
use UnexpectedValueException;
use DB;
use Doctrine\SqlFormatter\SqlFormatter;

/**
 * Class which represents records on the table interadmin_{client name}.
 */
abstract class RecordAbstract
{
    use TryMethod;

    const DEFAULT_FIELDS_ALIAS = true;
    const DEFAULT_NAMESPACE = 'Jp7\InterAdmin\\';
    const DEFAULT_FIELDS = '*';

    protected static $unguarded = false;

    protected $_primary_key = 'id';
    /**
     * Array of all the attributes with their names as keys and the values of the attributes as values.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Connection name
     * @var string
     */
    protected $_db = '';

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Magic get acessor.
     *
     * @param string $attributeName
     *
     * @return mixed
     */
    public function &__get($name)
    {
        $value = null;
        if (array_key_exists($name, $this->attributes)) {
            $value = $this->attributes[$name];
            $value = $this->getMutatedAttribute($name, $value);
            return $value;
        }
        // Mutators
        $mutator = 'get'.Str::studly($name).'Attribute';
        if (method_exists($this, $mutator)) {
            $value = $this->$mutator($value);
            return $value;
        }
        return $value;
    }
    /**
     * Magic set acessor.
     *
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value)
    {
        if ($name === 'attributes') {
            throw new Exception("attributes is protected"); // FIXME remove when old code is validated
        }
        if ($name === $this->_primary_key) {
            $this->exists = (bool) $value;
        }
        $mutator = 'set' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $mutator)) {
            return $this->$mutator($value);
        }
        $this->attributes[$name] = $value;
    }
    /**
     * Magic unset acessor.
     *
     * @param string $attributeName
     */
    public function __unset($attributeName)
    {
        unset($this->attributes[$attributeName]);
    }
    /**
     * Magic isset acessor.
     *
     * @param string $attributeName
     *
     * @return bool
     */
    public function __isset($attributeName)
    {
        return isset($this->attributes[$attributeName]);
    }
    /**
     * String value of this record's primary_key.
     *
     * @return string String value of the primary_key property.
     */
    public function __toString()
    {
        $pk = $this->_primary_key;

        return (string) $this->$pk;
    }

    /**
     * Loads attributes if they are not set yet.
     *
     * @param array $attributes
     */
    public function loadAttributes($attributes, $fieldsAlias = true)
    {
        $attributes = array_diff($attributes, array_keys($this->attributes));
        if (!$attributes) {
            return;
        }
        // Retrieving data
        $options = [
            'fields' => $attributes,
            'fields_alias' => $fieldsAlias,
            'from' => $this->getTableName().' AS main',
            'where' => [$this->_primary_key.' = '.intval($this->{$this->_primary_key})],
            'use_published_filters' => false,
            // Internal use
            'aliases' => $this->getAttributesAliases(),
            'campos' => $this->getAttributesFields(),
        ];
        $rs = $this->_executeQuery($options);
        if ($row = array_shift($rs)) {
            $this->_getAttributesFromRow($row, $this, $options);
        }
        //$rs->Close();
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->{$this->_primary_key};
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->_primary_key;
    }

    /**
     * Converts to date or file
     *
     * @param string $name  The name of the field.
     *
     * @return mixed
     */
    protected function getMutatedAttribute($name, $value)
    {
        if (is_string($value)) {
            if (strpos($name, 'date_') === 0) {
               return new \Date($value);
            }
            if (strpos($name, 'file_') === 0 && strpos($name, '_text') === false && $value) {
                static $fileClassName = [];
                if (!isset($fileClassName[static::DEFAULT_NAMESPACE])) {
                    if (class_exists(static::DEFAULT_NAMESPACE.'InterAdminFieldFile')) {
                        $fileClassName[static::DEFAULT_NAMESPACE] = static::DEFAULT_NAMESPACE.'InterAdminFieldFile';
                    } else {
                        $fileClassName[static::DEFAULT_NAMESPACE] = static::DEFAULT_NAMESPACE.'FileField';
                    }
                    if (!class_exists($fileClassName[static::DEFAULT_NAMESPACE])) {
                        $fileClassName[static::DEFAULT_NAMESPACE] = 'Jp7\\InterAdmin\\FileField';
                    }
                }
                $className = $fileClassName[static::DEFAULT_NAMESPACE];
                $file = new $className($value, $this->{$name.'_text'});
                $file->setParent($this);
                return $file;
            }
        }
        return $value;
    }

    public function getFillable()
    {
        return [];
    }

    // Used by ResetsPasswords
    public function forceFill(array $attributes)
    {
        return $this->setRawAttributes($attributes);
    }

    /**
     * @param array $attributes
     * @return self
     * @throws MassAssignmentException
     */
    public function fill(array $attributes)
    {
        if (!$attributes) {
            return $this;
        }
        if (static::$unguarded) {
            $this->setRawAttributes($attributes);

            return $this;
        }

        $fillable = $this->getFillable();
        if (!$fillable) {
            throw new MassAssignmentException(key($attributes));
        }
        foreach ($fillable as $name) {
            if (isset($attributes[$name])) {
                $this->$name = $attributes[$name];
            }
        }

        return $this;
    }

    /**
     * Updates all the attributes from the passed-in array and saves the record.
     *
     * @param array $attributes Array with fields names and values.
     */
    public function update(array $attributes)
    {
        return $this->fill($attributes)->save();
    }
    /**
     * Saves this record.
     */
    public function save()
    {
        return $this->saveRaw();
    }

    /**
     * Saves without logs and triggers.
     */
    public function saveRaw()
    {
        return $this->_update($this->attributes);
    }

    /**
     * Updates all the attributes from the passed-in array and saves the record.
     *
     * @param array $attributes Array with fields names and values.
     */
    public function updateRawAttributes($attributes)
    {
        $this->setRawAttributes($attributes);
        $this->_update($attributes);
    }

    /**
     * Increments a numeric attribute.
     *
     * @param string $attribute
     * @param int    $by
     */
    public function increment($attribute, $by = 1)
    {
        $this->$attribute += $by;
        $pk = $this->_primary_key;
        if ($this->$pk) {
            $this->_update([$attribute => $this->$attribute]);
        } else {
            $this->saveRaw();
        }
    }
    /**
     * Updates using SQL.
     *
     * @param array $attributes
     */
    protected function _update($attributes)
    {
        $db = $this->getDb();

        $aliases = array_flip($this->getAttributesAliases());
        $valuesToSave = $this->_convertForDatabase($attributes, $aliases);

        $pk = $this->_primary_key;
        $table = str_replace($db->getTablePrefix(), '', $this->getTableName()); // FIXME

        if ($this->exists) {
            if (getenv('APP_DEBUG') && !$db->table($table)->where($pk, $this->$pk)->exists()) {
                throw new UnexpectedValueException('No record found before update with PK ('.$pk.'): '.$this->$pk);
            }
            $db->table($table)->where($pk, $this->$pk)->update($valuesToSave);
        } else {
            $db->table($table)->insert($valuesToSave);

            $this->$pk = $db->getPdo()->lastInsertId();
        }

        return $this;
    }

    /**
     * Columns whose empty value is a real NULL. Everywhere else this schema spells "empty" as '',
     * so the blanket null -> '' below is right; a nullable datetime would land on 0000-00-00.
     */
    protected static $nullableAttributes = ['deleted_at'];

    protected function _convertForDatabase($attributes, $aliases)
    {
        $valuesToSave = [];
        foreach ($attributes as $key => $value) {
            $key = isset($aliases[$key]) ? $aliases[$key] : $key;
            switch (gettype($value)) {
                case 'object':
                    if ($value instanceof Expression) {
                        $valuesToSave[$key] = $value;
                    } else {
                        $valuesToSave[$key] = (string) $value;
                        if ($value instanceof FileField) {
                            $valuesToSave[$key.'_text'] = $value->text;
                        }
                    }
                    break;
                case 'array':
                    $valuesToSave[$key] = implode(',', $value);
                    break;
                case 'NULL':
                    $valuesToSave[$key] = in_array($key, static::$nullableAttributes, true) ? null : '';
                    break;
                case 'boolean':
                    if (str_starts_with($key, 'char_')) {
                        $valuesToSave[$key] = $value ? 'S' : '';
                        break;
                    }
                    // fall through
                default:
                    $valuesToSave[$key] = $value;
                    break;
            }
        }
        return $valuesToSave;
    }

    /**
     * Executes a SQL Query based on the values passed by $options.
     *
     * @param array $options Default array of options. Available keys: fields, fields_alias, from, where, order, group, limit, all, campos and aliases.
     * @param string $_stmt Performs DELETE or UPDATE instead of a SELECT
     * @param array $_valuesToSave On UPDATE calls SET these values
     * @return ADORecordSet
     */
    protected function _executeQuery($options, $_stmt = false, $_valuesToSave = []) // , &$select_multi_fields = []
    {
        $db = $this->getDb();
        $APP_DEBUG = getenv('APP_DEBUG');

        $use_published_filters = $this->_normalizeQueryOptions($options);

        // Resolve Alias and Joins for 'fields' and 'from'
        $this->_resolveFieldsAlias($options);
        // Resolve Alias and Joins for 'where', 'group' and 'order';
        $clauses = $this->_resolveSqlClausesAlias($options, $use_published_filters);

        list($from, $filters) = $this->sqlCompiler()->from($options, $use_published_filters);

        // Sql
        $sql = ' WHERE '.$filters.$clauses.
            (!empty($options['limit']) ? ' LIMIT '.$options['limit'] : '');

        if ($APP_DEBUG) {
            $startQuery = microtime(true);
        }

        try {
            if ($_stmt === 'UPDATE') {
                // The SET list is compiled first because a raw expression in it can append
                // a join of its own, which the FROM read below has to pick up.
                $set = $this->_compileSetValues($_valuesToSave, $options, $use_published_filters);
                $sql = 'UPDATE '.$from.$this->_joinSql($options).
                    ' SET '.$set.
                    $sql;
                $rs = $db->update($sql, $options['bindings']);
            } elseif ($_stmt === 'DELETE') {
                // Temp table needed for LIMIT
                $sql = 'DELETE main FROM '.$from.' INNER JOIN ('.
                    'SELECT main.id FROM '.$from.$this->_joinSql($options).$sql.
                    ') AS temp ON main.id = temp.id';
                $rs = $db->delete($sql, $options['bindings']);
            } else {
                $sql = 'SELECT '.implode(',', $options['fields']).
                    ' FROM '.$from.$this->_joinSql($options).
                    $sql;
                $rs = $db->select($sql, $options['bindings']);
            }
        } catch (QueryException $e) {
            $sql = self::replaceBindings($options['bindings'], $sql);
            if (str_contains($e->getMessage(), 'Unknown column') && $options['aliases']) {
                $sql .= ' /* Available fields: '.implode(', ', array_keys($options['aliases'])) . '*/';
            }

            // Re-throw with the interpolated SQL (and the available-fields hint), which is
            // the whole point of this catch.
            //
            // This used to call the Laravel 5 three-arg constructor,
            // QueryException($sql, $bindings, $previous). Since Laravel 8 the signature is
            // ($connectionName, $sql, array $bindings, Throwable $previous) -- so $bindings
            // received the PDOException and it blew up with:
            //
            //   TypeError: Argument #3 ($bindings) must be of type array, PDOException given
            //
            // i.e. EVERY database error anywhere in the app was reported as that TypeError
            // instead of the actual SQL error. The real cause was never visible.
            throw new QueryException(
                $e->getConnectionName(),
                $sql,
                $options['bindings'],
                $e->getPrevious() ?: $e
            );
        }

        if ($APP_DEBUG) {
            $this->_debugQuery(
                self::replaceBindings($options['bindings'], $sql),
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                $startQuery
            );
        }

        if (!empty($options['debug'])) {
            // $time = $debugger->getTime($options['debug']);
            echo (new SqlFormatter())->format(self::replaceBindings($options['bindings'], $sql));
        }
        // $select_multi_fields = isset($options['select_multi_fields']) ? $options['select_multi_fields'] : null;
        return $rs;
    }

    /**
     * Fills in the option shapes the rest of the query path assumes -- list-typed from,
     * where and fields, a bindings array, aliases flipped to column => alias -- and folds
     * skip into limit, which MySQL spells as an offset pair.
     *
     * @return bool whether the publishing predicates apply: the option when set, the global
     *              default otherwise.
     */
    private function _normalizeQueryOptions(array &$options)
    {
        foreach (['from', 'where', 'fields'] as $key) {
            if (!is_array($options[$key])) {
                $options[$key] = (array) $options[$key];
            }
        }
        if (!array_key_exists('bindings', $options)) {
            $options['bindings'] = [];
        }
        $options['where'] = implode(' AND ', $options['where']);
        $options['aliases'] = empty($options['fields_alias']) ? [] : array_flip($options['aliases']);

        if (isset($options['skip'])) {
            // MySQL has no offset without a limit, hence the largest BIGINT
            $options['limit'] = $options['skip'].','.($options['limit'] ?? '18446744073709551615');
        }

        if (array_key_exists('use_published_filters', $options)) {
            return $options['use_published_filters'];
        }

        return Record::isPublishedFiltersEnabled();
    }

    /**
     * The JOINs, as one string. Read late and never cached: the SET list of an UPDATE can
     * still add one.
     */
    private function _joinSql(array $options)
    {
        return $options['from'] ? implode('', $options['from']) : '';
    }

    /**
     * Compiles an UPDATE's SET list. A raw expression goes through the alias walk so it can
     * name this type's fields; anything else becomes a binding.
     */
    private function _compileSetValues(array $values, array &$options, $use_published_filters)
    {
        foreach ($values as $key => $value) {
            if ($value instanceof Expression) {
                $values[$key] = $key.' = '.$this->_resolveSql(
                    RawSql::toSql($value),
                    $options,
                    $use_published_filters
                );
            } else {
                $binding = ':val'.count($options['bindings']);
                $options['bindings'][$binding] = $value;
                $values[$key] = $key.' = '.$binding;
            }
        }

        return implode(', ', $values);
    }

    private function _debugQuery($sql, $trace, $startQuery)
    {
        //$sql = explode('FROM ', str_replace(self::getPublishedFilters('registros', 'main'), '/* ... */ ', $sql))[1];
        $ms = function ($start) {
            return number_format((microtime(true)-$start)*1000).'ms';
        };
        $caller = '';
        foreach ($trace as $item) {
            if (!empty($item['file']) && !str_contains($item['file'], '/vendor/')) {
                $caller = str_replace(base_path(), '', $item['file']).':'.$item['line'];
                break;
            }
        }
        $callee = '';
        //foreach (array_reverse($trace) as $item) {
        //    if (!empty($item['class']) && str_starts_with($item['class'], 'Jp7\InterAdmin\Query') && $item['function'] !== '__call') {
        //        $callee = str_replace('Jp7\\InterAdmin\\', '', $item['class']).'@'.$item['function'];
        //        break;
        //    }
        //}
        \Log::debug($sql.PHP_EOL.'/* '.$caller.' - '.$ms($startQuery).' - '.$callee.' */');
    }

    /**
     * The compiler that turns this record's alias syntax into SQL.
     *
     * Built per call rather than memoized: it holds the record it was built for, and a
     * record that gets cloned would otherwise hand its clone a compiler still pointing at
     * the original.
     */
    protected function sqlCompiler()
    {
        return new SqlCompiler($this);
    }

    /**
     * @see SqlCompiler::clauses()
     */
    protected function _resolveSqlClausesAlias(array &$options, $use_published_filters)
    {
        return $this->sqlCompiler()->clauses($options, $use_published_filters);
    }

    /**
     * @see SqlCompiler::resolve()
     */
    protected function _resolveSql($clause, array &$options, $use_published_filters)
    {
        return $this->sqlCompiler()->resolve($clause, $options, $use_published_filters);
    }

    /**
     * Resolves Aliases on $options fields.
     *
     * The resolved fields go back through $options by reference; nothing is returned.
     *
     * @param array  $options Same syntax as $options
     * @param string $table   Table alias for the fields.
     *
     * @return void
     */
    protected function _resolveFieldsAlias(&$options = [], $table = 'main.')
    {
        $campos = &$options['campos'];
        $aliases = &$options['aliases'];
        $fields = $options['fields'];

        foreach ($fields as $key => $field) {
            // Traduzindo 'join.campo' para 'join' => array('campo')
            if (is_string($field) && strpos($field, '.') !== false && strpos($field, '(') === false) {
                list($join, $nome) = explode('.', $field);
                $fields[$join][] = $nome;
                unset($fields[$key]);
            }
        }

        foreach ($fields as $join => $field) {
            // Com join
            if (is_array($field)) {
                //$nome = isset($aliases[$join]) ? $aliases[$join] : $join;

                // Join e Recursividade
                if (isset($options['joins']) && isset($options['joins'][$join])) {
                    $joinType = $options['joins'][$join][1];
                } elseif (strpos($join, 'select_multi_') === 0) {
                    $joinType = null; // Just ignore select_multi used on legacy code, lazy load them
                    /*
                    $fields[] = $table.$nome.(($table != 'main.') ? ' AS `'.$table.$nome.'`' : '');
                    // Processamento dos campos do select_multi é feito depois
                    $joinType = null;
                    $options['select_multi_fields'][$join] = [
                        'fields' => $fields[$join],
                        'fields_alias' => $options['fields_alias'],
                    ];
                    */
                } elseif (isset($aliases[$join.'_ids'])) {
                    throw new Exception('The field "'.$join.'" cannot be used with select() ('.get_class($this).' - PK: '.$this->__toString().').');
                } else {
                    // Select
                    $nome = isset($aliases[$join.'_id']) ? $aliases[$join.'_id'] : $join;
                    $fields[] = $table.$nome.(($table != 'main.') ? ' AS `'.$table.$nome.'`' : '');
                    // Join e Recursividade
                    if (empty($options['from_alias']) || !in_array($join, (array) $options['from_alias'])) {
                        if (!isset($campos[$nome])) {
                            throw new Exception('The field "'.$join.'" cannot be used with select() ('.get_class($this).' - PK: '.$this->__toString().').');
                        }
                        $joinClasse = $this->_addJoinAlias($options, $join, $campos[$nome]);
                        if ($joinClasse !== 'type') {
                            $fields[$join][] = 'id';
                        }
                    }
                    $joinType = $this->getFieldType($campos[$nome]);
                }
                if ($joinType) {
                    $joinModel = Record::getInstance(0, ['default_namespace' => static::DEFAULT_NAMESPACE], $joinType);
                    $this->_resolveWildcard($fields[$join], $joinModel);

                    $joinOptions = [
                        'fields' => $fields[$join],
                        'fields_alias' => $options['fields_alias'],
                        'campos' => $joinType->getFields(),
                        'aliases' => array_flip($joinType->getFieldAliases()),
                    ];
                    $this->_resolveFieldsAlias($joinOptions, $join.'.');
                    foreach ($joinOptions['fields'] as $joinField) {
                        array_push($fields, $joinField);
                    }
                }
                unset($fields[$join]);

            // Com função
            } elseif (strpos($field, '(') !== false || strpos($field, ' ') !== false) {
                if (strpos($field, ' AS ') === false) {
                    $aggregateAlias = trim(strtolower(preg_replace('/[^[:alnum:]]/', '_', $field)), '_');
                } else {
                    $parts = explode(' AS ', $field);
                    $aggregateAlias = array_pop($parts);
                    $field = implode(' AS ', $parts);
                }
                $fields[$join] = $this->_resolveSql($field, $options, true).' AS `'.$table.$aggregateAlias.'`';
            // Sem join
            } else {
                $nome = $this->_aliasToColumn($field, $aliases);
                if (strpos($nome, 'file_') === 0 && strpos($nome, '_text') === false) {
                    $fields[] = $table.$nome.'_text';
                }

                $fields[$join] = $table.$nome.(($table != 'main.') ? ' AS `'.$table.$nome.'`' : '');
            }
        }
        $options['fields'] = $fields;
    }

    /**
     * The Type a relation field points at, or null when the field stores no type.
     *
     * Every subclass answers it -- Type and Record with a lookup, the three file-backed
     * ones with `void` -- and SqlCompiler reaches it from outside the hierarchy.
     *
     * @see SqlCompiler
     */
    abstract public function getFieldType($field);

    /**
     * Maps one of this record's field aliases to its physical column.
     *
     * Public because it is a POLYMORPHIC SEAM the compiler reaches from outside the
     * hierarchy: the legacy InterAdmin/InterAdminTipo subclasses override it to accept a
     * relation's bare name where its `_id`/`_ids` column is meant, and a clause compiled
     * without that override silently queries a column that does not exist.
     *
     * @see SqlCompiler
     */
    public function _aliasToColumn($alias, $aliases)
    {
        if (isset($aliases[$alias])) {
            return $aliases[$alias];
        }
        return $alias;
    }

    /**
     * @see SqlCompiler::addJoin()
     */
    protected function _addJoinAlias(array &$options, $alias, $field, $table = 'main')
    {
        return $this->sqlCompiler()->addJoin($options, $alias, $field, $table);
    }
    /**
     * Associates the values on a SQL RecordSet with the fields and insert them on the attributes array.
     *
     * @param array $row         Row of a SQL RecordSet.
     * @param bool  $fieldsAlias
     * @param array $attributes  If not provided it will populate an empty array.
     */
    protected function _getAttributesFromRow($row, $object, $options)
    {
        //$campos = &$options['campos'];
        $attributes = &$object->attributes;

        foreach ($row as $key => $value) {
            if (strpos($key, '.') === false) {
                $table = 'main';
                $field = $key;
            } else {
                list($table, $field) = explode('.', $key); // $table might be 'main'?
            }
            if ($table === 'main') {
                $attributes[$field] = $value;
                /*
                if (!empty($options['select_multi_fields'])) {
                    if (strpos($campos[$field]['type'], 'select_multi_') === 0) {
                        $multi_options = $options['select_multi_fields'][$alias];
                        if ($multi_options) {
                            Relation::getFieldsValues($value, $multi_options['fields'], $multi_options['fields_alias']);
                        }
                    }
                }
                */
            } elseif (isset($options['joins'][$table])) {
                // manual join
                if (!isset($object->attributes[$table])) {
                    $object->attributes[$table] = $options['joins'][$table][1]->records()->getModel();
                }
                $object->attributes[$table]->$field = $value;
            } else {
                // select_* relationship
                $column = array_search($table.'_id', $options['aliases']);
                if ($column === false && isset($options['aliases'][$table])) {
                    // sem alias (select_key)
                    $column = $table;
                    $table = substr($options['aliases'][$table], 0, -3);
                }
                $fk = $object->$column;

                $loaded = &$object->relations[$table];
                if (!$loaded || $loaded->id != $fk) {
                    /// stale data or not loaded
                    $relationships = $object->getType()->getRelationships();
                    $data = $relationships[$table];
                    if ($data['type']) {
                        $loaded = Type::getInstance($fk, ['default_namespace' => static::DEFAULT_NAMESPACE]);
                    } else {
                        $loaded = (clone $data['query'])->getModel();
                        $loaded->id = $fk;
                    }
                }
                if ($loaded) {
                    $loaded->attributes[$field] = $value;
                }
            }
        }
    }
    /**
     * Resolves '*'.
     *
     * @param array              $fields
     * @param RecordAbstract $object
     */
    protected function _resolveWildcard(&$fields, RecordAbstract $object)
    {
        if ($fields === '*') {
            $fields = [$fields];
        }
        if (!is_array($fields)) {
            $fields = (array) $fields;
            return;
        }
        $position = array_search('*', $fields);
        if ($position !== false) {
            unset($fields[$position]);
            $attributes = array_intersect($object->getColumns(), array_merge(
                $object->getAttributesNames(),
                $object->getAdminAttributes()
            ));
            $fields = array_merge($attributes, $fields);
        }
    }
    /**
     * Sets this object's attributes with the given array keys and values.
     *
     * @param array $attributes
     */
    public function setRawAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * Sets this row as deleted as saves it.
     */
    public function delete()
    {
        $this->deleted = 'S';
        return $this->save();
    }
    /**
     * Deletes this row from the table.
     *
     * @return
     */
    public function forceDelete()
    {
        $pk = $this->_primary_key;
        $db = $this->getDb();

        $table = str_replace($db->getTablePrefix(), '', $this->getTableName()); // FIXME

        return $db->table($table)
            ->where($pk, $this->$pk)
            ->delete();
    }

    public function restore()
    {
        $this->deleted = '';
        return $this->save();
    }

    /**
     * @param array $where FIXME temporário para wheres que eram com string
     */
    protected function _whereArrayFix(&$where)
    {
        if (is_string($where)) {
            // Inlined from jp7io/inc's jp7_explode(), which this package called without
            // declaring the dependency. array_filter() preserves keys and drops '0' as
            // well as '' -- both are the legacy behaviour and are kept deliberately.
            $where = array_filter(explode(' AND ', $where), 'trim');
        } elseif (!$where) {
            $where = [];
        }
    }

    abstract public function getAttributesFields();
    abstract public function getAttributesNames();
    abstract public function getAttributesAliases();
    abstract public function getAdminAttributes();
    abstract public function getTableName();

    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Column names of this record's table, as reported by the schema.
     *
     * An empty listing is never cached, and a cached one is never trusted. A table with no
     * columns does not exist, so [] is always the answer to a read that FAILED -- a connection
     * still pointed at another database, a tenant whose tables are not imported yet, a table
     * name this class got wrong -- and it is not a fact worth keeping for even a second.
     *
     * Keeping it wedges the whole tenant instead of the one call that failed. Type's attribute
     * names ARE this listing, so an empty one stops Type::__get() recognising `campos` as a
     * column; the types row then never lazy-loads, getFields() parses '' into an empty field
     * map, and every relation field throws out of _resolveFieldsAlias(). It reads as a schema
     * problem while the schema is perfectly fine. Nor does the TTL bound it: remember() rewrote
     * the empty value on every miss, so a condition that recurs kept the poison topped up.
     *
     * Reading THROUGH the guard is also what heals a cache poisoned by an earlier version of
     * this method, with nobody having to flush anything.
     *
     * @see BaseClassMap::getClasses() same rule ("only cache if it has classes"), same reason.
     */
    public function getColumns()
    {
        $table = $this->getTableName();
        $cacheKey = 'columns,'.$this->_db.','.$table;

        if ($columns = \Cache::get($cacheKey)) {
            return $columns;
        }

        $db = $this->getDb();
        // getColumnListing() puts the prefix back on, so it has to come off first. Anchored,
        // and once: a table whose own name repeats the prefix (a type whose `table_name` holds the
        // fully qualified name, say) was rewritten by the old unanchored str_replace() into a
        // DIFFERENT table, which answers with the wrong columns when it happens to exist.
        $prefix = $db->getTablePrefix();
        if ($prefix && str_starts_with($table, $prefix)) {
            $table = substr($table, strlen($prefix));
        }
        $columns = $db->getSchemaBuilder()->getColumnListing($table);

        if ($columns) {
            \Cache::put($cacheKey, $columns, Type::CACHE_TTL);
        }
        return $columns;
    }

    /**
     * The publishing/visibility predicates for a table, qualified with $alias.
     *
     * Stays here, and stays the entry point, because it is a POLYMORPHIC SEAM: subclasses
     * override it to opt out (Log returns nothing -- log rows have no publishing
     * calendar), and the compiler reaches it through late static binding. The predicate
     * building itself now lives in PublishedFilter.
     *
     * @see PublishedFilter
     * @see Log::getPublishedFilters()
     */
    public static function getPublishedFilters($table, $alias)
    {
        return PublishedFilter::sql($table, $alias);
    }

    /**
     * Returns the SQL WHERE for filtering this as a tag.
     *
     * @return string
     */
    abstract public function getTagFilters();

    /**
     * Returns the database object.
     *
     * @return ?
     */
    public function getDb()
    {
        return $this->_db ? DB::connection($this->_db) : DB::connection();
    }
    /**
     * Sets the database object.
     *
     * @param string $db Connection name
     */
    public function setDb($db)
    {
        if (is_string($db) || is_null($db)) {
            $this->_db = $db;
        } elseif ($db instanceof ConnectionInterface) {
            $this->_db = $db->getName();
        } else {
            throw new UnexpectedValueException('Expected instance of ConnectionInterface or connection name, received '.gettype($db));
        }
    }

    public function setConnection($name)
    {
        $this->setDb($name);
    }

    public function getDbName()
    {
        return $this->_db;
    }

    /**
     * Disable all mass assignable restrictions.
     */
    public static function unguard()
    {
        static::$unguarded = true;
    }

    /**
     * Enable the mass assignment restrictions.
     */
    public static function reguard()
    {
        static::$unguarded = false;
    }

    public static function replaceBindings($bindinds, $sql)
    {
        // backwards compatibility, use quote instead of bindings
        // used only for debugging and some subqueries that could not be converted
        $db = \DB::connection();
        $pdo = $db->getPdo();
        if (!$pdo) {
            $db->reconnect();
            $pdo = $db->getPdo();
        }
        $quoted = '(?<quoted>\'((?<=\\\\)\'|[^\'])*\'|"((?<=\\\\)"|[^"])*")';
        foreach ($bindinds as $key => $value) {
            $found = false;
            $sql = preg_replace_callback(
                '~'.$quoted.'|(?<before>\W)'.$key.'\b~', // pattern
                function ($matches) use ($pdo, $value, &$found) { // callback
                    if ($found || $matches['quoted']) {
                        return $matches[0]; // quoted, unchanged
                    }
                    $found = true; // replace only first occurrence
                    return $matches['before'].$pdo->quote($value);
                },
                $sql // subject
            );
        }
        return $sql;
    }
}
