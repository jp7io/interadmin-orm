<?php

namespace Jp7\Interadmin;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\Query\Expression;
use Jp7\TryMethod;
use Exception;
use UnexpectedValueException;
use DB;
use SqlFormatter;

/**
 * Class which represents records on the table interadmin_{client name}.
 */
abstract class RecordAbstract
{
    use TryMethod;

    const DEFAULT_FIELDS_ALIAS = true;
    const DEFAULT_NAMESPACE = 'Jp7\Interadmin\\';
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
            'campos' => $this->getAttributesCampos(),
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
                        $fileClassName[static::DEFAULT_NAMESPACE] = 'Jp7\\Interadmin\\FileField';
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
                    $valuesToSave[$key] = '';
                    break;
                case 'boolean':
                    if (Str::startsWith($key, 'char_')) {
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
        //global $debugger;
        $db = $this->getDb();
        $APP_DEBUG = getenv('APP_DEBUG');

        // Type casting
        if (!is_array($options['from'])) {
            $options['from'] = (array) $options['from'];
        }
        if (!is_array($options['where'])) {
            $options['where'] = (array) $options['where'];
        }
        if (!array_key_exists('bindings', $options)) {
            $options['bindings'] = [];
        }
        $options['where'] = implode(' AND ', $options['where']);
        if (!is_array($options['fields'])) {
            $options['fields'] = (array) $options['fields'];
        }
        if (empty($options['fields_alias'])) {
            $options['aliases'] = [];
        } else {
            $options['aliases'] = array_flip($options['aliases']);
        }
        if (array_key_exists('use_published_filters', $options)) {
            $use_published_filters = $options['use_published_filters'];
        } else {
            $use_published_filters = Record::isPublishedFiltersEnabled();
        }

        // Resolve Alias and Joins for 'fields' and 'from'
        $this->_resolveFieldsAlias($options);
        // Resolve Alias and Joins for 'where', 'group' and 'order';
        $clauses = $this->_resolveSqlClausesAlias($options, $use_published_filters);

        $filters = '';
        if ($use_published_filters) {
            foreach ($options['from'] as $key => $from) {
                list($table, $alias) = explode(' AS ', $from);
                if ($alias == 'main') {
                    $filters = static::getPublishedFilters($table, 'main');
                } else {
                    $joinArr = explode(' ON', $alias);
                    $options['from'][$key] = $table.' AS '.$joinArr[0].' ON '.static::getPublishedFilters($table, $joinArr[0]).$joinArr[1];
                }
            }
        }

        $from = array_shift($options['from']); // main table
        if (isset($options['joins']) && $options['joins']) {
            $pre_joins = $options['pre_joins'] ?? [];
            foreach ($options['joins'] as $alias => $join) {
                @list($joinType, $type, $on, $typeless) = $join;
                if ($type === Type::class) {
                    $table = (new Type)->getTableName();
                } else {
                    $table = $type->getInterAdminsTableName();
                }
                $joinSql = ' '.$joinType.' JOIN '.$table.' AS '.$alias.' ON '.
                    ($use_published_filters ? static::getPublishedFilters($table, $alias) : '');
                if (!$typeless) {
                    $joinSql .= $alias.'.type_id = '.$type->type_id.' AND ';
                }
                $preIndex = count($options['from']);
                $joinSql .= $this->_resolveSql($on, $options, $use_published_filters);
                if (isset($pre_joins[$alias])) {
                    $after = array_splice($options['from'], $preIndex);
                    // it's on pre_join so it's a dependency for some FROM join
                    array_unshift($options['from'], $joinSql);
                    // it was inserted after, so it's a dependency
                    $options['from'] = array_merge($after, $options['from']);
                } else {
                    $options['from'][] = $joinSql;
                }
            }
        }

        if (isset($options['skip'])) {
            $options['limit'] = $options['skip'].','.($options['limit'] ?? '18446744073709551615');
        }

        // Sql
        $sql = ' WHERE '.$filters.$clauses.
            (!empty($options['limit']) ? ' LIMIT '.$options['limit'] : '');

        if ($APP_DEBUG) {
            $startQuery = microtime(true);
        }

        try {
            if ($_stmt === 'UPDATE') {
                foreach ($_valuesToSave as $key => $value) {
                    if ($value instanceof Expression) {
                        $_valuesToSave[$key] = $key.' = '.$this->_resolveSql($value, $options, $use_published_filters);
                    } else {
                        $binding = ':val'.count($options['bindings']);
                        $options['bindings'][$binding] = $value;
                        $_valuesToSave[$key] = $key.' = '.$binding;
                    }
                }
                $sql = 'UPDATE '.$from.
                    ($options['from'] ? implode('', $options['from']) : '').
                    ' SET '.implode(', ', $_valuesToSave).
                    $sql;
                $rs = $db->update($sql, $options['bindings']);
            } elseif ($_stmt === 'DELETE') {
                // Temp table needed for LIMIT
                $sql = 'DELETE main FROM '.$from.' INNER JOIN ('.
                    'SELECT main.id FROM '.$from.
                    ($options['from'] ? implode('', $options['from']) : '').
                    $sql.
                    ') AS temp ON main.id = temp.id';
                $rs = $db->delete($sql, $options['bindings']);
            } else {
                $sql = 'SELECT '.implode(',', $options['fields']).
                    ' FROM '.$from.
                    ($options['from'] ? implode('', $options['from']) : '').
                    $sql;
                $rs = $db->select($sql, $options['bindings']);
            }
        } catch (QueryException $e) {
            dd($e->getMessage(), $sql, $options['bindings']);
            $sql = self::replaceBindings($options['bindings'], $sql);
            if (str_contains($e->getMessage(), 'Unknown column') && $options['aliases']) {
                $sql .= ' /* Available fields: '.implode(', ', array_keys($options['aliases'])) . '*/';
            }
            throw new QueryException($sql, $options['bindings'], $e->getPrevious());
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
            echo SqlFormatter::format(self::replaceBindings($options['bindings'], $sql));
        }
        // $select_multi_fields = isset($options['select_multi_fields']) ? $options['select_multi_fields'] : null;
        return $rs;
    }

