<?php

class Jp7_InterAdmin_Soap {
	
	public static function isDynamicClass($type) {
		return preg_match('/^([a-zA-Z]*)_([0-9]*)$/', $type);
	}
	
	public static function getClassTipo($type) {
		if (self::isDynamicClass($type)) {
			$id_tipo = preg_replace('/[a-zA-Z_]*/', '', $type);
			$tipo = new InterAdminTipo($id_tipo);
		} else {
			$tipoName = $type . 'Tipo';
			$tipo = new $tipoName();
		}
		return $tipo;
	}
	
	public static function getFaultXml($message) {
		return <<<STR
		<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
			<SOAP-ENV:Body>
			  <SOAP-ENV:Fault>
			     <faultcode>Receiver</faultcode>
			     <faultstring>$message</faultstring>
			  </SOAP-ENV:Fault>
			</SOAP-ENV:Body>
		</SOAP-ENV:Envelope>
STR;
	}
	
	protected static function _prepareOptions($arg) {
		$options = array();
		if ($arg) {
			if ($arg->where) {
				$where = '(' . $arg->where . ')';
			}
			$fields = array();
			if ($arg->fields) {
				$fields = jp7_explode(',', $arg->fields);
			}
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
			$options = array(
				'fields' => $fields,
				'where' => jp7_explode(',', $where),
				'limit' => $arg->limit
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
	
	/**
	 * Função que age como proxy entre a chamada e o método real.
	 * 
	 * @param string $methodName
	 * @param array $args
	 * @return mixed
	 */
	public function __call($methodName, $args) {
		if (strpos($methodName, 'get') === 0) {
			$options = self::_prepareOptions($args[0]);
			
			// Por padrão só pega os publicados
			$options['use_published_filters'] = true;
			$options['fields_alias'] = true;
			
			if (strpos($methodName, 'getFirst') === 0) {
				$className = substr($methodName, strlen('getFirst'));
				$result = $this->getFirst($className, $options);
			} elseif (strpos($methodName, 'getAll') === 0) {
				$className = substr($methodName, strlen('getAll'));
				$result = $this->getAll($className, $options);
			} else { 
				$className = substr($methodName, strlen('get'));
				$result = $this->get($className, $options);
			}
		}
		return array($methodName . 'Result' => $result);
	}
}