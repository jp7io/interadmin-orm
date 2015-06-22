<?php

/**
 * @category   Jp7
 */
class Jp7_Form_Element_Checkbox extends Zend_Form_Element_Checkbox
{
    public function setValue($value)
    {
        return parent::setValue($value ? $this->getCheckedValue() : '');
    }
}
