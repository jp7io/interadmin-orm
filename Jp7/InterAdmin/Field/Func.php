<?php

class Jp7_InterAdmin_Field_Func extends Jp7_InterAdmin_Field_Base {
    public function getHeaderValue()
    {
        if (is_callable($this->nome)) {
            return call_user_func($this->nome, (array) $this, '', 'header');
        } else {
            return 'Função '.$this->nome.' não encontrada.';
        }
    }
    
    public function getListValue(ADOFetchObj $value)
    {
        if (is_callable($this->nome)) {
            return call_user_func($this->nome, (array) $this, $value, 'list');
        } else {
            return 'Função '.$this->nome.' não encontrada.';
        }
    }
}
