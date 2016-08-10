<?php

namespace Jp7\Interadmin\Field;

use Former;

class PasswordField extends ColumnField
{
    protected $id = 'password';

    public function getText()
    {
        return $this->getValue() ? '******' : '';
    }

    protected function getFormerField()
    {
        $input = Former::password($this->getFormerName())
            ->id($this->getFormerId());

        if ($this->getValue()) {
            // Disabled so it won't force the user to change the password
            $input->disabled()
                ->data_filled();
        }
        return $input;
    }

    public function getRules()
    {
        $rules = parent::getRules();
        if ($this->getValue()) {
            // Remove required
            if (isset($rules[$this->getRuleName()])) {
                $rules[$this->getRuleName()] = array_diff($rules[$this->getRuleName()], ['required']);
            }
        }
        return $rules;
    }
}
