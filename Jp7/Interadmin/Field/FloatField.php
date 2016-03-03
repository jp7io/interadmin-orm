<?php

namespace Jp7\Interadmin\Field;

class FloatField extends ColumnField
{
    protected $id = 'float';
    
    public function getRules()
    {
        $rules = parent::getRules();
        $rules[$this->getFormerName()][] = 'numeric';
        return $rules;
    }
}
