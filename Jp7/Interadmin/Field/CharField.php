<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

class CharField extends ColumnField
{
    protected $name = 'char';
    
    public function getCellHtml(ADOFetchObj $value)
    {
        return $this->getCellText($value) ? '&bull;' : '';
    }
}
