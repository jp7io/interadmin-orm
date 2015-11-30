<?php

class Jp7_InterAdmin_User extends InterAdmin
{
    /**
     * Masks something@example.com into someth***@exam***.com
     */
    public function maskEmail()
    {
        list($username, $domain) = explode('@', $this->email);
        $showchars = min(array(3, mb_strlen($username)));
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
}
