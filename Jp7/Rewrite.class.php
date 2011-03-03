<?php

class Jp7_Rewrite {
	/**
	 * Creates a .htaccess file.
	 * @param string $content
	 * @return string
	 */
	public static function createHtaccess($content) {
		global $config;
		
		return <<<STR
RewriteEngine On
RewriteBase /{$config->name_id}/

# Image Autosize
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_FILENAME} ^(.*).(jpg|jpeg|gif|png)$
RewriteCond %{QUERY_STRING} ^(.*)size=(.*)$
RewriteRule ^upload/(.*)$    site/_templates/imageresize.php?url=$1 [QSA,L]

{$content}

# Bootstrap
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/{$config->name_id}/(img|img_dyn|upload|js|swf)([/]?)
RewriteRule ^.*$ index.php [NC,L]
STR;
	}
	
	/**
	 * Returns the RewriteCond's and RewriteRule's for the given InterAdminTipo.
	 * 
	 * @param InterAdminTipo $redirectTipo
	 * @return string
	 */
	public static function getRedirects(InterAdminTipo $redirectTipo) {
		$redirects = "# Redirects\r\n";
		$records = $redirectTipo->getInterAdmins(array(
			'fields' => array('url', 'destino', 'tipo_redirecionamento' => array('nome')),
			'use_published_filters' => true
		));
		foreach ($records as $record) {
			$redirects .= $record->getRedirect();
		}
		return $redirects;
	}
	/**
	 * Returns the RewriteCond's and RewriteRule's for the given InterAdmin.
	 * 
	 * @param InterAdmin $record
	 * @param string     $externalPrefix [optional]
	 * @return string
	 */
	public static function getRedirect(InterAdmin $record, $externalPrefix = '') {
		$redirect = '';
		if (strpos($record->getUrl(), '?') !== false) {
			$redirect .= 'RewriteCond %{QUERY_STRING} ^' . preg_replace('/(.*)\?(.*)$/', '\2', $record->getUrl()) . '$' . "\r\n";
		}
		$redirect .= 'RewriteRule ^' . preg_replace('/(.*)\?(.*)$/', '\1', $record->getUrl());
		if (strpos($record->getUrl(), '.php') === false) {
			$redirect .= '([/]?)';
		}
		if (strpos($record->getDestino(), 'http://') === 0) {
			$redirect .= '$	' . $externalPrefix . $record->getDestino() . ' ' . $record->getTipoRedirecionamento();
		} else {
			$redirect .= '$	' . $record->getDestino() . ' ' . $record->getTipoRedirecionamento();
		}
		return $redirect . "\r\n";
	}
		
}