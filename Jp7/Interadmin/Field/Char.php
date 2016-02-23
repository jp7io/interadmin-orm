<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

class Char extends ColumnField
{
    protected $name = 'char';
    
    public function getCellHtml(ADOFetchObj $value)
    {
        return $this->getCellText($value) ? '&bull;' : '';
    }
}
