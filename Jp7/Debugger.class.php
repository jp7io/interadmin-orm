<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category Jp7
 * @package Jp7_Debugger
 */
 
/**
 * Debug class, used to display filenames, processing time and display formatted SQL queries.
 *
 * @package Jp7_Debugger
 */
class Jp7_Debugger{
	const EMAIL = 'debug@jp7.com.br';
	/**
	 * Flag, it is <tt>TRUE</tt> if its displaying filenames or SQL queries.
	 * @var bool
	 */
	public $active;
	/**
	 * Flag indicating if filenames will be showed or not. Use $_GET['debug_filename'] to set it.
	 * @var bool 
	 */
	public $debugFilename;
	/**
	 * Flag indicating if the SQL queries will be showed or not. Use $_GET['debug_sql'] to set it.
	 * @var bool
	 */
	public $debugSql;
	/**
	 * In order to prevent errors with output and headers, set this variable <tt>TRUE</tt> after the headers are sent.
	 * @var bool 
	 */
	protected $_safePoint;
	/**
	 * Array containing the activity log for queries, filenames and their processing time.
	 * @var array 
	 */
	protected $_log;
	/**
	 * Stores the start time which is used to calculate the processing time. Use the method startTime() to set this variable.
	 * @var float|array
	 */
	protected $_startTime;
	/**
	 * Indicates the current template file loaded. Used on the showToolbar() method.
	 * @var bool
	 */
	protected $_templateFilename;
	protected $_exceptionsEnabled = false;
	protected $_maintenancePage = '/_default/index_manutencao.htm';
		
