<?php

namespace Jp7\Interadmin\Relation;

use Jp7\Interadmin\Query;
use InterAdmin;

class HasMany {
	private $record;
	private $className;
	private $foreign_key;
	private $local_key;
	private $query;

	public function __construct(InterAdmin $record, $className, $foreign_key, $local_key) {
		$this->record = $record;
		$this->className = $className;
		$this->foreign_key = $foreign_key;
		$this->local_key = $local_key;
	}

	public function getRelationshipData() {
		$type = call_user_func([$this->className, 'type']);
		$aliases = $type->getCamposAlias();
		$conditions = [
			// 'cursos.sede = id'
			array_search($this->foreign_key, $aliases) . ' = main.' . $this->local_key
		];
		if ($this->query) {
			$where = $this->query->getOptionsArray()['where'];
			array_shift($where);
			$conditions = array_merge($conditions, $where);
		}
		return array(
			'tipo' => $type,
			'conditions' => $conditions
		);
	}

	public function query() {
		if (!$this->query) {
			$this->query = call_user_func([$this->className, 'query']);
			$local_key = $this->local_key;
			$this->query->where($this->foreign_key, $this->record->$local_key);
		}
		return $this->query;
	}

	public function __call($method, $arguments) {
		$response = call_user_func_array([$this->query(), $method], $arguments);
		if ($response instanceof Query) {
			return $this;
		}
		return $response;
	}
}