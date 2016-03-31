<?php

class Jp7_Interadmin_User extends InterAdmin
{
    /**
     * Masks something@example.com into someth***@exam***.com
     */
    public function maskEmail()
    {
        list($username, $domain) = explode('@', $this->email);
        $showchars = min([3, mb_strlen($username)]);
        $username = mb_substr($username, 0, $showchars).str_repeat('*', mb_strlen($username) - $showchars);

        $parts = explode('.', $domain);
        $parts[0] = mb_substr($parts[0], 0, 1).str_repeat('*', mb_strlen($parts[0]) - 1);
        $domain = implode('.', $parts);

        return $username.'@'.$domain;
    }
    
    public function getResetToken()
    {
        if ($this->reset_token_sent_at < new Jp7_Date('-1 day')) {
            // Token is stale
            $this->updateAttributes([
                'reset_token' => $this->newResetToken(),
                'reset_token_sent_at' => new Jp7_Date
            ]);
        }
        return $this->reset_token;
    }
    
    /**
     * Crypto strong 256-bit random token
     */
    private function newResetToken()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
    
    public function resetPassword($password, $confirm_password)
    {
        if (strlen($password) < 6) {
            throw new Exception('Senha deve conter no mínimo 6 caracteres');
        }
        if ($password !== $confirm_password) {
            throw new Exception('Confirmação de senha deve ser igual à nova senha');
        }
        
        $this->senha = md5($password);
        $this->reset_token = '';
        $this->reset_token_sent_at = new Jp7_Date('0000-00-00');
        
        $this->save();
    }
    
    // Special - Disparo
    public static function disparo($from, $id, $id_tipo)
    {
        if ($from !== 'insert' || empty($_POST['char_send_link'][0])) {
            return;
        }
        $userTipo = (new Interadmin_Login)->getUsuarioTipo();
        $user = $userTipo->findById($id, [
            'fields' => '*',
            'fields_alias' => true,
            'class' => Jp7_Interadmin_User::class
        ]);
        
        // Flash msg
        Zend_Session::$_unitTestEnabled = true;
        $msg = new Zend_Controller_Action_Helper_FlashMessenger();
        
        try {
            if (!$user->isPublished()) {
                throw new Exception('Usuário está despublicado');
            } elseif (!$user->leitura) {
                throw new Exception('Usuário sem acesso de leitura');
            } elseif (!$user->email) {
                throw new Exception('Usuário não possui e-mail cadastrado');
            } else {
                // User starts without password
                $user->updateAttributes([
                    // Its not MD5, so user will never login with it
                    'senha' => uniqid()
                ]);
                
                $mailer = new Interadmin_Mailer_PasswordRegistration($user);
                $mailer->handle();
                
                $msg->setNamespace('success');
                $msg->addMessage('Enviado e-mail com link para cadastro de senha');
            }
        } catch (Exception $e) {
            $msg->addMessage('Link para cadastro de senha não enviado: ' . $e->getMessage());
        }
    }
}
