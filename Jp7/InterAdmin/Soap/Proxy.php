<?php

class Jp7_InterAdmin_Soap_Proxy {
	/**
	 * Função que age como proxy entre a chamada e o método real.
	 * 
	 * @param string $methodName
	 * @param array $args
	 * @return mixed
	 */
	public function __call($methodName, $args) {
		try {
		
			// Formatando os parâmetros
			$params = (array) $args[0];
			foreach ($params as $key => $param) {
				if ($param instanceof Jp7_InterAdmin_Soap_Options) {
					$params[$key] = $param->getArray();
				}
			}
			
			while (true) {
				// Classes na stack
				$classes = Jp7_InterAdmin_Soap::getClasses();
				foreach ($classes as $classe) {
					if (method_exists($classe, $methodName)) {
						$obj = new $classe();
						$result = call_user_func_array(array($obj, $methodName), $params);
						break 2;
					}
				}
				
				// Genérico
				if (strpos($methodName, 'get') === 0) {
					$generic = new Jp7_InterAdmin_Soap_Generic();
					
					$options = reset($params);
					// Por padrão só pega os publicados
					$options['use_published_filters'] = true;
					$options['fields_alias'] = true;
					
					if (strpos($methodName, 'getFirst') === 0) {
						$className = substr($methodName, strlen('getFirst'));
						$result = $generic->getFirst($className, $options);
					} elseif (strpos($methodName, 'getAll') === 0) {
						$className = substr($methodName, strlen('getAll'));
						$result = $generic->getAll($className, $options);
					} else { 
						$className = substr($methodName, strlen('get'));
						$result = $generic->get($className, $options);
					}
				}
				break;
			}
			
		} catch (Jp7_InterAdmin_Exception $e) {
			if (strpos($e->getMessage(), 'Unknown column') !== false) {
				$nomeCampo = preg_replace('/([^.]*).(.*?)\'(.*)/', '\2', $e->getMessage());
				throw new Jp7_InterAdmin_Soap_Exception('Unknown field "' . $nomeCampo . '" in "fields" or "where".');
			} else {
				throw new Jp7_InterAdmin_Soap_Exception('Invalid format for "where" or "limit".');
			}
		}
		
		return Jp7_InterAdmin_Soap::formatResult($result, $methodName);
	}
}