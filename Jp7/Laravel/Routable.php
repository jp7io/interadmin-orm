<?php

namespace Jp7\Laravel;

trait Routable {
	
	public function getControllerBasename() {
		return $this->getStudly() . 'Controller';
	}
	
	// AEmpresa\PerfilController
	public function getControllerName() {
		$namespace = $this->getNamespace();
		return ($namespace ? $namespace . '\\' : '') . $this->getControllerBasename();
	}
	
	public function getNamespace() {
		$parent = $this->getParent();
		$namespace = array();
		while ($parent && $parent->id_tipo > 0) {
			$namespace[] = $parent->getStudly();
			$parent = $parent->getParent();
		}
		return implode($namespace, '\\');
	}
	
	// a-empresa
	public function getSlug() {
		$nome = toSlug($this->nome);
		if (is_numeric($nome)) {
			// verificar maneira de tratar isso
			$nome = 'list-' . $nome;
		}
		return substr($nome, 0, 32);
	}
	
	public function getStudly() {
		return studly_case($this->getSlug());
	}
	
	public function isRoot() {
		return $this->id_tipo == '0';
	}
	
	public function getChildrenMenu() {
		return $this->children()->where(['menu' => true ])->all();
	}
	
}
