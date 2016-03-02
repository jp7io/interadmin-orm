<?php

namespace Jp7\Interadmin\Field;

use Former;

class ColumnField extends BaseField
{
    protected $campo;
    protected $i = 0;
    
    /**
     * @param array $campo
     */
    public function __construct(array $campo)
    {
        $this->campo = $campo;
    }
    
    public function setIndex($i)
    {
        $this->i = $i;
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
        if ($this->campo['ajuda']) {
            $input->help($this->campo['ajuda']);
        }
        $input->getLabel()->setAttribute('title', $this->campo['tipo']);
        $input->onGroupAddClass($this->name);
        return $input;
    }
    
    protected function getFormerField()
    {
        return Former::text($this->getFormerName())
            ->value($this->getText());
    }
    
    protected function getFormerName()
    {
        $column = $this->campo['tipo'];
        return $column.'['.$this->i.']';
    }
    
    public function getRules()
    {
        $rules = [];
        if ($this->campo['obrigatorio']) {
            $rules[$this->getFormerName()][] = 'required';
        }
        return $rules;
    }
}
