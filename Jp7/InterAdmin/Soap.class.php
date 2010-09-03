<?php

class Jp7_InterAdmin_Soap {
	protected static $classes = array();
	
	/**
	 * @param string $type
	 * @return bool
	 */
	public static function isDynamicClass($type) {
		return preg_match('/^([a-zA-Z]*)_([0-9]*)$/', $type);
	}
	/**
	 * @param string $type
	 * @return string
	 */
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
	/**
	 * @param string $message
	 * @return string
	 */
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
	/**
	 * @param mixed $result
	 * @param string $method
	 * @return 
	 */
	public static function formatResult($result, $method) {
		if (is_array($result) && reset($result) instanceof InterAdminAbstract) {
			foreach ($result as $key => $record) {					
				$result[$key] = self::_formatAttributes($record);
			}
		} elseif ($result instanceof InterAdminAbstract) {
			$result = self::_formatAttributes($result);
		}		
		return array($method . 'Result' => $result);
	}
	/**
	 * @param InterAdminAbstract $record
	 * @return array
	 */
	protected static function _formatAttributes($record) {
		global $config;
		
		foreach ($record->attributes as $key2 => $value) {
			// Relacionamentos
			if ($value instanceof InterAdminAbstract) {
				$record->attributes[$key2] = $value->attributes;
			// Formato de data, não pode ser 0000-00-00
			} elseif ($value instanceof Jp7_Date) {
				if ($value->isValid()) {
					$record->attributes[$key2] = $value->format('c');
				} else {
					$record->attributes[$key2] = null;
				}
			} elseif ($value instanceof InterAdminFieldFile) {
				$record->attributes[$key2] = $config->url . preg_replace('~../../~', '', $value);
			}
		}
		return $record->attributes;
	}
	/**
	 * Creates a WSDL server.
	 *  
	 * @return Jp7_InterAdmin_Soap_AutoDiscover
	 */
	public static function createWsdlServer() {
		global $config;
		$server = new Jp7_InterAdmin_Soap_AutoDiscover('Jp7_InterAdmin_Soap_Strategy', $config->url);
		// Usuario possui as seções liberadas
		$server->setOperationBodyStyle(array('use' => 'literal'));
		$server->setBindingStyle(array('style' => 'document'));
		return $server;
	}
	/**
	 * Creates a SOAP server.
	 *  
	 * @param string $wsdl
	 * @return Zend_Soap_Server
	 */
	public static function createSoapServer($wsdl) {
		$server = new Zend_Soap_Server($wsdl);
		$server->setEncoding('ISO-8859-1');
		$server->registerFaultException('Jp7_InterAdmin_Soap_Exception');
		$server->setClassmap(array(
			'Options' => 'Jp7_InterAdmin_Soap_Options'
		));
		return $server;
	}
	/**
	 * Prepend a class to the proxy.
	 * 
	 * @param object $className
	 * @return 
	 */
	public static function prependClass ($className) {
		array_unshift(self::$classes, $className);
	}
	/**
	 * Append a class to the proxy.
	 * 
	 * @param object $className
	 * @return 
	 */
	public static function appendClass ($className) {
		array_push(self::$classes, $className);
	}
	/**
	 * @return array
	 */
	public static function getClasses() {
		return self::$classes;
	}
	/**
	 * @param array $classes
	 * @return void
	 */
	public static function setClasses($classes) {
		self::$classes = $classes;
	}
	/**
	 * Describes the functions, parameters and return values from a WSDL.
	 * 
	 * @param 	string $wsdl
	 * @return 	array
	 */
	public static function describeWsdl($wsdl) {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadXML(file_get_contents($wsdl));
		
		// Elements
		$schema = $dom->getElementsByTagName('schema')->item(0);
		$elements = array();
		$types = array();
		foreach ($schema->childNodes as $child) {
			$tagname = str_replace($child->prefix . ':', '', $child->nodeName); 
			if ($tagname == 'element') {
				$elements[$child->getAttribute('name')] = $child;
			} elseif ($tagname == 'complexType') {
				// Attributes
				$attributes = array();
				$attrs = $child->getElementsByTagName('element');
				foreach ($attrs as $attr) {
					$attributes[] = array(
						'name' => $attr->getAttribute('name'),
						'type' => $attr->getAttribute('type')  . (($attr->getAttribute('maxOccurs') == 'unbounded') ? '[]' : '')
					);
				}
				$types[$child->getAttribute('name')] = $attributes;
			}
		}
		
		// Funções
		$functions = array();
		
		$portType = $dom->getElementsByTagName('portType')->item(0);
		$operations = $portType->getElementsByTagName('operation');
		foreach ($operations as $operation) {
			$function = array(
				'name' => $operation->getAttribute('name'),
				'description' => utf8_decode($operation->getElementsByTagName('documentation')->item(0)->textContent),
				'params' => array()
			);
			// Parâmetros
			$params = $elements[$operation->getAttribute('name')]->getElementsByTagName('element');
			foreach ($params as $param) {
				$function['params'][] = array(
					'name' => $param->getAttribute('name'),
					'type' => $param->getAttribute('type')
				);
			}
			// Retorno
			$return = $elements[$operation->getAttribute('name') . 'Response']->getElementsByTagName('element');
			$function['return'] = $return->item(0)->getAttribute('type');
			
			$functions[] = $function;
		}
		return compact('functions', 'types');
	}
}