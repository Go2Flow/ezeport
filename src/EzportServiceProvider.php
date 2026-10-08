<?php

namespace Go2Flow\Ezport;

use Go2Flow\Ezport\Commands\MakeCustomer;
use Go2Flow\Ezport\Commands\PrepareProject;
use Go2Flow\Ezport\Commands\PublishHelpers;
use Go2Flow\Ezport\Events\Listeners\JobFailed as JobFailedListener;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        $loader = AliasLoader::getInstance();

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

        $this->loadRoutesFrom(__DIR__.'./../routes/console.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PrepareProject::class,
                MakeCustomer::class,
                PublishHelpers::class,
            ]);

            require __DIR__.'/../routes/console.php';
        }
    }
}
