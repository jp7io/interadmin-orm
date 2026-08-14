<?php

class InterAdminArquivoBanco
{
    public $db_prefix;

    public function __construct($options = [])
    {
        $this->db_prefix = ($options['db_prefix'] ?? '') ?: $GLOBALS['db_prefix'];
    }

    /**
     * @param array $fieldsValues
     *
     * @throws LogicException always
     */
    public function addFile($fieldsValues)
    {
        throw new LogicException(
            'InterAdminArquivoBanco::addFile() is not supported: its only implementation was '.
            "jp7io/inc's jp7_db_insert(), and that package is gone. Use Jp7\\Interadmin\\FileDatabase."
        );
    }

    public function getTableName()
    {
        return $this->db_prefix.'_arquivos_banco';
    }
}
