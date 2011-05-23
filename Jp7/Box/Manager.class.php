<?php

class Jp7_Box_Manager {    const COL_1_LEFT = 1;
	const COL_1_CENTER = 2;
	const COL_1_RIGHT = 3;
	const COL_2_LEFT = 4;
	const COL_2_RIGHT = 5;
	const COL_3 = 6;
	
	public static $labels = array(
		self::COL_1_LEFT 	=> '1 coluna - Esquerda',
		self::COL_1_CENTER 	=> '1 coluna - Centro',
		self::COL_1_RIGHT 	=> '1 coluna - Direita',
		self::COL_2_LEFT 	=> '2 colunas - Esquerda',
		self::COL_2_RIGHT 	=> '2 colunas - Direita',
		self::COL_3 		=> '3 colunas'
	);
	
	public static $positions = array(
		self::COL_1_LEFT 	=> 0,
		self::COL_1_CENTER 	=> 1,
		self::COL_1_RIGHT 	=> 2,
		self::COL_2_LEFT 	=> 0,
		self::COL_2_RIGHT 	=> 1,
		self::COL_3 		=> 0
	);
	
	public static $widths = array(
		self::COL_1_LEFT 	=> 1,
		self::COL_1_CENTER 	=> 1,
		self::COL_1_RIGHT 	=> 1,
		self::COL_2_LEFT 	=> 2,
		self::COL_2_RIGHT 	=> 2,
		self::COL_3 		=> 3
	);
	
	/**
     * @var array
     */
	private static $array = array(
		'facebook' => 'Jp7_Box_Facebook',
		'news' => 'Jp7_Box_News',
		'images' => 'Jp7_Box_Images',
		'slideshow' => 'Jp7_Box_Slideshow',
		'offices' => 'Jp7_Box_Offices',
		'content' => 'Jp7_Box_Content'
	);
	
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
	
	public static function remove($id) {
		unset(self::$array[$id]);
	}
	
	/**
	 * Builds the boxes considering the $boxTipo and $pageRecord.
	 * 
	 * @param 	InterAdminTipo 	$boxTipo
	 * @param 	InterAdmin 		$pageRecord [optional]
	 * @return 	InterAdmin[]	An array of columns. Each column has an attribute called "boxes".
	 */
	public static function buildBoxes($boxTipo, $pageRecord = null) {
		$records = $boxTipo->getInterAdmins(array(
			'fields' => array('*'),
			'where' => array($pageRecord ? "records_page <> ''" : "records_page = ''")
		));
		// Convert to objects
		$columns = self::createObjects($records);
		// Layout
		$parentTipo = $boxTipo->getParent();
		$layout = $pageRecord ? $parentTipo->layout_registros : $parentTipo->layout;
		if ($layout) {
			$position = self::$positions[$layout];
			$columns[$position]->content = true;
			$columns[$position]->boxes = array();
			$columns[$position]->width = self::$widths[$layout];
		}
		
		// Loading data
		foreach ($columns as $column) {
			foreach ($column->boxes as $box) {
				$box->prepareData();
			}
		}
		return $columns;
	}
	/**
	 * Creates objects from records using their respective classes.
	 * 
	 * @param 	InterAdmin[] 	$columns
	 * @return 	InterAdmin[]	An array of columns. Each column has an attribute called "boxes".
	 */
	public static function createObjects($columns) {
		foreach ($columns as $column) {
			$records = $column->getBoxes(array(
				'fields' => array('*'),
				'fields_alias' => true,
				'use_published_filters' => true
			));
			$column->boxes = array();
			foreach ($records as $record) {
				if ($classe = self::get($record->id_box)) {
					$box = new $classe($record);
					if (!$box instanceof Jp7_Box_BoxAbstract) {
						throw new Exception('Expected an instance of Jp7_Box_BoxAbstract, received a ' . get_class($box) . '.');
					}
					$column->boxes[] = $box;
				}
			}
		}
		return $columns;
	}
}