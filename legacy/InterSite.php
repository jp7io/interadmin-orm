<?php

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category JP7
 */

/**
 * Configurations for a site.
 *
 * @version (2008/07/30)
 */
class InterSite
{
    const PRODUCAO = 'Produção';
    const QA = 'QA';
    const DESENVOLVIMENTO = 'Desenvolvimento';

    const PRODUCTION = 'Produção';
    const DEVELOPMENT = 'Desenvolvimento';

    const HOST_MAIN = 'main';
    const HOST_ALIAS = 'alias';
    const HOST_REMOTE = 'remote';

    /**
     * Sets if the magic __wakeup() is enabled.
     *
     * @var bool
     */
    private static $_wakeupEnabled = true;

    /**
     * Array of servers for this site.
     *
     * @var array
     */
    public $servers = [];
    /**
     * Array of languages for this site.
     *
     * @var array
     */
    public $langs = [];
    /**
     * Current server.
     *
     * @var object
     */
    public $server;
    /**
     * Current server type: 'main', 'alias' or 'remote'.
     *
     * @var string
     */
    public $hostType;
    /**
     * Current Database.
     *
     * @var object
     */
    public $db;
    /**
     * Current Url.
     *
     * @var object
     */
    public $url;
    /**
     * Default language.
     *
     * @var string
     */
    public $lang_default = 'pt-br';
    /**
     * Default charset.
     *
     * @var string
     */
    public $charset = 'UTF-8';

