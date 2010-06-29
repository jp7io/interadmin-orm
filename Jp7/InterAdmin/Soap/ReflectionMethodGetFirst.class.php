<?php

/**
 * É usado para simular um método no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodGetFirst extends Jp7_InterAdmin_Soap_ReflectionMethodGet {
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'getFirst' . $this->secao->class;
	}
	
	/**
	 * @return array
	 */
	public function getParameters() {
		return array(
			new Jp7_InterAdmin_Soap_ReflectionParameter('fields', 'string'),
			new Jp7_InterAdmin_Soap_ReflectionParameter('where', 'string'),
			new Jp7_InterAdmin_Soap_ReflectionParameter('token', 'string'),
		);
	}
	
	/**
	 * @return string 
	 */
	public function getReturnType() {
		return $this->secao->class;
	}
	
	public function getDescription() {
		return utf8_encode('Retorna o primeiro registro da secao ' . $this->secao->nome . '.');
	}
}