<?php

namespace Jp7\Laravel;

class Application extends \Illuminate\Foundation\Application {
	/**
	 * Register the routing service provider.
	 *
	 * @return void
	 */
	protected function registerRoutingProvider()
	{
		$this->register(new RoutingServiceProvider($this));
	}
}
