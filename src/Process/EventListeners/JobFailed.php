<?php


namespace Go2Flow\Ezport\Process\EventListeners;

use Go2Flow\Ezport\ContentTypes\ActivityLog;
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

            (new ActivityLog)
                ->action($action)
                ->isJob()
                ->properties([
                    'job' => $event->job->payload(),
                ])->log($event->exception->getMessage());

            $action->update([
                'active' => false,
                'finished_at' => now(),
            ]);
        }
    }
}
