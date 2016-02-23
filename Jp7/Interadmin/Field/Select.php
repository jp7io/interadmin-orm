<?php

namespace Jp7\Interadmin\Field;

use InterAdminTipo;
use ADOFetchObj;

class Select extends ColumnField
{
    const XTRA_BY_TYPE = 'S';
    protected $name = 'select';
    
    public function getHeaderText()
    {
        if ($this->campo['label']) {
            return $this->campo['label'];
        } elseif ($this->campo['nome'] instanceof InterAdminTipo) {
            return $this->campo['nome']->getFieldsValues('nome');
        } elseif ($this->campo['nome'] == 'all') {
            return 'Tipos';
        }
        throw new Exception('Not implemented');
    }
    
    public function getCellText(ADOFetchObj $record)
    {
        if (!$value = parent::getCellText($record)) {
            return;
        }
        if ($this->hasTipoRelationship()) {
            return interadmin_tipos_nome($value);
        } else {
            $registro = $this->campo['nome']->findById($value);
            if ($registro) {
                return $registro->getStringValue();
            }
        }
        return $value.' (Deletado)';
    }
    
    protected function hasTipoRelationship()
    {
        return in_array($this->campo['xtra'], [self::XTRA_BY_TYPE, 'ajax_tipos', 'radio_tipos']);
    }
}
