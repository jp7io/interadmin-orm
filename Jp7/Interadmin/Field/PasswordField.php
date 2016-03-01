<?php

namespace Jp7\Interadmin\Field;

class PasswordField extends ColumnField
{
    protected $name = 'password';
    
    public function getText()
    {
        return parent::getText() ? '******' : '';
    }
}
