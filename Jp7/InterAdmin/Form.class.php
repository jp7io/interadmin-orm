<?php

class Jp7_InterAdmin_Form extends InterAdminField {
	public $tipo;
	public $campos;
	
	public function __construct($tipo) {
		InterAdminField::$php5_2_hack_className = get_class($this);
		
		$this->tipo = $tipo;
		$this->campos = $tipo->getCampos();
	}
	
	public function getHtml($record) {
		global $j, $iframes_i;
		$j = 0; // Gambiarra InterAdmin
		$iframes_i = 0;
		
		global $id;
		$id = false; // Gambiarra para usar o valor default do campo
		
		return InterAdminField::getForm($this->campos, $record);
	}
	
	public function uploadFiles($record, $key = 0) {
		foreach ($this->campos as $campo) {
			if ($campo['form'] && startsWith('file_', $campo['tipo'])) {
				$nome_id = $campo['nome_id'];
				
				$uploader = new Jp7_Uploader($nome_id, '/.*/', '/.*/');
				$uploader->setBasePath('upload/');
				
				$record->$nome_id = '';
				// Irá procurar por , essa classe só funciona se o campo for array
				if ($url_temp = $uploader->save($key, 'temp_')) {
					// Cria um objeto InterAdminArquivo, necessário porque é essa classe que tem o método addToArquivosBanco
					$imagem = $record->createArquivo();
					$imagem->url = $url_temp;
					// Salva no banco de imagens tornando disponivel para busca no InterAdmin
					$url_final = $imagem->addToArquivosBanco('upload/');
					// Transforma em InterAdminFile, que é o tipo correto do campo 'foto' do objeto $record
					$record->$nome_id = new InterAdminFieldFile($url_final);
				}
			}
		}
	}
	
	public function validateAndSave($record) {
		foreach ($this->campos as $campo) {
			$this->_validateCampo($record, $campo);
		}
		$record->save();
	}
	
	protected function _validateCampo($record, $campo) {
		if (!$campo['form']) {
			return;
		}
		return InterAdminField::validate($record, $campo);
	}
}
