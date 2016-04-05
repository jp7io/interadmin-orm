<?php

/**
 * É usado para simular um parâmetro de cada método no WebService.
 */
class Jp7_Interadmin_Soap_ReflectionParameter
{
    protected $name;
    protected $type;

    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isOptional()
    {
        return true;
    }
}
