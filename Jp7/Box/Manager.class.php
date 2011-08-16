<?php

class Jp7_Box_Manager {
    const COL_1_LEFT = 1;
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
	 * @var	Zend_View
	 */
	private static $view = null;
	
	/**
     * @var array
     */
	private static $array = array(
		'content' => 'Jp7_Box_Content',
		'facebook' => 'Jp7_Box_Facebook',
		'facebook-comments' => 'Jp7_Box_FacebookComments',
		'files' => 'Jp7_Box_Files',
		'html' => 'Jp7_Box_Html',
		'iframe' => 'Jp7_Box_Iframe',
		'images' => 'Jp7_Box_Images',
		'news' => 'Jp7_Box_News',
		'news-archive' => 'Jp7_Box_NewsArchive',
		'offices' => 'Jp7_Box_Offices',
		'sections' => 'Jp7_Box_Sections',
		'slideshow' => 'Jp7_Box_Slideshow',
		'twitter' => 'Jp7_Box_Twitter',
		'videos' => 'Jp7_Box_Videos',
		'youtube' => 'Jp7_Box_Youtube',
		'_content' => 'Jp7_Box_PageContent'
	);
	
	private static $recordMode = false;
	
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
     * Returns the keys on $array.
     * 
     * @return array
     */
    public static function getKeys() {
        return array_keys(self::$array);
    }
	/**
	 * Sets a classname to the given box id.
	 * 
	 * @param string $id
	 * @param string $className
	 * @return void
	 */
	public static function set($id, $className) {
		self::$array[$id] = $className;
	}
	/**
	 * Gets the classname for the given box id.
	 * 
	 * @param string $id
	 * @return string
	 */
	public static function get($id) {
		return self::$array[$id];
	}
	/**
	 * Removes an item from the array.
	 * 
	 * @param string $id
	 * @return void
	 */
	public static function remove($id) {
		unset(self::$array[$id]);
	}
	/**
	 * Creates a Jp7_Box_BoxAbstract from a record.
	 * 
	 * @param InterAdmin $record
	 * @return Jp7_Box_BoxAbstract
	 */
	public static function createBox($record) {
		if ($classe = self::get($record->id_box)) {
			$box = new $classe($record);
			if (!$box instanceof Jp7_Box_BoxAbstract) {
				throw new Exception('Expected an instance of Jp7_Box_BoxAbstract, received a ' . get_class($box) . '.');
			}
			$box->view = self::$view;
			return $box;
		}
	}
	
	public static function createBoxFromId($id_box) {
		$fakeRecord = new InterAdmin();
		$fakeRecord->id_box = $id_box;
		return self::createBox($fakeRecord);
	}
	
	/**
	 * Builds the boxes considering the $boxTipo and $pageRecord.
	 * 
	 * @param 	InterAdminTipo 	$boxTipo
	 * @param 	InterAdmin 		$pageRecord [optional]
	 * @return 	InterAdmin[]	An array of columns. Each column has an attribute called "boxes".
	 */
	public static function buildBoxes($boxTipo) {
		$records = $boxTipo->getInterAdmins(array(
			'fields' => array('*'),
			'where' => array(self::getRecordMode() ? "records_page <> ''" : "records_page = ''")
		));
		// Convert to objects
		$columns = self::createObjects($records);
		// Layout
		$parentTipo = $boxTipo->getParent();
		$layout = self::getRecordMode() ? $parentTipo->layout_registros : $parentTipo->layout;
		if ($layout) {
			$position = self::$positions[$layout];
			if (!$columns[$position]->boxes) {
				$columns[$position]->width = self::$widths[$layout];
				$columns[$position]->boxes = array(self::createBoxFromId('_content'));
			}
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
				if ($box = self::createBox($record)) {
					$column->boxes[] = $box;
				}
			}
		}
		return $columns;
	}
	/**
     * Returns $view.
     *
     * @return Zend_View
     */
    public static function getView() {
        return self::$view;
    }
    /**
     * Sets $view.
     *
     * @param Zend_View $view
     * @see Jp7_Box_Manager::$view
     */
    public static function setView(Zend_View $view) {
		self::$view = $view;
    }
	
	public static function setRecordMode($mode) {
		self::$recordMode = (bool) $mode;
	}
	public static function getRecordMode() {
		return self::$recordMode;
	}
}