<?php

namespace Jp7\InterAdmin;

class DynamicLoader {
	
	// Cria classes cadastradas no InterAdmin sem a necessidade de criar um arquivo para isso
	public static function load($class) {
		$tipo = \InterAdminTipo::findFirstTipo(array(
			'where' => array(
				"(class = '" . addslashes($class) . "' OR class_tipo = '" . addslashes($class) . "')"
			)
		));
		if ($tipo) {
			if ($tipo->class === $class) {
				$code = \Jp7_InterAdmin_Util::gerarClasseInterAdmin($tipo, false);
			} else {
				$code = \Jp7_InterAdmin_Util::gerarClasseInterAdminTipo($tipo, false);
			}
			eval('?>' . $code);
			return true;
		}
		
		return false;
	}
}