<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use Former;
use InterAdminTipo;

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
    /**
     * @var InterAdminTipo
     */
    protected $type;
    /**
     * @var int|null
     */
    protected $index = null;
    
    public function setRecord($record)
    {
        assert(is_object($record) || is_null($record));
        $this->record = $record;
    }
    
    public function setType(InterAdminTipo $type)
    {
        $this->type = $type;
    }
    
    public function setIndex($index)
    {
        $this->index = $index;
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
        return Former::text($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue());
    }
    
    protected function getFormerName()
    {
        return $this->id.(is_null($this->index) ? '' : '['.$this->index.']');
    }
    
    protected function getFormerId()
    {
        return $this->id.(is_null($this->index) ? '' : '_'.$this->index);
    }
    
    protected function getRuleName()
    {
        $name = str_replace( // same replace Laravel and Former do
            ['[', ']'],
            ['.', ''],
            $this->getFormerName()
        );
        return trim($name, '.');
    }
    
    protected function getValue()
    {
        $column = $this->id;
        $value = $this->record->$column;
        if (!$this->record->id && !$value) {
            $value = $this->getDefaultValue();
        }
        return $value;
    }
    
    protected function getDefaultValue()
    {
        return null;
    }
    
    public function getRules()
    {
        return [];
    }
}
