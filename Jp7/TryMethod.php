<?php

namespace Jp7;

trait TryMethod
{
    public function _try($attribute)
    {
        return $this->$attribute ?: new NullObject;
    }
}
