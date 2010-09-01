<?php

class Jp7_InterAdmin_Soap_Strategy extends  Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence {
	
	protected $_inProgress = array();
	
	public function addComplexType($type) {
		if (!in_array($type, $this->getContext()->getTypes())) {
			
			$isDynamicClass = Jp7_InterAdmin_Soap::isDynamicClass($type);
			
			if (!$isDynamicClass && (!class_exists($type) || !is_subclass_of($type, 'InterAdminAbstract'))) {
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
				if ($isDynamicClass || is_subclass_of($type, 'InterAdmin')) {
					$tipo = Jp7_InterAdmin_Soap::getClassTipo($type);
					
					$tipo->getCamposAlias();
					$campos = $tipo->getCampos();
					
					$campos[] = array('nome_id' => 'id', 'tipo' => 'id_');
					$campos[] = array('nome_id' => 'id_tipo', 'tipo' => 'id_');
					$campos[] = array('nome_id' => 'parent_id', 'tipo' => 'id_');
					$campos[] = array('nome_id' => 'date_insert', 'tipo' => 'date_');
					$campos[] = array('nome_id' => 'date_modify', 'tipo' => 'date_');
					$campos[] = array('nome_id' => 'date_publish', 'tipo' => 'date_');
					$campos[] = array('nome_id' => 'deleted', 'tipo' => 'char_');
					$campos[] = array('nome_id' => 'publish', 'tipo' => 'char_');
				} else {
					$campos[] = array('nome_id' => 'id_tipo', 'tipo' => 'id_');
					$campos[] = array('nome_id' => 'nome', 'tipo' => 'varchar_');
					$campos[] = array('nome_id' => 'parent_id_tipo', 'tipo' => 'id_');
					$campos[] = array('nome_id' => 'model_id_tipo', 'tipo' => 'id_');
					$campos[] = array('nome_id' => 'class', 'tipo' => 'varchar_');
					$campos[] = array('nome_id' => 'class_tipo', 'tipo' => 'varchar_');
					$campos[] = array('nome_id' => 'deleted_tipo', 'tipo' => 'char_');
					$campos[] = array('nome_id' => 'mostrar', 'tipo' => 'char_');
				}
				
				foreach ($campos as $campo) {
					if (strpos($campo['tipo'], 'tit_') === false &&  strpos($campo['tipo'], 'func_') === false) {
			    		$element = $dom->createElement('xsd:element');
						$element->setAttribute('minOccurs', '0');
						$element->setAttribute('name', $campo['nome_id']);
						$element->setAttribute('nillable', 'true');
						$element->setAttribute('type', $this->_getCampoTipo($campo));
						$all->appendChild($element);
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