	/**
	 * Public Constructor, it checks the flags and settings, will do nothing if $c_jp7 is <tt>FALSE</tt>.
	 *
	 * @global bool
	 */	
	public function __construct() {
		global $c_jp7;
		if (!$c_jp7) return; // Only by Devs
		$this->startTime();
		// Debug - SQL
		$this->debugSql = $_GET['debug_sql'];
		// Debug - Filename
		if (isset($_GET['debug_filename'])) {
			setcookie('debug_filename', $_GET['debug_filename'], 0, '/');
			$_COOKIE['debug_filename'] = $_GET['debug_filename'];
		}
		if ($_COOKIE['debug_filename']) $this->debugFilename = $_COOKIE['debug_filename'];
		// Debug - Toolbar
		if (isset($_GET['debug_toolbar'])){
			setcookie('debug_toolbar', $_GET['debug_toolbar'], 0, '/');
			$_COOKIE['debug_toolbar'] = $_GET['debug_toolbar'];
		}
		// Setting it as active
		if ($_COOKIE['debug_toolbar'] || $this->debugSql || $this->debugFilename) $this->active = true;
	}
	/**
	 * Starts recording the time spent on the code. When using more than one startTime(), the time will be displayed from the last to the first when getTime() is called.
	 *
	 * @return void
	 */	
	public function startTime() {
		$debug_mtime = explode(' ', microtime());
		$this->_startTime[] = $debug_mtime[1] + $debug_mtime[0];
	}
	/**
	 * Calculates and displays the time spent from the moment startTime() was called.
	 *
	 * @param bool Sets whether the time will be outputted or not.
	 * @return void
	 */	
	public function getTime($output = FALSE) {
		if (!count($this->_startTime)) return;
		$debug_mtime = explode(' ', microtime());
		// Retrieves and deletes the last value
		$debug_starttime = array_pop($this->_startTime);
		$debug_totaltime = round(($debug_mtime[0] + $debug_mtime[1] - $debug_starttime) * 1000);
		if ($output && $this->isSafePoint()) echo '<div class="debug_msg">Processed in: ' . $debug_totaltime . 'ms.</div>';
		return $debug_totaltime;
	}
	/**
	 * Shows the filename if $safePoint and $debugFilename are <tt>TRUE</tt>. Adds the filename to $_log.
	 *
	 * @param string $filename Name of the file.
	 * @return string Returns the $filename value unchanged.
	 * @global string
	 */	
	public function showFilename($filename) {
		global $c_doc_root;
		if ($this->debugFilename && $this->isSafePoint()) echo '<div class="debug_msg">' .  str_replace($c_doc_root, '/', $filename ) . '</div>';
		if ($this->active) {
			// Creates a new log entry for this file
			$this->addLog($filename, 'file');
		}
		return $filename;
	}
	/**
	 * Formats and displays an SQL query.
	 *
	 * @param string $sql SQL query to be formatted and displayed.
	 * @param bool $forceDebug If <tt>TRUE</tt> it will show the SQL even when $_GET['debug_sql'] is not set, the default value is <tt>FALSE</tt>.
	 * @param string Stylesheet on the box displayed. The default value is ''.
	 * @return void
	 */	
	public function showSql($sql, $forceDebug = false, $style = '') {
		if ($forceDebug) {
			ob_flush();
			flush();
		}
		if (!$this->isSafePoint()) return;
		if ($this->debugSql || $forceDebug) echo '<div class="debug_sql" style="' . $style . '">' . preg_replace('/(SELECT | FROM | WHERE | ORDER BY |HAVING|GROUP BY|LEFT JOIN)/','<b>\1</b>', $sql) . '</div>';
	}
	/**
	 * Formats and returns the backtrace.
	 *
	 * @param string $msgErro Error message (optional).
	 * @param string $sql SQL query which was executed (optional).
	 * @param array $backtrace Backtrace generated by debug_backtrace() (optional).
 	 * @return string Formatted HTML backtrace.
	 */	
	public function getBacktrace($msgErro = null, $sql = null, $backtrace = null) {
		global $c_doc_root;
		
		$S = '';
		if ($msgErro) {
			$S .= $this->_getBacktraceLabel('ERRO') . wordwrap($msgErro, 85, "\n") . "\n";
		}
		
		$S .= $this->getBasicBacktrace($backtrace);
		
		$S .= '<hr />';
		if ($sql) {
			$S .= $this->_getBacktraceLabel('SQL') . preg_replace(array('/( FROM )/','/( WHERE )/','/( ORDER BY )/'), "\n" . '            \1', $sql)  . "\n";
		}
		$S .= $this->_getBacktraceLabel('URL') . (($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n";
		if ($_SERVER['HTTP_REFERER']) {
			$S .= $this->_getBacktraceLabel('REFERER') . $_SERVER['HTTP_REFERER'] . "\n";
		}
		$S .= $this->_getBacktraceLabel('IP CLIENTE') . $_SERVER['REMOTE_ADDR'] . "\n";
		$S .= $this->_getBacktraceLabel('IP SERVIDOR') . $_SERVER['SERVER_ADDR'] . "\n";
		$S .= $this->_getBacktraceLabel('USER_AGENT') . $_SERVER['HTTP_USER_AGENT'] . "\n";
		$S .= '<hr />';
		if (count($_POST)) {
			$S .= $this->_getBacktraceLabel('POST') . print_r($_POST, true);	
		}
		if (count($_GET)) {
			$S .= $this->_getBacktraceLabel('GET') . print_r($_GET, true);
		}
		if (count($_SESSION)) {
			$S .= $this->_getBacktraceLabel('SESSION') . print_r($_SESSION, true);
		}
		if (count($_COOKIE)) {
			$S .= $this->_getBacktraceLabel('COOKIE') . print_r($_COOKIE, true);
		}
		return '<pre style="background-color:#FFFFFF;font-size:11px;text-align:left;">' . $S . '</pre>';
	}
	
	public function getBasicBacktrace($backtrace = null) {
		if (!$backtrace) {
			$backtrace = debug_backtrace();
		}
		krsort($backtrace);
		
		$html = '<hr />';
		$html .= $this->_getBacktraceLabel('CALL STACK') . '<br />';
		$html .= '<table id="jp7_debugger_table"><tr><th>#</th><th>Function</th><th>Location</th></tr>';
		foreach ($backtrace as $key => $row) {
			$html .= '<tr><td>' . (count($backtrace) - $key) . '</td>';
			$html .= '<td>' . $row['class'] . $row['type'] . $row['function'] . '()</td>';
			$html .= '<td>' . str_replace(ROOT_PATH, '', $row['file']) . ':' . $row['line'] . '</td></tr>';
		}
		$html .= '</table>';
		return $html;
	}
	
	/**
	 * Adds padding and html formatting to the backtrace label.
	 *
	 * @param string $caption Label caption.
	 * @return string Formatted label.
	 */
	protected function _getBacktraceLabel($caption) {
		return '<strong style="color:red">'. str_pad($caption, 12, ' ', STR_PAD_LEFT) . ':</strong> ';
	}
	/**
	 * Lança exceções em caso de erro de SQL, ao invés de utilizar a função jp7_debug(). 
	 * 
	 * @param 	bool 	$bool
	 * @return 	void
	 */
	public function setExceptionsEnabled($bool) {
		$this->_exceptionsEnabled = $bool;
	}
	/**
	 * @return bool
	 */
	public function isExceptionsEnabled() {
		return $this->_exceptionsEnabled;
	}
	/**
	 * Method to be used as default error handler with set_error_handler() function.
	 *
	 * @param $code Error type code, like E_STRICT, E_NOTICE and so on.
	 * @param $msgErro Error message.
	 * @return bool
	 */
	public function errorHandler($code, $msgErro) {
		if ($code == E_STRICT || $code == E_NOTICE || $code == E_DEPRECATED) {
			return false; // FALSE -> the default error handler will take care of it.
		}
		if (error_reporting() == 0) {
			return false; // Programmer used @ so the error reporting value is 0.
		}

		$backtrace = debug_backtrace();
		array_shift($backtrace);
		die(jp7_debug($msgErro, NULL, $backtrace));
	}
	/**
	 * Adds a log to the $_log array.
	 *
	 * @param string $value Value to be displayed.
	 * @param string $tag Tag which represents the type of data stored.
	 * @param int $time Time this proccess took in miliseconds, e.g. ammount of time a SQL query took to be executed.
 	 * @return void
	 */	
	public function addLog($value, $tag = 'log', $time = NULL) {
		$this->_log[] = array('tag' => $tag, 'value' => $value, 'time' => $time);
	}
	/**
	 * Returns the log array.
	 *
	 * @param string $value Value to be displayed.
	 * @param string $tag Tag which represents the type of data stored.
	 * @param int $time Time this proccess took in miliseconds, e.g. ammount of time a SQL query took to be executed.
 	 * @return array Returns the value of $_log.
	 */
	public function getLog() {
		return $this->_log;
	} 
	/**
	 * Sets the filename of the current template.
	 *
	 * @param string $filename Name of the current template file.
 	 * @return void
	 */
	public function setTemplateFilename($filename) {
		$this->_templateFilename = $filename;
	}
	/**
	 * Returns the filename of the current template.
	 *
 	 * @return string Name of the current template file.
	 */
	public function getTemplateFilename() {
		return $this->_templateFilename;
	}
	/**
	 * Displays current template, log data, and time for the page.
	 *
 	 * @return void
	 */
	public function showToolbar() {
		if (!$this->active || !$this->isSafePoint()) return;
		
		if ($this->_templateFilename ) echo ('Template: ' . $this->_templateFilename);
		else echo('PHP_SELF: ' . $_SERVER['PHP_SELF']);
		
		jp7_print_r($this->_log);
		$this->getTime(true);
	}
	public function isSafePoint() {
		return $this->_savePoint || headers_sent();
	}
	public function setSafePoint($bool) {
		$this->_savePoint = $bool;
	}
	/**
	 * Envia o trace do erro para debug+CLIENTE@jp7.com.br
	 * 
	 * @param string $backtrace
	 * @return bool
	 */
	public function sendTraceByEmail($backtrace) {
		global $config, $s_interadmin_cliente, $jp7_app;
		$nome_app = ($jp7_app) ? $jp7_app : 'Site';
		if (trim($config->name_id)) {
			$cliente = $config->name_id;
		} elseif (trim($s_interadmin_cliente)) {
			$cliente = $s_interadmin_cliente;
		}
		$subject = '['. $cliente . '][' . $nome_app . '][Erro]';
		$message = 'Ocorreram erros no ' . $nome_app . ' - ' . $cliente . '<br />' . $backtrace;
		$to = 'debug+' . $cliente . '@jp7.com.br';
		$headers = 'To: ' . $to . " <" . $to . ">\r\n";
		$headers .= 'From: ' . $to . " <" . $to . ">\r\n";
		
		return jp7_mail($to, $subject, $message, $headers, '', $template, true);
	}    
    /**
     * Returns $maintenancePage.
     *
     * @see Jp7_Debugger::$maintenancePage
     */
    public function getMaintenancePage() {
        return $this->_maintenancePage;
    }
    /**
     * Sets $maintenancePage.
     *
     * @param object $maintenancePage
     * @see Jp7_Debugger::$maintenancePage
     */
    public function setMaintenancePage($maintenancePage) {
        $this->_maintenancePage = $maintenancePage;
    }
}
