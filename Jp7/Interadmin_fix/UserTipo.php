<?php

class Jp7_InterAdmin_UserTipo extends InterAdminTipo
{
    public function getCampoUsuario()
    {
        $aliases = $this->getCamposAlias();
        
        if (in_array('usuario', $aliases)) {
            return 'usuario';
        } elseif (in_array('login', $aliases)) {
            return 'login';
        }
    }
}
