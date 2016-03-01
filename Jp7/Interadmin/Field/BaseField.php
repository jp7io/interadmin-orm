<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use Former;
use ADOFetchObj;

abstract class BaseField implements FieldInterface
{
    protected $name;
    protected $record;
    
    public function setRecord(ADOFetchObj $record)
    {
        $this->record = $record;
    }
    
    public function getHeaderTag()
    {
        return Element::th($this->getHeaderHtml())
            ->class($this->name);
    }

    public function getCellTag()
    {
        return Element::td($this->getCellHtml())
            ->class($this->name);
    }
    
    public function getHeaderHtml()
    {
        return e($this->getLabel());
    }

    public function getCellHtml()
    {
        return nl2br(e($this->getText()));
    }
    
    public function getEditTag()
    {
        return $this->getFormerField()
            ->label($this->getLabel());
    }
    
    protected function getFormerField()
    {
        return Former::text($this->name);
    }
}
