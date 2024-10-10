<?php

namespace Go2Flow\Ezport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublishGetters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:getters' ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes the getters to App/Getters';

    /**
     * Execute the console command.
     */
    public function handle() {

        $path = Str::of(File::dirname(__FILE__))->before('Commands') . 'Getters';

        File::copyDirectory($path, base_path('app/Getters'));

        foreach (File::directories('app/Getters') as $dir){

            foreach (File::files($dir) as $file) {

                File::put(
                    $file,
                    Str::of(File::get($file))->replace('namespace Go2Flow\Ezport', 'namespace App')
                );
            }
        }
    }
}
