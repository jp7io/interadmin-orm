<?php

namespace Jp7\InterAdmin;

use Illuminate\Support\Str;
use Exception;

/**
 * Compiles InterAdmin's alias-based clause syntax into SQL, materializing the joins it
 * needs along the way.
 *
 * A clause is written against a type's field ALIASES rather than its physical columns
 * ("titulo = 'x'", not "varchar_1 = 'x'"), and may reach through a relationship by
 * qualifying an alias with a join name ("loja.titulo"), which no caller has to declare:
 * the compiler appends the LEFT JOIN to $options['from'] the first time it sees one.
 *
 * The compiler is constructed with the record it compiles FOR, because resolving an alias
 * is a question only that record can answer -- and, for three of those questions, only its
 * runtime class can:
 *
 *   - {@see RecordAbstract::_aliasToColumn()}, which the legacy InterAdmin/InterAdminTipo
 *     subclasses override to accept a relation's name where its `_id`/`_ids` column is meant;
 *   - {@see RecordAbstract::getPublishedFilters()}, which Log overrides to opt out;
 *   - getInterAdminsChildren(), which exists on Type -- so a clause naming a child type only
 *     compiles when what is being queried is a type.
 *
 * That is why this is an object holding a record and not a set of pure functions, and it is
 * the reason the two collaborators that came out first ({@see PublishedFilter},
 * {@see CharBooleanRewriter}) are the only pure ones there were.
 *
 * @see RecordAbstract::_resolveSql() -- the entry point subclasses still call
 */
class SqlCompiler
{
    /**
     * SQL words the alias walk must leave alone. Anything else that looks like an
     * identifier is treated as an alias to resolve.
     */
    const RESERVED = [
        'SELECT', 'WHERE',
        'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'NOT', 'LIKE', 'IS',
        'NULL', 'DESC', 'ASC', 'BETWEEN', 'REGEXP', 'HAVING', 'DISTINCT', 'UNSIGNED', 'AS',
        'INTERVAL', 'DAY', 'WEEK', 'MONTH', 'YEAR', 'CASE', 'WHEN', 'THEN', 'END', 'BINARY',
        'HOUR', 'MINUTE', 'SECOND',
    ];

    protected \Jp7\InterAdmin\RecordAbstract $record;

    public function __construct(RecordAbstract $record)
    {
        $this->record = $record;
    }

    /**
     * Compiles WHERE, GROUP BY, HAVING and ORDER BY into one trailing SQL fragment.
     */
    public function clauses(array &$options, $use_published_filters): string
    {
        $resolvedWhere = $this->resolve($options['where'], $options, $use_published_filters);
        if (isset($options['order'])) {
            $resolvedOrder = $this->resolve($options['order'], $options, $use_published_filters);
        }
        // Group by para wheres com children, DISTINCT é usado para corrigir COUNT() com children
        $fields = $options['fields'] ?? [];
        $firstField = $fields ? reset($fields) : '';
        if (empty($options['group']) && strpos($firstField, 'DISTINCT') === false) {
            if (!empty($options['auto_group_flag'])) {
                $options['group'] = 'main.id';
            }
        }

        $clause = ((!empty($options['group'])) ? ' GROUP BY '.$options['group'] : '').
            ((!empty($options['having'])) ? ' HAVING '.implode(' AND ', $options['having']) : '');

        return $resolvedWhere.
            $this->resolve($clause, $options, $use_published_filters).
            ((isset($resolvedOrder)) ? ' ORDER BY '.$resolvedOrder : '');
    }

