<?php

namespace Jp7\Interadmin\Field;

use Jp7_Date;
use Former;

class DateField extends ColumnField
{
    const XTRA_NORMAL = '0';
    const XTRA_NO_TIME = 'S';
    protected $id = 'date';
    /*
    $S_form_xtra_arr[0] = "Normal";
    $S_form_xtra_arr['S']="Sem Hora"; // TODOS ABAIXO MORRERAM!!! ;)
    $S_form_xtra_arr['calendar_datetime'] = "Calendário";
    $S_form_xtra_arr['calendar_date']="Calendário S/ Hora";
    $S_form_xtra_arr['nocombo_datetime']="S/ Combo";
    $S_form_xtra_arr['nocombo_date']="S/ Combo S/ Hora";
    $S_form_xtra_arr['calendar_nocombo_datetime']="Calendário S/ Combo";
    $S_form_xtra_arr['calendar_nocombo_date']="Calendário S/ Combo S/ Hora";
    */
    public function getText()
    {
        $date = new Jp7_Date($this->getValue());
        return $date->format('d/m/Y'.($this->isDatetime() ? ' - H:i' : ''));
    }
    
    protected function isDatetime()
    {
        return (ends_with($this->xtra, '_datetime') || $this->xtra === self::XTRA_NORMAL);
    }
    
    protected function getFormerField()
    {
        $input = Former::date($this->getFormerName())
            ->value($this->getValue());
        if ($this->isDatetime()) {
            $input->type('datetime-local');
        }
        return $input;
    }

}
