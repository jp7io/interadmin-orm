<?php

namespace Jp7\Interadmin\Field;

use Former;
use HtmlObject\Input;
use HtmlObject\Element;
use Jp7_Date as Date;

trait DateFieldTrait
{
    public function getText()
    {
        return $this->formatValue('d/m/Y'.($this->isDatetime() ? ' - H:i' : ''));
    }
    
    protected function getValue()
    {
        return $this->formatValue('Y-m-d'.($this->isDatetime() ? '\TH:i' : ''));
    }
    
    protected function formatValue($format)
    {
        $value = parent::getValue();
        if (!$value) {
            return '';
        }
        $date = new Date($value);
        if (!$date->isValid()) {
            return '';
        }
        return $date->format($format);
    }
    
    protected function getFormerField()
    {
        $input = Former::date($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue())
            ->append($this->getUpdateButton());
        if ($this->isDatetime()) {
            $input->type('datetime-local');
        }
        return $input;
    }
    
    public function getMassEditTag()
    {
        return Element::td($this->getFormerField())
            ->class('date');
    }

    protected function getUpdateButton()
    {
        $input = Input::button(null, 'Atualizar');
        $this->handleReadonly($input);
        return $input;
    }
}
