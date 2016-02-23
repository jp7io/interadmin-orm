<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

class Password extends ColumnField
{
    protected $name = 'password';
    
    public function getCellText(ADOFetchObj $value)
    {
        return parent::getCellText($value) ? '******' : '';
    }
}
