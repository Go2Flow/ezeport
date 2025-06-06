<?php

namespace Go2Flow\Ezport;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\CleanActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class EzportScheduleServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->callAfterResolving(
            Schedule::class,
            function (Schedule $schedule) {
                $schedule->call(
                    function () {
                        foreach (Project::get() as $project) {
                            Find::instruction($project, 'Schedule')
                                ?->collect()
                                ->each(
                                    function ($instruction, $loop) {

                                        if (!$instruction->isTime()) return;

                                        // if ($instruction->getEnvironment() !== config('app.env')) return;

                                        if ($instruction->get('unique')) {
                                            $lock = Cache::lock(
                                                $instruction->setKey($loop)->get('key'),
                                                3600
                                            );

                                            if (!$lock->get()) return;
                                        }

                                        $instruction->jobs($lock ?? []);
                                    }
                                );
                        }
                    }
                )->everyMinute();

                $schedule->command('horizon:snapshot')->everyFiveMinutes();
                $schedule->command('queue:prune-batches')->daily();

                $schedule->command('backup:run --only-db')->daily()->at('05:50');
                $schedule->command('backup:clean')->daily()->at('06:30');

                $schedule->call(
                    function () {
                        Action::where('active', true)
                            ->where('created_at', '<', now()
                                ->subHours(3))
                            ->get()
                            ->each->update(['active' => false]);
                    }
                )->everyThirtyMinutes();

                $schedule->job(
                    new CleanActivityLog()
                )->daily();

            });
    }
}
