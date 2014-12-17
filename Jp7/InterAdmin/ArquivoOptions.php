<?php

namespace Jp7\Interadmin;
use InterAdmin;

class ArquivoOptions extends BaseOptions {
	
	protected function _isChar($field) {
		$chars = array(
			'mostrar',
			'destaque',
			'deleted',
			'link_blank'
		);

		return in_array($field, $chars);
	}
		
	public function all() {
		return $this->provider->getArquivos(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	public function first() {
		$this->options['limit'] = 1;
		
		$arquivos = $this->provider->getArquivos(InterAdmin::DEPRECATED_METHOD, $this->options);
		return $arquivos[0];
	}
		
}