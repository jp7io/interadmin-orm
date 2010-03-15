<?php

class Jp7_Mail extends Zend_Mail{
	/*
	 * Parses an e-mail string and passes it to the given Zend_Mail method.
	 *
	 * @param string Name of the method to be used on Zend_Mail.
	 * @param string E-mail on any of these formats: "name surname <e-mail@anything.com>" or "e-mail@anything.com".
	 * @return Zend_Mail Provides fluent interface.
	 * @throws Exception for empty e-mails or not supported methods.
	 */
	public function parseEmailAndSet($method, $email) {
		$allowedMethods = array('addTo', 'setFrom', 'addBcc', 'addCc', 'setReturnPath');
		
		if (!in_array($method, $allowedMethods)) throw new Zend_Mail_Exception('Invalid method for this function.');
		if (!$email) throw new Zend_Mail_Exception('Cannot parse an empty email.');
	
		$firstPart = trim(strtok($email, '<>')); // Name or e-mail
		$secondPart = trim(strtok('<>')); // E-mail or empty
		
		if ($secondPart) {
			if ($method == 'setReturnPath' || $method == 'addBcc') $this->$method($secondPart); // Email only
			else $this->$method($secondPart, $firstPart); // Email, Name
		} else {
			$this->$method($firstPart); // Email
		}
		
		return $this;
	}
	
}
