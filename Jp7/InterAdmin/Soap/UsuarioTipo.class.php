<?php

class Jp7_InterAdmin_Soap_UsuarioTipo extends InterAdminTipo {
	const DEFAULT_FIELDS_ALIAS = true;
	
	public function __construct($options = array()) {
		/**
		 * @global Define o $id_tipo em que são gravados os usuários com acesso.
		 */
		global $c_tipos_permissoes_xml_csv;
		parent::__construct($c_tipos_permissoes_xml_csv, $options);
	}
	
	public function login($username, $password) {
		$usuario = $this->getFirstInterAdmin(array(
			'fields' => array('secoes'),
			'where' => array(
				"usuario = '" . addslashes($username) . "'",
				"senha = '" . md5($password) . "'"
			),
			'use_published_filters' => true
		));
		
		if ($usuario && $this->verifyIps($usuario)) {
			return $usuario;
		}
	}
	
	public function verifyIps($usuario) {
		$ips = $usuario->getIps(array(
			'fields' => array('ip'),
			'fields_alias' => true,
			'use_published_filters' => true
		));
		
		//Verifica se IP é igual ou está na faixa de ip cadastrados
		$usuarioIp = $_SERVER['REMOTE_ADDR'];
		
		if ($ips) {
			foreach ($ips as $ip) {
				$ip->ip = '/' . addcslashes(str_replace('*', '[0-9]{1,3}', $ip->ip), '.') . '/';		
				if (preg_match($ip->ip, $usuarioIp)) {
					return true;
				}
			}
		}
	}
}
	