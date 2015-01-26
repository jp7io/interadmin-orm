<?php

namespace Jp7\Interadmin;

class DynamicLoader {

	// Cria classes cadastradas no InterAdmin sem a necessidade de criar um arquivo para isso
	public static function load($class) {
		$cm = ClassMap::getInstance();

		$code = null;
		if ($id_tipo = $cm->getClassIdTipo($class)) {
			$tipo = new \InterAdminTipo($id_tipo);
			$code = \Jp7_InterAdmin_Util::gerarClasseInterAdmin($tipo, false);
		} elseif ($id_tipo = $cm->getClassTipoIdTipo($class)) {
			$tipo = new \InterAdminTipo($id_tipo);
			$code = \Jp7_InterAdmin_Util::gerarClasseInterAdminTipo($tipo, false);
		}
		if ($code) {
			eval('?>' . $code);
			return true;
		}
		
		return false;
	}

}