<?php

use \Illuminate\Support\Str;

/**
 * Configurations for a site.
 *
 * @version (2008/07/30)
 */
class InterSite
{
    const QA = 'QA';
    const PRODUCTION = 'Produção';
    const DEVELOPMENT = 'Desenvolvimento';

    /**
     * Array of servers for this site.
     *
     * @var array
     */
    public $servers = array();
    /**
     * Array of languages for this site.
     *
     * @var array
     */
    public $langs = array();
    /**
     * Current server.
     *
     * @var object
     */
    public $server;
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

    protected static $instance = null;

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
        
    public function setServer($server)
    {
        $this->server = $server;
        
        // Set variables that depend on the server
        $this->db = clone $this->server->db;
        $this->db->prefix = 'interadmin_'.$this->name_id;

        foreach ((array) $this->server->vars as $var => $value) {
            $this->$var = $value;
        }

        $protocol = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '');
        $this->url = $protocol.'://'.$this->server->host.'/'.$this->server->path;
        $this->url = Str::finish($this->url, '/');

        foreach ($this->langs as $sigla => $lang) {
            if ($lang->default) {
                $this->lang_default = $sigla;
                break;
            }
        }
    }
    
    public function start()
    {
        $host = self::getHost();
        
        if (isset($this->servers[$host])) {
            $this->setServer($this->servers[$host]);
        }
        
        self::setConfig($this);
    }

    public static function getHost()
    {
        return getenv('INTERADMIN_HOST');
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

    /* Old globals */
    /*
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
        $GLOBALS['c_cliente_domains'] = array_merge($GLOBALS['c_cliente_domains'], (array) $server->aliases);
    }
    foreach($this->langs as $sigla => $lang) {
        $GLOBALS['c_lang'][] = array($sigla, $lang->name, (bool) $lang->multibyte);
    }
    $GLOBALS['c_lang_default'] = $this->lang_default;
    */
}