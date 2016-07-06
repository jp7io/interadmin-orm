<?php

namespace Jp7\Interadmin;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Jp7\CollectionUtil;
use Jp7\TryMethod;
use Serializable;
use Exception;
use DB;
use SqlFormatter;

/**
 * Class which represents records on the table interadmin_{client name}.
 */
abstract class RecordAbstract implements Serializable
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

    protected $_db = null;

    /**
     * Magic get acessor.
     *
     * @param string $attributeName
     *
     * @return mixed
     */
    public function &__get($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        } else {
            return $null;
        }
    }
    /**
     * Magic set acessor.
     *
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value)
    {
        $mutator = 'set' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $mutator)) {
            return $this->$mutator($value);
        }
        if (is_string($value)) {
            $column = array_search($name, $this->getAttributesAliases()) ?: $name;
            $value = $this->getMutatedAttribute($column, $value);
        }
        if ($name === 'attributes') {
            throw new Exception("attributes is protected");
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

    public function __krumoTitle()
    {
        return $this->__toString().' - '.$this->getName();
    }

    public function __krumoProperties()
    {
        return $this->attributes;
    }

    public function serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_db']);

        return serialize($vars);
    }

    public function unserialize($data)
    {
        global $db;
        $vars = unserialize($data);
        foreach ($vars as $key => $value) {
            $this->$key = $value;
        }
        $this->_db = $db;
    }

    /**
     * Loads attributes if they are not set yet.
     *
     * @param array $attributes
     */
    public function loadAttributes($attributes, $fieldsAlias = true)
    {
        $fieldsToLoad = array_diff($attributes, array_keys($this->attributes));
        // Retrieving data
        if ($fieldsToLoad) {
            $options = [
                'fields' => (array) $fieldsToLoad,
                'fields_alias' => $fieldsAlias,
                'from' => $this->getTableName().' AS main',
                'where' => [$this->_primary_key.' = '.intval($this->{$this->_primary_key})],
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
        if (strpos($name, 'date_') === 0) {
            return new \Date($value);
        }
        if (strpos($name, 'file_') === 0 && strpos($name, '_text') === false && $value) {
            $class_name = static::DEFAULT_NAMESPACE.'FileField';
            if (!class_exists($class_name)) {
                $class_name = 'Jp7\\Interadmin\\FileField';
            }
            $file = new $class_name($value);
            $file->setParent($this);
            return $file;
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
                $this->attributes[$name] = $attributes[$name];
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
        return $this->_update($this->attributes);
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
        $this->_update([$attribute => $this->$attribute]);
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

        if ($this->$pk) {
            if ($db->table($table)->where($pk, $this->$pk)->update($valuesToSave) === false) {
                throw new Exception('Error while updating values in `'.$this->getTableName().'` '.
                    $db->getPdo()->errorCode(), print_r($valuesToSave, true));
            }
        } else {
            if ($db->table($table)->insert($valuesToSave) === false) {
                throw new Exception('Error while inserting data into `'.$this->getTableName().'` '.
                    $db->getPdo()->errorCode(), print_r($valuesToSave, true));
            }

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
                    $valuesToSave[$key] = (string) $value;
                    if ($value instanceof FileField) {
                        $valuesToSave[$key.'_text'] = $value->text;
                    }
                    break;
                case 'array':
                    $valuesToSave[$key] = implode(',', $value);
                    break;
                case 'NULL':
                    $valuesToSave[$key] = '';
                    break;
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
     *
     * @return ADORecordSet
     */
    protected function _executeQuery($options) // , &$select_multi_fields = []
    {
        //global $debugger;
        $db = $this->getDb();

        // Type casting
        if (!is_array($options['from'])) {
            $options['from'] = (array) $options['from'];
        }
        if (!is_array($options['where'])) {
            $options['where'] = (array) $options['where'];
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

        $joins = '';
        if (isset($options['joins']) && $options['joins']) {
            foreach ($options['joins'] as $alias => $join) {
                list($joinType, $tipo, $on) = $join;
                $table = $tipo->getInterAdminsTableName();
                $joins .= ' '.$joinType.' JOIN '.$table.' AS '.$alias.' ON '.
                    ($use_published_filters ? static::getPublishedFilters($table, $alias) : '').
                    $alias.'.id_tipo = '.$tipo->id_tipo.' AND '.$this->_resolveSql($on, $options, $use_published_filters);
            }
        }

        if (isset($options['skip'])) {
            $options['limit'] = $options['skip'].','.$options['limit'];
        }

        // Sql
        $sql = 'SELECT '.implode(',', $options['fields']).
            ' FROM '.array_shift($options['from']).
            $joins.
            ($options['from'] ? ' LEFT JOIN '.implode(' LEFT JOIN ', $options['from']) : '').
            ' WHERE '.$filters.$clauses.
            ((!empty($options['limit'])) ? ' LIMIT '.$options['limit'] : '');
        $rs = $db->select($sql);

        if (!$rs && !is_array($rs)) {
            $erro = $db->ErrorMsg();
            if (strpos($erro, 'Unknown column') === 0 && $options['aliases']) {
                $erro .= ". Available fields: \n\t\t- ".implode("\n\t\t- ", array_keys($options['aliases']));
            }

            throw new Exception($erro.' - SQL: '.$sql);
        }

        if (!empty($options['debug'])) {
            // $time = $debugger->getTime($options['debug']);
            echo SqlFormatter::format($sql);
        }
        // $select_multi_fields = isset($options['select_multi_fields']) ? $options['select_multi_fields'] : null;
        return $rs;
    }
    /**
     * Resolves the aliases on clause using regex.
     *
     * @param string $clause
     *
     * @return
     */
    protected function _resolveSqlClausesAlias(&$options = [], $use_published_filters)
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

    protected function _resolveSql($clause, &$options = [], $use_published_filters)
    {
        $campos = &$options['campos'];
        $aliases = &$options['aliases'];

        $quoted = '(\'((?<=\\\\)\'|[^\'])*\')';
        $keyword = '\b[a-zA-Z0-9_.]+\b';
        // not followed by "(" or " (", so it won't match "CONCAT(" or "IN ("
        $not_function = '(?![ ]?\()';
        $reserved = [
            'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'NOT', 'LIKE', 'IS',
            'NULL', 'DESC', 'ASC', 'BETWEEN', 'REGEXP', 'HAVING', 'DISTINCT', 'UNSIGNED', 'AS',
            'INTERVAL', 'DAY', 'WEEK', 'MONTH', 'YEAR', 'CASE', 'WHEN', 'THEN', 'END', 'BINARY'
        ];

        $offset = 0;
        $ignoreJoinsUntil = -1;

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

                    $joinNome = studly_case($table);
                    if (isset($childrenArr[$joinNome])) {
                        // Children
                        $joinTipo = Type::getInstance($childrenArr[$joinNome]['id_tipo'], [
                            'db' => $this->_db,
                            'default_class' => static::DEFAULT_NAMESPACE.'Type',
                        ]);

                        $joinFilter = ($use_published_filters) ? $this->getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';
                        $existsMatches[2] = 'SELECT id FROM '.$joinTipo->getInterAdminsTableName().' AS '.$table.
                        ' WHERE '.$joinFilter.$table.'.parent_id = main.id AND '.$table.'.id_tipo = '.$joinTipo->id_tipo.''.
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
                            ' AND '.$table.'.id_tipo = '.$joinTipo->id_tipo.''.
                            (($existsMatches[4]) ? ' AND ' : '');
                    }

                    $inicioRep = $inicio.$existsMatches[1].$existsMatches[2].$existsMatches[3];
                    $clause = $inicioRep.substr($clause, strlen($inicio.$existsMatches[0]));
                    $offset = strlen($inicioRep);

                    $ignoreJoinsUntil = $offset;
                    continue;
                }
            }

            if ($termo[0] != "'" && !is_numeric($termo) && !in_array(strtoupper($termo), $reserved)) {
                $len = strlen($termo);
                $table = 'main';
                if (strpos($termo, '.') !== false) {
                    @list($table, $termo, $subtermo) = explode('.', $termo);
                }
                if ($table === 'main') {
                    $campo = $this->_aliasToColumn($termo, $aliases);
                } else {
                    if (!isset($childrenArr)) {
                        $childrenArr = $this->getInterAdminsChildren();
                    }

                    // Joins com children
                    $joinNome = studly_case($table);
                    // Support for old join alias: ChildrenLojas => Lojas
                    $joinNome = replace_prefix('Children', '', $joinNome);
                    if (isset($childrenArr[$joinNome]) || isset($childrenArr[$joinNome])) {
                        $joinTipo = Type::getInstance($childrenArr[$joinNome]['id_tipo'], [
                            'db' => $this->_db,
                            'default_class' => static::DEFAULT_NAMESPACE.'Type',
                        ]);

                        if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                            $options['from_alias'][] = $table;
                            $options['from'][] = $joinTipo->getInterAdminsTableName().
                                ' AS '.$table.' ON '.$table.'.parent_id = main.id AND '.$table.'.id_tipo = '.$joinTipo->id_tipo;

                            $options['auto_group_flag'] = true;
                        }
                        $joinAliases = array_flip($joinTipo->getCamposAlias());

                    // Joins com tags @todo Verificar jeito mais modularizado de fazer esses joins
                    } elseif ($table == 'tags') {
                        if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                            $options['from_alias'][] = $table;
                            $options['from'][] = $this->getDb()->getTablePrefix().'tags AS '.$table.
                                ' ON '.$table.'.parent_id = main.id';

                            $options['auto_group_flag'] = true;
                        }
                        $joinAliases = [];
                    } else {
                        $joinNome = isset($aliases[$table]) ? $aliases[$table] : $table;
                        // Permite utilizar relacionamentos no where sem ter usado o campo no fields
                        if (isset($options['joins'][$table])) {
                            $joinTipo = $options['joins'][$table][1];
                        // Joins de select
                        } elseif (isset($campos[@$aliases[$joinNome.'_id']])) {
                            $joinNome = $aliases[$joinNome.'_id'];
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
                        } elseif (method_exists($options['model'], $joinNome)) {
                            $relationshipData = $options['model']->$joinNome()->getRelationshipData();

                            $joinTipo = $relationshipData['tipo'];
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $conditions = array_map(function ($x) use ($table) {
                                    return $table.'.'.$x;
                                }, $relationshipData['conditions']);

                                $options['from_alias'][] = $table;
                                $options['from'][] = $joinTipo->getInterAdminsTableName().
                                    ' AS '.$table.' ON '.implode(' AND ', $conditions).
                                    ' AND '.$table.'.id_tipo = '.$joinTipo->id_tipo;

                                $options['auto_group_flag'] = true;
                            }
                        } else {
                            throw new Exception('The field "'.$joinNome.'" cannot be used as a join ('.get_class($this).' - PK: '.$this->__toString().').');
                        }
                        $joinAliases = array_flip($joinTipo->getCamposAlias());
                    }
                    // TEMPORARIO FIXME, necessario melhor maneira
                    if ($subtermo) {
                        $subtable = $table.'__'.$termo;
                        $termo = $termo.'_id';

                        $subCampos = $joinTipo->getCampos();
                        $subJoinTipo = $joinTipo->getCampoTipo($subCampos[$joinAliases[$termo]]);

                        // Permite utilizar relacionamentos no where sem ter usado o campo no fields
                        if (!in_array($subtable, $options['from_alias'])) {
                            $options['from_alias'][] = $subtable;
                            $options['from'][] = $subJoinTipo->getInterAdminsTableName().
                                ' AS '.$subtable.' ON '.$subtable.'.id = '.$table.'.'.$joinAliases[$termo].' AND '.$subtable.'.id_tipo = '.$subJoinTipo->id_tipo;
                        }

                        $table = $subtable;
                        $termo = $subtermo;
                        $joinAliases = array_flip($subJoinTipo->getCamposAlias());
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
                $nome = isset($aliases[$join]) ? $aliases[$join] : $join;

                // Join e Recursividade
                if (isset($options['joins']) && isset($options['joins'][$join])) {
                    $joinTipo = $options['joins'][$join][1];
                } elseif (isset($aliases[$join.'_ids']) || strpos($join, 'select_multi_') === 0) {
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
                } else {
                    // Select
                    $nome = isset($aliases[$join.'_id']) ? $aliases[$join.'_id'] : $join;
                    $fields[] = $table.$nome.(($table != 'main.') ? ' AS `'.$table.$nome.'`' : '');
                    // Join e Recursividade
                    if (empty($options['from_alias']) || !in_array($join, (array) $options['from_alias'])) {
                        $joinClasse = $this->_addJoinAlias($options, $join, $campos[$nome]);
                        if ($joinClasse !== 'tipo') {
                            $fields[$join][] = 'id';
                            $fields[$join][] = 'id_slug';
                        }
                    }
                    $joinTipo = $this->getCampoTipo($campos[$nome]);
                }
                if ($joinTipo) {
                    $wildcardPos = array_search('*', $fields[$join]);
                    if ($wildcardPos !== false) {
                        unset($fields[$join][$wildcardPos]);
                        $fields[$join] = array_merge($fields[$join], $joinTipo->getCamposNames(), $joinTipo->getInterAdminsAdminAttributes());
                    }
                    $joinOptions = [
                        'fields' => $fields[$join],
                        'fields_alias' => $options['fields_alias'],
                        'campos' => $joinTipo->getCampos(),
                        'aliases' => array_flip($joinTipo->getCamposAlias()),
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
                    if (strpos($campo, 'file_') === 0) {
                        // necessário para quando o parametro fields está sem alias, mas o retorno está com alias
                        $file_campo = array_search($campo, $aliases);
                    } else {
                        $file_campo = $campo;
                    }
                    $fields[] = $table.$nome.'_text  AS `'.$file_campo.'.text`';
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
    protected function _addJoinAlias(&$options = [], $alias, $campo, $table = 'main')
    {
        $joinTipo = $this->getCampoTipo($campo);
        if (!$joinTipo || strpos($campo['tipo'], 'select_multi_') === 0) {
            throw new Exception('The field "'.$alias.'" cannot be used as a join ('.get_class($this).' - PK: '.$this->__toString().').');
        }
        $options['from_alias'][] = $alias; // Used as cache when resolving Where

        if (in_array($campo['xtra'], FieldUtil::getSelectTipoXtras()) || in_array($campo['xtra'], FieldUtil::getSpecialTipoXtras())) {
            $options['from'][] = $joinTipo->getTableName().
                ' AS '.$alias.' ON '.$table.'.'.$campo['tipo'].' = '.$alias.'.id_tipo';

            return 'tipo';
        } else {
            $options['from'][] = $joinTipo->getInterAdminsTableName().
                ' AS '.$alias.' ON '.$table.'.'.$campo['tipo'].' = '.$alias.'.id';

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
        $campos = &$options['campos'];
        $aliases = &$options['aliases'];
        if (empty($options['fields_alias'])) {
            $aliases = [];
        }
        if ($aliases) {
            $fields = array_flip($aliases);
        }
        $attributes = &$object->attributes;

        foreach ($row as $key => $value) {
            $parts = explode('.', $key);
            if (count($parts) == 1) {
                list($table, $field) = ['main', $parts[0]];
            } else {
                list($table, $field) = $parts;
            }
            if ($table == 'main') {
                $alias = isset($aliases[$field]) ? $aliases[$field] : $field;
                if (isset($attributes[$alias]) && is_object($attributes[$alias])) {
                    continue;
                }
                $attributes[$alias] = $object->getMutatedAttribute($field, $value);
                /*
                if (!empty($options['select_multi_fields'])) {
                    if (strpos($campos[$field]['tipo'], 'select_multi_') === 0) {
                        $multi_options = $options['select_multi_fields'][$alias];
                        if ($multi_options) {
                            CollectionUtil::getFieldsValues($value, $multi_options['fields'], $multi_options['fields_alias']);
                        }
                    }
                }
                */
            } else {
                $joinAlias = '';
                $join = isset($fields[$table]) ? $fields[$table] : $table;
                $joinTipo = isset($campos[$join]) ? $this->getCampoTipo($campos[$join]) : null;

                if (!$joinTipo) {
                    if (isset($fields[$table.'_id'])) {
                        $join = $fields[$table.'_id'];
                        $joinTipo = $this->getCampoTipo($campos[$join]);
                        if (!is_object(@$attributes[$table]) && $field === 'id' && $value) {
                            $attributes[$table] = Record::getInstance($value, [], $joinTipo);
                        }
                    } elseif (!empty($options['joins'][$table])) {
                        // $options['joins']
                        list($_joinType, $joinTipo, $_on) = $options['joins'][$table];
                        if (!is_object(@$attributes[$table])) {
                            $attributes[$table] = Record::getInstance(0, [], $joinTipo);
                        }
                    }
                }

                if ($joinTipo) {
                    $joinCampos = $joinTipo->getCampos();
                    if ($joinTipo->id_tipo == '0') {
                        $joinAlias = ''; // Tipos
                    } else {
                        $joinAlias = $joinTipo->getCamposAlias($field);
                    }
                }

                if (isset($attributes[$table]) && is_object($attributes[$table])) {
                    $subobject = $attributes[$table];
                    $alias = ($aliases && $joinAlias) ? $joinAlias : $field;
                    if (isset($subobject->attributes[$alias]) && is_object($subobject->attributes[$alias])) {
                        continue;
                    }
                    $subobject->$alias = $object->getMutatedAttribute($field, $value);
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
        if ($fields == '*') {
            $fields = [$fields];
        }
        if (is_array($fields) && in_array('*', $fields)) {
            unset($fields[array_search('*', $fields)]);

            $attributes = array_merge($object->getAttributesNames(), $object->getAdminAttributes());
            $attributes = array_intersect($attributes, $object->getColumns());

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

    /**
     * @param array $where
     *                     FIXME temporário para wheres que eram com string
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
        $dbName = $this->getDb()->getDatabaseName();
        $table = $this->getTableName();
        $cache = TipoCache::getInstance($dbName);

        if (!$columns = $cache->get($table)) {
            $columns = $this->_pdoColumnNames($table);
            $cache->set($table, $columns);
        }

        return $columns;
    }

    private function _pdoColumnNames($table)
    {
        $db = $this->getDb()->getPdo();

        $rs = $db->query('SELECT * FROM `'.$table.'` LIMIT 0');
        for ($i = 0; $i < $rs->columnCount(); $i++) {
            $col = $rs->getColumnMeta($i);
            $columns[] = $col['name'];
        }

        return $columns;
    }

    public static function getPublishedFilters($table, $alias)
    {
        global $s_session;
        
        // Tipos
        if (strpos($table, '_tipos') === (strlen($table) - strlen('_tipos'))) {
            return $alias.".mostrar <> '' AND ".$alias.".deleted_tipo = '' AND ";
        // Tags
        } elseif (strpos($table, '_tags') === (strlen($table) - strlen('_tags'))) {
            // do nothing
        // Arquivos
        } elseif (strpos($table, '_arquivos') === (strlen($table) - strlen('_arquivos'))) {
            return $alias.".mostrar <> '' AND ".$alias.".deleted = '' AND ";
        // Registros
        } else {
            $return = $alias.".date_publish <= '".date('Y-m-d H:i:59', Record::getTimestamp())."'".
                ' AND ('.$alias.".date_expire > '".date('Y-m-d H:i:00', Record::getTimestamp())."' OR ".$alias.".date_expire = '0000-00-00 00:00:00')".
                ' AND '.$alias.".char_key <> ''".
                ' AND '.$alias.".deleted = ''".
                ' AND ';
            if (config('interadmin.preview') && !$s_session['preview']) {
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
        return $this->_db ?: DB::connection();
    }
    /**
     * Sets the database object.
     *
     * @param ADOConnection $db
     */
    public function setDb(ConnectionInterface $db)
    {
        $this->_db = $db;
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
}
