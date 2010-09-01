<?php

/**
 * É usado para simular um método no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodGetAll extends Jp7_InterAdmin_Soap_ReflectionMethodGet {
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'getAll' . $this->_getClassName();
	}
	
	public function getDescription() {
		return utf8_encode('Retorna todos os registros da seção ' . $this->secao->nome . ', incluindo os registros deletados e os não publicados.');
	}
}