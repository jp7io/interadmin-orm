<?php

class InterAdminHandler
{
    public function handleException($e)
    {
        $this->report($e);
        $this->render($e);
    }

    protected function report($e)
    {
        $sentryClient = new Raven_Client(getenv('SENTRY_DSN'));
        $sentryHandler = new Raven_ErrorHandler($sentryClient);
        $sentryHandler->handleException($e);
    }

    protected function render($e)
    {
        if ($this->isDebug()) {
            $this->renderWithWhoops($e);
            return;
        }
        $this->renderCustomPage($e);
    }

    protected function isDebug()
    {
        return getenv('APP_DEBUG');
    }

    protected function renderWithWhoops($e)
    {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        $whoops->handleException($e);
    }

    protected function renderCustomPage($e)
    {
        // TODO: Replace this iframe with a custom error page
        echo '<iframe style="height:100%;width:100%;position:absolute;top:0;left:0;" frameborder="0" src="/vendor/jp7internet/_default/index_manutencao.htm"/>';
    }
}
