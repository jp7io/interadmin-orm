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
 * Class which represents a site on InterSite.
 *
 * @version (2008/07/30)
 * @package InterSite
 */
class InterSite extends InterAdmin {
	/**
	 * @var Array of servers for this site.
	 */
	public $servers;
	/**
	 * @var Array of languages for this site.
	 */
	public $langs;
	/**
	 * Populates and returns the array of servers for this site.
	 * 
	 * @return array
	 */
	public function getServers() {
		$options = array(
			'fields' => array('varchar_key', 'select_1', 'varchar_1', 'varchar_2',  'varchar_3', 'varchar_4', 'password_key', 'select_2'),
			'fields_alias' => true
		);
		$servers = $this->getChildren(26, $options);
		
		// Variaveis do site
		$vars = $this->getChildren(39, array( 'fields' => array( 'select_key', 'varchar_1')));
		
		foreach((array) $vars as $var) {
			$varName = new InterAdmin($var->select_key, array('fields' => 'varchar_1'));
			$varName = $varName->varchar_1;
			$this->$varName = $var->varchar_1;
		}
		foreach ((array) $servers as $server) {
			// Variaveis do server
			$server_vars = $server->getChildren(39, array('fields' => array('select_key', 'varchar_1')));
			$server->vars = null;
			foreach((array) $server_vars as $var) {
				$varName = new InterAdmin($var->select_key, array('fields' => 'varchar_1'));
				$server->vars[$varName->varchar_1] = $var->varchar_1;
			}
			// Tipo 
			$type = new InterAdmin($server->type, array('fields' => 'varchar_key'));
			$server->type = $type->varchar_key;
			// Database
			$options = array(
				'fields' => array('varchar_key', 'varchar_1', 'varchar_2', 'varchar_3', 'varchar_4', 'password_key', 'select_2'),
				'fields_alias' => true
			);
			$server->db = new InterAdmin($server->db->id, $options);
			$type = new InterAdmin($server->db->type->id, array('fields' => 'varchar_1'));
			$server->db->type = $type->varchar_1;
			// Aliases
			$aliasesObj = $server->getChildren(31, array('fields' => array('varchar_key', 'char_1')));
			$server->aliases = null;
			foreach ((array)$aliasesObj as $aliasObj) {
				$server->aliases[] = $aliasObj->varchar_key;
				if ($aliasObj->char_1) {
					if (strpos($aliasObj->varchar_key, 'www.') === 0) {
						$server->aliases[] = substr($aliasObj->varchar_key, 4);
					} else {
						$server->aliases[] = 'www.' . $aliasObj->varchar_key;
					}
				}
			}
			// Cleaning unused data
			unset($server->db->_tipo);
			unset($server->db->_parent);
			unset($server->_tipo);
			unset($server->_parent);
		}
		// Renaming keys
		foreach ((array)$servers as $server) {
			$renamed_servers[$server->host] = $server;
		}
		$this->servers = $renamed_servers;
		return $this->servers;
	}
	
