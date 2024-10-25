<?php

namespace Go2Flow\Ezport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublishGetHelpers extends Command
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
            base_path('app/Ezport/Helpers')
        );

        collect(File::directories('app/Ezport/Helpers'))
            ->each(fn($top) => collect(File::directories($top))
                ->each(fn($folder) => collect(File::files($folder))
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
                )
            )
        );
    }
}
