<?php

namespace Jp7\InterAdmin;

class DynamicLoader {
	private static $classMap = [];
	private static $classTipoMap = [];

	// Cria classes cadastradas no InterAdmin sem a necessidade de criar um arquivo para isso
	public static function load($class) {
		if (empty(self::$classMap)) {
			self::createClassMap();
		}

		$code = null;
		if ($id_tipo = self::$classMap[$class]) {
			$tipo = new \InterAdminTipo($id_tipo);
			$code = \Jp7_InterAdmin_Util::gerarClasseInterAdmin($tipo, false);
		} elseif ($id_tipo = self::$classTipoMap[$class]) {
			$tipo = new \InterAdminTipo($id_tipo);
			$code = \Jp7_InterAdmin_Util::gerarClasseInterAdminTipo($tipo, false);
		}
		if ($code) {
			eval('?>' . $code);
			return true;
		}
		
		return false;
	}

	public static function createClassMap() {
		$maps = \Cache::remember('Interadmin.classmap', 60, function() {
			$classMap = [];
			$classTipoMap = [];

			$tipos = \InterAdminTipo::findTipos(array(
				'fields' => array('class', 'class_tipo'),
				'where' => array(
					"(class <> '' OR class_tipo <> '')"
				),
				'class' => 'stdClass'
			));
			
			foreach ($tipos as $tipo) {
				$attr = &$tipo->attributes;
				if ($attr['class']) {
					$classMap[$attr['class']] = $attr['id_tipo'];
				}
				if ($attr['class_tipo']) {
					$classTipoMap[$attr['class_tipo']] = $attr['id_tipo'];
				}
			}

			return compact('classMap', 'classTipoMap');
		});

		self::$classMap = $maps['classMap'];
		self::$classTipoMap = $maps['classTipoMap'];
	}
}