<?php

namespace Go2Flow\Ezport;

use Go2Flow\Ezport\Commands\MakeCustomer;
use Go2Flow\Ezport\Commands\PrepareProject;
use Go2Flow\Ezport\Commands\PublishGetHelpers;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\CleanActivityLog;
use Go2Flow\Ezport\Events\Listeners\JobFailed as JobFailedListener;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;


class EzportServiceProvider extends ServiceProvider
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
        Event::listen(
            JobFailed::class,
            JobFailedListener::class,
        );
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();

        $loader->alias(
            'Content',
            'Go2Flow\Ezport\ContentTypes\Helpers\Content'
        );
        $loader->alias(
            'Find',
            'Go2Flow\Ezport\Finders\Find'
        );

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ]);

        $this->loadRoutesFrom(__DIR__ . './../routes/console.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PrepareProject::class,
                MakeCustomer::class,
                PublishGetHelpers::class
            ]);
        }
    }
}
