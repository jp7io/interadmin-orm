<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @package JP7
 */
 
/**
 * FileCache class, used to store copies of pages to save database connections and processing time.
 *
 * @subpackage FileCache
 */
class FileCache{
	/**
	 * Site root directory.
	 * @var string 
	 */
	public $fileRoot;
	/**
	 * Path used to store cached files.
	 * @var string 
	 */
	public $cachePath;
	/**
	 * Name of the file to be cached or loaded from cache.
	 * @var string 
	 */
	public $fileName;
	/**
	 * Time delay before re-caching.
	 * @var int 
	 */
	protected $delay;
	/**
	 * If <tt>TRUE</tt> exits the script after retrieving the cached file. Set it as <tt>FALSE</tt> when caching parts of a page.
	 * @var bool
	 */
	public $exit;
	/**
	 * Public Constructor, defines the path and filename and starts caching or loading it.
	 *
	 * @param mixed $storeId ID of the file. Only needed if the same page has different data deppending on the ID.
	 * @param string $cachePath Sets the directory where the cache will be saved, the default value is 'cache'.
	 * @global string
 	 * @global string
 	 * @global bool
  	 * @global int
	 * @global array
	 * @global Debug
  	 * @global bool
	 * @global bool
	 */	
	public function __construct($storeId = FALSE, $exit = TRUE, $cachePath = 'cache') {
		global $c_root, $c_path, $c_cache, $c_cache_delay, $c_devIps, $debugger, $s_session, $interadmin_gerar_menu;
		// Cache not desired
		if (!$c_cache || $debugger->debugFilename || $debugger->debugSql || $s_session['preview'] || $interadmin_gerar_menu) return;

		$this->fileRoot = $c_root;
		$this->cachePath = $this->fileRoot . $cachePath . '/';
		// Parsing Filename
		$this->fileName = substr($_SERVER['REQUEST_URI'], strlen($c_path) + 1);
		$pos_query = strpos($this->fileName, '?');
		if ($pos_query !== FALSE) $this->fileName = substr($this->fileName, 0, $pos_query);
		$this->fileName = jp7_path($this->fileName, TRUE);
				
		$pathinfo = pathinfo($this->cachePath . $this->fileName);
		// Parsing ID for dynamic content
		if ($storeId){
			if ($pathinfo['extension']) $ext = '.' . $pathinfo['extension'];
			$this->fileName = dirname($this->fileName) . '/' . basename($this->fileName, $ext) . '/' . $storeId . $ext . '.cache';
		}else{
			if ($pathinfo['extension']) $this->fileName .= '.cache';
			else $this->fileName .= (($this->fileName) ? '/' : '') . 'index.cache';
		}
		// Setting default behaviors
		$this->exit = $exit;
		$this->setDelay($c_cache_delay, $c_devIps);
		// Retrieving/creating cache
		if ($this->checkLog() && !$_GET['nocache_force']) $this->getCache();
		else $this->startCache();
	}
	/**
	 * Sets delay time.
	 *
	 * @return void
	 */	
	public function setDelay($time, $devIps) {
		if (!in_array($_SERVER['REMOTE_ADDR'], (array) $devIps)) {
			$this->delay = $time;
		}
	}
	/**
	 * Starts caching the current file.
	 *
	 * @return void
	 */	
	public function startCache() {
		//if ($this->exit) header('pragma: no-cache');
		ob_start();
	}
	/**
	 * Stops caching and saves the current file, the file is saved with a commentary saying when it was published.
	 *
	 * @return void
	 */	
	public function endCache() {
		if (!$this->fileName) return;

		$file_content = ob_get_contents();
		
		/* Comentando, estava gerando resultados diferentes entre conteudo cacheado ou não
		$file_content = str_replace(chr(9), '', $file_content); 
		$file_content = str_replace(chr(13), '', $file_content);
		*/
		
		// Checking if there is enough content to cache
		if (strlen($file_content) > 100) {
			// Creating directories
			$dir_arr = explode('/', $this->fileName);
			array_pop($dir_arr);
			$dir_path = '';
			foreach ($dir_arr as $dir) {
					$dir_path .= $dir . '/';
					if (!is_dir($this->cachePath . $dir_path)) {
						@mkdir($this->cachePath . $dir_path);
						@chmod($this->cachePath . $dir_path, 0777);
					}
			}
			// Saving file and changing permissions
			$file = @fopen($this->cachePath . $this->fileName, 'w');
			$file_content .= "\n" . '<!-- Published by JP7 InterAdmin in ' . date('Y/m/d - H:i:s') . ' -->';
			@fwrite($file, $file_content);
			@chmod($this->cachePath . $this->fileName, 0777);
		}
		ob_end_flush();
	}
	/**
	 * Opens the cached file and outputs it.
	 *
	 * @return void
	 */
	public function getCache() {
		global $debugger;
		readfile($this->cachePath . $this->fileName);
		$this->isCached = TRUE;
		if ($this->exit) {
			$debugger->showToolbar();
		 	exit();
		}
	}
	/**
	 * Checks if the log file is newer than the cached file,  and if the cached file is older than 1 day.
	 *
	 * @return bool
	 */	
	public function checkLog() {
		$cache_time = @filemtime($this->cachePath . $this->fileName);
		$log_time = @filemtime($this->fileRoot . 'interadmin.log');
		// TRUE = Cache is ok, no need to refresh
		if ($cache_time && time() - $log_time < $this->delay) return TRUE;
		if (($log_time < $cache_time) && date('d', $cache_time) == date('d')) return TRUE;
		return FALSE;
	}
}
?>