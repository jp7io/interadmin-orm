<?php

namespace Jp7\Former;

use Illuminate\Container\Container;
use Former\FormerServiceProvider as OriginalServiceProvider;
use Former\Former as OriginalFormer;

class FormerServiceProvider extends OriginalServiceProvider
{
    public function bindFormer(Container $app)
    {
        parent::bindFormer($app);
        
        // Add folder to dispatcher
        $dispatcher = $app->make('former.dispatcher');
        $dispatcher->addRepository('Jp7\\Former\\Fields\\');
        
        // Extend former
        $former = $app->make('former');
        
        $app->singleton('former', function ($app) use ($former) {
            return new FormerExtension($former);
        });
        
        return $app;
    }
}
