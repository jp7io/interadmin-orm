<?php

class Jp7_Model_SiteSettingsTipo extends Jp7_Model_TipoAbstract {
	public $isSubTipo = true;
	
	public $attributes = array(
		'id_tipo' => 'SiteSettings',
		'nome' => 'Configurações Gerais',
		'campos' => '    tit_1{,}Cabeçalho{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_1{;}varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_title{;}varchar_1{,}Sub-Título{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_subtitle{;}tit_2{,}Template{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_2{;}special_1{,}Jp7_Model_SiteSettingsTipo::getTemplateFields{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}Template{,}{,}{,}template_path{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'unico' => 'S',
		'disparo' => 'Jp7_Model_SiteSettingsTipo::saveTemplateFields'
	);
		
	public static function getTemplateFields($campo, $valor, $parte = 'edit') {
		switch ($parte) {
			case 'header':
				return $campo['label'];
				break;
			case 'list':
				// Retorna alguma coisa
				return $value;
				break;
			case 'edit':
				$campo['tipo'] = 'css_template';
				$campo['tipo_de_campo'] = 'select';
				$campo['separador'] = 'S';
				$campo['opcoes'] = array();
				
				foreach (glob(ROOT_PATH . '/_default/templates/*', GLOB_ONLYDIR) as $templateDir) {
					$relativeDir = str_replace(ROOT_PATH, '', $templateDir);
					$campo['opcoes'][$relativeDir] = basename($relativeDir);
				}
				$field = new InterAdminField($campo);
				echo $field->getHtml();
				
				self::_getColorField('header_background', 'Cabeçalho Fundo', '#ff0000');
				self::_getColorField('header_title_color', 'Cabeçalho Título', '#ff0000');
				self::_getColorField('header_subtitle_color', 'Cabeçalho Subtítulo', '#ff0000', true);
				
				self::_getColorField('menu_background', 'Menu Fundo', '#ff0000');
				self::_getColorField('menu_color', 'Menu Cor', '#ff0000');
				self::_getColorField('menu_active_background', 'Menu Ativo Fundo', '#ff0000');
				self::_getColorField('menu_active_color', 'Menu Ativo Cor', '#ff0000', true);
				
				self::_getColorField('content_background', 'Conteúdo Fundo', '#ff0000');
				self::_getColorField('content_title_color', 'Conteúdo Título', '#ff0000');
				self::_getColorField('content_subtitle_background', 'Conteúdo Subtítulo', '#ff0000');
				self::_getColorField('content_color', 'Conteúdo Texto', '#ff0000', true);
				break;
		}
	}
	
	protected static function _getColorField($nome_id, $nome, $value, $separador = '', $options = array()) {
		$campo = $options + array(
			'tipo' => 'css_' . $nome_id,
			'tipo_de_campo' => 'varchar',
			'nome' => $nome,
			'xtra' => 'cor',
			'value' => $value,
			'separador' => $separador
		);
		
		$field = new InterAdminField($campo);
		echo $field->getHtml();
	}
	
	public static function saveTemplateFields() {
		global $id, $interadmin_id;
		if (!$id) {
			$id = $interadmin_id;
		}
		if ($id) {
			$tipo = InterAdminTipo::getInstance($_POST['id_tipo']);
			if ($registro = $tipo->getInterAdminById($id)) {
				$special_1 = array();
				foreach ($_POST as $key => $values) {
					if (startsWith('css_', $key) && !endsWith('_xtra', $key)) {
						$special_1[$key] = $values[0];
					}
				}
			}
		}
	}
}