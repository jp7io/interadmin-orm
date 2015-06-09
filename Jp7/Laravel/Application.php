<?php

namespace Jp7\Laravel;

// CODE COPIED FROM LARAVEL TO CHANGE THE NAMESPACE TO JP7
class Application extends \Illuminate\Foundation\Application
{
    /**
     * Register the routing service provider.
     */
    protected function registerRoutingProvider()
    {
        $this->register(new RoutingServiceProvider($this));
    }
}
