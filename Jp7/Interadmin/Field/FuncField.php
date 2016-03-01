<?php

namespace Jp7\Interadmin\Field;

class FuncField extends ColumnField
{
    protected $name = 'func';
    
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
        if (!is_callable($this->campo['nome'])) {
            return 'Function '.$this->campo['nome'].' not found.';
        }
        ob_start();
        $response = call_user_func($this->campo['nome'], $this->campo, $value, $parte);
        $response .= ob_get_clean();
        return $response;
    }
}
