<?php

namespace Jp7\Validator;

class NotPresent
{
    public function validate($attribute, $value, $parameters)
    {
        return is_null($value);
    }
}
