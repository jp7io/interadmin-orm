<?php

namespace Jp7\Interadmin\Field;

use Former;

class IntField extends ColumnField
{
    protected $name = 'int';
    
    public function getRules()
    {
        $rules = parent::getRules();
        $rules[$this->getFormerName()][] = 'numeric';
        return $rules;
    }
}
