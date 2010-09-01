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
			$tipo = self::getClassTipo($className);
			
			$records = $tipo->getInterAdmins($options);
			foreach ($records as $key => $record) {
				foreach ($record->attributes as $key2 => $value) {
					if ($value instanceof InterAdminAbstract) {
						$record->attributes[$key2] = $value->attributes;
					} elseif ($value instanceof Jp7_Date) {
						if ($value->isValid()) {
							$record->attributes[$key2] = $value->format('c');
						} else {
							$record->attributes[$key2] = null;
						}
					}
				}
				$records[$key] = $record->attributes;
			}
			return $records;
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