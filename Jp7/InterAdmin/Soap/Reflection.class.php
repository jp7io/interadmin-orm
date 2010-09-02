<?php

/**
 * Usada para adicionar os métodos de cada Tipo no WebService.
 */
class Jp7_InterAdmin_Soap_Reflection {
	
	protected $usuario;
	
	public function __construct(InterAdmin $usuario) {
		$this->usuario = $usuario;
	} 
	
	public function getUsuario() {
		return $this->usuario;
	}
	
	public function reflectClass() {
		return $this;
	}
	
	public function getMethods() {
		$methods = array();
		$reflection = new Zend_Server_Reflection();
				
		// Reflection das classes na stack
		foreach (Jp7_InterAdmin_Soap::getClasses() as $className) {
			$methods = array_merge($methods, $reflection->reflectClass($className)->getMethods());
		}
		
		// Reflection dinâmico para Jp7_InterAdmin_Soap_Generic
		foreach ($this->usuario->secoes as $secao) {
			$methods[] = new Jp7_InterAdmin_Soap_ReflectionMethodGet($secao);
			$methods[] = new Jp7_InterAdmin_Soap_ReflectionMethodGetAll($secao);
			$methods[] = new Jp7_InterAdmin_Soap_ReflectionMethodGetFirst($secao);
		}
		return $methods;
	}

}