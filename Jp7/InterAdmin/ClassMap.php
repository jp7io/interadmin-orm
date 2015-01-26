<?php

namespace Jp7\Interadmin;

class ClassMap {

	static private $instance;

	private $classes;
	private $classesTipos;

	private function __construct() {
		// singleton
	}

	public static function getInstance() {
		// singleton
		if (!self::$instance) {
			self::$instance = \Cache::remember('Interadmin.classMap', 60, function() {
				// cache instance
				$instance = new self();

				$tipos = \InterAdminTipo::findTipos(array(
					'fields' => array('class', 'class_tipo'),
					'where' => array(
						"(class <> '' OR class_tipo <> '')"
					),
					'class' => 'stdClass'
				));

				$classes = [];
				$classesTipos = [];
				foreach ($tipos as $tipo) {
					$attr = &$tipo->attributes;
					if ($attr['class']) {
						$classes[$attr['id_tipo']] = $attr['class'];
					}
					if ($attr['class_tipo']) {
						$classesTipos[$attr['id_tipo']] = $attr['class_tipo'];
					}
				}
				// return cached instance
				$instance->setClasses($classes);
				$instance->setClassesTipos($classesTipos);
				return $instance;
			});
		}
		return self::$instance;
	}

	public function setClasses(array $classes) {
		$this->classes = $classes;
	}

	public function setClassesTipos(array $classesTipos) {
		$this->classesTipos = $classesTipos;
	}

	public function getClassIdTipo($class) {
		return array_search($class, $this->classes);
	}

	public function getClassTipoIdTipo($classTipo) {
		return array_search($classTipo, $this->classesTipos);
	}

	public function getClass($id_tipo) {
		return isset($this->classes[$id_tipo]) ? $this->classes[$id_tipo] : null;
	}

	public function getClassTipo($id_tipo) {
		return isset($this->classesTipos[$id_tipo]) ? $this->classesTipos[$id_tipo] : null;
	}

}