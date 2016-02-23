<?php

class Jp7_Interadmin_Mfa extends Jp7_Interadmin_User
{
    const DEFAULT_FIELDS_ALIAS = true;

    private static $issuer;

    /**
     * @return Jp7_Interadmin_Mfa
     */
    public static function getLoggedUser($s_user = null)
    {
        global $s_session;
        if (!$s_user) {
            $s_user = $s_session['temp_user'];
        }
        if (!$s_user['id_tipo']) {
            return;
        }
        $userTipo = new Jp7_Interadmin_UserTipo($s_user['id_tipo']);

        if (!$userTipo->getCampoUsuario()) {
            throw new Exception('Campo "usuario" não existe no tipo Usuários.');
        }

        return $userTipo->findById($s_user['id'], [
            'fields' => '*',
            'fields_alias' => true,
            'class' => self::class,
        ]);
    }

    // Special - Campo
    public static function habilitar($campo, $value, $parte = 'edit')
    {
        switch ($parte) {
            case 'header':
                return $campo['label'];
            case 'list':
                return $value;
            case 'edit':
                $campo = [
                    'tipo_de_campo' => 'select',
                    'xtra' => '',
                    'value' => $value,
                    'opcoes' => [
                        'email' => 'E-mail',
                        'google' => 'Google Authenticator',
                    ],
                ] + $campo;
                ob_start();
                $field = new InterAdminField($campo);
                echo $field->getHtml();
                echo str_replace('value="0"', 'value=""', ob_get_clean());
                break;
        }
    }

    public function hasSecret()
    {
        return $this->mfa_secret && ($this->mfa !== 'google' || mb_strlen($this->mfa_secret) == 16);
    }

    public function isEnabled()
    {
        return $this->mfa != '';
    }

    public function isExpired()
    {
        if ($this->mfa === 'google' && !$this->hasSecret()) {
            return true;
        }

        $mfaCookie = $this->readCookie();

        if (!isset($mfaCookie)) {
            return true;
        }

        $dbToken = $this->getValidMfaToken([
            'where' => [
                "md5(id) = '".$mfaCookie['id']."'",
                "md5(UNIX_TIMESTAMP(date_publish)) = '".$mfaCookie['timestamp']."'",
            ],
        ]);

        return !$dbToken;
    }

    public function verifyToken($code)
    {
        if ($this->mfa === 'google') {
            $gauth = new GoogleAuthenticator();

            return $gauth->verifyCode($this->mfa_secret, $code);
        } elseif ($this->mfa === 'email') {
            if ($this->mfa_secret === $code) {
                $this->updateAttributes(['mfa_secret' => '']);

                return true;
            }
        }
    }

    public function addMfaToken($code)
    {
        $mfaToken = $this->createMfaTokens();

        $mfaToken->code = $code;
        $mfaToken->date_publish = new Jp7_Date();
        $mfaToken->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $mfaToken->publish = 'S';
        $mfaToken->save();

        return $mfaToken;
    }

    public function getValidMfaToken($options)
    {
        $options = InterAdmin::mergeOptions([
            'fields' => ['date_publish'],
            'fields_alias' => true,
            'where' => [
                "date_publish >= '".new Jp7_Date('-15 days')."'",
            ],
            'use_published_filters' => true,
        ], $options);

        return $this->getFirstMfaTokens($options);
    }

    public function success($last_code = null)
    {
        if ($last_code) {
            $this->saveCookie($last_code);
        }
        $this->saveSession();
    }

    public function readCookie()
    {
        global $jp7_app;

        $cookieKey = $jp7_app ? $jp7_app : 0;
        
        return unserialize($_COOKIE['mfa'][$this->getCliente()][$cookieKey]);
    }

    private function getCliente()
    {
        global $config;
        if (in_array($config->name_id, ['extra', 'casasbahia', 'pontofrio', 'cdiscount'])) {
            return 'novapontocom';
        } else {
            return $config->name_id;
        }
    }

    public function saveCookie($last_code)
    {
        global $jp7_app;
        
        $cookieKey = $jp7_app ? $jp7_app : 0;
        
        $mfaToken = $this->addMfaToken($last_code);

        $cookie = [
            'id' => md5($mfaToken->id),
            'timestamp' => md5($mfaToken->date_publish->getTimestamp()),
        ];

        setcookie('mfa['.$this->getCliente().']['.$cookieKey.']', serialize($cookie), strtotime('+15 days'), '/');
    }

    public function saveSession()
    {
        global $s_session, $jp7_app, $s_interadmin_cliente;

        $s_session['user'] = $s_session['temp_user'];
        unset($s_session['temp_user']);

        setcookie($s_interadmin_cliente.'['.$jp7_app.']', serialize($s_session['temp_cookie']), strtotime('+1 month'), '/');
        unset($s_session['temp_cookie']);
    }

    public function sendToken()
    {
        if (!$this->email) {
            throw new Exception('Usuário sem e-mail cadastrado.');
        }

        $secret = '';
        for ($i = 0; $i < 6; $i++) {
            $secret .= rand(0, 9);
        }
        $this->updateAttributes([
            'mfa_secret' => $secret,
        ]);

        $issuer = $this->getIssuer();

        $message = '<div style="font-family: Verdana;font-size: 13px;">';
        $message .= 'Token de acesso - '.$issuer.':<br><br>';
        $message .= '<div style="background:#ccc;font-size:24px;display:inline-block;padding: 10px;">'.$secret.'</div>';
        $message .= '<br><br>';
        $message .= 'Obrigado por solicitar o seu token de acesso.';
        $message .= '</div>';
        
        $subject = $issuer.' Token';
        $headers = 'From: '.$issuer." <no-reply@jp7.com.br>\r\n";
        
        jp7_mail($this->email, $subject, $message, $headers);
    }

    public function createGoogleSecret()
    {
        $gauth = new GoogleAuthenticator();

        return $gauth->createSecret();
    }

    public function getIssuer()
    {
        if (!self::$issuer) {
            global $config, $c_interadmin_app_title;
            self::$issuer = $c_interadmin_app_title.' - '.$config->name;

            if ($config->server->type != InterSite::PRODUCAO) {
                self::$issuer .= ' ('.$config->server->type.')';
            }
        }

        return self::$issuer;
    }

    public function setIssuer($name)
    {
        self::$issuer = $name;
    }

    public function getGoogleQRCodeUrl($code)
    {
        $gauth = new GoogleAuthenticator();

        return $gauth->getQRCodeGoogleUrl($this->usuario ?: $this->login, $this->getIssuer(), $code);
    }
}
