<?php
require_once(dirname(__FILE__) . '/SforceEnterpriseClient.php');

/**
 * Client to connect to Salesforce Web Services.
 */
class Salesforce_Client extends SforceEnterpriseClient {
	const MAX_RETURNED_ROWS = 2000;
	
	/**
	 * Executes a query using Salesforce WebServices client and returns the records.
	 *
	 * @param array|string 	$options	Query String or Array of options with keys: fields, from, where, group, order, limit.
	 * @return QueryResult
	 */
	public function query($options) {
		if (is_array($options)) {
			// Prepares SOQL parameters
			$query = "SELECT " . implode(',', (array) $options['fields']) .
				" FROM " . $options['from'];
			if ($options['where']) {
				$query .= " WHERE " . implode(' AND ', (array) $options['where']);
			}
			if ($options['group']) {
				$query .= " GROUP BY " . $options['group'];
			}
			if ($options['order']) {
				$query .= " ORDER BY " . $options['order'];
			}
			if ($options['debug']) {
				krumo($query);
			}
			if ($options['limit']) {
				// Begin: Faking OFFSET, LIMIT, Salesforce doesn't support OFFSET inside the LIMIT clause
				$limitArr = explode(',', $options['limit']);
				if (count($limitArr) > 1) {
					list($offset, $limit) = array_map('intval', $limitArr);
				} else {
					list($offset, $limit) = array(0, intval($options['limit']));
				}
				$options['limit'] = $offset + $limit;
				$query .= " LIMIT " . $options['limit'];
				
				$result = parent::query($query);
				while ($offset + $limit > self::MAX_RETURNED_ROWS) {
					$offset -= self::MAX_RETURNED_ROWS;
					$result = $this->queryMore($result->queryLocator);
				}
				$result->records = array_slice($result->records, $offset, $limit);
				// End: Faking OFFSET, LIMIT
				return $result;
			}
		} else {
			 $query = $options;
		}
		return parent::query($query);
	}
	
	/**
	 * Creates a connection to the SoapClient. I had to override because of ISO-8859-1.
	 * 
	 * @param string 	$wsdl
	 * @param string 	$proxy [optional]
	 * @param array 	$options [optional] Options to be passed to SoapClient::__construct().
	 * @return SoapClient
	 */
	public function createConnection($wsdl, $proxy = null, $options = array()) {
		$soapClientArray = $options + array (
			'encoding' => 'ISO-8859-1',
			'trace' => 1,
			'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
		);
		
		// Begin: Original CODE
		if ($proxy != null) {
			$proxySettings = array();
			$proxySettings['proxy_host'] = $proxy->host;
			$proxySettings['proxy_port'] = $proxy->port; // Use an integer, not a string
			$proxySettings['proxy_login'] = $proxy->login; 
			$proxySettings['proxy_password'] = $proxy->password;
			
			$soapClientArray = array_merge($soapClientArray, $proxySettings);
		}
		// End: Original CODE
		
		if ($options['connection_timeout']) {
			@ini_set('default_socket_timeout', $options['connection_timeout']);
		}
		try {
			$this->sforce = @new SoapClient($wsdl, $soapClientArray);
		} catch (Exception $e) {
			if ($options['connection_timeout']) {
				@ini_restore('default_socket_timeout');
			}
			throw $e;
		}
		if ($options['connection_timeout']) {
			@ini_restore('default_socket_timeout');
		}
		return $this->sforce;
	}
}