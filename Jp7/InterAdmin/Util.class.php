<?php

class Jp7_InterAdmin_Util {
	
	protected static $_default_vars = array('parent_id', 'date_publish', 'date_insert', 'date_expire', 'date_modify', 'log', 'publish', 'deleted');
	
	/**
	 * Exports records and their children.
	 * 
	 * @param 	InterAdminTipo 	$tipoObj	InterAdminTipo where the records are.
	 * @param 	array         	$ids		Array de IDs.
	 * @return 	InterAdmin[]
	 */
	public static function export(InterAdminTipo $tipoObj, array $ids, $use_id_string = false) {
		$options = array(
			'fields' => array_merge(array('*'), self::$_default_vars),
			'class' => 'InterAdmin',
			'fields_alias' => false
		);
		
		$optionsRegistros = $options;
		if ($use_id_string) {
			$optionsRegistros = self::_prepareOptionsForIdString($optionsRegistros, $tipoObj);
		}
		$exports = $tipoObj->find($optionsRegistros + array(
			'where' => "id IN(" . implode(',', $ids) . ')'
		));

		$tiposChildren = $tipoObj->getInterAdminsChildren();
		foreach ($exports as $export) {
			$export->_children = array();
			foreach ($tiposChildren as $tipoChildren) {
				$optionsChildren = $options;
				$tipoChildren = $export->getChildrenTipo($tipoChildren['id_tipo']/*, array('class' => 'InterAdminTipo')*/);
				if ($use_id_string) {
					$optionsChildren = self::_prepareOptionsForIdString($optionsChildren, $tipoChildren);
				}
				
				//$optionsChildren['fields_alias'] = true;
				$children = $tipoChildren->find($optionsChildren);
				foreach ($children as $child) {
					$child->setTipo(null);
					$child->setParent(null);
				}
				$export->_children[$tipoChildren->id_tipo] = $children;
			}
			$export->setTipo(null);
		}
		return $exports;
	}
	
