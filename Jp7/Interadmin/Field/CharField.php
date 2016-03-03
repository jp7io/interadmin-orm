<?php

namespace Jp7\Interadmin\Field;

use Former;

class CharField extends ColumnField
{
    protected $id = 'char';
    
    public function getCellHtml()
    {
        return $this->getText() ? '&bull;' : '';
    }
    
    protected function getFormerField()
    {
        $input = Former::checkbox($this->getFormerName())
            ->text('&nbsp;'); // Bootstrap CSS - padding
        
        // TODO marcado xtra
        if ($this->getValue()) {
            $input->check();
        }
        return $input;
    }
}
