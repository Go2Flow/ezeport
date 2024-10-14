<?php

namespace Go2Flow\Ezport\Commands;

use Go2Flow\Ezport\Commands\PrepareProject\CreateProject;
use Go2Flow\Ezport\Commands\PrepareProject\CreateProjectCache;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Console\Command;

class PrepareProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ezproject:prepare {project}' ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $project = Project::firstWhere('Identifier', $this->argument('project'))) {

            $project = (new CreateProject($this->argument('project')))->run();

            $this->info('project created');
        }


        $this->info('preparing cache');
        (new CreateProjectCache($project))->prepareCache();
        $this->info('Project prepared');
    }
}
