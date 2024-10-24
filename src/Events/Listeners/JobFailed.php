<?php


namespace Go2Flow\Ezport\Events\Listeners;

use Go2Flow\Ezport\Logger\LogOutput;
use Go2Flow\Ezport\Models\Action;
use Illuminate\Queue\Events\JobFailed as JobFailedEvent;

class JobFailed {

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // ...
    }

    /**
     * Handle the event.
     */
    public function handle(JobFailedEvent $event): void
    {
        $payload = $event->job->getRawBody();

        $job = unserialize(json_decode($payload)->data->command);

        $action = Action::whereProjectId($job->project)
            ->where('active', true)
            ->first();

        if ($action) {

            (new LogOutput($job->project, 'isJob'))
                ->properties([$event->exception->getMessage()])
                ->log(
                    ['job' => $event->job->payload()],
                    'high'
                );

            $action->update([
                'active' => false,
                'finished_at' => now(),
            ]);
        }
    }
}
