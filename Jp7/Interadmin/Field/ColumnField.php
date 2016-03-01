<?php

namespace Jp7\Interadmin\Field;

use Former;

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

    public function getHeaderTag()
    {
        return parent::getHeaderTag()->title($this->campo['tipo']);
    }
    
    public function getLabel()
    {
        return $this->campo['nome'];
    }

    public function getText()
    {
        $column = $this->campo['tipo'];
        return $this->record->$column;
    }
    
    public function getEditTag()
    {
        $input = parent::getEditTag();
        $input->getLabel()->setAttribute('title', $this->campo['tipo']);
        return $input;
    }
    
    protected function getFormerField()
    {
        $column = $this->campo['tipo'];
        
        return Former::text($column.'[]')
            ->value($this->getText());
    }
}
