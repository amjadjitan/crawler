<?php

namespace App\Models\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

abstract class Job implements ShouldQueue
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use InteractsWithQueue, Queueable, SerializesModels;

    public function failed(Throwable $e)
    {
        Log::critical('QUEUE_JOB_FAILED_AFTER_MAX_TRIES',[
            'errorMessage' => $e->getMessage(),
            'traceSummary' => $e->getTraceAsString(),
            'file'         => $e->getFile(),
            'line'         => $e->getLine(),
          ]
        );
    }
}
