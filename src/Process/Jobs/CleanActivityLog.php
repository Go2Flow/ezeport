<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Activity;
use Go2Flow\Ezport\Models\Error;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanActivityLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Activity::where('created_at', '<', now()->subMonths(3))->chunk(
            100,
            fn ($activities) => $activities->each->delete()
        );

        Error::where('created_at', '<', now()->subMonths(3))->chunk(
            100,
            fn ($activities) => $activities->each->delete()
        );


        Action::where('created_at', '<', now()->subMonths(3))->chunk(
            100,
            fn ($actions) => $actions->each->delete()
        );

    }
}
