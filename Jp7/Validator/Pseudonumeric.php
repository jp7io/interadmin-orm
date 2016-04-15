<?php

namespace Jp7\Validator;

class Pseudonumeric
{
    public function validate($attribute, $value, $parameters)
    {
        return preg_match('/^[+-]?[0-9]+([0-9,.]*[0-9]+)?$/', $value);
    }
}
