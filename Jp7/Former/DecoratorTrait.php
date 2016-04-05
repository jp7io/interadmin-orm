<?php

namespace Jp7\Former;

trait DecoratorTrait
{
    private $decorators = [];

    abstract public function __call($method, $arguments);

    public function decorator()
    {
        $decorator = new Decorator();
        $this->decorators[] = $decorator;

        return $decorator;
    }

    public function closeDecorator()
    {
        array_pop($this->decorators);
    }

    private function decorateField($field)
    {
        foreach ($this->decorators as $decorator) {
            $decorator->_runOn($field);
        }
    }
}
