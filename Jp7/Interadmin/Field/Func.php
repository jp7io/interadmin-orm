<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

class Func extends ColumnField
{
    protected $name = 'func';
    
    public function getHeaderHtml()
    {
        if (is_callable($this->campo['nome'])) {
            return $this->runFunc('', 'header');
        } else {
            return 'Function '.$this->campo['nome'].' not found.';
        }
    }
    
    public function getCellHtml(ADOFetchObj $record)
    {
        if (is_callable($this->campo['nome'])) {
            $value = $this->getCellText($record);
            return $this->runFunc($value, 'list');
        } else {
            return 'Function '.$this->campo['nome'].' not found.';
        }
    }
    
    protected function runFunc($value, $parte)
    {
         ob_start();
         $response = call_user_func($this->campo['nome'], get_object_vars($this), $value, $parte);
         $response .= ob_get_clean();
         return $response;
    }
}
