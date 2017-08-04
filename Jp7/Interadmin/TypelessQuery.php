<?php

namespace Jp7\Interadmin;

class TypelessQuery extends Query
{
    protected function providerFind($options)
    {
        return $this->provider->deprecatedTypelessFind($options);
    }

    public function count()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->provider->deprecatedCount($this->options, true);
    }
}
