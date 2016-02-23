<?php

namespace Jp7\Interadmin\Field;

class Password extends Base
{
    public function getListValue($value)
    {
         return $value ? '******' : '';
    }
}
