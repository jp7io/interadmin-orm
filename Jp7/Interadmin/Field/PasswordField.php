<?php

namespace Jp7\Interadmin\Field;

class PasswordField extends ColumnField
{
    protected $id = 'password';
    
    public function getText()
    {
        return $this->getValue() ? '******' : '';
    }
}
