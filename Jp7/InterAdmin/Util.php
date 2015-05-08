<?php

namespace Jp7\Interadmin;

use InterAdminTipo;

class Util {
	
	public static function gerarClasseInterAdmin(InterAdminTipo $tipo, $gerarArquivo = true, $nomeClasse = '') {
		$prefixoClasse = constant(InterAdminTipo::getDefaultClass() . '::DEFAULT_NAMESPACE');
		
		if (!$nomeClasse) {
			$nomeClasse = $tipo->class;
		}
		
		$phpdoc = '/**' . "\r\n";
		/*
		foreach ($tipo->getCampos() as $campo) {
			$phpdoc .= ' * @property ' . self::_getTipoPhpDocCampo($tipo, $campo) . ' $'. $campo['nome_id'] . "\r\n";
		}
		$phpdoc .= ' * @property Jp7_Date date_publish' . "\r\n";
		*/ 
		$phpdoc.= ' */';
		
		$conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}InterAdmin {
	
}
STR;
		if ($gerarArquivo) {
			return self::salvarClasse($nomeClasse, $conteudo);
		} else {
			return $conteudo;
		}
	}
	
	public static function gerarClasseInterAdminTipo(InterAdminTipo $tipo, $gerarArquivo = true, $nomeClasse = '', $nomeClasseInterAdmin = '') {
		$prefixoClasse = constant(InterAdminTipo::getDefaultClass() . '::DEFAULT_NAMESPACE');
		
		if (!$nomeClasse) {
			$nomeClasse = $tipo->class_tipo;
		}
		/*
		if (!$nomeClasseInterAdmin) {
			$nomeClasseInterAdmin = $tipo->class;
		}
		if (!$nomeClasseInterAdmin) {
			$constname = InterAdminTipo::getDefaultClass() . '::DEFAULT_NAMESPACE';
			if (defined($constname)) {
				$nomeClasseInterAdmin = constant($constname) . 'InterAdmin';				
			} else {
				$nomeClasseInterAdmin = 'InterAdmin';
			}
		}
		*/
		$phpdoc = '/**' . "\r\n";
		//$phpdoc.= ' * @method ' . $nomeClasseInterAdmin . '[] find' . "\r\n";
		//$phpdoc.= ' * @method ' . $nomeClasseInterAdmin . ' findFirst' . "\r\n";
		//$phpdoc.= ' * @method ' . $nomeClasseInterAdmin . ' findById' . "\r\n";
		$phpdoc.= ' */';
		
		$conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}InterAdminTipo {
	const ID_TIPO = {$tipo->id_tipo};
}
STR;
		if ($gerarArquivo) {
			return self::salvarClasse($nomeClasse, $conteudo);
		} else {
			return $conteudo;
		}
	}
	
}