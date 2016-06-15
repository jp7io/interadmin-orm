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
    
    public static function error($message, array $context = [])
    {
    	// Send Log::error() to Sentry
        if ($message instanceof Exception && class_exists('Raven_Client')) {
            $sentryClient = new Raven_Client(getenv('SENTRY_DSN'));
            $sentryHandler = new Raven_ErrorHandler($sentryClient);
            $sentryHandler->handleException($message);
        }
        
        return parent::error($message, $context);
    }
}
