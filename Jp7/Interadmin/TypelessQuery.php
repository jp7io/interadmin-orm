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
        $options = $this->options;
        $options['fields'] = "COUNT(*)";
        $options['limit'] = 1;
        $result = $this->provider->deprecatedTypelessFind($options)->first();
        return $result->count;
    }
}
