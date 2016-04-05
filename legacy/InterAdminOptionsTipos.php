<?php

class InterAdminOptionsTipos extends InterAdminOptionsBase
{
    public function all()
    {
        return InterAdminTipo::findTipos($this->options);
    }

    public function first()
    {
        return InterAdminTipo::findFirstTipo($this->options);
    }
    
    public function __call($method_name, $params)
    {
        throw new BadMethodCallException($method_name);
    }
}
