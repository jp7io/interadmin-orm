<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterSite
 */

/**
 * Configurations for a site.
 *
 * @version (2008/07/30)
 * @package InterSite
 */
class InterSite {
	const PRODUCAO = 'Produção';
	const QA = 'QA';
	const DESENVOLVIMENTO = 'Desenvolvimento';
	
	const HOST_MAIN = 'main';
	const HOST_ALIAS = 'alias';
	const HOST_REMOTE = 'remote';
	
	/**
	 * Sets if the magic __wakeup() is enabled.
	 * @var bool
	 */
	private static $_wakeupEnabled = true;
	
	/**
	 * Array of servers for this site.
	 * @var array
	 */
	public $servers = array();
	/**
	 * Array of languages for this site.
	 * @var array
	 */
	public $langs = array();
	/**
	 * Current server.
	 * @var object
	 */
	public $server;
	/**
	 * Current server type: 'main', 'alias' or 'remote'.
	 * @var string
	 */
	public $hostType;
	/**
	 * Current Database.
	 * @var object
	 */
	public $db;
	/**
	 * Current Url.
	 * @var object
	 */
	public $url;
	/**
	 * Default language.
	 * @var string
	 */
	public $lang_default = 'pt-br';
		
	/**
	 * Checks if it´s at a localhost or at the IPS 127.0.0.1 or 192.168.0.*. 
	 * If the HTTP_HOST has a . (dot) like something.com, it will return false.
	 *
	 * @return bool
	 */
	public static function isAtLocalhost()
	{
		if ($_SERVER['HTTP_HOST'] == 'localhost') {
			return true;
		} elseif ($_SERVER['SERVER_ADDR'] == '127.0.0.1' || strpos($_SERVER['SERVER_ADDR'], '192.168.0.') === 0) {
			// Has no dots
			if (strpos($_SERVER['HTTP_HOST'], '.') === false || $_SERVER['SERVER_ADDR'] == $_SERVER['HTTP_HOST']) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Checks if the server type is PRODUCAO.
	 * @return bool
	 */
	public function isProducao() {
		return $this->server->type === self::PRODUCAO;
	}
	/**
	 * Checks if the server type is QA.
	 * @return bool
	 */
	public function isQa() {
		return $this->server->type === self::QA;
	}
	/**
	 * Checks if the server type is DESENVOLVIMENTO.
	 * @return bool
	 */
	public function isDesenvolvimento() {
		return $this->server->type === self::DESENVOLVIMENTO;
	}
	
	/**
	 * Returns the first server which has a given type.
	 * 
	 * @param string $type Type of the server, such as InterSite::PRODUCAO, InterSite::QA or InterSite::DESENVOLVIMENTO.
	 * @return InterAdmin
	 */
	public function getFirstServerByType($type) {
		foreach ($this->servers as $server) {
			if ($server->type == $type) {
				return $server;
			}
		}
	}
	
	public static function setWakeupEnabled($bool) {
		self::$_wakeupEnabled = (bool) $bool;
	}
	
	public static function isWakeupEnabled() {
		return self::$_wakeupEnabled;
	}
	
	/**
	 * Initializes the variables for the given host.
	 * 
	 * @param string $host
	 * @return void
	 */
	public function init($host) {
		global $jp7_app;
		
		// This server is a main host
		$this->server = $this->servers[$host];
		$this->hostType = self::HOST_MAIN;
		
		// Not Found, searching aliases
		while (!$this->server) {
			foreach ($this->servers as $serverHost => $server) {
				// InterAdmin Remote
				if ($jp7_app) {
					$remotes = $server->interadmin_remote;
					if (in_array($host, $remotes) || in_array('www.' . $host, $remotes)) {
						$this->server = $this->servers[$host] = $server;
						$this->interadmin_remote = $host;
						$this->hostType = self::HOST_REMOTE;
						break 2;  // Exit foreach and while.
					}
				}
				// Domínios Alternativos - Não redirecionam
				if (is_array($server->alias_domains) && in_array($host, $server->alias_domains)) {
					$this->server = $this->servers[$host] = $server;
					break 2;  // Exit foreach and while.
				}
				// Aliases - Redirecionam
				if (in_array($host, $server->aliases)) {
					$this->server = $this->servers[$host] = $server;
					$this->hostType = self::HOST_ALIAS;
					break 2;  // Exit foreach and while.
				}
			}
			// Dev Local
			if (self::isAtLocalhost()) {
				if ($server = $this->getFirstServerByType(self::DESENVOLVIMENTO)) {
					$this->server = $this->servers[$host] = $server;
					$this->server->host = $host;
				}
			}
			break;
		}
		
		if ($this->server) {
			$this->db = clone $this->server->db;
			if ($this->db->host_internal && $this->hostType != self::HOST_REMOTE) {
				$this->db->host = $this->db->host_internal;
			}
			foreach($this->server->vars as $var => $value) {
				$this->$var = $value;
			}
			$this->url = 'http://' . $this->server->host . '/' . jp7_path($this->server->path);
			
			foreach($this->langs as $sigla => $lang) {
				if ($lang->default) {
					$this->lang_default = $sigla;
					break;
				}
			}
		}
	}
	
	/**
	 * Executada quando é utilizado unserialize().
	 * 
	 * @return void
	 */
	function __wakeup() {
		global $debugger;
		
		if (!self::isWakeupEnabled()) {
			return;
		}
		
		$host = $_SERVER['HTTP_HOST'];
		if (strpos($host, ':80') !== false) {
			// O browser não envia a porta junto com o host, mas alguns bots enviam
			$host = preg_replace('/:80$/', '', $host);
		}
		$this->init($host);
		
		switch ($this->hostType) {
			case self::HOST_ALIAS:
				header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
				header('Location: http://' . $this->server->host . $_SERVER['REQUEST_URI']);
				exit;
			case !$this->server: {
				$message = 'Host não está presente nas configurações: ' . $_SERVER['HTTP_HOST'];
				jp7_mail('debug@jp7.com.br', $message, $debugger->getBacktrace($message));
				$message .= '.<br /><br />Você pode ter digitado um endereço inválido.<br /><br />';
				if ($this->servers) {
					if ($siteProducao = $this->getFirstServerByType(self::PRODUCAO)) {
						$urlProducao = 'http://' . jp7_implode('/', array($siteProducao->host, $siteProducao->path));
						$messageLink = 'Acesse o site: <a href="' . $urlProducao . '">' . $urlProducao . '</a>';
					}
				}
				die($message . $messageLink);
			}
		}
		
		/* @todo TEMP - Creating old globals */
		$oldtypes = array(
			self::PRODUCAO => 'Principal',
			self::QA => 'QA',
			self::DESENVOLVIMENTO => 'Local'
		);
		$GLOBALS['c_url'] = $this->url;
		$GLOBALS['c_server_type'] = $oldtypes[$this->server->type];
		$GLOBALS['c_site'] = $this->name_id;
		$GLOBALS['c_menu'] = $this->menu;
		$GLOBALS['c_cache'] = $this->cache;
		$GLOBALS['c_cache_delay'] = $this->cache_delay;
		$GLOBALS['db_prefix'] = 'interadmin_' . $this->name_id;
		$GLOBALS['c_cliente_url_path'] = $GLOBALS['c_path'] = jp7_path($this->server->path);
		$GLOBALS['c_analytics'] = $this->google_analytics;
		$GLOBALS['googlemaps_key'] = $this->google_maps;
		$GLOBALS['c_w3c'] = true;
		$GLOBALS['c_doc_root'] = jp7_doc_root();
		// DB
		$GLOBALS['db_type'] = $this->db->type;
		$GLOBALS['db_host'] = $this->db->host;
		$GLOBALS['db_name'] = $this->db->name;
		$GLOBALS['db_user'] = $this->db->user;
		$GLOBALS['db_pass'] = $this->db->pass;
		// FTP
		$GLOBALS['ftp']['host'] = $this->server->ftp;
		$GLOBALS['ftp']['user'] = $this->server->user;
		$GLOBALS['ftp']['pass'] = $this->server->pass;
		// InterAdmin
		$GLOBALS['c_publish'] = $this->interadmin_preview;
		$GLOBALS['c_remote'] = $this->interadmin_remote;
		$GLOBALS['c_cliente_title'] = $this->name;
		$GLOBALS['c_nobackup'] = $this->nobackup;
		foreach ($this->servers as $host => $server) {
			$GLOBALS['c_cliente_domains'][] = $host;
			$GLOBALS['c_cliente_domains'] = array_merge($GLOBALS['c_cliente_domains'], $server->aliases);
		}
		foreach($this->langs as $sigla => $lang) {
			$GLOBALS['c_lang'][] = array($sigla, $lang->name, (bool) $lang->multibyte);
		}
		$GLOBALS['c_lang_default'] = $this->lang_default;
		/* TEMP - Creating old globals */
	}
		
	/**
	 * Testa as configurações do site, conecta com o DB e o FTP e recupera dados do PHPInfo.
	 * 
	 * @return void
	 * @todo Atualizar código
	 */
	public function testConfig() {
		foreach($this->servers as $server) {
			$fieldsValues = '';
			$fieldsValuesDB = '';
			if ($server->type != 'Desenvolvimento') {
				echo '<br /><br /><div style="font-weight:bold">&bull; ' . $server->name . '</div>';
				if (!$server->ftp) $server->ftp = $server->host;
				$conn_id = @ftp_connect($server->ftp); 
				$login_result = @ftp_login($conn_id, $server->user, $server->pass);
				if ($login_result) {
					echo '<div class="configok">Conectado com FTP: ' .  $server->ftp . '</div>';
					$fieldsValues = array(
						'varchar_5' => ftp_systype($conn_id),
						'date_1' => date('Y-m-d H:i:s'),
						'char_1' => $login_result
					);
				} else {
					echo '<div class="configerror">Erro de conexão com FTP: ' .  $server->ftp . '</div>';
				}
				@ftp_close($conn_id);
				// PHP Info
				$content = $this->_socketRequest($server->host, '/_admin/phpinfo.php', '', 'GET', 'http://' . $server->host . '/_admin/phpinfo.php');
				$pos1_str = 'login/index.php?error=3';
				$pos1 = strpos($content, $pos1_str);
				$cookie = '';
				// Login required
				if ($pos1 !== false) {
					$WS_parameters = 'user=jp7_jp&pass=naocolocar';
					$content_2 = $this->_socketRequest($server->host, '/_admin/login/login.php', $WS_parameters, 'POST', 'http://' . $server->host . '/_admin/login/index.php');
					$content_header = explode('\r\n\r\n', $content_2);
					$pos1_str = 'Set-Cookie: ';
					$pos1 = strpos($content_2, $pos1_str) + strlen($pos1_str);
					$pos2 = strpos($content_2, ';', $pos1);
					$cookie = substr($content_2, $pos1, $pos2-$pos1);
					$content = $this->_socketRequest($server->host, '/_admin/phpinfo.php', '', 'GET', 'http://' . $server->host . '/_admin/phpinfo.php', FALSE, $cookie);
				}
				// Version not found - Trying another file
				$pos1_str = 'PHP Version';
				$pos1 = strpos($content, $pos1_str);
				if ($pos1 === false) {
					$content = $this->_socketRequest($server->host, '/_admin/phpinfo_manual.php', '', 'GET', 'http://' . $server->host . '/_admin/phpinfo.php', FALSE, $cookie);
					$content = str_replace("phpversion:", "PHP Version", $content);
				}
				if ($content) { 
					echo '<div class="configok">Arquivo phpinfo.php lido.</div>';
					// Preparing to update file
					$pos1 = strpos($content, '<html>') + strlen('<html>');
					$pos2 = strpos($content, '</html>', $pos1);
					$fieldsValues['text_1'] = substr($content, $pos1, $pos2 - $pos1);
					// Getting only the <body>
					$pos1_str = '<body>';
					$pos1 = strpos($content, $pos1_str) + strlen($pos1_str);
					$pos2 = strpos($content, '</body>', $pos1);
					$content = substr($content, $pos1, $pos2 - $pos1);
					// Table to array conversion
					$content = str_replace("</tr>", "{;}", $content);
					$content = str_replace("</td>", "{,}", $content);
					$content = strip_tags($content);
					$arr = explode('{;}', $content);
					// Getting PHP Version
					$pos1_str = 'PHP Version';
					$pos1 = strpos($content, $pos1_str) + strlen($pos1_str);
					$pos2 = strpos($content, "\n", $pos1);
					$php = substr($content, $pos1, $pos2-$pos1);
					$server->phpinfo = null;
					$server->phpinfo['PHP'] = trim($php, " \r\n\t");
					// Sets other parameters
					$parameters = array('PHP', 'host', 'SERVER_ADDR', 'LOCAL_ADDR', 'register_globals', 'GD Version', 'MySQL', 'MySQL Version');
					foreach ($arr as $value) {
						$value_arr = explode('{,}', $value);
						foreach ((array)$value_arr as $position=>$parameter) {
							$parameter = trim($parameter, " \r\n\t");
							if (in_array($parameter, $parameters)) {
								$server->phpinfo[$parameter] = $value_arr[$position + 1];
							}
						}
					}
					$fieldsValues['varchar_6'] = $server->phpinfo['PHP'];
					
				} else {
					echo '<div class="configerror">Não foi possível abrir phpinfo.php.</div>';
				}
				// Saving FTP and PHP Info data		
				if ($fieldsValues) {
					//jp7_print_r($fieldsValues);
					$server->setFieldsValues($fieldsValues, true);
				}
				
				// DB
				$dsn = (($server->db->type) ? $server->db->type : 'mysql') . ':host='.$server->db->host;
				try {
		    		$server_db_conn = new PDO($dsn, $server->db->user, $server->db->pass);
				} catch (PDOException $e) {
					echo '<div class="configerror">Erro de conexão com DB: ' . $server->db->host . ' - ' . $e->getMessage() . '</div>';
				}
				if ($server_db_conn) {
					echo '<div class="configok">Conectado com DB: ' . $server->db->host . '</div>';
					if ($server->db->type == 'mssql') $db_select = "(CAST(SERVERPROPERTY('productversion') as varchar(255)) + ' - ' + CAST(SERVERPROPERTY('productlevel') as varchar(255)) + ' - ' + CAST(SERVERPROPERTY('edition') as varchar(255)))";
					else $db_select = "Version()";
					$ver_result = $server_db_conn->prepare("SELECT " . $db_select . " AS version"); 
					$ver_result->execute();
					$result = $ver_result->fetch(PDO::FETCH_ASSOC);
					$fieldsValuesDB = array(
						'varchar_5' => $result['version'],
						'date_1' => date('Y-m-d H:i:s'),
						'char_1' => $login_result
					);
					//jp7_print_r($fieldsValuesDB);
					$server->db->setFieldsValues($fieldsValuesDB);
				}
			}
		}
	}
	
	protected function _socketRequest($host, $url, $parameters, $method = 'GET', $referer = '', $debug = false, $cookie = '') {
		$header = "" .
		$method . " " . $url . " HTTP/1.1\r\n" .
		"Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, */*\r\n" .
		"Referer: " . $referer."\r\n" .
		"Accept-Language: pt-br\r\n" .
		"Content-Type: application/x-www-form-urlencoded\r\n" .
		"Accept-Encoding: gzip, deflate\r\n" .
		"User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n" .
		"Host: " . $host . "\r\n" .
		"Content-Length: " . strlen($parameters) . "\r\n" .
		"Connection: Close\r\n" .
		"Cache-Control: no-cache\r\n" .
		"Cookie: " . $cookie . "\r\n\r\n" .
		$parameters;
		
		$fp = @fsockopen ($host, 80, $errno, $errstr, 30);
		if ($fp) {
		   	fputs ($fp, $header);
			while (!feof($fp)) {
				$content.= fgets ($fp,128);
			}
			fclose ($fp);
		}
		return $content;
	}
	
}
