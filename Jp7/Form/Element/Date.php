<?php

/**
 * Date form element.
 *
 * @category   Jp7
 */
class Jp7_Form_Element_Date extends Zend_Form_Element_Xhtml
{
    public $helper = 'formDate';

    public function isValid($value, $context = null)
    {
        $newValue = $this->_foraDeFormato($value);
        if ($newValue !== false) {
            $value = $newValue;
        }

        return parent::isValid($value, $context);
    }

    public function getValue()
    {
        $newValue = $this->_foraDeFormato($value);
        if ($newValue !== false) {
            $this->setValue($newValue);
        }

        return parent::getValue();
    }

    private function _foraDeFormato($value)
    {
        if (is_array($value)) {
            if (preg_match('~^([0-9]{2})/([0-9]{2})/([0-9]{4})$~', $value['date'], $matches)) {
                list($tudo, $d, $m, $y) = $matches;
                $value['date'] = $y.'-'.$m.'-'.$d;
                if ($value['date'] == '0000-00-00') {
                    return;
                }
            }

            return $value['date'].($value['time'] ? ' '.$value['time'] : '');
        }

        return false;
    }
}
