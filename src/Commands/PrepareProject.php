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

        if ($project = Project::firstWhere('Identifier', $this->argument('project'))) {
            if ($this->confirm('The project ' . $project->name . ' already exists. Do you want to overwrite it?')) {
                $project = (new CreateProject($project))->project();
                $this->info('project created from Project Instructions');
            }
        }

        $overwrite = true;
        if ($project->connectors()->count() > 0) {
            $overwrite = $this->confirm('This project ' . $project->name . ' already has connectors. Do you want to overwrite them?');
        }

        if ($overwrite) {
            (new CreateProject($project))->connectors();
            $this->info('connectors created from Project Instructions');
        }

        $this->info('preparing cache');
        (new CreateProjectCache($project))->prepareCache();
        $this->info('Process complete');
    }
}
