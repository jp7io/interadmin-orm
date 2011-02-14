<?php

class Jp7_Box_Manager {    /**
     * @var array
     */
	private static $array = array();
	
	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Static class
	}
    /**
     * Returns $array.
     *
     * @see Jp7_Box_Manager::$array
     */
    public static function getArray() {
        return self::$array;
    }
	/**
	 * Sets a classname to the given box id.
	 * @return void
	 */
	public static function set($id, $className) {
		self::$array[$id] = $className;
	}
	/**
	 * Gets the classname for the given box id.
	 * @return string
	 */
	public static function get($id) {
		return self::$array[$id];
	}
}