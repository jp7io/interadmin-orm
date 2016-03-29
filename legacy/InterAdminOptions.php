<?php

class InterAdminOptions extends InterAdminOptionsBase
{
    public function all()
    {
        return $this->provider->find($this->options);
    }

    public function first()
    {
        return $this->provider->findFirst($this->options);
    }
    
    public function createInterAdmin(array $attributes = [])
    {
        return $this->provider->createInterAdmin($attributes);
    }
}
