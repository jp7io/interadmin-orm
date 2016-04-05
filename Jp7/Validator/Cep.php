<?php

namespace Jp7\Validator;

class Cep
{
    public function validate($attribute, $value, $parameters)
    {
        return preg_match('/^[0-9]{5}-[0-9]{3}$/', $value);
    }
}