    private function _debugQuery($sql, $trace, $startQuery)
    {
        //$sql = explode('FROM ', str_replace(self::getPublishedFilters('records', 'main'), '/* ... */ ', $sql))[1];
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
        //    if (!empty($item['class']) && Str::startsWith($item['class'], 'Jp7\Interadmin\Query') && $item['function'] !== '__call') {
        //        $callee = str_replace('Jp7\\Interadmin\\', '', $item['class']).'@'.$item['function'];
        //        break;
        //    }
        //}
        if (!isset($GLOBALS['__queries'])) {
            $GLOBALS['__queries'] = 0;
        }
        $GLOBALS['__queries']++;
        \Log::debug($sql.PHP_EOL.'/* '.$caller.' - '.$ms($startQuery).' - '.$callee.' */');
    }

    /**
     * Resolves the aliases on clause using regex.
     *
     * @param string $clause
     *
     * @return
     */
    protected function _resolveSqlClausesAlias(array &$options, $use_published_filters)
    {
        $resolvedWhere = $this->_resolveSql($options['where'], $options, $use_published_filters);
        if (isset($options['order'])) {
            $resolvedOrder = $this->_resolveSql($options['order'], $options, $use_published_filters);
        }
        // Group by para wheres com children, DISTINCT é usado para corrigir COUNT() com children
        $firstField = reset($options['fields']);
        if (empty($options['group']) && strpos($firstField, 'DISTINCT') === false) {
            if (!empty($options['auto_group_flag'])) {
                $options['group'] = 'main.id';
            }
        }

        $clause = ((!empty($options['group'])) ? ' GROUP BY '.$options['group'] : '').
            ((!empty($options['having'])) ? ' HAVING '.implode(' AND ', $options['having']) : '');

        return $resolvedWhere.
            $this->_resolveSql($clause, $options, $use_published_filters).
            ((isset($resolvedOrder)) ? ' ORDER BY '.$resolvedOrder : '');
    }