    protected static $instance = null;

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
            return true;
        }

        return false;
    }

    /**
     * @deprecated
     *
     * @return bool
     */
    public function isProducao()
    {
        return $this->server->type === self::PRODUCAO;
    }
    /**
     * Checks if the server type is PRODUCAO.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this->server->type === self::PRODUCTION;
    }
    /**
     * Checks if the server type is QA.
     *
     * @return bool
     */
    public function isQa()
    {
        return $this->server->type === self::QA;
    }
    /**
     * @deprecated
     *
     * @return bool
     */
    public function isDesenvolvimento()
    {
        return $this->server->type === self::DESENVOLVIMENTO;
    }
    /**
     * Checks if the server type is PRODUCAO.
     *
     * @return bool
     */
    public function isDevelopment()
    {
        return $this->server->type === self::DEVELOPMENT;
    }

    /**
     * Returns the first server which has a given type.
     *
     * @param string $type Type of the server, such as InterSite::PRODUCAO, InterSite::QA or InterSite::DESENVOLVIMENTO.
     *
     * @return InterAdmin
     */
    public function getFirstServerByType($type)
    {
        foreach ($this->servers as $server) {
            if ($server->type == $type) {
                return $server;
            }
        }
    }

    public static function config()
    {
        return self::$instance;
    }

    public static function setConfig(InterSite $instance)
    {
        self::$instance = $instance;
    }

    public static function setWakeupEnabled($bool)
    {
        self::$_wakeupEnabled = (bool) $bool;
    }

    public static function isWakeupEnabled()
    {
        return self::$_wakeupEnabled;
    }

    /**
     * Initializes the variables for the given host.
     *
     * @param string $host
     */
    public function init($host)
    {
        global $jp7_app;

        // Browsers não fazem isso, mas alguns User Agents estranhos podem vir em maiúscula
        $host = mb_strtolower($host);

        // This server is a main host
        $this->server = $this->servers[$host];
        $this->hostType = self::HOST_MAIN;

        // Not Found, searching aliases
        while (!$this->server) {
            foreach ($this->servers as $serverHost => $server) {
                // InterAdmin Remote
                if ($jp7_app) {
                    $remotes = $server->interadmin_remote;
                    if (in_array($host, $remotes) || in_array('www.'.$host, $remotes)) {
                        if ($server->vars['check_dns'] && !self::hasDnsRecord($server->host) && $server->alias_domains) {
                            $server->host = $server->alias_domains[0];
                        }
                        $this->server = $this->servers[$host] = $server;
                        $this->interadmin_remote = $host;
                        $this->hostType = self::HOST_REMOTE;
                        break 2;  // Exit foreach and while.
                    }
                }
                // Domínios Alternativos - Não redirecionam
                if (is_array($server->alias_domains) && in_array($host, $server->alias_domains)) {
                    $this->server = $this->servers[$host] = $server;
                    $this->server->host = $host;
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
                if ($this->servers['localhost']) {
                    $this->server = $this->servers['localhost'];
                    $this->servers[$host] = $this->server;
                    $this->server->host = $host;
                }
            }
            break;
        }

        if ($this->server) {
            $this->db = clone $this->server->db;
            // Exceção para funcionamento do InterAdmin Remote nos sites Express
            /*
            if ($this->db->host == 'mysql.jp7.com.br' && $this->hostType == self::HOST_REMOTE) {
                $this->db->host = 'localhost';
            }
            */
            if ($this->db->host_internal && $this->hostType != self::HOST_REMOTE) {
                $this->db->host = $this->db->host_internal;
            }
            foreach ((array) $this->server->vars as $var => $value) {
                $this->$var = $value;
            }
            $this->url = (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$this->server->host.'/'.jp7_path($this->server->path);

            foreach ($this->langs as $sigla => $lang) {
                if ($lang->default) {
                    $this->lang_default = $sigla;
                    break;
                }
            }

            // Storage
            if (!$this->storage) {
                $this->storage = [
                    'host' => $this->server->host,
                    'path' => $this->server->path
                ];

                if ($jp7_app && $jp7_app != 'interadmin') {
                    $this->storage['path'] .= '/'.$jp7_app;
                }
            }
        }
    }

    public function start()
    {
        global $debugger, $jp7_app;

        if (!self::isWakeupEnabled()) {
            return;
        }

        $host = $_SERVER['HTTP_HOST'];
        // O browser não envia a porta junto com o host, mas alguns bots enviam
        $host = preg_replace('/:80$/', '', $host);
        if ($host != trim($host, '. ')) {
            // Corrigindo hosts inválidos, com . no final
            header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');
            header('Location: http://'.trim($host, '. ').$_SERVER['REQUEST_URI']);
            exit;
        }
        $this->init($host);

        switch ($this->hostType) {
            case self::HOST_ALIAS:
                header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');
                header('Location: http://'.$this->server->host.$_SERVER['REQUEST_URI']);
                exit;
            case !$this->server: {
                $message = 'Host não está presente nas configurações: '.$_SERVER['HTTP_HOST'];
                //jp7_mail('debug@jp7.com.br', $message, $debugger->getBacktrace($message));
                $message .= '.<br /><br />Você pode ter digitado um endereço inválido.<br /><br />';
                if ($this->servers) {
                    if ($siteProducao = $this->getFirstServerByType(self::PRODUCAO)) {
                        $urlProducao = 'http://'.jp7_implode('/', [$siteProducao->host, $siteProducao->path]);
                        $messageLink = 'Acesse o site: <a href="'.$urlProducao.'">'.$urlProducao.'</a>';
                    }
                }
                die($message.$messageLink);
            }
        }

        /* @todo TEMP - Creating old globals */
        $oldtypes = [
            self::PRODUCAO => 'Principal',
            self::QA => 'QA',
            self::DESENVOLVIMENTO => 'Local',
        ];
        $GLOBALS['c_url'] = $this->url;
        $GLOBALS['c_server_type'] = $oldtypes[$this->server->type];
        $GLOBALS['c_site'] = $this->name_id;
        $GLOBALS['c_menu'] = @$this->menu;
        $GLOBALS['c_cache'] = $this->cache;
        $GLOBALS['c_cache_delay'] = @$this->cache_delay;
        $GLOBALS['db_prefix'] = $jp7_app.'_'.$this->name_id;
        $GLOBALS['c_cliente_url_path'] = $GLOBALS['c_path'] = jp7_path($this->server->path);
        $GLOBALS['c_analytics'] = @$this->google_analytics;
        $GLOBALS['googlemaps_key'] = @$this->google_maps;
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
        $GLOBALS['c_publish'] = @$this->interadmin_preview;
        $GLOBALS['c_remote'] = @$this->interadmin_remote;
        $GLOBALS['c_cliente_title'] = $this->name;
        $GLOBALS['c_nobackup'] = @$this->nobackup;
        foreach ($this->servers as $host => $server) {
            $GLOBALS['c_cliente_domains'][] = $host;
            $GLOBALS['c_cliente_domains'] = array_merge($GLOBALS['c_cliente_domains'], (array) $server->aliases);
        }
        foreach ($this->langs as $sigla => $lang) {
            $GLOBALS['c_lang'][] = [$sigla, $lang->name, (bool) $lang->multibyte];
        }
        $GLOBALS['c_lang_default'] = $this->lang_default;
        /* TEMP - Creating old globals */
    }

    /**
     * Executada quando é utilizado unserialize().
     */
    public function __wakeup()
    {
        if (self::$_wakeupEnabled) {
            $this->start(); // Backwards compatibility
        }
    }

    public static function __set_state($array)
    {
        $instance = new self();
        foreach ($array as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    public function export()
    {
        $code = var_export($this, true);

        $code = preg_replace("/' => \n\s+/", "' => ", $code);
        $code = str_replace('stdClass::__set_state', '(object)', $code);

        return $code;
    }

    /**
     * Cacheando verificação, porque chega a demorar 1 segundo.
     */
    public static function hasDnsRecord($domain)
    {
        $cacheFile = sys_get_temp_dir().'__dns_'.$domain;
        if (is_file($cacheFile) && filemtime($cacheFile) > strtotime('-2 minute')) {
            return file_get_contents($cacheFile);
        } else {
            $dns = dns_get_record($domain);
            @file_put_contents($cacheFile, (bool) $dns);
        }
    }
}
