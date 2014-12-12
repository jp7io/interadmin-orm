<?php

class Jp7_InterAdmin_Soap_Options {
	
	public function getArray() {
		$options = array();
		if ($this) {
			// Where
			if ($this->where) {
				$where = '(' . $this->where . ')';
			}
			// Fields
			$fields = array();
			if ($this->fields) {
				$fields = jp7_explode(',', $this->fields);
				
				if (in_array('*', $fields)) {
					$fields = array_merge($fields, array(
						'parent_id',
						'date_insert',
						'date_modify',
						'date_publish',
						'deleted',
						'publish'
					));
				}
			}
			// Montagem
			$options = array(
				'fields' => $fields,
				'where' => jp7_explode(',', $where),
				'limit' => $this->limit
			);
			foreach ($options['fields'] as $key => $field) {
				if (strpos($field, '.')) {
					list($join, $joinField) = explode('.', $field);
					$options['fields'][$join][] = $joinField;
					$options['fields'][$key] = $join;
				}
			}
		}
		return $options;
	}
}