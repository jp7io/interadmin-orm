<?php

namespace Jp7\Interadmin\Field;

use Jp7_Date;

class Date extends ColumnField
{
    const XTRA_NORMAL = '0';
    const XTRA_NO_TIME = 'S';
    protected $name = 'date';
    
    public function getCellText(\ADOFetchObj $record)
    {
        $date = new Jp7_Date(parent::getCellText($record));
        $withTime = (ends_with($this->campo['xtra'], '_datetime') || $this->campo['xtra'] === self::XTRA_NORMAL);
        return $date->format('d/m/Y'.($withTime ? ' - H:i' : ''));
    }
}
