<?php

class InterAdminOptionsBase
{
    protected $provider;
    protected $options;
    protected $allFields = true;

    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
    ];
    
    public function __construct(InterAdminTipo $provider)
    {
        $this->provider = $provider;
        $this->options = [
            'fields' => ['*'],
            'fields_alias' => true,
            'where' => [],
        ];
    }

    protected function invalidOperatorAndValue($operator, $value)
    {
        $isOperator = in_array($operator, $this->operators);
        return ($isOperator && $operator != '=' && is_null($value));
    }
    
    public function where($column, $operator = null, $value = null)
    {
        $where = [];
        if (is_array($column)) {
            // Hash = [a => 1, b => 2]
            foreach ($column as $key => $value) {
                if (is_bool($value)) {
                    $where[] = "$key ".($value ? "<> ''" : "= ''");
                } else {
                    $where[] = "$key = ".$this->_escapeParam($value);
                }
            }
        } else {
            if (func_num_args() == 2) {
                list($value, $operator) = [$operator, '='];
            } elseif (func_num_args() == 1 || $this->invalidOperatorAndValue($operator, $value)) {
                throw new \InvalidArgumentException('Value must be provided.');
            }
            if (!in_array(strtolower($operator), $this->operators, true)) {
                if (is_null($value)) {
                    // short circuit operator
                    list($operator, $value) = ['=', $operator];
                } else {
                    throw new \InvalidArgumentException('Invalid operator.');
                }
            }
            if (str_contains($column, ' ') || str_contains($column, '(')) {
                throw new \BadMethodCallException('Invalid column.');
            }
            $where[] = $this->_parseComparison($column, $operator, $value);
        }
        $this->options['where'] = array_merge($this->options['where'], $where);
        
        return $this;
    }
    
    protected function _parseComparison($column, $operator, $value)
    {
        if (is_null($value) && $operator == '=') {
            $operator = 'IS';
        }
        return $column.' '.$operator.' '.$this->_escapeParam($value);
    }

    public function whereRaw($where)
    {
        $this->options['where'][] = $where;
        return $this;
    }
    
    public function whereIn($column, $values)
    {
        $values = array_map([$this, '_escapeParam'], $values);
        $where = $column.' IN ('.implode(',', $values).')';

        $this->options['where'][] = $where;
        return $this;
    }

    public function whereFindInSet($column, $value)
    {
        $value = $this->_escapeParam($value);
        $where = 'FIND_IN_SET ('.$value.', '.$column.')';

        $this->options['where'][] = $where;
        return $this;
    }

    public function whereNotIn($column, $values)
    {
        $values = array_map([$this, '_escapeParam'], $values);
        $where = $column.' NOT IN ('.implode(',', $values).')';

        $this->options['where'][] = $where;
        return $this;
    }

    public function whereYear($column, $value)
    {
        $where = $this->_parseComparison('YEAR('.$column.')', '=', $value);
        
        $this->options['where'][] = $where;
        return $this;
    }

    public function whereMonth($column, $value)
    {
        $where =  $this->_parseComparison('MONTH('.$column.')', '=', $value);

        $this->options['where'][] = $where;
        return $this;
    }

    public function whereDay($column, $value)
    {
        $where =  $this->_parseComparison('DAY('.$column.')', '=', $value);

        $this->options['where'][] = $where;
        return $this;
    }
    
    protected function _escapeParam($value)
    {
        global $db;
        if (is_object($value)) {
            $value = $value->__toString();
        }
        if (is_string($value)) {
            $value = $db->qstr($value);
        }
        if (is_null($value)) {
            $value = 'NULL';
        }
        
        return $value;
    }

    public function fields($_)
    {
        $fields = is_array($_) ? $_ : func_get_args();
        if ($this->allFields) {
            $this->options['fields'] = [];
            $this->allFields = false;
        }

        $this->options['fields'] = array_merge($this->options['fields'], $fields);

        return $this;
    }

    public function join($alias, $tipo, $on)
    {
        $this->options['joins'][$alias] = ['INNER', $tipo, $on];

        return $this;
    }

    public function leftJoin($alias, $tipo, $on)
    {
        $this->options['joins'][$alias] = ['LEFT', $tipo, $on];

        return $this;
    }

    public function rightJoin($alias, $tipo, $on)
    {
        $this->options['joins'][$alias] = ['RIGHT', $tipo, $on];

        return $this;
    }

    public function limit($offset, $rows = null)
    {
        $limit = $offset.(is_null($rows) ? '' : ','.$rows);
        $this->options['limit'] = $limit;

        return $this;
    }

    public function group($group)
    {
        $this->options['group'] = $group;

        return $this;
    }

    public function order($_)
    {
        $order = func_get_args();
        $this->options['order'] = implode(',', $order);

        return $this;
    }

    public function debug($debug = true)
    {
        $this->options['debug'] = (bool) $debug;

        return $this;
    }

    public function published($filters = true)
    {
        $this->options['use_published_filters'] = (bool) $filters;

        return $this;
    }

    public function getOptionsArray()
    {
        return $this->options;
    }

    public function setOptionsArray(array $options)
    {
        $this->options = $options;
    }
    
    public function __call($method_name, $params)
    {
        $last = count($params) - 1;
        if (is_array($params[$last])) {
            $params[$last] = InterAdmin::mergeOptions($this->options, $params[$last]);
        } else {
            $params[] = $this->options;
        }

        $retorno = call_user_func_array([$this->provider, $method_name], $params);
        if ($retorno instanceof self) {
            $this->options = InterAdmin::mergeOptions($this->options, $retorno->getOptionsArray());

            return $this;
        }

        return $retorno;
    }
}
