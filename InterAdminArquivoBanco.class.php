<?php

class InterAdminArquivoBanco {
	
	public function __construct($options = array()) {
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
	}
	
	/**
	 * Adiciona arquivo ao banco e retorna ID.
	 * 
	 * @param array $fieldsValues
	 * @return string  id_arquivo_banco
	 */
	public function addFile($fieldsValues) {
		$id_arquivo_banco = jp7_db_insert($this->getTableName() . '_banco', 'id_arquivo_banco', '', $fieldsValues);
		return str_pad($id_arquivo_banco, 8, '0', STR_PAD_LEFT);
	}
	
	public function getTableName() {
    	return $this->db_prefix . '_arquivos_banco';
    }
}
