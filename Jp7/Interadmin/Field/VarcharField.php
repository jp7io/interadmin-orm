<?php

namespace Jp7\Interadmin\Field;

class VarcharField extends ColumnField
{
    protected $id = 'varchar';
    
    public function getRules()
    {
        $rules = parent::getRules();
        $name = $this->getFormerName();
        if ($this->campo['xtra'] === 'email') {
            $rules[$name][] = 'email';
        }
        if ($this->campo['tamanho']) {
            $rules[$name][] = 'max:'.$this->campo['tamanho'];
        }
        return $rules;
    }
}
