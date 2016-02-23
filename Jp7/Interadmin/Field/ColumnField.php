<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

class ColumnField extends BaseField
{
    protected $campo;
    
    /**
     * @param array $campo
     */
    public function __construct(array $campo)
    {
        $this->campo = $campo;
    }

    public function getHeaderTag() {
        return parent::getHeaderTag()->title($this->campo['tipo']);
    }
    
    public function getHeaderText()
    {
        return $this->campo['nome'];
    }

    public function getCellText(ADOFetchObj $record)
    {
        $column = $this->campo['tipo'];
        return $record->$column;
    }
}
