<?php

namespace Jp7\Interadmin\Field;

class SelectMultiField extends SelectField
{
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
        $ids = jp7_explode(',', ColumnField::getText());
        $array = [];
        foreach ($ids as $id) {
            $array[] = $this->formatText($id, $html);
        }
        return $array;
    }
}
