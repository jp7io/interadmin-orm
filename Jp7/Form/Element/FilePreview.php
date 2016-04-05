<?php

/**
 * @category   Jp7
 */
class Jp7_Form_Element_FilePreview extends Zend_Form_Element_File
{
    public $helper = 'formFilePreview';
    protected $_database_value;
    protected $_post_value;
    protected $_fileClass = null;

    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('File')
                ->addDecorator(['fileWrapper' => 'HtmlTag'], ['tag' => 'div', 'class' => 'file'])
                ->addDecorator('ViewHelper')
                ->addDecorator('Errors') // append
                ->addDecorator('Description', ['tag' => 'p', 'class' => 'description']) // append
                ->addDecorator('HtmlTag', ['tag' => 'dd']) // wrap
                ->addDecorator('Label', ['tag' => 'dt']); // prepend
        }

        return $this;
    }

    public function isValid($value, $context = null)
    {
        // $value aqui é o valor vindo do $_POST
        // o Zend_Form_Element_File ignora esse valor. Mas nós não vamos ignorá-lo.
        $this->_post_value = $value;

        return parent::isValid($value, $context);
    }

    public function getValue()
    {
        // 	FILES	| POST		| DB		| RESULTADO
        //	'a.jpg'	| 'b.jpg'	| 'c.jpg'	| 'a.jpg'
        //	''		| 'b.jpg'	| 'c.jpg'	| 'b.jpg'
        //	''		| ''		| 'c.jpg'	| ''
        //	null	| null		| 'c.jpg'	| 'c.jpg'
        if ($this->_value === null) {
            if (key_exists($this->getName(), $_FILES)) {
                $parent_return = parent::getValue();
                if ($parent_return !== null) {
                    $this->_value = $this->getFileName();
                } else {
                    $this->_value = $this->_post_value;
                }
            } else {
                $this->_value = $this->_database_value;
            }
        }
        if ($this->_value && !$this->_value instanceof InterAdminFieldFile) {
            // windows fix
            $this->_value = str_replace('\\', '/', $this->_value);
            // necessário por enquanto
            $this->_value = replace_prefix('upload/', '../../upload/', $this->_value);
            $className = $this->getFileClass();
            $this->_value = new $className($this->_value);
            $this->_value->addToArquivosBanco();
        }

        return $this->_value;
    }

    public function setValue($value)
    {
        $this->_database_value = $value;

        return $this;
    }

    /**
     * Returns $_fileClass.
     *
     * @see Jp7_Form_Element_FilePreview::$_fileClass
     */
    public function getFileClass()
    {
        if ($this->_fileClass === null) {
            $tipoClass = InterAdminTipo::getDefaultClass();
            $namespace = @constant($tipoClass.'::DEFAULT_NAMESPACE');
            $this->_fileClass = $namespace.'InterAdminFieldFile';
        }

        return $this->_fileClass;
    }

    /**
     * Sets $_fileClass.
     *
     * @param object $_fileClass
     *
     * @see Jp7_Form_Element_FilePreview::$_fileClass
     */
    public function setFileClass($_fileClass)
    {
        $this->_fileClass = $_fileClass;
    }
}
