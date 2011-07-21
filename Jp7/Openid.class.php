<?php

/**
 * It's basically Zend_OpenId_Consumer with the changes proposed on http://framework.zend.com/issues/browse/ZF-6905
 * 
 */
class Jp7_Openid extends Zend_OpenId_Consumer {
	/**
     * Performs discovery of identity and finds OpenID URL, OpenID server URL
     * and OpenID protocol version. Returns true on succees and false on
     * failure.
     *
     * @param string &$id OpenID identity URL
     * @param string &$server OpenID server URL
     * @param float &$version OpenID protocol version
     * @return bool
     * @todo OpenID 2.0 (7.3) XRI and Yadis discovery
     */
    protected function _discovery(&$id, &$server, &$version)
    {
        $realId = $id;
        if ($this->_storage->getDiscoveryInfo(
                $id,
                $realId,
                $server,
                $version,
                $expire)) {
            $id = $realId;
            return true;
        }

        /* TODO: OpenID 2.0 (7.3) XRI and Yadis discovery */

        /* HTML-based discovery */
        $response = $this->_httpRequest($id, 'GET', array(), $status);
        if ($status != 200 || !is_string($response)) {
            return false;
        }
		// FIX PARA OpenId 2.0 - Proximas 3 linhas
		if (preg_match('/<URI>([^<]+)<\/URI>/i', $response, $r)) {
			$version = 2.0;
			$server = $r[1];
		// NAO ALTERAR CODIGO ABAIXO, ESTA IGUAL O DA ZEND
      	} else if (preg_match(
                '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.provider[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i',
                $response,
                $r)) {
            $version = 2.0;
            $server = $r[3];
        } else if (preg_match(
                '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.provider[ \t]*[^"\']*\\3[^>]*\/?>/i',
                $response,
                $r)) {
            $version = 2.0;
            $server = $r[2];
        } else if (preg_match(
                '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.server[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i',
                $response,
                $r)) {
            $version = 1.1;
            $server = $r[3];
        } else if (preg_match(
                '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.server[ \t]*[^"\']*\\3[^>]*\/?>/i',
                $response,
                $r)) {
            $version = 1.1;
            $server = $r[2];
        } else {
            return false;
        }
        if ($version >= 2.0) {
            if (preg_match(
                    '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.local_id[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i',
                    $response,
                    $r)) {
                $realId = $r[3];
            } else if (preg_match(
                    '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.local_id[ \t]*[^"\']*\\3[^>]*\/?>/i',
                    $response,
                    $r)) {
                $realId = $r[2];
            }
        } else {
            if (preg_match(
                    '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.delegate[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i',
                    $response,
                    $r)) {
                $realId = $r[3];
            } else if (preg_match(
                    '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.delegate[ \t]*[^"\']*\\3[^>]*\/?>/i',
                    $response,
                    $r)) {
                $realId = $r[2];
            }
        }

        $expire = time() + 60 * 60;
        $this->_storage->addDiscoveryInfo($id, $realId, $server, $version, $expire);
        $id = $realId;
        return true;
    }
	
