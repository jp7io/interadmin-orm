<?php

namespace Jp7\Interadmin\Field;

use Former;
use HtmlObject\Input;
use HtmlObject\Element;
use Date;

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
        if (!$value || !$value->isValid()) {
            return '';
        }
        return $value->format($format);
    }
    
    protected function getDefaultValue()
    {
        $default = parent::getDefaultValue();
        return $default ? new Date($default) : $default;
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
        $input = $this->getFormerField();
        $this->handleReadonly($input);
        return Element::td((string) $input)->class('date');
    }
    
    public function hasMassEdit()
    {
        return true;
    }

    protected function getUpdateButton()
    {
        $input = Input::button(null, 'Atualizar');
        $this->handleReadonly($input);
        return $input;
    }
}
