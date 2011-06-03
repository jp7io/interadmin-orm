<?php

class Jp7_Model_SiteSettingsTipo extends Jp7_Model_TipoAbstract {
	public $isSubTipo = true;
	
	/**
	 * Usado pelo helper _getColorField
	 * @var array
	 */
	private static $_dados = array();
	private static $_theme_editor = false;
	
	public $attributes = array(
		'id_tipo' => 'SiteSettings',
		'nome' => 'Configurações Gerais',
		'campos' => 'tit_1{,}Cabeçalho{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_1{;}varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_title{;}varchar_1{,}Sub-Título{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_subtitle{;}tit_2{,}Template{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_2{;}special_1{,}Jp7_Model_SiteSettingsTipo::getTemplateFields{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}Template{,}{,}{,}template_data{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
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
		
	public static function getTemplateFields($campo, $value, $parte = 'edit') {
		global $c_cliente_url, $s_interadmin_cliente ;
		
		switch ($parte) {
			case 'header':
				return $campo['label'];
				break;
			case 'list':
				// Retorna alguma coisa
				return $value;
				break;
			case 'theme_editor':
				self::$_theme_editor = true;
				// sem break;
			case 'edit':
				// Não sei porque ele coloca &quot;
				self::$_dados = unserialize(str_replace('&quot;', '"', $value));
				
				if (!self::$_theme_editor) {
					$campo['tipo'] = 'css_template';
					$campo['tipo_de_campo'] = 'select';
					$campo['separador'] = 'S';
					$campo['value'] = self::$_dados[$campo['tipo']];
					$campo['opcoes'] = array();
					
					foreach (glob(ROOT_PATH . '/_default/templates/*', GLOB_ONLYDIR) as $templateDir) {
						$relativeDir = str_replace(ROOT_PATH, '', $templateDir);
						$campo['opcoes'][$relativeDir] = basename($relativeDir);
					}
					$field = new InterAdminField($campo);
					echo $field->getHtml();
					
					?>
					<tr>
						<td colspan="4">
							<input type="button" value="Abrir Editor de Cores" 
							onclick="window.open('<?php echo $c_cliente_url; ?>interadmin/site/<?php echo $s_interadmin_cliente; ?>/theme_editor.php')" />
						</td>
					</tr>
					<?php
					
				} else {
					?>
					<input type="hidden" name="css_template[]" value="<?php echo self::$_dados['css_template']; ?>" />
					<?php	
				}
				
				self::_getTit('Cores do Cabeçalho');
				self::_getColorField('header_background', 'Cor de Fundo');
				self::_getColorField('header_title_color', 'Título');
				self::_getColorField('header_subtitle_color', 'Subtítulo', true);
				
				self::_breakTable();
				
				self::_getTit('Cores do Menu');
				self::_getColorField('menu_background', 'Cor de Fundo');
				self::_getColorField('menu_color', 'Texto');
				self::_getColorField('menu_active_background', 'Fundo Ativo');
				self::_getColorField('menu_active_color', 'Texto Ativo', true);
				
				self::_breakTable();
				
				self::_getTit('Cores do Breadcrumb');
				self::_getColorField('breadcrumb_background', 'Cor de Fundo');
				self::_getColorField('breadcrumb_color', 'Texto', true);
				
				self::_breakTable();
				
				self::_getTit('Cores do Conteúdo');
				self::_getColorField('content_background', 'Cor de Fundo');
				self::_getColorField('content_title_color', 'Título');
				self::_getColorField('content_subtitle_color', 'Subtítulo');
				self::_getColorField('content_color', 'Texto');
				self::_getColorField('content_a_color', 'Links', true);
				
				self::_breakTable();
				
				self::_getTit('Cores dos Boxes');
				self::_getColorField('box_background', 'Cor de Fundo');
				self::_getColorField('box_title_color', 'Título');
				self::_getColorField('box_subtitle_color', 'Subtítulo');
				self::_getColorField('box_color', 'Texto');
				self::_getColorField('box_header_background', 'Cabeçalho');
				self::_getColorField('box_header_color', 'Texto do Cabeçalho');
				self::_getColorField('box_footer_background', 'Rodapé');
				self::_getColorField('box_footer_color', 'Texto do Rodapé', true);
				
				self::_breakTable();
				
				self::_getTit('Cores do Rodapé');
				self::_getColorField('footer_background', 'Cor de Fundo');
				self::_getColorField('footer_title_color', 'Título');
				self::_getColorField('footer_color', 'Texto', true);
				break;
		}
	}
	
	protected static function _getColorField($nome_id, $nome, $separador = '') {
		$campo = array(
			'tipo' => 'css_' . $nome_id,
			'tipo_de_campo' => 'varchar',
			'nome' => $nome,
			'xtra' => 'cor',
			'value' => self::$_dados['css_' . $nome_id],
			'default' => '',
			'separador' => $separador && !self::$_theme_editor
		);
		
		$field = new InterAdminField($campo);
		echo $field->getHtml();
	}
	
	protected static function _getTit($nome) {
		$field = new InterAdminField(array(
			'tipo' => 'tit_' . toId($nome),
			'nome' => $nome
		));
		echo $field->getHtml();
	}
	
	protected static function _breakTable() {
		if (self::$_theme_editor) {
			?>
			</table>
			<table class="color-table">
			<?php
		}
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
				$registro->updateAttributes(array(
					'special_1' => serialize($special_1)
				));
				
				self::$_dados = $special_1;
				self::_saveDynamicCss();
			}
		}
	}
	
	protected static function _saveDynamicCss() {
		global $c_interadminConfigPath, $c_remote;
		$filename = $c_interadminConfigPath . 'dynamic.css';
		
		$content = '/*' . "\r\n" . 
			'NÃO EDITE ESTE ARQUIVO - Arquivo é gerado dinamicamente' . "\r\n" .
			'DO NOT EDIT THIS FILE - File is dynamically generated' . "\r\n" .
			'*/' .  "\r\n" .
			self::_getCssBase('header', array('header_background')) .
			self::_getCssBase('header-title', array('header_title_color')) .
			self::_getCssBase('header-subtitle', array('header_subtitle_color')) .
			
			self::_getCssBase('menu', array('menu_background')) .
			self::_getCssBase('menu-a', array('menu_color')) .
			self::_getCssBase('menu-on', array('menu_active_background')) .
			self::_getCssBase('menu-a-on', array('menu_active_color')) .
			
			self::_getCssBase('breadcrumb', array('breadcrumb_background')) .
			self::_getCssBase('breadcrumb-a', array('breadcrumb_color')) .
			
			self::_getCssBase('content', array('content_background')) .
			self::_getCssBase('content-title', array('content_title_color')) .
			self::_getCssBase('content-subtitle', array('content_subtitle_color')) .
			self::_getCssBase('content-text', array('content_color')) .
			self::_getCssBase('content-a', array('content_a_color')) .
			
			self::_getCssBase('box-header', array('box_header_background')) .
			self::_getCssBase('box-header-a', array('box_header_color')) .
			
			self::_getCssBase('box', array('box_background')) .
			self::_getCssBase('box-title', array('box_title_color')) .
			self::_getCssBase('box-subtitle', array('box_subtitle_color')) .
			self::_getCssBase('box-text', array('box_color')) .
			
			self::_getCssBase('box-footer', array('box_footer_background')) .
			self::_getCssBase('box-footer-a', array('box_footer_color')) .
			
			self::_getCssBase('footer', array('footer_background')) .
			self::_getCssBase('footer-title', array('footer_title_color')) .
			self::_getCssBase('footer-text', array('footer_color')) .
			'';
		
		file_put_contents($filename, $content);
		
		if ($c_remote) {
			interadmin_update_remote_files(array('dynamic.css'));		
		}
	}
	
	protected static function _getCssBase($base_id, $properties) {
		$css = '@base(' . $base_id . ') {' . "\r\n";
		foreach ($properties as $property) {
			if (endsWith('_background', $property)) {
				$cssProperty = 'background';
			} elseif (endsWith('_color', $property)) {
				$cssProperty = 'color';
			} else {
				continue;
			}
			if ($value = self::$_dados['css_' . $property]) {
				$css .= "\t" . $cssProperty . ': ' . $value . ';' . "\r\n";
			}
		}
		$css .= '}' . "\r\n";
		return $css;
	}
	
}