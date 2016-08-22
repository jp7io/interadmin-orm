<?php

namespace Jp7\Laravel;

use Illuminate\Queue\Events\JobProcessed;
use Throwable;
use Queue;
use Log;

trait LogServiceProviderTrait
{
    protected function renameSyslogApp()
    {
        if (config('app.log') === 'syslog') {
            Log::getMonolog()->popHandler();
            Log::useSyslog(config('app.name'));
        }
    }

    protected function listenQueueEvents()
    {
        Queue::after(function (JobProcessed $event) {
            Log::info('[QUEUE] Processed: '.$event->job->getName());
        });
        Queue::looping(function () {
            static $last = 0;
            if ($last > time() - 60) {
                return; // too soon for ping
            }
            Log::info('[QUEUE] Ping');
            if ($url = env('QUEUE_HEARTBEAT_URL')) {
                get_headers($url);
            }
            $last = time();
        });
    }

    // Exceptions thrown or handled and logged with Log::error($e)
    protected function sentoToSentryAllExceptions()
    {
        Log::listen(function ($level, $message, $context) {
            if ($level === 'error' && $message instanceof Throwable) {
                app('sentry')->captureException($message);
            }
        });
    }
}
