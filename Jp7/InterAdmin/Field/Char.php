<?php

use HtmlObject\Element;

class Jp7_InterAdmin_Field_Char extends Jp7_InterAdmin_Field_Base {
    public function getHeaderHtml()
    {
         return Element::th(substr($this->getHeaderValue(), 0, 3))
            ->title($this->getHeaderValue().' ('.$this->tipo.')');
    }
    
    public function getListHtml($value)
    {
        return Element::td($this->getListValue($value) ? '&bull;' : '');
    }
}
