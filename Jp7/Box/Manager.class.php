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
	 * Adds a box to the array.
	 * @return void
	 */
	public static function add($id, $className) {
		self::$array[$id] = $className;
	}
}