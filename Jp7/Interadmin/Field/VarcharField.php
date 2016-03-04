<?php

namespace Jp7\Interadmin\Field;

class VarcharField extends ColumnField
{
    protected $id = 'varchar';
    
    public function getRules()
    {
        $rules = parent::getRules();
        $name = $this->getFormerName();
        if ($this->xtra === 'email') {
            $rules[$name][] = 'email';
        }
        if ($this->tamanho) {
            $rules[$name][] = 'max:'.$this->tamanho;
        }
        return $rules;
    }
}
