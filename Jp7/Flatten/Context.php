<?php
namespace Jp7\Flatten;

/**
 * Provides informations about the current context
 */
class Context extends \Flatten\Context
{
	// JP7: Permite cachear AJAX
	public function shouldCachePage() {
		// Check if the content type of the page is allowed to be cached
		if ($this->app['request']->getMethod() !== 'GET') {
			return false;
		}

		// Get pages to cache
		$only    = (array) $this->app['config']->get('flatten::only');
		$ignored = (array) $this->app['config']->get('flatten::ignore');
		$cache   = false;

		// Ignore and only
		if (empty($ignored) && empty($only)) {
			$cache = true;
		} else {
			if (!empty($only) && $this->matches($only)) {
				$cache = true;
			}
			if (!empty($ignored) && !$this->matches($ignored)) {
				$cache = true;
			}
		}

		return (bool) $cache;
	}
	
}
