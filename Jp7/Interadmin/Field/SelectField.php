<?php

namespace Jp7\Interadmin\Field;

use Former;

class SelectField extends ColumnField
{
    use SelectFieldTrait;
    
    protected $id = 'select';
    
    const XTRA_RECORD = '0';
    const XTRA_RECORD_RADIO = 'radio';
    const XTRA_RECORD_AJAX = 'ajax';
    const XTRA_TYPE = 'S';
    const XTRA_TYPE_RADIO = 'radio_tipos';
    const XTRA_TYPE_AJAX = 'ajax_tipos';
        
    public function getCellHtml()
    {
        return $this->formatText($this->getValue(), true);
    }

    public function getText()
    {
        return $this->formatText($this->getValue(), false);
    }
    
    public function hasTipo()
    {
        return in_array($this->xtra, [self::XTRA_TYPE, self::XTRA_TYPE_AJAX, self::XTRA_TYPE_RADIO]);
    }
    
    protected function getFormerField()
    {
        return Former::select($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue())
            ->options($this->getOptions());
    }
}
