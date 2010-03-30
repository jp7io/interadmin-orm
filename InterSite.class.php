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
	const PRODUCAO = 'Produção';
	const QA = 'QA';
	const DESENVOLVIMENTO = 'Desenvolvimento';
	
	const DEFAULT_FIELDS_ALIAS = true;
	
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
	
	public $allowAttributes = false;
	public function __set($var, $value) {
		if (!$this->allowAttributes) {
			parent::__set($var, $value);
		} else {
			$this->$var = $value;
		}
	}
	
	/**
	 * Populates and returns the array of servers for this site.
	 * 
	 * @return array
	 */
	public function getServers(InterSite_Aplicacao $app = null) {
		$options = array(
			'fields' => array('name', 'type', 'host', 'ftp', 'user', 'path', 'pass', 'db', 'interadmin_remote'),
			'fields_alias' => true
		);
		
		if ($app) {
			$app->getByAlias('nome');
			$options['where'][] = "select_key = " . $app;
		}
		$servers = $this->getServidores($options);
		
		
		$optionsVars = array(
			'fields' => array('variavel' => array('name_id'), 'valor')
		);
		// Variaveis do site
		$vars = $this->getVariaveis($optionsVars);
		
		foreach ($vars as $var) {
			$varName = $var->variavel->name_id;
			$this->$varName = $var->valor;
		}
		
		foreach ($servers as $server) {
			// Variaveis do server
			$server_vars = $server->getVariaveis($optionsVars);
			$server->vars = array();
			foreach ($server_vars as $var) {
				$varName = $var->variavel->name_id;
				$server->vars[$varName] = $var->valor;
			}
			
			// InterAdmin Remote
			foreach ($server->interadmin_remote as $key => $interadmin_remote) {
				$server->interadmin_remote[$key] = $interadmin_remote->getFieldsValues('varchar_key');
			}
			// FTP
			if (!$server->ftp) {
				$server->ftp = $server->host;
			}
			
			// Tipo 
			if ($server->type) {
				$server->type = $server->type->getByAlias('nome');
			}
			// Database
			$options = array(
				'fields' => array('varchar_key', 'varchar_1', 'varchar_2', 'varchar_3', 'varchar_4', 'varchar_6','password_key', 'select_2'),
				'fields_alias' => true
			);
			if ($server->db) {
				$server->db->getFieldsValues($options['fields'], false, $options['fields_alias']);
				$type = new InterAdmin($server->db->type->id, array('fields' => 'varchar_1'));
				$server->db->type = $type->varchar_1;
			} else {
				$server->db = new stdClass();
			}
			
			// Aliases
			$aliases = $server->getAliases(array('fields' => array('host', 'www_subdomain')));
			$server->aliases = array();
			foreach ($aliases as $alias) {
				$server->aliases[] = $alias->host;
				if ($alias->www_subdomain) {
					if (strpos($alias->host, 'www.') === 0) {
						$server->aliases[] = substr($alias->host, 4);
					} else {
						$server->aliases[] = 'www.' . $alias->host;
					}
				}
			}
		}
		
		// Apenas o InterAdmin tem $config->interadmin_preview
		if ($app && toId($app->nome) != 'interadmin') {
			$this->interadmin_preview = false;  
		}
				
		// Renaming keys
		foreach ($servers as $server) {
			$renamed_servers[$server->host] = $server;
		}
		$this->servers = $renamed_servers;
			
		return $this->servers;
	}
	
	public function getLangs(InterSite_Aplicacao $app = null)
	{
		$this->langs = null;
		$idiomasTipo = new InterSite_IdiomaTipo();
		$options = array('fields' => array('lang', 'name', 'multibyte'));
		
		// Outras aplicações: sempre pt-br
		if ($app && toId($app->getByAlias('nome')) != 'interadmin') {
			$lang = $idiomasTipo->getFirstInterAdmin($options + array('where' => "lang = 'pt-br'"));
			$lang->default = true;
			$this->langs[$lang->lang] = $lang;
			return;
		}
		
		// InterAdmin: segue cadastro
		$languages = $this->getChildren(37, array(
			'fields' => array('lang', 'title', 'description', 'keywords', 'default'),
			'fields_alias' => true
		));
		
		foreach ((array)$languages as $language) {
			$lang = $language->lang->getByAlias($options['fields']);
			$language->lang = $lang->lang;
			$language->name = $lang->name;
			$language->multibyte = $lang->multibyte;
			$this->langs[$language->lang] = $language;
		}
	}
	
	/**
	 * Testa as configurações do site, conecta com o DB e o FTP e recupera dados do PHPInfo.
	 * 
	 * @return void
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
			if (strpos($_SERVER['HTTP_HOST'], '.') === false) {
				return true;
			}
		}
		return false;
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
		
	protected function _removeUnneeded($object, array $attributes = array('id', 'id_tipo', 'parent_id'))
	{
		foreach ($attributes as $name) 
		{
			unset($object->$name);
		}
	}
	
	protected function _toSimpleObject($object)
	{
		return (object) $object->attributes;
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
	
	/**
	 * Remove valores não necessários antes de ser serializado.
	 * 
	 * @return array
	 */
	function __sleep() 
	{
		foreach ($this->servers as $key => &$server) {
			$server = $this->_toSimpleObject($server);
			$this->_removeUnneeded($server);
			if ($server->db) {
				$server->db = $this->_toSimpleObject($server->db);
				$this->_removeUnneeded($server->db);
			}
		}
		
		foreach ($this->langs as $key => &$lang) {
			$lang = $this->_toSimpleObject($lang);
			$this->_removeUnneeded($lang);
		}
		
		// FIXME hack para funcionar com versões antigas das classes no host do site
		$this->allowAttributes = true; 
		foreach ($this->attributes as $var => $value) {
			$this->$var = $value;
		}
		
		$keys = array_keys($this->attributes);
		$keys = array_diff($keys, array('interadmin_logo', 'id', 'id_tipo'));
		
		return array_merge($keys, array('servers', 'langs'));
	}
	
	/**
	 * Executada quando é utilizado unserialize().
	 * 
	 * @return void
	 */
	function __wakeup() {
		global $debugger, $jp7_app;
		$thisHost = $_SERVER['HTTP_HOST'];
		
		// This server is a main host
		$this->server = $this->servers[$thisHost];
		
		while (!$this->server) {
			// InterAdmin Remote
			if ($jp7_app) {
				foreach ($this->servers as $host => $server) {
					$remotes = $server->interadmin_remote;
					if (in_array($thisHost, $remotes) || in_array('www.' . $thisHost, $remotes)) {
						$this->server = $this->servers[$thisHost] = $server;
						$GLOBALS['c_remote'] = $thisHost;
						break 2;  // Exit foreach and while.
					}
				}
			}
			// Alias found, redirect it to the host
			foreach ($this->servers as $host => $server) {
				if (in_array($thisHost, $server->aliases)) {
					header('Location: http://' . $host . $_SERVER['REQUEST_URI']);
					exit();
				}
			}
			// Dev Local
			if (self::isAtLocalhost()) {
				$server = $this->getFirstServerByType(self::DESENVOLVIMENTO);
				if ($server) {
					$this->server = $this->servers[$thisHost] = $server;
					break; // Exit while
				}
			}
			// No server found
			$message = 'Host não está presente nas configurações: ' . $thisHost;
			jp7_mail('debug@jp7.com.br', $message, $debugger->getBacktrace($message));
			$message .= '.<br /><br />Você pode ter digitado um endereço inválido.<br /><br />';
			if ($this->servers) {
				$siteProducao = $this->getFirstServerByType(self::PRODUCAO);
				$urlProducao = 'http://' . jp7_implode('/', array($siteProducao->host, $siteProducao->path));
				$messageLink = 'Acesse o site: <a href="' . $urlProducao . '">' . $urlProducao . '</a>';
			}
			die($message . $messageLink);
		}
		$this->db = $this->server->db;
		
		foreach($this->server->vars as $var => $value) {
			$this->$var = $value;
		}

		/* @todo TEMP - Creating old globals */
		$oldtypes = array(
			self::PRODUCAO => 'Principal',
			self::QA => 'QA',
			self::DESENVOLVIMENTO => 'Local'
		);
		
		$this->url = 'http://' . $this->server->host . '/' . $this->server->path . '/';
		
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
		$GLOBALS['db_host'] = ($this->db->host_internal) ? $this->db->host_internal : $this->db->host;
		$GLOBALS['db_name'] = $this->db->name;
		$GLOBALS['db_user'] = $this->db->user;
		$GLOBALS['db_pass'] = $this->db->pass;
		// FTP
		$GLOBALS['ftp']['host'] = $this->server->ftp;
		$GLOBALS['ftp']['user'] = $this->server->user;
		$GLOBALS['ftp']['pass'] = $this->server->pass;
		// InterAdmin
		$GLOBALS['c_publish'] = $this->interadmin_preview;
		$GLOBALS['c_demo'] = $this->interadmin_demo;
		$GLOBALS['c_cliente_title'] = $this->name;
		$GLOBALS['c_nobackup'] = $this->nobackup;
		
		foreach ($this->servers as $host => $server) {
			$GLOBALS['c_cliente_domains'][] = $host;
			$GLOBALS['c_cliente_domains'] = array_merge($GLOBALS['c_cliente_domains'], $server->aliases);
		}
		foreach($this->langs as $sigla => $lang) {
			$GLOBALS['c_lang'][] = array($sigla, $lang->name, (bool) $lang->multibyte);
			if ($lang->default) {
				$this->lang_default = $sigla;
			}
		}
		if (!$this->lang_default) {
			$this->lang_default = 'pt-br';			
		}
		$GLOBALS['c_lang_default'] = $this->lang_default;
		/* TEMP - Creating old globals */
	}
}
