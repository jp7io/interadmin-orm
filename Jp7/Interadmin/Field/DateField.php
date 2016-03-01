<?php

namespace Jp7\Interadmin\Field;

use Jp7_Date;
use ADOFetchObj;

class DateField extends ColumnField
{
    const XTRA_NORMAL = '0';
    const XTRA_NO_TIME = 'S';
    protected $name = 'date';
    
    public function getText()
    {
        $date = new Jp7_Date(parent::getText());
        $withTime = (ends_with($this->campo['xtra'], '_datetime') || $this->campo['xtra'] === self::XTRA_NORMAL);
        return $date->format('d/m/Y'.($withTime ? ' - H:i' : ''));
    }
}
