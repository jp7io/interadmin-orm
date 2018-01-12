<?php

namespace Jp7\Interadmin;

use BadMethodCallException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Query extends Query\BaseQuery
{
    protected $model = null;

    /**
     * @return Type
     */
    public function type()
    {
        return $this->provider;
    }

    public function class($class)
    {
        $this->options['class'] = $class;
        return $this;
    }

    protected function providerFind($options)
    {
        if (empty($options['class'])) {
            // performance, check class once and not for every instance
            $recordClass = $this->provider->getRecordClass();
            if (class_exists($recordClass)) {
                $options['class'] = $recordClass;
            }
        }

        return $this->provider->deprecatedFind($options);
    }

    /**
     * Returns a instance with id 0, to get scopes, rules, and so on.
     *
     * @return Record
     */
    public function getModel()
    {
        if (is_null($this->model)) {
            $defaultNamespace = constant(get_class($this->provider).'::DEFAULT_NAMESPACE');
            $this->model = Record::getInstance(0, ['default_namespace' => $defaultNamespace], $this->provider);
        }
        return $this->model;
    }

    /**
     * Create a record without saving.
     *
     * @return Record
     */
    public function build(array $attributes = [])
    {
        return $this->provider->deprecated_createInterAdmin($attributes);
    }

    /**
     * Create and save a record.
     *
     * @return Record
     */
    public function create(array $attributes = [])
    {
        return $this->build($attributes)->save();
    }

    /**
     * Example: TipoDeCurso::joinThrough('Ci_Escola', 'escola.cursos.tipo');.
     *
     * @param string|Type $className
     * @param string $relationshipPath
     *
     * @throws BadMethodCallException
     *
     * @return \Jp7\Interadmin\Query
     */
    public function joinThrough($className, $relationshipPath)
    {
        $type = $this->_resolveType($className);

        $path = explode('.', $relationshipPath);
        $tableLeft = array_shift($path);
        if (!$path) {
            throw new BadMethodCallException('Bad relationship path: '.$relationshipPath);
        }

        $joins = [];

        while ($relationship = array_shift($path)) {
            $relationshipData = $type->getRelationshipData($relationship);
            $tableRight = (empty($path)) ? '' : $relationship.'.';

            if ($relationshipData['type'] == 'children') {
                $joins[] = [$tableLeft, $type, "{$tableLeft}.id = {$tableRight}parent_id"];
            } else {
                $joins[] = [$tableLeft, $type, "{$tableLeft}.{$relationship}_id = {$tableRight}id"];
            }

            $tableLeft = $relationship;
            $type = $relationshipData['tipo'];
        }

        foreach (array_reverse($joins) as $join) {
            $this->join($join[0], $join[1], $join[2]);
        }

        return $this;
    }

    public function taggedWith()
    {
        foreach (func_get_args() as $tag) {
            $this->whereHas('tags', $tag->getTagFilters());
        }

        return $this;
    }

    public function count()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->provider->deprecatedCount($this->options);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function sum($column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    protected function aggregate($function, $columns)
    {
        $column = reset($columns);
        $result = $this->provider->deprecated_aggregate($function, $column, $this->options);

        if ($result) {
            return reset($result);
        }
    }

    /**
     * @param array $values
     * @return int
     */
    public function update(array $values)
    {
        return $this->provider->deprecated_updateInterAdmins($values, $this->options);
    }

    /**
     * Remove permanently from the database.
     */
    public function forceDelete()
    {
        return $this->provider->deprecated_deleteInterAdminsForever($this->options);
    }

    /**
     * @param $id string|int
     * @return Record|null
     */
    public function find($id)
    {
        if (func_num_args() != 1) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 1.');
        }
        if (is_array($id)) {
            throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use get() instead of find().');
        }
        if (!$id) {
            return null; // save a query
        }
        if (is_string($id) && !is_numeric($id)) {
            $this->options['where'][] = $this->_parseComparison('id_slug', '=', $id);
        } else {
            $this->options['where'][] = $this->_parseComparison('id', '=', $id);
        }

        return $this->first();
    }

    public function findMany($ids)
    {
        if (!$ids) {
            return jp7_collect();  // save a query
        }
        $sample = reset($ids);
        if (is_string($sample) && !is_numeric($sample)) {
            $key = 'id_slug';
        } else {
            $key = 'id';
        }

        $this->whereIn($key, $ids);

        return $this->providerFind($this->options);
    }

    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            throw new ModelNotFoundException('Unable to find a record with id: '.$id);
        }

        return $result;
    }

    public function save(Record $model)
    {
        if (!$this->provider->getParent() instanceof Record) {
            throw new BadMethodCallException('This method can only save children to a parent.');
        }
        $model->setParent($this->provider->getParent());
        return $model->save() ? $model : false;
    }

    /**
     * Attach a collection of models to the parent instance.
     *
     * @param  \Traversable|array  $models
     * @return \Traversable|array
     */
    public function saveMany($models)
    {
        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }

    public function __call($method_name, $params)
    {
        // Scope support
        if ($model = $this->getModel()) {
            $scope = 'scope'.ucfirst($method_name);
            if (method_exists($model, $scope)) {
                array_unshift($params, $this);

                return call_user_func_array([$model, $scope], $params);
            }
        }

        return parent::__call($method_name, $params);
    }
}
