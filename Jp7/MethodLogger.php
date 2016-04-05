<?php

namespace Jp7;

class MethodLogger extends MethodForwarder
{
    protected $log = [];
    
    public function __call($method, $arguments)
    {
        $this->log[] = [
            'method' => $method,
            'arguments' => $arguments,
        ];
        
        return parent::__call($method, $arguments);
    }
    
    public function _getLog()
    {
        return $this->log;
    }

    public function _replay($logs)
    {
        foreach ($logs as $log) {
            call_user_func_array([$this->target, $log['method']], $log['arguments']);
        }
    }
}
