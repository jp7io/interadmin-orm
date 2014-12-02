<?php

namespace Jp7\Interadmin;

class TipoOptions extends BaseOptions {
	
	protected function _isChar($field) {
		$chars = array(
			'mostrar',
			'language',
			'menu',
			'busca',
			'restrito',
			'admin',
			'editar',
			'unico',
			'versoes',
			'hits',
			'tags',
			'tags_list',
			'tags_tipo',
			'tags_registros',
			'publish_tipo',
			'visualizar',
			'deleted_tipo'
		);

		return in_array($field, $chars);
	}
		
	public function all() {
		if (func_num_args() > 0) throw new \BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->tipo->getChildren($this->options);
	}
	
	public function first() {
		if (func_num_args() > 0) throw new \BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->tipo->getFistChild($this->options);
	}
		
}