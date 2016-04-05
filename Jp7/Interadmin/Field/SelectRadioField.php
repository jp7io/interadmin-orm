<?php

namespace Jp7\Interadmin\Field;

use Former;

class SelectRadioField extends SelectField
{
    protected function getFormerField()
    {
        return Former::radios($this->getFormerName())
                // ->id($this->getFormerId()) // TODO test this
                ->radios($this->getRadios())
                ->check($this->getValue());
    }
    
    protected function getRadios()
    {
        $radios = [];
        if (!$this->obrigatorio) {
            $radios['(nenhum)'] = ['value' => '', 'checked' => true];
        }
        foreach ($this->getOptions() as $key => $value) {
            $radios[$value] = ['value' => $key];
        }
        return $radios;
    }
}
