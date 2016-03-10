<?php

namespace Jp7\Interadmin\Field;

use Former;
use HtmlObject\Input;
use Jp7_Date as Date;

trait DateFieldTrait
{
    public function getText()
    {
        $date = new Date(parent::getValue());
        return $date->format('d/m/Y'.($this->isDatetime() ? ' - H:i' : ''));
    }
    
    protected function getValue()
    {
        $date = new Date(parent::getValue());
        return $date->format('Y-m-d'.($this->isDatetime() ? '\TH:i' : ''));
    }
    
    protected function getFormerField()
    {
        $input = Former::date($this->getFormerName())
            ->value($this->getValue())
            ->append($this->getUpdateButton());
        if ($this->isDatetime()) {
            $input->type('datetime-local');
        }
        return $input;
    }
    
    protected function getUpdateButton()
    {
        return Input::button(null, 'Atualizar');
    }
}
