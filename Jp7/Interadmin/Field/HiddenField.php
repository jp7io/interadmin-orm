<?php

namespace Jp7\Interadmin\Field;

use Former;

class HiddenField extends ColumnField
{
    protected $id = 'hidden';

    protected function getFormerField()
    {
        return Former::hidden($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue());
    }
}
