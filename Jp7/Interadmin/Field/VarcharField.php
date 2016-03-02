<?php

namespace Jp7\Interadmin\Field;

class VarcharField extends ColumnField
{
    protected $name = 'varchar';
    
    public function __construct(array $campo)
    {
        $this->campo = $campo;
    }
    
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
