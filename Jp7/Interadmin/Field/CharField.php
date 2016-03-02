<?php

namespace Jp7\Interadmin\Field;

use Former;

class CharField extends ColumnField
{
    protected $name = 'char';
    
    public function getCellHtml()
    {
        return $this->getText() ? '&bull;' : '';
    }
    
    protected function getFormerField()
    {
        $input = Former::checkbox($this->getFormerName())
            ->text('&nbsp;'); // Bootstrap CSS - padding
        
        if ($this->getText()) {
            $input->check();
        }
        return $input;
    }
}