	public function getLangs(){
		$options = array(
			'fields' => array('select_key', 'varchar_1', 'text_1', 'text_2', 'char_1'),
			'fields_alias' => true
		);
		$languages = $this->getChildren(37, $options);
		$this->langs = null;
		foreach ((array)$languages as $language) {
			$lang =	new InterAdmin($language->lang, array('fields' => array('varchar_1', 'varchar_key', 'char_1')));
			$language->lang = $lang->varchar_1;
			$language->name = $lang->varchar_key;
			$language->multibyte = $lang->char_1;
			unset($language->_tipo);
			unset($language->_parent);
			unset($language->db_prefix);
			$this->langs[$language->lang] = $language;
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
	
	/**
	 * Checks if it´s at a localhost or at the IPS 127.0.0.1 or 192.168.0.*. 
	 * If the HTTP_HOST has a . (dot) like something.com, it will return false.
	 *
	 * @return bool
	 */
	public static function isAtLocalhost(){
		if ($_SERVER['HTTP_HOST'] == 'localhost') {
			return true;
		} elseif ($_SERVER['SERVER_ADDR'] == '127.0.0.1' || strpos($_SERVER['SERVER_ADDR'], '192.168.0.') === 0) {
			// Has no dots like 
			if (strpos($_SERVER['HTTP_HOST'], '.') === false) {
				return true;
			}
		}
		return false;
	}
	
	function __sleep() {
		unset($this->_tipo);
		unset($this->_parent);
		return array_keys(get_object_vars($this));
	}
	
	function __wakeup() {
		global $debugger;
		
		// This server is a main host
		$this->server = $this->servers[$_SERVER['HTTP_HOST']];
		$this->interadmin_remote = jp7_explode(';', $this->interadmin_remote);

		while (!$this->server) {
			// InterAdmin Remote
			if ((in_array($_SERVER['HTTP_HOST'], $this->interadmin_remote) || in_array('www.' . $_SERVER['HTTP_HOST'], $this->interadmin_remote)) && $GLOBALS['jp7_app']) {
				foreach ($this->servers as $host => $server) {
					if ($server->type == 'Produção') {
						$this->server = $this->servers[$_SERVER['HTTP_HOST']] = $server;
						$GLOBALS['c_remote'] = $_SERVER['HTTP_HOST'];
						break 2;  // Exit the foreach and the while.
					}
				}
			}
			// Alias found, redirect it to the host
			foreach ($this->servers as $host => $server) {
				if (in_array($_SERVER['HTTP_HOST'], (array) $server->aliases)) {
					header('Location: http://' . $host . $_SERVER['REQUEST_URI']);
					exit();
				}
			}
			// Dev Local
			if (self::isAtLocalhost()) {
				foreach ($this->servers as $host => $server) {
					if ($server->type == 'Desenvolvimento') {
						$this->server = $this->servers[$_SERVER['HTTP_HOST']] = $server;
						break 2; // Exit the foreach and the while.
					}
				}
				break;
			}
			// No server found
			$message = 'Host não está presente nas configurações: ' . $_SERVER['HTTP_HOST'];
			jp7_mail('debug@jp7.com.br', $message, $debugger->getBacktrace($message));
			if ($this->servers) {
				$urlSitePrincipal = 'http://' . jp7_implode('/', array(reset($this->servers)->host, reset($this->servers)->path));
				$messageLink = 'Acesse o site: <a href="' . $urlSitePrincipal . '">' . $urlSitePrincipal . '</a>';
			}
			die(
				$message . '.<br /><br />' .
				'Você pode ter digitado um endereço inválido.<br /><br />' .
				$messageLink
			);
		}
		$this->db = $this->server->db;
				
		foreach((array) $this->server->vars as $var=>$value) {
			$this->$var = $value;
		}

		/* @todo TEMP - Creating old globals */
		$oldtypes = array('Produção'=>'Principal', 'QA'=>'QA', 'Desenvolvimento'=>'Local');
		$GLOBALS['c_server_type'] = $oldtypes[$this->server->type];
		$GLOBALS['c_site'] = $this->name_id;
		$GLOBALS['c_menu'] = $this->menu;
		$GLOBALS['c_publish'] = $this->interadmin_preview;
		$GLOBALS['c_demo'] = $this->interadmin_demo;
		$GLOBALS['c_cache'] = $this->cache; // Paginas serão cacheadas ou não
		$GLOBALS['c_cache_delay'] = $this->cache_delay;
		$GLOBALS['db_prefix'] = 'interadmin_' . $this->name_id;
		$GLOBALS['c_path'] = $this->server->path;
		$GLOBALS['c_cliente_url_path'] = $this->server->path;
		$GLOBALS['c_analytics'] = $this->google_analytics;
		//if (in_array($_SERVER['HTTP_HOST'], $this->interadmin_remote) || in_array('www.' . $_SERVER['HTTP_HOST'], $this->interadmin_remote)) $GLOBALS['c_remote'] = $_SERVER['HTTP_HOST'];
		$GLOBALS['googlemaps_key'] = $this->google_maps;
		$GLOBALS['c_w3c'] = true;
		$GLOBALS['c_doc_root'] = jp7_doc_root();
		$GLOBALS['db_type'] = $this->db->type;
		$GLOBALS['db_host'] = ($this->db->host_internal) ? $this->db->host_internal : $this->db->host;
		$GLOBALS['db_name'] = $this->db->name;
		$GLOBALS['db_user'] = $this->db->user;
		$GLOBALS['db_pass'] = $this->db->pass;
		$GLOBALS['ftp']['user'] = $this->server->user;
		$GLOBALS['ftp']['pass'] = $this->server->pass;
		// InterAdmin
		$GLOBALS['c_cliente_title'] = $this->name;
		foreach ($this->servers as $host=>$server) {
			$GLOBALS['c_cliente_domains'][] = $host;
			$GLOBALS['c_cliente_domains'] = array_merge($GLOBALS['c_cliente_domains'], (array) $server->aliases);
		}
		foreach($this->langs as $acron=>$value) {
			$GLOBALS['c_lang'][] = array($acron, $value->name, (bool) $value->multibyte);
			if ($value->default) $GLOBALS['c_lang_default'] = $acron;
		}
		/* TEMP - Creating old globals */
	}
}
?>