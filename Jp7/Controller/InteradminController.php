<?php

class Jp7_Controller_InteradminController extends Jp7_Controller_Action
{
    // Redirect to remote interadmin
    public function indexAction()
    {
        $config = Zend_Registry::get('config');
        
        if ($interadmin_remote = reset($config->server->interadmin_remote)) {
            $this->_redirect('http://'.$interadmin_remote.'/'.$config->name_id);
        }
        echo 'No InterAdmin remote found.';
        exit;
    }
    
    // Set cookie to flag that user MIGHT have access to interadmin
    // Access should be validated elsewhere
    public function sessionAction()
    {
        global $config;
        $s_cookie = false;
        if (isset($_COOKIE[$config->name_id]['interadmin'])) {
            $s_cookie = unserialize($_COOKIE[$config->name_id]['interadmin']);
        }
        if (!$s_cookie) {
            $s_cookie = [
                'user' => $_GET['user']
            ];
        }
        header('Access-Control-Allow-Origin: *');
        setcookie($config->name_id.'[interadmin]', serialize($s_cookie), strtotime('+1 month'), '/');
        echo 'ok';
        exit;
    }
    
    // Updates log file / Used to invalidate cache
    public function logUpdateAction()
    {
        touch(BASE_PATH.'/interadmin/interadmin.log');
        echo 'ok';
        exit;
    }
}
