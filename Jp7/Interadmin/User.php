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

    public function getPasswordType()
    {
        return $this->getType()->getCampos()['password_key']['xtra'];
    }

    private function checkHash($value)
    {
        if (strlen($this->password_key) === 32 && ctype_xdigit($this->password_key) && md5($value) === $this->password_key) {
            // MD5 - needs migration
            $this->setPassword($value);
            $this->saveRaw();
        }
        return Hash::check($value, $this->password_key);
    }

    public function checkPassword($value)
    {
        if (!$value) {
            return false;
        }
        switch ($this->getPasswordType()) {
            case '': // Plain Text
                return $this->password_key === $value;
            case 'hash': // Hash
                return $this->checkHash($value, $this->password_key);
            case 'S': // MD5
                return $this->password_key === md5($value);
            default:
                throw new DomainException('Unknown password type');
        }
    }

    public function setPassword($password)
    {
        switch ($this->getPasswordType()) {
            case '': // Plain Text
                $this->password_key = $password;
                break;
            case 'hash': // Hash
                $this->password_key = Hash::make($password);
                break;
            case 'S': // MD5
                $this->password_key = md5($password);
                break;
            default:
                throw new DomainException('Unknown password type');
        }
    }

    public function resetPassword($password, $confirm_password, $old_password = null)
    {
        if (strlen($password) < 6) {
            throw new UnexpectedValueException('Senha deve conter no mínimo 6 caracteres');
        }
        if ($password !== $confirm_password) {
            throw new UnexpectedValueException('Confirmação de senha deve ser igual à nova senha');
        }
        $this->setPassword($password);
        $this->reset_token = '';
        $this->reset_token_sent_at = new Jp7_Date('0000-00-00');
        $this->save();

        $changePassEvent = Interadmin_Event_ChangePass::getInstance();
        $changePassEvent->setUserId($this->id);
        $changePassEvent->setOldPassword($old_password);
        $changePassEvent->setNewPassword($password);
        $changePassEvent->notify();
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

        try {
            if (!$user->isPublished()) {
                throw new UnexpectedValueException('Usuário está despublicado');
            } elseif (!$user->leitura) {
                throw new UnexpectedValueException('Usuário sem acesso de leitura');
            } elseif (!$user->email) {
                throw new UnexpectedValueException('Usuário não possui e-mail cadastrado');
            } else {
                // User starts without password
                $user->updateAttributes([
                    // Its not MD5, so user will never login with it
                    'password_key' => uniqid()
                ]);

                $mailer = new Interadmin_Mailer_PasswordRegistration($user);
                $mailer->handle();

                Session::push('flash.new', 'flash.success');
                Session::push('flash.success', 'Enviado e-mail com link para cadastro de senha.');
            }
        } catch (UnexpectedValueException $e) {
            Session::push('flash.new', 'flash.error');
            Session::push('flash.error', $e->getMessage());
        }
    }

    // Special - Campo
    /**
     * @deprecated Use Interadmin\Fields\PasswordLinkButton
     */
    public static function specialPassword($campo, $value, $parte = 'edit')
    {
        switch ($parte) {
            case 'header':
                return $campo['label'];
            case 'list':
                return $value;
            case 'edit':
                global $id;

                // Remove custom keys
                $campo['nome'] = $campo['nome_original'];
                unset($campo['tipo_de_campo']);
                unset($campo['nome_original']);

                if (!$id) {
                    // New user shows checkbox to send link
                    $campo['tipo'] = 'char_send_link';
                }
                $interAdminField = new InterAdminField($campo);
                ob_start();
                $interAdminField->getHtml();
                $html = ob_get_clean();

                if (!$id) {
                    // New user shows checkbox to send link
                    $end = '</td><td></td></tr>';
                    $label = '<label for="jp7_db_checkbox_char_send_link[0]">Enviar link para cadastro de senha</label>';
                    $html = str_replace($end, $label.$end, $html);
                }
                return $html;
        }
    }
}
