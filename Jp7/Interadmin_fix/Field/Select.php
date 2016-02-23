<?php

class Jp7_InterAdmin_Field_Select extends Jp7_InterAdmin_Field_Base {
    
    public function getHeaderValue()
    {
        if ($this->label) {
            return $this->label;
        } elseif ($this->nome instanceof InterAdminTipo) {
            return $this->nome->getFieldsValues('nome');
        } elseif ($this->nome == 'all') {
            return 'Tipos';
        }
        throw new Exception('Not implemented');
    }
    
    public function getListValue(ADOFetchObj $record)
    {
        if (!$value = $this->getValue($record)) {
            return;
        }
        if ($this->hasTipoRelationship()) {
            return interadmin_tipos_nome($value);
        } else {
            $registro = $this->nome->findById($value);
            if ($registro) {
                return $registro->getStringValue();
            }
        }
        return $value.' (Deletado)';
    }
    
    protected function hasTipoRelationship()
    {
        return in_array($this->xtra, ['S', 'ajax_tipos', 'radio_tipos']);
    }
}