	protected static function _prepareOptionsForIdString($options, $tipo) {
		$campos = $tipo->getCampos();
		foreach ($campos as $campo) {
			$isSpecialRegistro = strpos($campo['tipo'], 'special_') === 0 && $campo['xtra'] == 'registros' && $tipo->getCampoTipo($campo) instanceof InterAdminTipo;
			$isSelectRegistro = strpos($campo['tipo'], 'select_') === 0 && strpos($campo['tipo'], 'select_multi_') !== 0 && !in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());
			if ($isSpecialRegistro || $isSelectRegistro) {
				$options['fields'][$campo['tipo']] = array('id_string');
			}
		}
		return $options;
	}
	
	protected static function _importAttributeFromIdString($record, $bind_children = false) {
		foreach ($record->attributes as $attributeName => $attribute) {
			if ($attribute instanceof InterAdmin && $attribute->id_string) {
				if ($attributeTipo = $attribute->getTipo()) {
					$options = array();
					if ($bind_children) {
						$options['order'] = 'parent_id = ' . $record->parent_id . ' DESC';
					}
					$record->$attributeName = $attributeTipo->findByIdString($attribute->id_string, $options);
				}
			}
		}
	}
	
	/**
	 * Imports records and their children with a new ID.
	 * 
	 * @param 	array	$records
	 * @param 	int 	$id_tipo
	 * @param 	int 	$parent_id 			defaults to 0
	 * @param 	bool 	$import_children 	defaults to TRUE
	 * @param 	bool	$use_id_string		defaults to FALSE
	 * @param 	bool 	$bind_children		Children 1 has a relationship with Children 2, when copying, this relationship needs to be recreated
	 * @return 	void	
	 */
	public static function import(array $records, $id_tipo, $parent_id = 0, $import_children = true, $use_id_string = false, $bind_children = false) {
		foreach ($records as $record) {
			unset($record->id);
			
			$tipo = InterAdminTipo::getInstance($id_tipo);
			
			$record->parent_id = $parent_id;
			$record->setTipo($tipo);
				
			if ($use_id_string) {
				self::_importAttributeFromIdString($record);
			}
			
			$record->save();
			
			if ($import_children) {
				foreach ($record->_children as $child_id_tipo => $tipo_children) {
					$child_tipo = InterAdminTipo::getInstance($child_id_tipo);
					
					foreach ($tipo_children as $child) {
						unset($child->id);
												
						$child->parent_id = $record->id;
						$child->setTipo($child_tipo);
						
						if ($use_id_string || $bind_children) {
							self::_importAttributeFromIdString($child, $bind_children);
						}
						
						$child->save();
					}
				}
			}
		}
	}
	
	public static function syncTipos($model) {
		$inheritedTipos = InterAdminTipo::findTiposByModel($model->id_tipo, array(
			'class' => 'InterAdminTipo'
		));
		?>
		&bull; <?php echo $model->id_tipo; ?> - <?php echo $model->nome; ?> <br />
		<div class="indent">
			<?php foreach ($inheritedTipos as $key => $tipo) { ?>
				<?php
				$tipo->syncInheritance();
				$tipo->updateAttributes($tipo->attributes);
				?>
				<?php self::syncTipos($tipo); ?>
			<?php } ?>
		</div>
		<?php
	}
	
	/**
	 * Helper da função _getCampoType
	 *
	 * @param InterAdminTipo $campoTipo
	 * @param bool $isTipo
	 * @param bool $isMulti
	 *
	 * @return string Type para o PHPDoc
	 */
	protected function _getCampoTypeClass($campoTipo, $isTipo, $isMulti) {
		if ($isTipo) {
			$retorno = 'InterAdminTipo';
		} else {
			$retorno = $campoTipo->class ? $campoTipo->class : 'InterAdmin';
		}
		if ($isMulti && $retorno) {
			$retorno .= '[]';
		}
		return $retorno;
	}
	
	protected static function _getTipoPhpDocCampo($tipo, $campo) {
		if (strpos($campo['tipo'], 'special_') === 0 && $campo['xtra']) {

			$isMulti = in_array($campo['xtra'], InterAdminField::getSpecialMultiXtras());
			$isTipo = in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras());
		
			$retorno = self::_getCampoTypeClass($tipo->getCampoTipo($campo), $isTipo, $isMulti);
		
		} elseif (strpos($campo['tipo'], 'select_') === 0) {
		
			$isMulti = (strpos($campo['tipo'], 'select_multi') === 0);
			$isTipo = in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());
			
			$retorno = self::_getCampoTypeClass($campo['nome'], $isTipo, $isMulti);
		
		} elseif (strpos($campo['tipo'], 'int') === 0 || strpos($campo['tipo'], 'id') === 0) {
			$retorno = 'int';
		} elseif (strpos($campo['tipo'], 'char') === 0) {
			$retorno = 'string';
		} elseif (strpos($campo['tipo'], 'date') === 0) {
			return 'Jp7_Date';
		} else {
			$retorno = 'string';
		}
		return $retorno;
	}
	
	public static function gerarClasseInterAdmin(InterAdminTipo $tipo, $gerarArquivo = true, $nomeClasse = '') {
		global $config;
		$prefixoClasse = ucfirst($config->name_id);
		
		if (!$nomeClasse) {
			$nomeClasse = $tipo->class;
		}
		
		$phpdoc = '/**' . "\r\n";
		foreach ($tipo->getCampos() as $campo) {
			$phpdoc .= ' * @property ' . self::_getTipoPhpDocCampo($tipo, $campo) . ' $'. $campo['nome_id'] . "\r\n";
		}
		$phpdoc .= ' * @property Jp7_Date date_publish' . "\r\n"; 
		$phpdoc.= ' */';
		
		$conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}_InterAdmin {
	
}
STR;
		if ($gerarArquivo) {
			return self::salvarClasse($nomeClasse, $conteudo);
		} else {
			return $conteudo;
		}
	}
	
	public static function gerarClasseInterAdminTipo(InterAdminTipo $tipo, $gerarArquivo = true, $nomeClasse = '', $nomeClasseInterAdmin = '') {
		global $config;
		$prefixoClasse = ucfirst($config->name_id);
		
		if (!$nomeClasse) {
			$nomeClasse = $tipo->class_tipo;
		}
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
		$phpdoc = '/**' . "\r\n";
		$phpdoc.= ' * @method ' . $nomeClasseInterAdmin . '[] find' . "\r\n";
		$phpdoc.= ' * @method ' . $nomeClasseInterAdmin . ' findFirst' . "\r\n";
		$phpdoc.= ' * @method ' . $nomeClasseInterAdmin . ' findById' . "\r\n";
		$phpdoc.= ' */';
		
		$conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}_InterAdminTipo {
	const ID_TIPO = {$tipo->id_tipo};
}
STR;
		if ($gerarArquivo) {
			return self::salvarClasse($nomeClasse, $conteudo);
		} else {
			return $conteudo;
		}
	}
	/**
	 * Salva o conteudo da classe em arquivo 
	 * return array
	 */
	public static function salvarClasse($nomeClasse, $conteudo) {
		global $c_interadminConfigPath;
		
		$arquivo = dirname($c_interadminConfigPath) . '/classes/' . str_replace('_', '/', $nomeClasse) . '.class.php';
		if (!is_file($arquivo)) {
			@mkdir(dirname($arquivo), 0777, true);
			
			$retorno = file_put_contents($arquivo, $conteudo);
			if ($retorno === false) {
				$avisos['erro'][] = 'Não foi possível gravar arquivo: "' . $arquivo . '". Verifique permissões no diretório.';
			} else {
				$avisos['sucesso'][] = 'Arquivo "' . $arquivo . '" gerado.';
			}
		} else {
			$avisos['erro'][] = 'Arquivo "' . $arquivo . '" já existe.';
		}
		return $avisos;
	}
}