<?php

/**
 * É usado para simular um método no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodGetFirst extends Jp7_InterAdmin_Soap_ReflectionMethodGet {
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'getFirst' . $this->_getClassName();
	}
	
	/**
	 * @return string 
	 */
	public function getReturnType() {
		return $this->_getClassName();
	}
	
	public function getDescription() {
		return utf8_encode('Retorna o primeiro registro da seção ' . $this->secao->nome . '.');
	}
}