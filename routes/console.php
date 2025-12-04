<?php

use Go2Flow\Ezport\Commands\ProjectSpecificCommands;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

if (Schema::hasTable('projects')) {
    foreach (Project::get() as $shop) {

        $command = new ProjectSpecificCommands($shop);

        Artisan::command(
            $shop->identifier . ':' . 'add-to-upload {type?}',
            function (?string $type = null) use ($command) {
                $this->info($command->addToUpload($type));
            }
        )->purpose('Add content type to next upload for ' . $shop->identifier);

        Artisan::command(
            $shop->identifier . ':' . 'remove-from-upload {type?}',
            function (?string $type = null) use ($command) {
                $this->info($command->removeFromUpload($type));
            }
        )->purpose('Remove content type from next upload for ' . $shop->identifier);


        Artisan::command(
            $shop->identifier . ':' . 'run-schedule {type?}',
            function (?string $type = null) use ($command) {
                $this->info($command->runSchedule($type));
            }
        )->purpose('Run schedule a scheduled set of jobs for ' . $shop->identifier);

        foreach (['import' => 'runImport', 'upload' => 'runUpload', 'clean' => 'runClean', 'transform' => 'runTransform'] as $job => $method) {

            Artisan::command(
                $shop->identifier . ":" . $job . ' {type?}',
                function (?string $type = null) use ($command, $job, $method) {
                    $command->runJobs($method, $type, $job);
                    $this->info(Str::ucfirst($type) . ' console.php' . $job . ' jobs added to horizon');
                }
            )->purpose('Run ' . $job . ' for ' . $shop->identifier);
        }

        Artisan::command(
            $shop->identifier . ":delete",
            function () use ($command) {
                $this->info($command->delete());
            }
        )->purpose('Run delete on shopware for ' . $shop->identifier);

        Artisan::command(
            $shop->identifier . ":connector",
            function () use ($command) {
                $this->info($command->createConnector());
            }
        )->purpose('Create connector for ' . $shop->identifier);

        Artisan::command(
            $shop->identifier . ":prepare",
            function () use ($command) {
                $command->prepareShop();
                $this->info('Shop prepared');

            }
        )->purpose('Prepare shop for ' . $shop->identifier);
    }
}
