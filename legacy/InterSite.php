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
    public function init($env)
    {
        global $jp7_app;
        switch ($env) {
            case 'local':
                $type = self::DEVELOPMENT;
                break;
            case 'staging':
                $type = self::QA;
                break;
            case 'production':
                $type = self::PRODUCTION;
                break;
            default:
                throw new UnexpectedValueException('Invalid APP_ENV: '.$env);
        }

        $this->server = $this->getFirstServerByType($type);
        if (!$this->server) {
            throw new UnexpectedValueException('Unable to find server for APP_ENV: '.$env);
        }
        $this->hostType = self::HOST_MAIN;
        // Check if it's an alias
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        if ($type === self::PRODUCTION && in_array($host, $this->server->aliases)) {
            $this->hostType = self::HOST_ALIAS;
        }
        // DB
        $this->db = (object) [
            'host' => getenv('DB_HOST'),
            'host_internal' => '',
            'name' =>  getenv('DB_DATABASE'),
            'user' =>  getenv('DB_USERNAME'),
            'flags' => '',
            'pass' =>  getenv('DB_PASSWORD'),
            'type' => 'mysql',
            'prefix' => ($jp7_app ?: 'interadmin').'_'.$this->name_id
        ];
        // Vars
        foreach ((array) $this->server->vars as $var => $value) {
            $this->$var = $value;
        }
        // URL
        $protocol = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '');
        if ($jp7_app) {
            // InterAdmin Remote
            $this->interadmin_remote = $host;
            // APP_URL is already used by InterAdmin
            $this->url = $protocol.'://'.$this->server->host.'/'.jp7_path($this->server->path);
        } else {
            $this->url = jp7_path($host ? $protocol.'://'.$host : getenv('APP_URL'));
        }

        // Langs
        foreach ($this->langs as $sigla => $lang) {
            if ($lang->default) {
                $this->lang_default = $sigla;
                break;
            }
        }
        // Storage
        $this->filesystems = [
            'filesystems.default' => getenv('FILESYSTEM_DISK'),
            'filesystems.disks.local' => [
                'driver' => 'local',
                'root'   => BASE_PATH,
            ],
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key'    => getenv('AWS_KEY'),
                'secret' => getenv('AWS_SECRET'),
                'region' => getenv('S3_REGION'),
                'bucket' => getenv('S3_BUCKET'),
            ]
        ];
        $this->storage = [
            'host' => getenv('STORAGE_HOST'),
            'path' => getenv('STORAGE_PATH'),
        ];
    }

    public function start()
    {
        if (!self::isWakeupEnabled()) {
            return;
        }

        if (!getenv('APP_ENV')) {
            throw new UnexpectedValueException('There is no APP_ENV');
        }
        if (!getenv('APP_URL')) {
            throw new UnexpectedValueException('There is no APP_URL');
        }
        $this->init(getenv('APP_ENV'));

        if ($this->hostType === self::HOST_ALIAS) {
            header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');
            header('Location: http://'.$this->server->host.$_SERVER['REQUEST_URI']);
            exit;
        }

        /* @todo TEMP - Creating old globals */
        $GLOBALS['c_url'] = $this->url;
        $GLOBALS['c_site'] = $this->name_id;
        $GLOBALS['c_menu'] = @$this->menu;
        $GLOBALS['c_cache'] = $this->cache;
        $GLOBALS['c_cache_delay'] = @$this->cache_delay;
        $GLOBALS['db_prefix'] = $this->db->prefix;
        $GLOBALS['c_cliente_url_path'] = $GLOBALS['c_path'] = jp7_path($this->server->path);
        $GLOBALS['c_analytics'] = @$this->google_analytics;
        $GLOBALS['googlemaps_key'] = @$this->google_maps;
        $GLOBALS['c_w3c'] = true;
        // InterAdmin
        $GLOBALS['c_publish'] = @$this->interadmin_preview;
        $GLOBALS['c_remote'] = @$this->interadmin_remote;
        $GLOBALS['c_cliente_title'] = $this->name;
        $GLOBALS['c_nobackup'] = @$this->nobackup;
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
}
