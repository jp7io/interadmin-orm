<?php

namespace Jp7\Interadmin\Field;

use Former;

class CharField extends ColumnField
{
    protected $id = 'char';

    const XTRA_UNCHECKED = '0';
    const XTRA_CHECKED = 'S';

    public function getCellHtml()
    {
        return $this->getValue() ? '&bull;' : '';
    }

    protected function getFormerField()
    {
        $input = Former::checkbox($this->getFormerName())
            ->id($this->getFormerId())
            ->setAttribute('value', 'S')
            ->text('&nbsp;'); // Bootstrap CSS - padding
        // initial check status
        if ($input->getValue() === null && $this->getValue()) {
            $input->check();
        }
        return $input;
    }

    protected function getDefaultValue()
    {
        if ($this->default) {
            return $this->default;
        }
        if ($this->xtra === self::XTRA_CHECKED) {
            return 'S';
        }
    }

    public function hasMassEdit()
    {
        return true;
    }
}



