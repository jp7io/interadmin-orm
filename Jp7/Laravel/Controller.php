<?php

namespace Jp7\Laravel;

class Controller extends \Illuminate\Routing\Controller {
	
	protected static $current = null;

	protected $layout = 'layouts.master';

	public function __construct() {
		static::$current = $this;
	}

	/* Temporary solution - Avoid using this as much as possible */
	public static function getCurrentController() {
		return static::$current;
	}
}
