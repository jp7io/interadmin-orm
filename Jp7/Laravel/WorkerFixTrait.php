<?php

namespace Jp7\Laravel;

use Exception;
use PDOException;
use App;
use Log;
use Illuminate\Database\QueryException;

trait WorkerFixTrait
{
  protected function preventWorkerLooping(Exception $e)
  {
    if ($e instanceof PDOException || $e instanceof QueryException) {
        if (App::runningInConsole() && isset($GLOBALS['argv'][1]) && $GLOBALS['argv'][1] === 'queue:work') {
            Log::notice('Preventing queue:work from looping without database');
            sleep(10);
        }
    }
  }
}
