<?php

namespace Jp7\Laravel;
use Blade, App, Input, Request, Cache;

class Temp {
	public static function handleException($exception, $code) {
		$mensagem = $exception->getMessage() . '<br>' .
			'FILE: ' . $exception->getFile() . ':' . $exception->getLine() . '<br><hr>';
		
		$lines = explode(PHP_EOL, file_get_contents($exception->getFile()));
		$mensagem .= PHP_EOL;
		$line = $exception->getLine();
		$offset = max($line - 10, 0);
				
		$lines[$line - 1] .= ' // <--';
		$lines = ($offset > 0 ? '<?php///' . PHP_EOL : '') . implode(PHP_EOL, array_slice($lines, $offset, 20));
		
		$code = highlight_string($lines, true);
		if ($offset > 0) {
			$code = str_replace('&lt;?php</span><span style="color: #FF8000">///<br />', '', $code);
		}
		$mensagem .= $code;
		
		$mensagem .=
			'<hr /><br>URL: http://' . @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . '<br>' .
			'REFERER: ' . @$_SERVER['HTTP_REFERER'] . '<br>' .
			'IP CLIENTE: ' . @$_SERVER['REMOTE_ADDR'] . '<br>' .
			'IP SERVIDOR: ' . @$_SERVER['SERVER_ADDR'] . '<br>' .
			'USER_AGENT: ' . @$_SERVER['HTTP_USER_AGENT'] . '<br>' .
			'<hr /><br>' . 
			nl2br($exception->getTraceAsString()) .
			'<hr /><br>';
		
		if (!empty($_POST)) {
			$mensagem .= 'POST: <pre>' . print_r($_POST, true) . '</pre><br>';	
		}
		if (!empty($_GET)) {
			$mensagem .= 'GET: <pre>' . print_r($_GET, true) . '</pre><br>';
		}
		if (!empty($_SESSION)) {
			$mensagem .= 'SESSION: <pre>' . print_r($_SESSION, true) . '</pre><br>';
		}
		if (!empty($_COOKIE)) {
			$mensagem .= 'COOKIE: <pre>' . print_r($_COOKIE, true) . '</pre><br>';
		}
		
		$subject = '[ci][Site][Erro] ' . $exception->getMessage();
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		
		mail('debug@jp7.com.br', $subject, $mensagem, $headers);
		
	    return error_controller('error');
	}
	
	public static function extendBlade() {
		Blade::extend(function($view, $compiler) {
			// @include with partials/_partial instead of partials/partial			
		    $pattern = '/(?<!\w)(\s*)@include\(([^,\)]+)/';
		    return preg_replace($pattern, '$1@include(\Jp7\Laravel\Temp::inc($2)', $view);
		});
		
		Blade::extend(function($view, $compiler) {
			 $pattern = $compiler->createMatcher('ia');
			 
			 return preg_replace($pattern, '$1<?php echo interadmin_data$2; ?>', $view);
		});
		
		Blade::setEscapedContentTags('{{', '}}');
    	Blade::setContentTags('{!!', '!!}');
	}
	
	public static function inc($file) {
		$parts = explode('.', $file);
		$parts[] = '_' . array_pop($parts);
		return implode('.', $parts);
	}
	
	public static function extendWhoops() {
		if (Request::ajax() || PHP_SAPI === 'cli') return;
		if (App::bound('whoops')) {
			$whoops = App::make('whoops');
			
			$whoops->pushHandler(function($exception, $exceptionInspector, $runInstance) {
				?>
				<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
				<style>
				.frame.app {
      				background-color: #ffeeee;
      			}
				</style>
				<script>
				setTimeout(function() {
					$('.frame:contains("app")').addClass('app');
				}, 200);
				</script>
				<?php
			});			
		}
	}
	
	public static function clearCache() {
		if (!App::environment('local')) {
			return;
		}
		// Atualiza classmap e routes com CMD+SHIFT+R ou no terminal
		if (Request::server('HTTP_CACHE_CONTROL') === 'no-cache' || PHP_SAPI === 'cli') {
			Cache::forget('Interadmin.routes');
			Cache::forget('Interadmin.classMap');
		}
	}
}