<?php

namespace Jp7\Interadmin\Field;

class FuncField extends ColumnField
{
    protected $id = 'func';
    
    public function getHeaderHtml()
    {
        return $this->getFuncHtml('', 'header');
    }
    
    public function getCellHtml()
    {
        return $this->getFuncHtml($this->getText(), 'list');
    }
    
    protected function getFuncHtml($value, $parte)
    {
        if (!is_callable($this->nome)) {
            return 'Function '.$this->nome.' not found.';
        }
        ob_start();
        $response = call_user_func($this->nome, $this->campo, $value, $parte);
        $response .= ob_get_clean();
        return $response;
    }
}
