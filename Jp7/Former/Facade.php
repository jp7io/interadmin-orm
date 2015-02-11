<?php

namespace Jp7\Former;

class Facade extends \Former\Facades\Former {

	private static $extension;

	public static function getFacadeRoot() {
		if (!self::$extension) {
			self::$extension = new FormerExtension(parent::getFacadeRoot());
		}
		return self::$extension;
	}
}