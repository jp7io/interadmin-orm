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
class FileCache {
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
	 * If <tt>FALSE</tt> exits the script after retrieving the cached file. Set it as <tt>FALSE</tt> when caching parts of a page.
	 * @var bool
	 */
	public $partial;
	/**
	 * @var bool
	 */
	public $isCached;
	protected static $placeholderEnabled = false;
	/**
	 * Public Constructor, defines the path and filename and starts caching or loading it.
	 *
	 * @param 	mixed 	$storeId 	ID of the file. Only needed if the same page has different data deppending on the ID.
	 * @param 	bool 	$partial 	FALSE
	 * @param 	string 	$cachePath 	Sets the directory where the cache will be saved, the default value is 'cache'.
	 * @param 	int 	$lifetime 	Lifetime in seconds. Cached files with $lifetime == 0 expire using getLogTime(). 
	 */	
	public function __construct($storeId = false, array $options = array()) {
		$default = array(
			'partial' 	=> false,
			'cachePath' => 'cache',
			'lifetime'	=> 0
		);
		extract($options + $default);
		
		global $c_doc_root, $c_cache, $c_cache_delay;
		global $debugger, $s_session, $interadmin_gerar_menu;
		
		// Cache not desired
		if (!$c_cache || $debugger->debugFilename || $debugger->debugSql || $s_session['preview'] || $interadmin_gerar_menu) {
			return;
		}
		
		$this->fileRoot = $c_doc_root;
		$this->cachePath = $this->getCachePath($this->fileRoot, $cachePath);
		
		if ($partial) {
			$nocache_force = $_GET['nocache_partial'];
			if ($options['keepUri']) {
				$this->fileName = self::getFileName($_SERVER['REQUEST_URI'], '_partial_' . $storeId, $this->cachePath);
			} else {
				$this->fileName = '_partial_' . $storeId . '.cache';
			}
		} else {
			self::$placeholderEnabled = true;
			
			$nocache_force = $_GET['nocache_force'];
			// Retirando query string e $c_path
			$this->fileName = self::getFileName($_SERVER['REQUEST_URI'], $storeId, $this->cachePath);
			if (!$this->fileName) {
				return; // Falha de segurança.	
			}
		}
			
		// Other Settings
		$this->partial = $partial;
		$this->setDelay($c_cache_delay);
		
		// Retrieving/creating cache - Está cacheada
		if ($this->checkLog($lifetime) && !$nocache_force) {
			$this->getCache();
		} else {
			$this->startCache(); // Não está cacheada, cachear agora
		}
	}
	/**
	 * Sets delay time.
	 *
	 * @return void
	 */	
	public function setDelay($time) {
        global $c_devIps;
		if (!in_array($_SERVER['REMOTE_ADDR'], (array) $c_devIps)) {
			$this->delay = $time;
		}
	}
	/**
	 * Starts caching the current file.
	 *
	 * @return void
	 */	
	public function startCache() {
		//if ($this->partial) {
			ob_start();
		//		} else {
		//			ob_start('ob_gzhandler');
		//		}
	}
	/**
	 * Stops caching and saves the current file, the file is saved with a commentary saying when it was published.
	 *
	 * @return void
	 */	
	public function endCache() {
		if (!$this->fileName) {
			return;
		}

		$file_content = ob_get_clean();
		
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
		if ($this->partial) {
			echo $file_content;
		} else {
			echo $this->replacePlaceholders($file_content);	
		}
	}
	/**
	 * Opens the cached file and outputs it.
	 *
	 * @return void
	 */
	public function getCache() {
		$file_content = file_get_contents($this->cachePath . $this->fileName);
		if ($this->partial) {
			echo $file_content;
			$this->isCached = true;
		} else {
			echo $this->replacePlaceholders($file_content);
			
			global $debugger, $c_jp7;
			
			// Debug
            if ($c_jp7 && strpos($this->fileName, 'xml') === false) {
                global $config;
                $css = 'position:absolute;border:1px solid black;border-top:0px;font-weight:bold;top:0px;padding:5px;background:#FFCC00;filter:alpha(opacity=50);opacity: .5;z-index:1000;cursor:pointer;';
                $title = array(
                    '# Cache: ',
                        '  ' . $this->cachePath . $this->fileName,
                        '  ' . date('d/m/Y H:i:s', @filemtime($this->cachePath . $this->fileName)), 
                    '# Log: ',
                        '  ' . $this->getLogFilename(),
                        '  ' . date('d/m/Y H:i:s', @filemtime($this->getLogFilename())),
                    '# Hora do servidor: ' . date('d/m/Y H:i:s', time()),
                    '# Delay para limpeza: ' . intval($this->delay) . ' segundos',
                );

                $title = implode('&#013;', $title);
                
                $urlNoCache = preg_replace('/^([^&]*)([&]?)([^&]*)$/', '$1?$3$2nocache_force=true', str_replace('?', '&', $_SERVER['REQUEST_URI']));
                $event = 'onclick="if (confirm(\'Deseja atualizar o cache desta página?\')) window.location = \'' . $urlNoCache . '\'"';
                
                echo '<div style="' . $css . 'left:0px;" title="' . $title . '" ' . $event . '>CACHE</div>';
                echo '<div style="' . $css . 'right:0px;" title="' . $title . '" ' . $event . '>CACHE</div>';
            }
			$debugger->showToolbar();
			
			// Finaliza
			//ob_end_flush();
		 	exit();
		}	
	}
	
