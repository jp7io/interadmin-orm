<?php

namespace Jp7\Interadmin\Field;

use Former;

class SelectMultiField extends ColumnField
{
    use SelectFieldTrait;

    protected $id = 'select_multi';

    const XTRA_RECORD = '0'; // checkboxes
    const XTRA_TYPE = 'S';   // checkboxes
    const XTRA_RECORD_SEARCH = 'X';

    public function getCellHtml()
    {
        return implode(',<br>', $this->getTextArray(true));
    }

    public function getText()
    {
        return implode(",\n", $this->getTextArray(false));
    }

    protected function getTextArray($html)
    {
        $array = [];
        foreach ($this->getCurrentRecords() as $related) {
            $array[] = $this->formatText($related, $html);
        }
        return $array;
    }

    public function hasTipo()
    {
        return $this->xtra === self::XTRA_TYPE;
    }

    public function getEditTag()
    {
        // Push checkbox / Former can't handle this on multiple checkboxes
        return '<input type="hidden" value="" name="'.$this->getFormerName().'" />'.
            parent::getEditTag();
    }

    protected function getFormerField()
    {
        $field = Former::checkboxes($this->getFormerName().'[]'); // [] makes it "grouped"
        return $field->push(false)
                ->checkboxes($this->getCheckboxes($field))
                ->onGroupAddClass('has-checkboxes');
                // ->id($this->getFormerId()) // Wont work with checkboxes
    }

    public function getFilterTag()
    {
        $selectField = new SelectField($this->campo);
        $selectField->setRecord($this->record);
        return $selectField->getFilterTag();
    }

    protected function getCheckboxes($field)
    {
        $checkboxes = [];
        // Problem with populate from POST: https://github.com/formers/former/issues/364
        $ids = $field->getValue();
        if (!$ids) {
            $ids = array_filter(explode(',', $this->getValue()));
        }

        foreach ($this->getOptions() as $key => $value) {
            $checkboxes[$value.'<s>'.$key.'</s>'] = [ // s = avoid collision
                'value' => $key, // ID
                'checked' => in_array($key, $ids),
                'required' => false // HTML5 validation can't handle multiple checkboxes
            ];
        }
        return $checkboxes;
    }
}
