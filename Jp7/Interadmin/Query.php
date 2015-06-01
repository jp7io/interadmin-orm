<?php

namespace Jp7\Interadmin;
use InterAdminTipo, InterAdmin, BadMethodCallException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Query extends Query\Base {
	
	protected $model = null;

	public function type() {
		return $this->provider;
	}
	
	/**
	 * Returns a instance with id 0, to get scopes, rules, and so on.
	 */
	public function getModel() {
		if (is_null($this->model)) {
			if ($classname = $this->provider->class) {
				$this->model = new $classname(0);
				$this->model->setType($this->provider);
			}
		}
		return $this->model;
	}
	
	/**
	 * Create a record without saving.
	 */	
	public function build(array $attributes = array()) {
		return $this->provider->deprecated_createInterAdmin($attributes);
	}
	
	/**
	 * Create and save a record.
	 */	
	public function create(array $attributes = array()) {
		return $this->build($attributes)->save();
	}
		
	/**
	 * Set deleted = 'S' and update the records.
	 */
	public function delete() {
		return $this->provider->deprecated_deleteInterAdmins($this->options);
	}
	
	/**
	 * Remove permanently from the database.
	 */
	public function forceDelete() {
		return $this->provider->deprecated_deleteInterAdminsForever($this->options);
	}
	
	protected function _isChar($field) {
		if (str_contains($field, '.')) {
			list($relationship, $field) = explode('.', $field);
			
			$data = $this->provider->getRelationshipData($relationship);
			
			$type = $data['tipo'];
		} else {
			$type = $this->provider;
		}
		
		if (in_array($field, ['deleted', 'publish'])) {
			return true;
		}
		
		$aliases = array_flip($type->getCamposAlias());
		if (isset($aliases[$field])) {
			return strpos($aliases[$field], 'char_') === 0;
		} else {
			return strpos($field, 'char_') === 0;
		}
	}
	
	/**
	 * Example: TipoDeCurso::joinThrough('Ci_Escola', 'escola.cursos.tipo');
	 * 
	 * @param string $className
	 * @param string $relationshipPath
	 * @throws BadMethodCallException
	 * @return \Jp7\Interadmin\Query
	 */
	public function joinThrough($className, $relationshipPath) {
		$type = $this->_resolveType($className);
		
		$path = explode('.', $relationshipPath);
		$tableLeft = array_shift($path);
		if (!$path) {
			throw new BadMethodCallException('Bad relationship path: ' . $relationshipPath);
		}
		
		$joins = array();
		
		while ($relationship = array_shift($path)) {
			$relationshipData = $type->getRelationshipData($relationship);
			$tableRight = (empty($path)) ? '' : $relationship . '.';
			
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

	public function taggedWith() {
		foreach (func_get_args() as $tag) {
			$this->whereHas('tags', $tag->getTagFilters());
		}
		return $this;
	}

	/**
	 * @return InterAdmin[]
	 */
	public function all() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->provider->deprecatedFind($this->options);
	}
	
	public function first() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->provider->findFirst(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	public function count() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->provider->count(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	/**
	 * Retrieve the minimum value of a given column.
	 *
	 * @param  string  $column
	 * @return mixed
	 */
	public function min($column)
	{
		return $this->aggregate(__FUNCTION__, array($column));
	}

	/**
	 * Retrieve the maximum value of a given column.
	 *
	 * @param  string  $column
	 * @return mixed
	 */
	public function max($column)
	{
		return $this->aggregate(__FUNCTION__, array($column));
	}

	/**
	 * Retrieve the sum of the values of a given column.
	 *
	 * @param  string  $column
	 * @return mixed
	 */
	public function sum($column)
	{
		$result = $this->aggregate(__FUNCTION__, array($column));

		return $result ?: 0;
	}

	/**
	 * Retrieve the average of the values of a given column.
	 *
	 * @param  string  $column
	 * @return mixed
	 */
	public function avg($column)
	{
		return $this->aggregate(__FUNCTION__, array($column));
	}
	
	protected function aggregate($function, $columns) {
		$column = reset($columns);
		$result = $this->provider->deprecated_aggregate($function, $column, $this->options);
		
		if ($result) {
			return reset($result);
		}
	}
	
	public function find($id) {
		if (func_num_args() != 1) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 1.');
		
		if (is_array($id)) {
			throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use all() instead of find().');
		}
		
		if (is_string($id) && !is_numeric($id)) {
			$this->options['where'][] = $this->_parseComparison('id_slug', '=', $id);
		} else {
			$this->options['where'][] = $this->_parseComparison('id', '=', $id);
		}
		
		return $this->provider->findFirst(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	public function findMany($ids) {
		$sample = reset($ids);
		if (is_string($sample) && !is_numeric($sample)) {
			$key = 'id_slug';
		} else {
			$key = 'id';
		}
		
		$this->whereIn($key, $ids);
		return $this->provider->deprecatedFind($this->options);
	}
	
	public function lists($column, $key = null) {
		$array = $this->provider->deprecatedFind(array(
			'fields' => array_filter([$column, $key]),
		) + $this->options);
		
		return array_pluck($array, $column, $key);
	}
	
	/**
	 * List to be used on json, with {key: 1, value: 'Lorem'}
	 */
	public function jsonList($column, $key) {
		$items = $this->provider->deprecatedFind(array(
			'fields' => array_filter([$column, $key]),
		) + $this->options);
		
		return $items->jsonList($column, $key);
	}
	
	/**
	 * Just like lists, but returns a collection.
	 */
	public function collect($column) {
		return new Collection($this->lists($column));
	}
	
	public function findOrFail($id) {
		$result = $this->find($id);
		if (!$result) {
			throw new ModelNotFoundException('Unable to find a record with id: ' . $id);
		}
		return $result;
	}
	
	public function __call($method_name, $params) {
		// Scope support
		if ($model = $this->getModel()) {
			$scope = 'scope' . ucfirst($method_name);
			if (method_exists($model, $scope)) {
				array_unshift($params, $this);
				return call_user_func_array([$model, $scope], $params);
			}			
		}
		return parent::__call($method_name, $params);
	}
	
}