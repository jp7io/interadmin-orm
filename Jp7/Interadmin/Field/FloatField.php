<?php

namespace Jp7\Interadmin\Field;

class FloatField extends ColumnField
{
    protected $id = 'float';

    public function getRules()
    {
        $rules = parent::getRules();
        $rules[$this->getRuleName()][] = 'numeric';
        return $rules;
    }

    public function hasMassEdit()
    {
        return true;
    }
}
