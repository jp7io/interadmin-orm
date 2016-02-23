<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use ADOFetchObj;

abstract class BaseField implements FieldInterface
{
    protected $name;
    
    public function getHeaderTag() {
        return Element::th($this->getHeaderHtml())
            ->class($this->name);
    }

    public function getCellTag(ADOFetchObj $record) {
        return Element::td($this->getCellHtml($record))
            ->class($this->name);
    }
    
    public function getHeaderHtml()
    {
        return $this->getHeaderText();
    }

    public function getCellHtml(ADOFetchObj $record)
    {
        return $this->getCellText($record);
    }
}
