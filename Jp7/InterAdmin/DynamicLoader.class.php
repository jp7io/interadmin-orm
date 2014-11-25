<?php

namespace Jp7\InterAdmin;

class DynamicLoader {
	
	// Cria classes cadastradas no InterAdmin sem a necessidade de criar um arquivo para isso
	public static function load($class) {
		$tipo = \InterAdminTipo::findFirstTipo(array(
			'where' => array(
				"class = '" . addslashes($class) . "'"
			)
		));
		if ($tipo) {
			$code = \Jp7_InterAdmin_Util::gerarClasseInterAdmin($tipo, false);
			eval('?>' . $code);
			return true;
		}
		
		return false;
	}
}