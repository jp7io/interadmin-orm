<?php

/**
 * É usado para simular um método no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodGet {
	
	protected $secao;
		
	public function __construct(InterAdminTipo $secao) {
		$this->secao = $secao;
		$this->secao->getFieldsValues(array('nome', 'class'));
	} 
	
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
			new Jp7_InterAdmin_Soap_ReflectionParameter('fields', 'string'),
			new Jp7_InterAdmin_Soap_ReflectionParameter('where', 'string'),
			new Jp7_InterAdmin_Soap_ReflectionParameter('limit', 'string')
		);
	}
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'get' . $this->_getClassName();
	}
	
	/**
	 * @return string 
	 */
	public function getReturnType() {
		return $this->_getClassName() . '[]';
	}
	
	public function getDescription() {
		return utf8_encode('Retorna os registros publicados e não deletados da seção ' . $this->secao->nome . '.');
	}
	
	protected function _getClassName() {
		return ($this->secao->class) ? $this->secao->class : Jp7_Inflector::camelize($this->secao->nome) . '_' . $this->secao->id_tipo;
	}
}