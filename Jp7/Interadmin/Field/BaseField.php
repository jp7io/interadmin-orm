<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use Former;

abstract class BaseField implements FieldInterface
{
    /**
     * @var string  Field identifier
     */
    protected $id;
    /**
     * @var object
     */
    protected $record;
    
    public function setRecord($record)
    {
        assert(is_object($record) || is_null($record));
        $this->record = $record;
    }
    
    public function getHeaderTag()
    {
        return Element::th($this->getHeaderHtml())
            ->class($this->id);
    }

    public function getCellTag()
    {
        return Element::td($this->getCellHtml())
            ->class($this->id);
    }
    
    public function getHeaderHtml()
    {
        return e($this->getLabel());
    }

    public function getCellHtml()
    {
        return nl2br(e($this->getText()));
    }
    
    /**
     * Return object for <div class="form-group">...</div>
     *
     * @return Element|string
     */
    public function getEditTag()
    {
        return $this->getFormerField()
            ->label($this->getLabel());
    }
    
    /**
     * Former field. A Former field has 3 parts: element, label and group.
     * Group and label attributes should be changed on getEditTag().
     *
     * @see https://github.com/formers/former/wiki/Usage-and-Examples
     * @return Former\Traits\Field
     */
    protected function getFormerField()
    {
        return Former::text($this->id);
    }
    
    public function getRules()
    {
        return [];
    }
}
