<?php

class InterAdminMfa extends InterAdmin {
	const DEFAULT_FIELDS_ALIAS = true;
	
	/**
	 * @return InterAdmin_Mfa
	 */
    public static function getLoggedUser($s_user = null) {
    	global $s_session;
        if (!$s_user) {
        	$s_user = $s_session['temp_user'];
        }
		
        $userTipo = new InterAdminTipo($s_user['id_tipo']);
        
        $aliases = $userTipo->getCamposAlias();
        $campos = array();
        if (in_array('usuario', $aliases)) {
        	$campos[] = 'usuario';
        } elseif (in_array('login', $aliases)) {
        	$campos[] = 'login';
        } else {
        	throw new Exception('Campo usuario não existe no tipo Usuários.');
        }       
        if (in_array('mfa', $aliases)) {
        	$campos[] = 'mfa';
        	$campos[] = 'mfa_secret';
        }
        if (in_array('email', $aliases)) {
        	$campos[] = 'email';
        }
        
       	$loggedUser = $userTipo->findById($s_user['id'], array(
        	'fields' => $campos,
       		'fields_alias' => true,
        	'class' => get_class($this)
       	));
				
        return $loggedUser;
    }
    
    public static function habilitar($campo, $value, $parte = 'edit') {
    	switch ($parte) {
    		case 'header':
    			return $campo['label'];
    			break;
    		case 'list':
    			return $value;
    		case 'edit':
    			$campo = array(
    				'tipo_de_campo' => 'select',
    				'xtra' => '',
    				'opcoes' => array(
    					'email' => 'E-mail',
    					'google' => 'Google Authenticator'
    				)
    			) + $campo;
    			ob_start();
    			$field = new InterAdminField($campo);
    			echo $field->getHtml();
    			echo str_replace('value="0"', 'value=""', ob_get_clean());
    			break;
    	}
    }
	
    public function hasSecret() {
        return $this->mfa_secret && ($this->mfa !== 'google' || strlen($this->mfa_secret) == 16);
    }

    public function isEnabled() {
	   	return $this->mfa != '';
    }
    
    public function isExpired() {
    	if ($this->mfa === 'google' && !$this->hasSecret()) {
    		return true;
    	}
    	
    	$mfaCookie = $this->readCookie();
    	        
        if (!isset($mfaCookie)) {
        	return true;
        }
       	
        $dbToken = $this->getValidMfaToken(array(
        	'where' => array(
                "md5(id) = '" . $mfaCookie['id'] . "'",
                "md5(UNIX_TIMESTAMP(date_publish)) = '" . $mfaCookie['timestamp'] . "'",
        	)
        ));
        
        return !$dbToken;
    }

    public function verifyToken($code) {
        if ($this->mfa === 'google') {
	    	$gauth = new GoogleAuthenticator();
	        return $gauth->verifyCode($this->mfa_secret, $code);
        } elseif ($this->mfa === 'email') {
        	if ($this->mfa_secret === $code) {
        		$this->updateAttributes(array('mfa_secret' => ''));
        		return true;
        	}
        }
    }
    
    public function addMfaToken($code) {
    	$mfaToken = $this->createMfaTokens();
    	 
    	$mfaToken->code = $code;
    	$mfaToken->date_publish = new Jp7_Date();
    	$mfaToken->user_agent = $_SERVER['HTTP_USER_AGENT'];
    	$mfaToken->publish = 'S';
    	$mfaToken->save();
    	return $mfaToken;
    }
    
    public function getValidMfaToken($options) {
    	$options = InterAdmin::mergeOptions(array(
	    	'fields' => array('date_publish'),
			'fields_alias' => true,
			'where' => array(
			    "date_publish >= '" . new Jp7_Date('-15 days') . "'"
			),
			'use_published_filters' => true
		), $options);
    	
    	return $this->getFirstMfaTokens($options);
    }  
    
    public function success($last_code = null) {
    	if ($last_code) {
    		$this->saveCookie($last_code);
    	}
    	$this->saveSession();
    }
    
    public function readCookie() {
    	global $jp7_app;
    	
    	$key = 0;
    	if ($jp7_app) { 
    		$key = $jp7_app;
    	}
    	
    	return unserialize($_COOKIE['mfa'][$this->getCliente()][$key]);
    }
    
    private function getCliente() {
    	global $s_interadmin_cliente;
    	if (in_array($s_interadmin_cliente, array('extra', 'casasbahia', 'pontofrio'))) {
    		return 'novapontocom';
    	} else {
    		return $s_interadmin_cliente;
    	}
    }

    public function saveCookie($last_code) {
        global $jp7_app;
		
        $mfaToken = $this->addMfaToken($last_code);
        
        $cookie = array(
            'id' => md5($mfaToken->id),
            'timestamp' => md5($mfaToken->date_publish->getTimestamp())
        );
		
        setcookie('mfa[' . $this->getCliente() . '][' . $jp7_app . ']', serialize($cookie), strtotime('+15 days'), '/');
    }
    
    public function saveSession() {
    	global $s_session, $jp7_app, $s_interadmin_cliente;
    	
    	$s_session['user'] = $s_session['temp_user'];
    	unset($s_session['temp_user']);
    	
    	setcookie($s_interadmin_cliente . '[' . $jp7_app . ']', serialize($s_session['temp_cookie']), strtotime('+1 month'), '/');
    	unset($s_session['temp_cookie']);
    }
	
    public function sendToken() {
    	if (!$this->email) {
    		throw new Exception('Usuário sem e-mail cadastrado.');
    	}
    	
    	$secret = '';
    	for ($i = 0; $i < 6; $i++) {
    		$secret .= rand(0,9);
    	}
    	$this->updateAttributes(array(
    		'mfa_secret' => $secret
    	));
    	
    	$issuer = $this->getIssuer();
    	
    	$message = '<div style="font-family: Verdana;font-size: 13px;">';
    	$message .= 'Token de acesso ao ' . $issuer . ':<br><br>';
    	$message .= '<div style="background:#ccc;font-size:24px;display:inline-block;padding: 10px;">' . $secret . '</div>';
    	$message .= '<br><br>';
    	$message .= 'Obrigado por solicitar o seu token de acesso.';
    	$message .= '</div>';
    	
    	//jp7_mail($this->email, $issuer . ' Token', $message, "From: " . $issuer . " <no-reply@jp7.com.br>\r\n");   	
    }
    
    public function maskEmail() {
    	list($username, $domain) = explode('@', $this->email);
    	$showchars = min(array(3, strlen($username)));
    	$username = substr($username, 0, $showchars) . str_repeat('*', strlen($username) - $showchars);
    	
    	$parts = explode('.', $domain);
    	$parts[0] = substr($parts[0], 0, 1) . str_repeat('*', strlen($parts[0]) - 1);
    	$domain = implode('.', $parts);
    	
    	return $username . '@' . $domain;
    }
    
    public function createGoogleSecret() {
        $gauth = new GoogleAuthenticator();
        return $gauth->createSecret();
    }
    
 	public function getIssuer() {
    	global $config, $c_interadmin_app_title;
    	$issuer = $c_interadmin_app_title . ' - ' . $config->name;
    	
    	if ($config->server->type != InterSite::PRODUCAO) {
    		$issuer .= " (" . strtoupper($config->server->type == 'Desenvolvimento' ? 'Dev' : '') . ")";
    	}
    	return $issuer;
    }
    
    public function getGoogleQRCodeUrl($code) {
        $gauth = new GoogleAuthenticator();
        return $gauth->getQRCodeGoogleUrl($this->usuario ?: $this->login, $this->getIssuer(), $code);
    }

}