<?php		
		
use Monolog\Logger as MonologLogger;		
use Monolog\Handler\SyslogHandler;		
		
class InterAdminLogFacade extends Illuminate\Support\Facades\Log		
{		
    // Temporario para usar facade sem Laravel		
    protected static function resolveFacadeInstance($name)		
    {		
        global $config;		
        		
        $monolog = new MonologLogger($config->server->host);		
        		
        $log = new \Illuminate\Log\Writer($monolog);		
        $log->useSyslog($config->name_id);		
        return $log;		
    }		
}
