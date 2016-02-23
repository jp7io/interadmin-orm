<?php

class Jp7_Interadmin_Field_Func extends Jp7_Interadmin_Field_Base {
    public function getHeaderValue()
    {
        if (is_callable($this->nome)) {
            ob_start();
            $response = call_user_func($this->nome, get_object_vars($this), '', 'header');
            $response .= ob_get_clean();
            return $response;
        } else {
            return 'Function '.$this->nome.' not found.';
        }
    }
    
    public function getListValue(ADOFetchObj $record)
    {
        if (is_callable($this->nome)) {
            $value = $this->getValue($record);
            ob_start();
            $response = call_user_func($this->nome, get_object_vars($this), $value, 'list');
            $response .= ob_get_clean();
            return $response;
        } else {
            return 'Function '.$this->nome.' not found.';
        }
    }
}
