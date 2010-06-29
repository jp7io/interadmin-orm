<?php

class Jp7_InterAdmin_Soap {
	/**
	 * Returna todos os registros publicados.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	protected function get($className, $options = array()) {
		$tipoName = $className . 'Tipo';
		if (!class_exists($className) || !class_exists($tipoName)) {
			throw new Jp7_InterAdmin_Soap_Exception("Class is not supported: $className");
		}
		
		try {
			$tipo = new $tipoName();
			
			$records = $tipo->getInterAdmins($options);
			foreach ($records as $key => $record) {
				foreach ($record->attributes as $key2 => $value) {
					if ($value instanceof InterAdminAbstract) {
						$record->attributes[$key2] = $value->attributes;
					} elseif ($value instanceof Jp7_Date) {
						if ($value->isValid()) {
							$record->attributes[$key2] = $value->format('c');
						} else {
							$record->attributes[$key2] = null;
						}
					}
				}
				$records[$key] = $record->attributes;
			}
			return $records;
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'Unknown column') !== false) {
				throw new Jp7_InterAdmin_Soap_Exception('Unknown field in "fields" or "where".');
			} else {
				throw new Jp7_InterAdmin_Soap_Exception('Invalid format for "where" or "limit".');
			}
		}
	}
	
	/**
	 * Returna o primeiro registro.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	protected function getFirst($className, $options = array()) {
		$options['limit'] = 1;
		return reset($this->get($className, $options));
	}
	
	/**
	 * Returna todos os registros, incluindo os deletados e os não publicados.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	protected function getAll($className, $options = array()) {
		$options['use_published_filters'] = false;
		return $this->get($className, $options);
	}
	
	public static function tokenError() {
		// Não consegui usar o erro padrão
		header('Content-type: text/xml');
		?>
		<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
			<SOAP-ENV:Body>
			  <SOAP-ENV:Fault>
			     <faultcode>Receiver</faultcode>
			     <faultstring>Invalid token. You need to login() before calling this method.</faultstring>
			  </SOAP-ENV:Fault>
			</SOAP-ENV:Body>
		</SOAP-ENV:Envelope>
		<?php
	}
	
	public function login($loginData) {
		$usuarioWsTipo = new Jp7_InterAdmin_Soap_UsuarioTipo();
		$usuarioWs = $usuarioWsTipo->login($loginData->username, $loginData->password);
		if ($usuarioWs) {
			$token = jp7_encrypt($loginData->username . '{:}' . $loginData->password . '{:}' . time());
		} else {
			throw new Jp7_InterAdmin_Soap_Exception('Invalid username/password combination.');	
		}
		return array('loginResult' => $token);
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
			if ($args[0]) {
				if ($args[0]->where) {
					$where = '(' . $args[0]->where . ')';
				}
				$fields = array();
				if ($args[0]->fields) {
					$fields = jp7_explode(',', $args[0]->fields);
				}
				$fields = array_merge($fields, array(
					'parent_id',
					'date_insert',
					'date_modify',
					'date_publish',
					'deleted',
					'publish'
				));
				
				$options = array(
					'fields' => $fields,
					'where' => jp7_explode(',', $where),
					'limit' => $args[0]->limit
				);
				
				foreach ($options['fields'] as $key => $field) {
					if (strpos($field, '.')) {
						list($join, $joinField) = explode('.', $field);
						$options['fields'][$join][] = $joinField;
						$options['fields'][$key] = $join;
					}
				}
			}
			// Por padrão só pega os publicados
			$options['use_published_filters'] = true;
			
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