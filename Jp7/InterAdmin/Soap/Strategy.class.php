<?php

class Jp7_InterAdmin_Soap_Strategy extends  Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence {
	
	protected $_inProgress = array();
	
	protected function _appendElements($dom, $container, $elements) {
		foreach ($elements as $name => $type) {
			$element = $dom->createElement('xsd:element');
			$element->setAttribute('minOccurs', '0');
			$element->setAttribute('name', $name);
			$element->setAttribute('nillable', 'true');
			$element->setAttribute('type', $type);
			$container->appendChild($element);
		}
	}
	
	public function addComplexType($type) {
		if (!in_array($type, $this->getContext()->getTypes())) {
			if (substr($type, strlen($type) - 2) == '[]') {
				return parent::addComplexType($type);
			}
			// Evitar looping infinito
			if (!in_array($type, $this->_inProgress)) {
				$this->_inProgress[] = $type;
				
				$dom = $this->getContext()->toDomDocument();
		       	
				$complexType = $dom->createElement('xsd:complexType');
		        $complexType->setAttribute('name', $type);
		        $all = $dom->createElement('xsd:all');
				
				// Jp7
				if ($type == 'Options') {
					$this->_appendElements($dom, $all, array(
						'fields' => 'xsd:string',
						'where' => 'xsd:string',
						'limit' => 'xsd:string'
					));				
				} else {
					$isDynamicClass = Jp7_InterAdmin_Soap::isDynamicClass($type);
					if ($isDynamicClass || is_subclass_of($type, 'InterAdmin')) {
						$tipo = Jp7_InterAdmin_Soap::getClassTipo($type);
						
						$tipo->getCamposAlias();
						$campos = $tipo->getCampos();
						
						$elements = array();
						foreach ($campos as $campo) {
							if (strpos($campo['tipo'], 'tit_') === false && strpos($campo['tipo'], 'func_') === false) {
								$elements[$campo['nome_id']] = $this->_getCampoTipo($campo);
							}
				        }
						
						$elements += array(
							'id' => 'xsd:int',
							'id_tipo' => 'xsd:int',
							'parent_id' => 'xsd:int',
							'date_insert' => 'xsd:dateTime',
							'date_modify' => 'xsd:dateTime',
							'date_publish' => 'xsd:dateTime',
							'deleted' => 'xsd:boolean',
							'publish' => 'xsd:boolean'
						);
						
						$this->_appendElements($dom, $all, $elements);
					} else {
						// InterAdminTipo
						$this->_appendElements($dom, $all, array(
							'id_tipo' => 'xsd:int',
							'nome' => 'xsd:string',
							'parent_id_tipo' => 'xsd:int',
							'model_id_tipo' => 'xsd:int',
							'class' => 'xsd:string',
							'class_tipo' => 'xsd:string',
							'deleted_tipo' => 'xsd:boolean',
							'mostrar' => 'xsd:boolean'
						));
					}
				}
				
		        $complexType->appendChild($all);
				$this->getContext()->getSchema()->appendChild($complexType);
		        $this->getContext()->addType($type);
			}
	        return "tns:$type";
			
		} else {
            // Existing complex type
            return $this->getContext()->getType($type);
        }
	}	
	
	protected function _getCampoTipo($campo) {
		if (strpos($field, 'special_') === 0 && $campo['xtra']) {
			
			$isMulti = in_array($campo['xtra'], InterAdminField::getSpecialMultiXtras());
			$isTipo = in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras());
			
			$retorno = $this->_getCampoSelectClass($campo, $isTipo, $isMulti);
			
		} elseif (strpos($campo['tipo'], 'select_') === 0) {
			
			$isMulti = (strpos($campo['tipo'], 'select_multi') === 0);
			$isTipo = in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());
			
			$retorno = $this->_getCampoSelectClass($campo, $isTipo, $isMulti);
			
		} elseif (strpos($campo['tipo'], 'int') === 0 || strpos($campo['tipo'], 'id') === 0) {
			$retorno = 'int';
		} elseif (strpos($campo['tipo'], 'char') === 0) {
			$retorno = 'boolean';
		} elseif (strpos($campo['tipo'], 'date') === 0) {
			return 'xsd:dateTime';
		} else {
			$retorno = 'string';
		}
		return $this->getContext()->getType($retorno);
	}
	
	protected function _getCampoSelectClass($campo, $isTipo, $isMulti) {
		if ($isTipo) {
			$retorno = 'InterAdminTipo';
		} else {
			$retorno = $campo['nome']->class;
		}
		if ($isMulti && $retorno) {
			$retorno .= '[]';
		}
		if (!$retorno) {
			$retorno = 'int';
		}
		return $retorno;
	}
}