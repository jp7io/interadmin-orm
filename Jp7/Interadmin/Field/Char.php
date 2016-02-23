<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;

class Char extends Base
{
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
