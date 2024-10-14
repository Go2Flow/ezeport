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
    protected $signature = 'ezpublish:get-helpers' ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes the getters to App/GetHelpers';

    /**
     * Execute the console command.
     */
    public function handle() {

        $path = Str::of(File::dirname(__FILE__))->before('Commands') . 'GetHelpers';

        File::copyDirectory(
            $path,
            base_path('app/GetHelpers')
        );

        foreach (File::directories('app/GetHelpers') as $dir){

            foreach (File::files($dir) as $file) {

                File::put(
                    $file,
                    Str::replace(
                        'namespace Go2Flow\Ezport',
                        'namespace App',
                        File::get($file)
                    )
                );
            }
        }
    }
}