	/**
     * Performs check of OpenID identity.
     *
     * This is the first step of OpenID authentication process.
     * On success the function does not return (it does HTTP redirection to
     * server and exits). On failure it returns false.
     *
     * @param bool $immediate enables or disables interaction with user
     * @param string $id OpenID identity
     * @param string $returnTo HTTP URL to redirect response from server to
     * @param string $root HTTP URL to identify consumer on server
     * @param mixed $extensions extension object or array of extensions objects
     * @param Zend_Controller_Response_Abstract $response an optional response
     *  object to perform HTTP or HTML form redirection
     * @return bool
     */
    protected function _checkId($immediate, $id, $returnTo=null, $root=null,
        $extensions=null, Zend_Controller_Response_Abstract $response = null)
    {
        $this->_setError('');

        if (!Zend_OpenId::normalize($id)) {
            $this->_setError("Normalisation failed");
            return false;
        }

        if (!$this->_discovery($id, $server, $version)) {
            $this->_setError("Discovery failed: " . $this->getError());
            return false;
        }
        if (!$this->_associate($server, $version)) {
            $this->_setError("Association failed: " . $this->getError());
            return false;
        }
        if (!$this->_getAssociation(
                $server,
                $handle,
                $macFunc,
                $secret,
                $expires)) {
            /* Use dumb mode */
            unset($handle);
            unset($macFunc);
            unset($secret);
            unset($expires);
        }

        $params = array();
        if ($version >= 2.0) {
            $params['openid.ns'] = Zend_OpenId::NS_2_0;
        }

        $params['openid.mode'] = $immediate ?
            'checkid_immediate' : 'checkid_setup';

        $params['openid.identity'] = $id;

        $params['openid.claimed_id'] = $claimedId;

        if ($version <= 2.0) {
            if ($this->_session !== null) {
                $this->_session->identity = $id;
                $this->_session->claimed_id = $claimedId;
				if (strpos($server, 'https://www.google.com') === 0) {
				  	$this->_session->identity = 'http://specs.openid.net/auth/2.0/identifier_select';
				 	$this->_session->claimed_id = 'http://specs.openid.net/auth/2.0/identifier_select';
				}
            } else if (defined('SID')) {
                $_SESSION["zend_openid"] = array(
                    "identity" => $id,
                    "claimed_id" => $claimedId);
				if (strpos($server, 'https://www.google.com') === 0) {
					$_SESSION['zend_openid']['identity'] = 'http://specs.openid.net/auth/2.0/identifier_select';
					$_SESSION['zend_openid']['claimed_id'] = 'http://specs.openid.net/auth/2.0/identifier_select';
				}
            } else {
                require_once "Zend/Session/Namespace.php";
                $this->_session = new Zend_Session_Namespace("zend_openid");
                $this->_session->identity = $id;
                $this->_session->claimed_id = $claimedId;
            }
			if (strpos($server, 'https://www.google.com') === 0) {
				$params['openid.identity'] = 'http://specs.openid.net/auth/2.0/identifier_select';
				$params['openid.claimed_id'] = 'http://specs.openid.net/auth/2.0/identifier_select';
			}
        }

        if (isset($handle)) {
            $params['openid.assoc_handle'] = $handle;
        }

        $params['openid.return_to'] = Zend_OpenId::absoluteUrl($returnTo);

        if (empty($root)) {
            $root = Zend_OpenId::selfUrl();
            if ($root[strlen($root)-1] != '/') {
                $root = dirname($root);
            }
        }
        if ($version >= 2.0) {
            $params['openid.realm'] = $root;
        } else {
            $params['openid.trust_root'] = $root;
        }

        if (!Zend_OpenId_Extension::forAll($extensions, 'prepareRequest', $params)) {
            $this->_setError("Extension::prepareRequest failure");
            return false;
        }
		
        Zend_OpenId::redirect($server, $params, $response);
        return true;
    }
	/**
     * Verifies authentication response from OpenID server.
     *
     * This is the second step of OpenID authentication process.
     * The function returns true on successful authentication and false on
     * failure.
     *
     * @param array $params HTTP query data from OpenID server
     * @param string &$identity this argument is set to end-user's claimed
     *  identifier or OpenID provider local identifier.
     * @param mixed $extensions extension object or array of extensions objects
     * @return bool
     */
    public function verify($params, &$identity = "", $extensions = null)
    {
        $this->_setError('');

        $version = 1.1;
        if (isset($params['openid_ns']) &&
            $params['openid_ns'] == Zend_OpenId::NS_2_0) {
            $version = 2.0;
        }

        if (isset($params["openid_claimed_id"])) {
            $identity = $params["openid_claimed_id"];
        } else if (isset($params["openid_identity"])){
            $identity = $params["openid_identity"];
        } else {
            $identity = "";
        }

        if ($version < 2.0 && !isset($params["openid_claimed_id"])) {
            if ($this->_session !== null) {
                if ($this->_session->identity === $identity) {
                    $identity = $this->_session->claimed_id;
                }
            } else if (defined('SID')) {
                if (isset($_SESSION["zend_openid"]["identity"]) &&
                    isset($_SESSION["zend_openid"]["claimed_id"]) &&
                    $_SESSION["zend_openid"]["identity"] === $identity) {
                    $identity = $_SESSION["zend_openid"]["claimed_id"];
                }
            } else {
                require_once "Zend/Session/Namespace.php";
                $this->_session = new Zend_Session_Namespace("zend_openid");
                if ($this->_session->identity === $identity) {
                    $identity = $this->_session->claimed_id;
                }
            }
        }

        if (empty($params['openid_mode'])) {
            $this->_setError("Missing openid.mode");
            return false;
        }
        if (empty($params['openid_return_to'])) {
            $this->_setError("Missing openid.return_to");
            return false;
        }
        if (empty($params['openid_signed'])) {
            $this->_setError("Missing openid.signed");
            return false;
        }
        if (empty($params['openid_sig'])) {
            $this->_setError("Missing openid.sig");
            return false;
        }
        if ($params['openid_mode'] != 'id_res') {
            $this->_setError("Wrong openid.mode '".$params['openid_mode']."' != 'id_res'");
            return false;
        }
        if (empty($params['openid_assoc_handle'])) {
            $this->_setError("Missing openid.assoc_handle");
            return false;
        }
        if ($params['openid_return_to'] != Zend_OpenId::selfUrl()) {
            /* Ignore query part in openid.return_to */
            $pos = strpos($params['openid_return_to'], '?');
            if ($pos === false ||
                SUBSTR($params['openid_return_to'], 0 , $pos) != Zend_OpenId::selfUrl()) {

                $this->_setError("Wrong openid.return_to '".
                    $params['openid_return_to']."' != '" . Zend_OpenId::selfUrl() ."'");
                return false;
            }
        }

        if ($version >= 2.0) {
            if (empty($params['openid_response_nonce'])) {
                $this->_setError("Missing openid.response_nonce");
                return false;
            }
            if (empty($params['openid_op_endpoint'])) {
                $this->_setError("Missing openid.op_endpoint");
                return false;
            /* OpenID 2.0 (11.3) Checking the Nonce */
            } else if (!$this->_storage->isUniqueNonce($params['openid_op_endpoint'], $params['openid_response_nonce'])) {
                $this->_setError("Duplicate openid.response_nonce");
                return false;
            }
        }


        if (!empty($params['openid_invalidate_handle'])) {
            if ($this->_storage->getAssociationByHandle(
                $params['openid_invalidate_handle'],
                $url,
                $macFunc,
                $secret,
                $expires)) {
                $this->_storage->delAssociation($url);
            }
        }

        if ($this->_storage->getAssociationByHandle(
                $params['openid_assoc_handle'],
                $url,
                $macFunc,
                $secret,
                $expires)) {
            $signed = explode(',', $params['openid_signed']);
            $data = '';
            foreach ($signed as $key) {
                $data .= $key . ':' . $params['openid_' . strtr($key,'.','_')] . "\n";
            }
            if (base64_decode($params['openid_sig']) ==
                Zend_OpenId::hashHmac($macFunc, $data, $secret)) {
                if (!Zend_OpenId_Extension::forAll($extensions, 'parseResponse', $params)) {
                    $this->_setError("Extension::parseResponse failure");
                    return false;
                }
                /* OpenID 2.0 (11.2) Verifying Discovered Information */
                if (isset($params['openid_claimed_id'])) {
                    $id = $params['openid_claimed_id'];
                    if (!Zend_OpenId::normalize($id)) {
                        $this->_setError("Normalization failed");
                        return false;
                    /*
					} else if (!$this->_discovery($id, $discovered_server, $discovered_version)) {
                        $this->_setError("Discovery failed: " . $this->getError());
                        return false;
                    
                    } else if ((!empty($params['openid_identity']) &&
                                $params["openid_identity"] != $id) ||
                               (!empty($params['openid_op_endpoint']) &&
                                $params['openid_op_endpoint'] != $discovered_server) ||
                               $discovered_version != $version) {
                        $this->_setError("Discovery information verification failed");
                        return false;
					*/ 
                    }     
                }
                return true;
            }
            $this->_storage->delAssociation($url);
            $this->_setError("Signature check failed");
            return false;
        }
        else
        {
            /* Use dumb mode */
            if (isset($params['openid_claimed_id'])) {
                $id = $params['openid_claimed_id'];
            } else if (isset($params['openid_identity'])) {
                $id = $params['openid_identity'];
            } else {
                $this->_setError("Missing openid.claimed_id and openid.identity");
                return false;
            }

            if (!Zend_OpenId::normalize($id)) {
                $this->_setError("Normalization failed");
                return false;
            } else if (!$this->_discovery($id, $server, $discovered_version)) {
                $this->_setError("Discovery failed: " . $this->getError());
                return false;
            }

            /* OpenID 2.0 (11.2) Verifying Discovered Information */
            if ((isset($params['openid_identity']) &&
                 $params["openid_identity"] != $id) ||
                (isset($params['openid_op_endpoint']) &&
                 $params['openid_op_endpoint'] != $server) ||
                $discovered_version != $version) {
                $this->_setError("Discovery information verification failed");
                return false;
            }

            $params2 = array();
            foreach ($params as $key => $val) {
                if (strpos($key, 'openid_ns_') === 0) {
                    $key = 'openid.ns.' . substr($key, strlen('openid_ns_'));
                } else if (strpos($key, 'openid_sreg_') === 0) {
                    $key = 'openid.sreg.' . substr($key, strlen('openid_sreg_'));
                } else if (strpos($key, 'openid_') === 0) {
                    $key = 'openid.' . substr($key, strlen('openid_'));
                }
                $params2[$key] = $val;
            }
            $params2['openid.mode'] = 'check_authentication';
            $ret = $this->_httpRequest($server, 'POST', $params2, $status);
            if ($status != 200) {
                $this->_setError("'Dumb' signature verification HTTP request failed");
                return false;
            }
            $r = array();
            if (is_string($ret)) {
                foreach(explode("\n", $ret) as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $x = explode(':', $line, 2);
                        if (is_array($x) && count($x) == 2) {
                            list($key, $value) = $x;
                            $r[trim($key)] = trim($value);
                        }
                    }
                }
            }
            $ret = $r;
            if (!empty($ret['invalidate_handle'])) {
                if ($this->_storage->getAssociationByHandle(
                    $ret['invalidate_handle'],
                    $url,
                    $macFunc,
                    $secret,
                    $expires)) {
                    $this->_storage->delAssociation($url);
                }
            }
            if (isset($ret['is_valid']) && $ret['is_valid'] == 'true') {
                if (!Zend_OpenId_Extension::forAll($extensions, 'parseResponse', $params)) {
                    $this->_setError("Extension::parseResponse failure");
                    return false;
                }
                return true;
            }
            $this->_setError("'Dumb' signature verification failed");
            return false;
        }
    }
}