    /**
     * Finishes the FROM clause once the alias walk has appended whatever joins it needed:
     * splices the publishing predicates into each join's ON, then materializes the joins
     * declared explicitly through Query::join().
     *
     * Returns the main table and the main alias's predicates, which belong in the WHERE
     * rather than in an ON -- separately, because a DELETE names the main table twice.
     *
     * @return array{0: string, 1: string} [$from, $filters]
     */
    public function from(array &$options, $use_published_filters): array
    {
        $filters = '';
        if ($use_published_filters) {
            foreach ($options['from'] as $key => $from) {
                list($table, $alias) = explode(' AS ', $from);
                if ($alias == 'main') {
                    $filters = $this->record::getPublishedFilters($table, 'main');
                } else {
                    $joinArr = explode(' ON', $alias);
                    $options['from'][$key] = $table.' AS '.$joinArr[0].' ON '.$this->record::getPublishedFilters($table, $joinArr[0]).$joinArr[1];
                }
            }
        }

        $from = array_shift($options['from']); // main table
        if (isset($options['joins']) && $options['joins']) {
            $pre_joins = $options['pre_joins'] ?? [];
            foreach ($options['joins'] as $alias => $join) {
                @list($joinType, $tipo, $on, $typeless) = $join;
                if ($tipo === Type::class) {
                    $table = (new Type)->getTableName();
                } else {
                    $table = $tipo->getInterAdminsTableName();
                }
                $joinSql = ' '.$joinType.' JOIN '.$table.' AS '.$alias.' ON '.
                    ($use_published_filters ? $this->record::getPublishedFilters($table, $alias) : '');
                if (!$typeless) {
                    $joinSql .= $alias.'.id_tipo = '.$tipo->id_tipo.' AND ';
                }
                $preIndex = count($options['from']);
                $joinSql .= $this->resolve($on, $options, $use_published_filters);
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

        return [$from, $filters];
    }

    /**
     * Rewrites every alias in $clause to `table.column`, appending to $options['from'] any
     * join that rewriting turned out to need.
     */
    public function resolve($clause, array &$options, $use_published_filters)
    {
        $campos = &$options['campos'];
        $aliases = &$options['aliases'];

        $quoted = '(\'((?<=\\\\)\'|[^\'])*\'|"((?<=\\\\)"|[^"])*")';
        $keyword = ':?\b[a-zA-Z0-9_.]+\b';
        // not followed by "(" or " (", so it won't match "CONCAT(" or "IN ("
        $not_function = '(?![ ]?\()';

        $offset = 0;
        $ignoreJoinsUntil = -1;
        $insideFrom = false;

        $options += [
            'from_alias' => [],
            'joins' => [],
        ];

        while (preg_match('/('.$quoted.'|'.$keyword.$not_function.'|EXISTS)/', $clause, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            list($term, $pos) = $matches[1];
            // Resolvendo true e false para char
            if (CharBooleanRewriter::handles($term)) {
                list($clause, $offset) = CharBooleanRewriter::rewrite($clause, $term, $pos);
                continue;
            }

            if ($term === 'FROM') {
                $insideFrom = true;
            }
            if ($insideFrom) {
                if ($term === 'WHERE') { // joins are not supported here yet
                    $insideFrom = false;
                }
                $offset = $pos + strlen($term);
                continue;
            }

            // Joins com EXISTS
            if ($term == 'EXISTS') {
                $start = substr($clause, 0, $pos + strlen($term));
                $existsClause = substr($clause, $pos + strlen($term));
                if (preg_match('/^([\( ]+)('.$keyword.')([ ]+)(WHERE)?/', $existsClause, $existsMatches)) {
                    $table = $existsMatches[2];
                    // TODO unificar logica
                    if (!isset($childrenArr)) {
                        $childrenArr = $this->record->getInterAdminsChildren();
                    }

                    $joinNome = Str::studly($table);
                    if (isset($childrenArr[$joinNome])) {
                        // Children
                        $joinTipo = Type::getInstance($childrenArr[$joinNome]['id_tipo'], [
                            'db' => $this->record->getDbName(),
                            'default_namespace' => $this->record::DEFAULT_NAMESPACE,
                        ]);

                        $joinFilter = ($use_published_filters) ? $this->record::getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';
                        $existsMatches[2] = 'SELECT id FROM '.$joinTipo->getInterAdminsTableName().' AS '.$table.
                        ' WHERE '.$joinFilter.$table.'.parent_id = main.id AND '.$table.'.id_tipo = '.$joinTipo->id_tipo.''.
                        (($existsMatches[4]) ? ' AND ' : '');
                    } elseif ($table == 'tags') {
                        // Tags
                        $existsMatches[2] = 'SELECT id_tag FROM '.$this->record->getDb()->getTablePrefix().'tags AS '.$table.
                        ' WHERE '.$table.'.parent_id = main.id'.(($existsMatches[4]) ? ' AND ' : '');
                    } elseif (isset($options['joins'][$table])) {
                        // Joins custom
                        $joinTipo = $options['joins'][$table][1];
                        $onClause = [
                            'joins' => $options['joins'],
                            'where' => $options['joins'][$table][2],
                        ];
                        $joinFilter = ($use_published_filters) ? $this->record::getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';
                        $existsMatches[2] = 'SELECT id FROM '.$joinTipo->getInterAdminsTableName().' AS '.$table.
                        ' WHERE '.$joinFilter.$this->clauses($onClause, $use_published_filters).(($existsMatches[4]) ? ' AND ' : '');
                    } elseif (isset($options['model']) && method_exists($options['model'], $joinNome)) {
                        // Metodo estilo Eloquent
                        $relationshipData = $options['model']->$joinNome()->getRelationshipData();

                        $joinTipo = $relationshipData['tipo'];

                        $joinFilter = ($use_published_filters) ? $this->record::getPublishedFilters($joinTipo->getInterAdminsTableName(), $table) : '';

                        $conditions = array_map(function ($x) use ($table): string {
                                return $table.'.'.$x;
                            }, $relationshipData['conditions']);

                        $existsMatches[2] = 'SELECT id FROM '.$joinTipo->getInterAdminsTableName().' AS '.$table.
                            ' WHERE '.$joinFilter.implode(' AND ', $conditions).
                            ' AND '.$table.'.id_tipo = '.$joinTipo->id_tipo.''.
                            (($existsMatches[4]) ? ' AND ' : '');
                    }

                    $startRep = $start.$existsMatches[1].$existsMatches[2].$existsMatches[3];
                    $clause = $startRep.substr($clause, strlen($start.$existsMatches[0]));
                    $offset = strlen($startRep);

                    $ignoreJoinsUntil = $offset;
                    continue;
                }
            }

            if (!in_array($term[0], ["'", '"', ":"]) && !is_numeric($term) && !in_array(strtoupper($term), static::RESERVED)) {
                $len = strlen($term);
                $table = 'main';
                // Reset per term: both survive an iteration otherwise, so a term that skips
                // the branch assigning them would compile a join off the PREVIOUS term's type.
                $subTerm = null;
                $joinTipo = null;
                if (strpos($term, '.') !== false) {
                    list($table, $term, $subTerm) = explode('.', $term) + [2 => null];
                }
                if ($table === 'main') {
                    $column = $this->record->_aliasToColumn($term, $aliases);
                } else {
                    if (!isset($childrenArr)) {
                        $childrenArr = $this->record->getInterAdminsChildren();
                    }

                    // Joins com children
                    $joinNome = Str::studly($table);
                    // Support for old join alias: ChildrenLojas => Lojas
                    $joinNome = replace_prefix('Children', '', $joinNome);
                    if (isset($childrenArr[$joinNome])) {
                        $joinTipo = Type::getInstance($childrenArr[$joinNome]['id_tipo'], [
                            'db' => $this->record->getDbName(),
                            'default_namespace' => $this->record::DEFAULT_NAMESPACE,
                        ]);

                        if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                            $options['from_alias'][] = $table;
                            $options['from'][] = ' LEFT JOIN '.$joinTipo->getInterAdminsTableName().
                                ' AS '.$table.' ON '.$table.'.parent_id = main.id'.
                                ' AND '.$table.'.id_tipo = '.$joinTipo->id_tipo;

                            $options['auto_group_flag'] = true;
                        }
                        $joinAliases = array_flip($joinTipo->getFieldAliases());

                    // Joins com tags @todo Verificar jeito mais modularizado de fazer esses joins
                    } elseif ($table == 'tags') {
                        if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                            $options['from_alias'][] = $table;
                            $options['from'][] = ' LEFT JOIN '.$this->record->getDb()->getTablePrefix().'tags AS '.$table.
                                ' ON '.$table.'.parent_id = main.id';

                            $options['auto_group_flag'] = true;
                        }
                        $joinAliases = [];
                    } else {
                        $joinNome = isset($aliases[$table]) ? $aliases[$table] : $table;
                        // Permite utilizar relacionamentos no where sem ter usado o campo no fields
                        if (isset($options['joins'][$table])) {
                            if ($subTerm) {
                                $options['pre_joins'][$table] = true;
                            }
                            $joinTipo = $options['joins'][$table][1];
                        // Joins de select
                        } elseif (isset($aliases[$joinNome.'_id']) && isset($campos[$aliases[$joinNome.'_id']])) {
                            $joinNome = $aliases[$joinNome.'_id'];
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $this->addJoin($options, $table, $campos[$joinNome]);
                            }
                            $joinTipo = $this->record->getFieldType($campos[$joinNome]);
                        // Joins de select_multi
                        } elseif (isset($aliases[$joinNome.'_ids']) && isset($campos[$aliases[$joinNome.'_ids']])) {
                            $joinNome = $aliases[$joinNome.'_ids'];
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $this->addJoin($options, $table, $campos[$joinNome]);
                            }
                            $joinTipo = $this->record->getFieldType($campos[$joinNome]);
                        // Joins de special
                        } elseif (isset($campos[$joinNome])) {
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $this->addJoin($options, $table, $campos[$joinNome]);
                            }
                            $joinTipo = $this->record->getFieldType($campos[$joinNome]);
                        } elseif (isset($options['model']) && method_exists($options['model'], $joinNome)) {
                            $relationshipData = $options['model']->$joinNome()->getRelationshipData();

                            $joinTipo = $relationshipData['tipo'];
                            if ($offset > $ignoreJoinsUntil && !in_array($table, $options['from_alias'])) {
                                $conditions = array_map(function ($x) use ($table): string {
                                    return $table.'.'.$x;
                                }, $relationshipData['conditions']);

                                $options['from_alias'][] = $table;
                                $options['from'][] = ' LEFT JOIN '.$joinTipo->getInterAdminsTableName().
                                    ' AS '.$table.' ON '.implode(' AND ', $conditions).
                                    ' AND '.$table.'.id_tipo = '.$joinTipo->id_tipo;

                                $options['auto_group_flag'] = true;
                            }
                        } else {
                            throw new Exception('The field "'.$joinNome.'" cannot be used as a join ('.get_class($this->record).' - PK: '.$this->record->__toString().').');
                        }
                        if ($joinTipo instanceof Type) {
                            $joinAliases = array_flip($joinTipo->getFieldAliases());
                        } else {
                            $joinAliases = [];
                        }
                    }
                    // TEMPORARIO FIXME, necessario melhor maneira
                    if ($subTerm) {
                        if (!$joinTipo instanceof Type) {
                            throw new Exception('The field "'.$table.'.'.$term.'" cannot be used as a join ('.get_class($this->record).' - PK: '.$this->record->__toString().').');
                        }
                        $subtable = $table.'__'.$term;
                        $term = $term.'_id';

                        $subCampos = $joinTipo->getFields();
                        $subJoinTipo = $joinTipo->getFieldType($subCampos[$joinAliases[$term]]);

                        // Permite utilizar relacionamentos no where sem ter usado o campo no fields
                        if (!in_array($subtable, $options['from_alias'])) {
                            $options['from_alias'][] = $subtable;
                            $options['from'][] = ' LEFT JOIN '.$subJoinTipo->getInterAdminsTableName().
                                ' AS '.$subtable.' ON '.$subtable.'.id = '.$table.'.'.$joinAliases[$term].
                                ' AND '.$subtable.'.id_tipo = '.$subJoinTipo->id_tipo;
                        }

                        $table = $subtable;
                        $term = $subTerm;
                        $joinAliases = array_flip($subJoinTipo->getFieldAliases());
                    }
                    $column = $this->record->_aliasToColumn($term, $joinAliases);
                }
                $term = $table.'.'.$column;
                $clause = substr_replace($clause, $term, $pos, $len);
            }
            $offset = $pos + strlen($term);
        }

        return $clause;
    }

    /**
     * Appends the LEFT JOIN a relation field needs, and reports which table it landed on:
     * 'tipo' for a join to tipos, 'interadmin' for one to a records table.
     */
    public function addJoin(array &$options, $alias, array $field, $table = 'main'): string
    {
        $joinTipo = $this->record->getFieldType($field);
        if (!$joinTipo ) { //  || strpos($field['tipo'], 'select_multi_') === 0
            throw new Exception('The field "'.$alias.'" cannot be used as a join ('.get_class($this->record).' - PK: '.$this->record->__toString().').');
        }
        $options['from_alias'][] = $alias; // Used as cache when resolving Where

        $column = $field['tipo'];
        $xtra = $field['xtra'];
        $isMulti = strpos($column, 'select_multi_') === 0 || in_array($xtra, FieldUtil::getSpecialMultiXtras());
        if (in_array($xtra, FieldUtil::getSelectTipoXtras()) || in_array($xtra, FieldUtil::getSpecialTipoXtras())) {
            $options['from'][] = ' LEFT JOIN '.$joinTipo->getTableName().
                ' AS '.$alias.' ON '.
                ($isMulti ?
                    'FIND_IN_SET('.$alias.'.id_tipo, '.$table.'.'.$column.')' :
                    $table.'.'.$column.' = '.$alias.'.id_tipo'
                );

            return 'tipo';
        } else {
            $options['from'][] = ' LEFT JOIN '.$joinTipo->getInterAdminsTableName().
                ' AS '.$alias.' ON '.
                ($isMulti ?
                    'FIND_IN_SET('.$alias.'.id, '.$table.'.'.$column.')' :
                    $table.'.'.$column.' = '.$alias.'.id'
                ).
                ' AND '.$alias.'.id_tipo = '.$joinTipo->id_tipo;

            return 'interadmin';
        }
    }
}
