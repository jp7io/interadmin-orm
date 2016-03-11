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
        $ids = jp7_explode(',', $this->getValue());
        $array = [];
        foreach ($ids as $id) {
            $array[] = $this->formatText($id, $html);
        }
        return $array;
    }
    
    public function hasTipo()
    {
        return $this->xtra === self::XTRA_TYPE;
    }
    
    protected function getFormerField()
    {
        return Former::checkboxes($this->getFormerName())
                ->checkboxes($this->getCheckboxes())
                ->onGroupAddClass('has-checkboxes');
    }
    
    protected function getCheckboxes()
    {
        $checkboxes = [];
        $name = $this->getFormerName().'[]';
        $ids = jp7_explode(',', $this->getValue());
        foreach ($this->getOptions() as $key => $value) {
            $checkboxes[$value] = [
                'name' => $name,
                'value' => $key, // ID
                'checked' => in_array($key, $ids),
                'required' => false // HTML5 validation can't handle multiple checkboxes
            ];
        }
        return $checkboxes;
    }
}