    protected function _resolveSql($clause, array &$options, $use_published_filters)
    {
        $campos = &$options['campos'];
        $aliases = &$options['aliases'];

        $quoted = '(\'((?<=\\\\)\'|[^\'])*\'|"((?<=\\\\)"|[^"])*")';
        $keyword = ':?\b[a-zA-Z0-9_.]+\b';
        // not followed by "(" or " (", so it won't match "CONCAT(" or "IN ("
        $not_function = '(?![ ]?\()';
        $reserved = [
            'SELECT', 'WHERE',
            'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'NOT', 'LIKE', 'IS',
            'NULL', 'DESC', 'ASC', 'BETWEEN', 'REGEXP', 'HAVING', 'DISTINCT', 'UNSIGNED', 'AS',
            'INTERVAL', 'DAY', 'WEEK', 'MONTH', 'YEAR', 'CASE', 'WHEN', 'THEN', 'END', 'BINARY',
            'HOUR', 'MINUTE', 'SECOND',
        ];

        $offset = 0;
        $ignoreJoinsUntil = -1;
        $insideFrom = false;

        $options += [
            'from_alias' => [],
            'joins' => [],
        ];

        while (preg_match('/('.$quoted.'|'.$keyword.$not_function.'|EXISTS)/', $clause, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            list($termo, $pos) = $matches[1];
            // Resolvendo true e false para char
            if (strtolower($termo) == 'true' || strtolower($termo) == 'false') {
                $negativas = ['', '!'];
                if (strtolower($termo) == 'false') {
                    $negativas = array_reverse($negativas);
                }
                $inicio = substr($clause, 0, $pos + strlen($termo));
                $inicioRep = preg_replace('/(\.char_[[:alnum:] ]*)(<>|!=)([ ]*)'.$termo.'$/i', '$1'.$negativas[0]."=$3''", $inicio, 1, $count);
                if (!$count) {
                    $inicioRep = preg_replace('/(\.char_[^=]*)=([ ]*)'.$termo.'/i', '$1'.$negativas[1]."=$2''", $inicio, 1);
                }
                $clause = $inicioRep.substr($clause, $pos + strlen($termo));
                $offset = strlen($inicioRep);
                continue;
            }

            if ($termo === 'FROM') {
                $insideFrom = true;
            }
            if ($insideFrom) {
                if ($termo === 'WHERE') { // join ainda nao suportado
                    $insideFrom = false;
                }
                $offset = $pos + strlen($termo);
                continue;
            }

            // Joins com EXISTS
            if ($termo == 'EXISTS') {
                $inicio = substr($clause, 0, $pos + strlen($termo));
                $existsClause = substr($clause, $pos + strlen($termo));
                if (preg_match('/^([\( ]+)('.$keyword.')([ ]+)(WHERE)?/', $existsClause, $existsMatches)) {
                    $table = $existsMatches[2];
                    // TODO unificar logica
                    if (!isset($childrenArr)) {
                        $childrenArr = $this->getInterAdminsChildren();
                    }

                    $joinNome = Str::studly($table);
                    if (isset($childrenArr[$joinNome])) {
                        // Children
                        $joinTipo = Type::getInstance($childrenArr[$joinNome]['type_id'], [
                            'db' => $this->_db,
                            'default_namespace' => static::DEFAULT_NAMESPACE,
                        ]);

                        $joinFilter = ($use_published_filters) ? $this->getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';
                        $existsMatches[2] = 'SELECT id FROM '.$joinTipo->getInterAdminsTableName().' AS '.$table.
                        ' WHERE '.$joinFilter.$table.'.parent_id = main.id AND '.$table.'.type_id = '.$joinTipo->type_id.''.
                        (($existsMatches[4]) ? ' AND ' : '');
                    } elseif ($table == 'tags') {
                        // Tags
                        $existsMatches[2] = 'SELECT id_tag FROM '.$this->getDb()->getTablePrefix().'tags AS '.$table.
                        ' WHERE '.$table.'.parent_id = main.id'.(($existsMatches[4]) ? ' AND ' : '');
                    } elseif (isset($options['joins'][$table])) {
                        // Joins custom
                        $joinTipo = $options['joins'][$table][1];
                        $onClause = [
                            'joins' => $options['joins'],
                            'where' => $options['joins'][$table][2],
                        ];
                        $joinFilter = ($use_published_filters) ? $this->getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';
                        $existsMatches[2] = 'SELECT id FROM '.$joinTipo->getInterAdminsTableName().' AS '.$table.
                        ' WHERE '.$joinFilter.$this->_resolveSqlClausesAlias($onClause, $use_published_filters).(($existsMatches[4]) ? ' AND ' : '');
                    } elseif (method_exists($options['model'], $joinNome)) {
                        // Metodo estilo Eloquent
                        $relationshipData = $options['model']->$joinNome()->getRelationshipData();

                        $joinTipo = $relationshipData['tipo'];

                        $joinFilter = ($use_published_filters) ? $this->getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';

                        $conditions = array_map(function ($x) use ($table) {
                                return $table.'.'.$x;
                            }, $relationshipData['conditions']);

                        $existsMatches[2] = 'SELECT id FROM '.$joinTipo->getInterAdminsTableName().' AS '.$table.
                            ' WHERE '.$joinFilter.implode(' AND ', $conditions).
                            ' AND '.$table.'.type_id = '.$joinTipo->type_id.''.
                            (($existsMatches[4]) ? ' AND ' : '');
                    }

                    $inicioRep = $inicio.$existsMatches[1].$existsMatches[2].$existsMatches[3];
                    $clause = $inicioRep.substr($clause, strlen($inicio.$existsMatches[0]));
                    $offset = strlen($inicioRep);

                    $ignoreJoinsUntil = $offset;
                    continue;
                }
            }

            if (!in_array($termo[0], ["'", '"', ":"]) && !is_numeric($termo) && !in_array(strtoupper($termo), $reserved)) {
                $len = strlen($termo);
                $table = 'main';
                if (strpos($termo, '.') !== false) {
                    list($table, $termo, $subtermo) = explode('.', $termo) + [2 => null];
                }
                if ($table === 'main') {
                    $campo = $this->_aliasToColumn($termo, $aliases);
                } else {
                    if (!isset($childrenArr)) {
                        $childrenArr = $this->getInterAdminsChildren();
                    }

                    // Joins com children
                    $joinNome = Str::studly($table);
                    // Support for old join alias: ChildrenLojas => Lojas
                    $joinNome = replace_prefix('Children', '', $joinNome);
                    if (isset($childrenArr[$joinNome]) || isset($childrenArr[$joinNome])) {
                        $joinTipo = Type::getInstance($childrenArr[$joinNome]['type_id'], [
                            'db' => $this->_db,
                            'default_namespace' => static::DEFAULT_NAMESPACE,
                        ]);

                        if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                            $options['from_alias'][] = $table;
                            $options['from'][] = ' LEFT JOIN '.$joinTipo->getInterAdminsTableName().
                                ' AS '.$table.' ON '.$table.'.parent_id = main.id'.
                                ' AND '.$table.'.type_id = '.$joinTipo->type_id;

                            $options['auto_group_flag'] = true;
                        }
                        $joinAliases = array_flip($joinTipo->getFieldsAlias());

                    // Joins com tags @todo Verificar jeito mais modularizado de fazer esses joins
                    } elseif ($table == 'tags') {
                        if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                            $options['from_alias'][] = $table;
                            $options['from'][] = ' LEFT JOIN '.$this->getDb()->getTablePrefix().'tags AS '.$table.
                                ' ON '.$table.'.parent_id = main.id';

                            $options['auto_group_flag'] = true;
                        }
                        $joinAliases = [];
                    } else {
                        $joinNome = isset($aliases[$table]) ? $aliases[$table] : $table;
                        // Permite utilizar relacionamentos no where sem ter usado o campo no fields
                        if (isset($options['joins'][$table])) {
                            if ($subtermo) {
                                $options['pre_joins'][$table] = true;
                            }
                            $joinTipo = $options['joins'][$table][1];
                        // Joins de select
                        } elseif (isset($aliases[$joinNome.'_id']) && isset($campos[$aliases[$joinNome.'_id']])) {
                            $joinNome = $aliases[$joinNome.'_id'];
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $this->_addJoinAlias($options, $table, $campos[$joinNome]);
                            }
                            $joinTipo = $this->getCampoTipo($campos[$joinNome]);
                        // Joins de select_multi
                        } elseif (isset($aliases[$joinNome.'_ids']) && isset($campos[$aliases[$joinNome.'_ids']])) {
                            $joinNome = $aliases[$joinNome.'_ids'];
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $this->_addJoinAlias($options, $table, $campos[$joinNome]);
                            }
                            $joinTipo = $this->getCampoTipo($campos[$joinNome]);
                        // Joins de special
                        } elseif (isset($campos[$joinNome])) {
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $this->_addJoinAlias($options, $table, $campos[$joinNome]);
                            }
                            $joinTipo = $this->getCampoTipo($campos[$joinNome]);
                        } elseif (isset($options['model']) && method_exists($options['model'], $joinNome)) {
                            $relationshipData = $options['model']->$joinNome()->getRelationshipData();

                            $joinTipo = $relationshipData['tipo'];
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $conditions = array_map(function ($x) use ($table) {
                                    return $table.'.'.$x;
                                }, $relationshipData['conditions']);

                                $options['from_alias'][] = $table;
                                $options['from'][] = ' LEFT JOIN '.$joinTipo->getInterAdminsTableName().
                                    ' AS '.$table.' ON '.implode(' AND ', $conditions).
                                    ' AND '.$table.'.type_id = '.$joinTipo->type_id;

                                $options['auto_group_flag'] = true;
                            }
                        } else {
                            throw new Exception('The field "'.$joinNome.'" cannot be used as a join ('.get_class($this).' - PK: '.$this->__toString().').');
                        }
                        if ($joinTipo instanceof Type) {
                            $joinAliases = array_flip($joinTipo->getFieldsAlias());
                        } else {
                            $joinAliases = [];
                        }
                    }
                    // TEMPORARIO FIXME, necessario melhor maneira
                    if ($subtermo) {
                        $subtable = $table.'__'.$termo;
                        $termo = $termo.'_id';

                        $subCampos = $joinTipo->getFields();
                        $subJoinTipo = $joinTipo->getCampoTipo($subCampos[$joinAliases[$termo]]);

                        // Permite utilizar relacionamentos no where sem ter usado o campo no fields
                        if (!in_array($subtable, $options['from_alias'])) {
                            $options['from_alias'][] = $subtable;
                            $options['from'][] = ' LEFT JOIN '.$subJoinTipo->getInterAdminsTableName().
                                ' AS '.$subtable.' ON '.$subtable.'.id = '.$table.'.'.$joinAliases[$termo].
                                ' AND '.$subtable.'.type_id = '.$subJoinTipo->type_id;
                        }

                        $table = $subtable;
                        $termo = $subtermo;
                        $joinAliases = array_flip($subJoinTipo->getFieldsAlias());
                    }
                    $campo = $this->_aliasToColumn($termo, $joinAliases);
                }
                $termo = $table.'.'.$campo;
                $clause = substr_replace($clause, $termo, $pos, $len);
            }
            $offset = $pos + strlen($termo);
        }

        return $clause;
    }

    /**
     * Resolves Aliases on $options fields.
     *
     * @param array  $options Same syntax as $options
     * @param array  $campos
     * @param array  $aliases 'alias' => 'field'
     * @param string $table   Table alias for the fields.
     *
     * @return array Revolved $fields.
     */
    protected function _resolveFieldsAlias(&$options = [], $table = 'main.')
    {
        $campos = &$options['campos'];
        $aliases = &$options['aliases'];
        $fields = $options['fields'];

        foreach ($fields as $key => $campo) {
            // Traduzindo 'join.campo' para 'join' => array('campo')
            if (is_string($campo) && strpos($campo, '.') !== false && strpos($campo, '(') === false) {
                list($join, $nome) = explode('.', $campo);
                $fields[$join][] = $nome;
                unset($fields[$key]);
            }
        }

        foreach ($fields as $join => $campo) {
            // Com join
            if (is_array($campo)) {
                //$nome = isset($aliases[$join]) ? $aliases[$join] : $join;

                // Join e Recursividade
                if (isset($options['joins']) && isset($options['joins'][$join])) {
                    $joinTipo = $options['joins'][$join][1];
                } elseif (strpos($join, 'select_multi_') === 0) {
                    $joinTipo = null; // Just ignore select_multi used on legacy code, lazy load them
                    /*
                    $fields[] = $table.$nome.(($table != 'main.') ? ' AS `'.$table.$nome.'`' : '');
                    // Processamento dos campos do select_multi é feito depois
                    $joinTipo = null;
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
                        if ($joinClasse !== 'tipo') {
                            $fields[$join][] = 'id';
                        }
                    }
                    $joinTipo = $this->getCampoTipo($campos[$nome]);
                }
                if ($joinTipo) {
                    $joinModel = Record::getInstance(0, ['default_namespace' => static::DEFAULT_NAMESPACE], $joinTipo);
                    $this->_resolveWildcard($fields[$join], $joinModel);

                    $joinOptions = [
                        'fields' => $fields[$join],
                        'fields_alias' => $options['fields_alias'],
                        'campos' => $joinTipo->getFields(),
                        'aliases' => array_flip($joinTipo->getFieldsAlias()),
                    ];
                    $this->_resolveFieldsAlias($joinOptions, $join.'.');
                    foreach ($joinOptions['fields'] as $joinField) {
                        array_push($fields, $joinField);
                    }
                }
                unset($fields[$join]);

            // Com função
            } elseif (strpos($campo, '(') !== false || strpos($campo, ' ') !== false) {
                if (strpos($campo, ' AS ') === false) {
                    $aggregateAlias = trim(strtolower(preg_replace('/[^[:alnum:]]/', '_', $campo)), '_');
                } else {
                    $parts = explode(' AS ', $campo);
                    $aggregateAlias = array_pop($parts);
                    $campo = implode(' AS ', $parts);
                }
                $fields[$join] = $this->_resolveSql($campo, $options, true).' AS `'.$table.$aggregateAlias.'`';
            // Sem join
            } else {
                $nome = $this->_aliasToColumn($campo, $aliases);
                if (strpos($nome, 'file_') === 0 && strpos($nome, '_text') === false) {
                    $fields[] = $table.$nome.'_text';
                }

                $fields[$join] = $table.$nome.(($table != 'main.') ? ' AS `'.$table.$nome.'`' : '');
            }
        }
        $options['fields'] = $fields;
    }

    protected function _aliasToColumn($alias, $aliases)
    {
        if (isset($aliases[$alias])) {
            return $aliases[$alias];
        }
        return $alias;
    }

    /**
     * Helper function to add a join.
     */
    protected function _addJoinAlias(array &$options, $alias, $campo, $table = 'main')
    {
        $joinTipo = $this->getCampoTipo($campo);
        if (!$joinTipo ) { //  || strpos($campo['tipo'], 'select_multi_') === 0
            throw new Exception('The field "'.$alias.'" cannot be used as a join ('.get_class($this).' - PK: '.$this->__toString().').');
        }
        $options['from_alias'][] = $alias; // Used as cache when resolving Where

        $column = $campo['tipo'];
        $xtra = $campo['xtra'];
        $isMulti = strpos($column, 'select_multi_') === 0 || in_array($xtra, FieldUtil::getSpecialMultiXtras());
        if (in_array($xtra, FieldUtil::getSelectTipoXtras()) || in_array($xtra, FieldUtil::getSpecialTipoXtras())) {
            $options['from'][] = ' LEFT JOIN '.$joinTipo->getTableName().
                ' AS '.$alias.' ON '.
                ($isMulti ?
                    'FIND_IN_SET('.$alias.'.type_id, '.$table.'.'.$column.')' :
                    $table.'.'.$column.' = '.$alias.'.type_id'
                );

            return 'tipo';
        } else {
            $options['from'][] = ' LEFT JOIN '.$joinTipo->getInterAdminsTableName().
                ' AS '.$alias.' ON '.
                ($isMulti ?
                    'FIND_IN_SET('.$alias.'.id, '.$table.'.'.$column.')' :
                    $table.'.'.$column.' = '.$alias.'.id'
                ).
                ' AND '.$alias.'.type_id = '.$joinTipo->type_id;

            return 'interadmin';
        }
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
                    if (strpos($campos[$field]['tipo'], 'select_multi_') === 0) {
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
        $this->deleted_at = date('Y-m-d H:i:s');
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
        $this->deleted_at = null;
        return $this->save();
    }

    /**
     * @param array $where FIXME temporário para wheres que eram com string
     */
    protected function _whereArrayFix(&$where)
    {
        if (is_string($where)) {
            $where = jp7_explode(' AND ', $where);
        } elseif (!$where) {
            $where = [];
        }
    }

    /**
     * Returns the Type for a field.
     *
     * @param object $campo
     *
     * @return Type
     */
    abstract public function getAttributesCampos();
    abstract public function getAttributesNames();
    abstract public function getAttributesAliases();
    abstract public function getAdminAttributes();
    abstract public function getTableName();

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getColumns()
    {
        $table = $this->getTableName();
        $cacheKey = 'columns,'.$this->_db.','.$table;
        return \Cache::remember($cacheKey, 5, function () use ($table) {
            $db = $this->getDb();
            $table = str_replace($db->getTablePrefix(), '', $table); // FIXME
            return $db->getSchemaBuilder()->getColumnListing($table);
        });
    }

    public static function getPublishedFilters($table, $alias)
    {
        $tableParts = explode('_', $table);
        $table = end($tableParts);
        // Tipos
        if ($table === 'types' && count($tableParts) === 3) {
            return $alias.".mostrar <> '' AND ".$alias.".deleted_at IS NULL AND ";
        // Tags
        } elseif ($table === 'tags' && count($tableParts) === 3) {
            // do nothing
        // Arquivos
        } elseif ($table === 'files') {
            return $alias.".mostrar <> '' AND ".$alias.".deleted_at IS NULL AND ";
        // Registros
        } else {
            $return = $alias.".date_publish <= '".date('Y-m-d H:i:59', Record::getTimestamp())."'".
                ' AND ('.$alias.".date_expire > '".date('Y-m-d H:i:00', Record::getTimestamp())."' OR ".$alias.".date_expire = '0000-00-00 00:00:00')".
                ' AND '.$alias.".char_key <> ''".
                ' AND '.$alias.".deleted_at IS NULL".
                ' AND ';
            if (config('interadmin.preview')) {
                $return .= '('.$alias.".publish <> '' OR ".$alias.'.parent_id > 0) AND ';
            }

            return $return;
        }
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
