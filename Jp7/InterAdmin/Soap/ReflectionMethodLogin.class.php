<?php

/**
 * Щ usado para recuperar o token de seguranчa que libera acesso ao Webservice.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodlogin {
	
	/**
	 * @return array
	 */
	public function getPrototypes() {
		return array($this);
	}
	
	/**
	 * @return array
	 */
	public function getParameters() {
		return array(
			new Jp7_InterAdmin_Soap_ReflectionParameter('username', 'string'),
			new Jp7_InterAdmin_Soap_ReflectionParameter('password', 'string')
		);
	}
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'login';
	}
	
	/**
	 * @return string 
	 */
	public function getReturnType() {
		return 'string';
	}
	
	public function getDescription() {
		return utf8_encode('Retorna o token que deve ser utilizado como autenticaчуo para os outro mщtodos.');
	}
}