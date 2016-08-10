<?php

abstract class Jp7_Controller_HtaccessController extends Jp7_Controller_Action
{
    public function indexAction()
    {
        Zend_Layout::getMvcInstance()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $redirectTipo = $this->getRedirectTipo();
        $fileContent = $redirectTipo->getHtaccessContent();

        $ok = false;
        if (strlen($fileContent) > 10) {
            $file = dirname(APPLICATION_PATH).'/.htaccess';
            $ok = file_put_contents($file, $fileContent);
        }

        if ($ok) {
            $config = Zend_Registry::get('config');

            echo 'Arquivo gerado e gravado.<br />';
            echo '<a href="' . $config->url . '" target="_blank">' . $config->url . '</a>';
        }

    }

    abstract protected function getRedirectTipo();
}