	public static function getCachePath($fileRoot, $cachePath = 'cache') {
		global $config; 
		return $fileRoot . $config->name_id . '/' . $cachePath . '/';
	}
	
	public static function getFileName($request_uri, $storeId = null, $cachePath = '') {
		global $c_path;
		$fileName = preg_replace('/\/' . addcslashes($c_path, '/') . '([^?]*)(.*)/', '\1', $request_uri);
		$fileName = jp7_path($fileName, true);
		
		// Parsing ID for dynamic content
		if ($storeId){
			preg_match('/\.([^\.]+)$/', $cachePath . $fileName, $matches);    
			if ($ext = $matches[1]) {
				$ext = '.' . $ext;
			}
			$fileName = dirname($fileName) . '/' . basename($fileName, $ext) . '/' . $storeId . $ext;
		} elseif (strpos($fileName, '.') === false) {
			$fileName .= (($fileName) ? '/' : '') . 'index';
		}
		
		// Falha de segurança. Passou com conteúdo inválido. Investigar depois.
		if (preg_match('(%|:|=|\.\.|\*|\?)', $fileName) || strlen($fileName) > 200) {
			die('entrou');
			return false;
		}
		$fileName .= '.cache';
		return $fileName;
	}
	public function replacePlaceholders($filecontent) {
		self::$placeholderEnabled = false;
		return $filecontent;
	}
	/**
	 * Checks if the log file is newer than the cached file,  and if the cached file is older than 1 day.
	 * 
	 * @return bool
	 */	
	public function checkLog($lifetime = 0) {
		$cache_time = @filemtime($this->cachePath . $this->fileName);
		if ($lifetime) {
			if ($cache_time && $cache_time > time() - $lifetime) {
				return true;
			}
		} else {
			$log_time = @filemtime($this->getLogFilename());
			// TRUE = Cache is ok, no need to refresh
			if ($cache_time && time() - $log_time < $this->delay) {
				return true;
			}
			// Outro dia é atualizado o cache
			if (($log_time < $cache_time) && date('d', $cache_time) == date('d')) {
				return true;
			}
		}
		// FALSE = Atualizar cache
		return false;
	}
	/**
	 * Returns TRUE if this partial is already cached.
	 * 
	 * @return bool
	 */
	public function isCached() {
		return (bool) $this->isCached;
	}
	/**
	 * @return int Timestamp 
	 */
	public function getLogFilename() {
		global $config;
		return $this->fileRoot . $config->name_id . '/interadmin/interadmin.log';
	}
	public static function getPlaceholder($name, $vars = array()) {
		return '{CACHE:' . $name . '|' . serialize($vars) . '}' . "\n";
	}
	
	public static function isPlaceholderEnabled() {
		return self::$placeholderEnabled;
	}
	
	protected function _replacePlaceholder($name, $include, $filecontent) {
		if (strpos($filecontent, '{CACHE:' . $name) !== false) {
			preg_match('/{CACHE:' . $name . '\|(.*)}/', $filecontent, $matches);
			$vars = unserialize($matches[1]);
			extract($vars);
			
			ob_start();
			include $include;
			$include_content = ob_get_clean();
			
			$filecontent = preg_replace('/{CACHE:' . $name . '\|(.*)}/', preg_replacement_quote($include_content), $filecontent);
		}
		return $filecontent;
	}
}
