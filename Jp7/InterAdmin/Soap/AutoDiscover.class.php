<?php

class Jp7_InterAdmin_Soap_AutoDiscover extends Zend_Soap_AutoDiscover {
	
	public function getUsuario() {
		return $this->_reflection->getUsuario();
	}
	
	public function setUsuario(InterAdmin $usuario) {
		$this->_reflection = new Jp7_InterAdmin_Soap_Reflection($usuario);
	}
	
	public function handle($request = false)
    {
        if (!headers_sent()) {
            header('Content-Type: text/xml');
        }
        $xml = $this->_wsdl->toXml();
		
		$locationReal = self::getServiceLocation();
		echo str_replace('<soap:address location="' . $this->_uri . '"/>', '<soap:address location="' . $locationReal . '"/>', $xml);
    }
	
	public static function getServiceLocation () {
		return 'http://' . $_SERVER['HTTP_HOST'] . preg_replace('/([^?]*)(.*)/', '\1', $_SERVER['REQUEST_URI']);
	}
	
	/**
     * Add a function to the WSDL document.
     *
     * @param $function Zend_Server_Reflection_Function_Abstract function to add
     * @param $wsdl Zend_Soap_Wsdl WSDL document
     * @param $port object wsdl:portType
     * @param $binding object wsdl:binding
     * @return void
     */
    protected function _addFunctionToWsdl($function, $wsdl, $port, $binding)
    {
    	/* FIXME CODIGO DA ZEND: NAO ALTERAR, NAO HAVIA COMO EXTENDER SOMENTE UMA PARTE DA FUNÇÃO */
        $uri = $this->getUri();

        // We only support one prototype: the one with the maximum number of arguments
        $prototype = null;
        $maxNumArgumentsOfPrototype = -1;
        foreach ($function->getPrototypes() as $tmpPrototype) {
            $numParams = count($tmpPrototype->getParameters());
            if ($numParams > $maxNumArgumentsOfPrototype) {
                $maxNumArgumentsOfPrototype = $numParams;
                $prototype = $tmpPrototype;
            }
        }
        if ($prototype === null) {
            require_once "Zend/Soap/AutoDiscover/Exception.php";
            throw new Zend_Soap_AutoDiscover_Exception("No prototypes could be found for the '" . $function->getName() . "' function");
        }

        // Add the input message (parameters)
        $args = array();
        if ($this->_bindingStyle['style'] == 'document') {
            // Document style: wrap all parameters in a sequence element
            $sequence = array();
            foreach ($prototype->getParameters() as $param) {
                $sequenceElement = array(
                    'name' => $param->getName(),
                    'type' => $wsdl->getType($param->getType())
                );
                if ($param->isOptional()) {
                    $sequenceElement['nillable'] = 'true';
					$sequenceElement['minOccurs'] = '0'; /* FIXME APENAS ESSA LINHA É CODIGO DA JP7 */
                }
                $sequence[] = $sequenceElement;
            }
            $element = array(
                'name' => $function->getName(),
                'sequence' => $sequence
            );
            // Add the wrapper element part, which must be named 'parameters'
            $args['parameters'] = array('element' => $wsdl->addElement($element));
        } else {
            // RPC style: add each parameter as a typed part
            foreach ($prototype->getParameters() as $param) {
                $args[$param->getName()] = array('type' => $wsdl->getType($param->getType()));
            }
        }
        $wsdl->addMessage($function->getName() . 'In', $args);

        $isOneWayMessage = false;
        if($prototype->getReturnType() == "void") {
            $isOneWayMessage = true;
        }

        if($isOneWayMessage == false) {
            // Add the output message (return value)
            $args = array();
            if ($this->_bindingStyle['style'] == 'document') {
                // Document style: wrap the return value in a sequence element
                $sequence = array();
                if ($prototype->getReturnType() != "void") {
                    $sequence[] = array(
                        'name' => $function->getName() . 'Result',
                        'type' => $wsdl->getType($prototype->getReturnType())
                    );
                }
                $element = array(
                    'name' => $function->getName() . 'Response',
                    'sequence' => $sequence
                );
                // Add the wrapper element part, which must be named 'parameters'
                $args['parameters'] = array('element' => $wsdl->addElement($element));
            } else if ($prototype->getReturnType() != "void") {
                // RPC style: add the return value as a typed part
                $args['return'] = array('type' => $wsdl->getType($prototype->getReturnType()));
            }
            $wsdl->addMessage($function->getName() . 'Out', $args);
        }

        // Add the portType operation
        if($isOneWayMessage == false) {
            $portOperation = $wsdl->addPortOperation($port, $function->getName(), 'tns:' . $function->getName() . 'In', 'tns:' . $function->getName() . 'Out');
        } else {
            $portOperation = $wsdl->addPortOperation($port, $function->getName(), 'tns:' . $function->getName() . 'In', false);
        }
        $desc = $function->getDescription();
        if (strlen($desc) > 0) {
            $wsdl->addDocumentation($portOperation, $desc);
        }

        // When using the RPC style, make sure the operation style includes a 'namespace' attribute (WS-I Basic Profile 1.1 R2717)
        if ($this->_bindingStyle['style'] == 'rpc' && !isset($this->_operationBodyStyle['namespace'])) {
            $this->_operationBodyStyle['namespace'] = ''.$uri;
        }

        // Add the binding operation
        $operation = $wsdl->addBindingOperation($binding, $function->getName(),  $this->_operationBodyStyle, $this->_operationBodyStyle);
        $wsdl->addSoapOperation($operation, $uri . '#' .$function->getName());

        // Add the function name to the list
        $this->_functions[] = $function->getName();
    }
}