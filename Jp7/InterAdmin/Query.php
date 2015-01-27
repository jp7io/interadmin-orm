<?php

namespace Jp7\Interadmin;
use InterAdminTipo, InterAdmin, BadMethodCallException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Query extends Query\Base {
	
	public function type() {
		return $this->provider;
	}
	
	public function create(array $attributes = array()) {
		return $this->provider->deprecated_createInterAdmin($attributes);
	}
	
	protected function _isChar($field) {
		$aliases = array_flip($this->provider->getCamposAlias());
		if (isset($aliases[$field])) {
			return strpos($aliases[$field], 'char_') === 0;
		} else {
			return strpos($field, 'char_') === 0;
		}
	}
	
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
				$joins[] = [$tableLeft, $type, "{$tableLeft}.{$relationship} = {$tableRight}id"];
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
			$this->where($tag->getTagFilters());
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
	
	public function find($id) {
		if (func_num_args() != 1) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 1.');
		
		if (is_array($id)) {
			throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use all() instead of find().');
		}
		
		if (is_string($id) && !is_numeric($id)) {
			$this->_whereHash(['id_slug' => $id]);
		} else {
			$this->_whereHash(['id' => $id]);
		}
		
		return $this->provider->findFirst(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	public function lists($column, $key = null) {
		$array = $this->provider->deprecatedFind(array(
			'fields' => array_filter([$column, $key]),
		) + $this->options);
		
		return array_pluck($array, $column, $key);
	}
	
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
	
	public function findFirst() {
		throw new BadMethodCallException('Use first() instead of findFirst().');
	}
	
	public function __call($method_name, $params) {
		// Scope support
		if ($classname = $this->provider->class) {
			// Cria instancia para simular comportamento do Eloquent
			$instance = new $classname(0);  
			
			array_unshift($params, $this);
			$method_name = 'scope' . ucfirst($method_name);
			if (!method_exists($instance, $method_name)) {
				throw new BadMethodCallException('Method ' . $method_name .  ' does not exist.');
			}			
			$query = call_user_func_array([$instance, $method_name], $params);
			if (!$query instanceof self) {
				throw new BadMethodCallException('Method ' . $method_name . ' should return instance of ' . __CLASS__);
			}
			return $query;
		}
		throw new BadMethodCallException('Unsupported method ' . $method_name);
	}
	
}