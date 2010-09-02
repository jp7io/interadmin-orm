<?php

class Jp7_InterAdmin_Soap_Generic {
	/**
	 * Returna todos os registros publicados.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	public function get($className, $options = array()) {
		try {
			$tipo = Jp7_InterAdmin_Soap::getClassTipo($className);
			return $tipo->getInterAdmins($options);
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'Unknown column') !== false) {
				throw new Jp7_InterAdmin_Soap_Exception('Unknown field in "fields" or "where".');
			} else {
				throw new Jp7_InterAdmin_Soap_Exception('Invalid format for "where" or "limit".');
			}
		}
	}
	
	/**
	 * Returna o primeiro registro.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	public function getFirst($className, $options = array()) {
		$options['limit'] = 1;
		return reset($this->get($className, $options));
	}
	
	/**
	 * Returna todos os registros, incluindo os deletados e os não publicados.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	public function getAll($className, $options = array()) {
		$options['use_published_filters'] = false;
		return $this->get($className, $options);
	}
}