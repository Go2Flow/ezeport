<?php

namespace Go2Flow\Ezport\Commands;

use Go2Flow\Ezport\Constants\Paths;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublishHelpers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ezpublish:helpers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes getters and helper traits to App/Ezport/Helpers';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        File::copyDirectory(
            Str::of(File::dirname(__FILE__))->before('Commands') . 'Helpers',
            base_path(Paths::appHelpers())
        );

        $this->replaceRecursively(File::Directories(Paths::appHelpers()));
    }

    private function replaceRecursively(array $folders) : void {

       collect($folders)->each(
            function ($folder) {

                collect(File::files($folder))
                    ->each(function ($file) {
                        File::put(
                            $file,
                            Str::replace(
                                'namespace Go2Flow\Ezport',
                                'namespace App\Ezport',
                                File::get($file)
                            )
                        );
                    }
                );

                $this->replaceRecursively(File::directories($folder));
            }
        );
    }
}
