<?php

namespace Go2Flow\Ezport\Commands\PrepareProject;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\Connector;
use Go2Flow\Ezport\Instructions\Setters\Types\Project as SetProject;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Collection;

class CreateProject
{
    private Project $project;
    private Collection $instructions;

    public function __construct(string|Project $identifier)
    {
        if ($identifier instanceof Project) {
            $this->project = $identifier;
        } else {
            $this->project = new Project;
            $this->project->identifier = $identifier;
        }


        $this->instructions =  Find::instruction($this->project, 'Project')->collect();
    }

    public function project(): Project
    {
        $this->project = $this->createProject();

        return $this->project;
    }

    public function connectors(): void
    {
        $this->createConnectors();
    }

    private function createConnectors(): void
    {
        foreach ($this->instructions->filter(fn ($item) => $item instanceof Connector) as $instruction) {

            $this->project->connectors()
                ->firstOrCreate(
                    ['type' => $instruction->getKey()],
                    collect(['password', 'username', 'host', 'environment'])
                        ->mapWithKeys(
                            fn ($key) => [$key => $instruction->get($key)]
                        )->toArray()
                );
        }
    }

    private function createProject(): Project
    {
        $setProject = $this->instructions->filter(
            fn ($item) => $item instanceof SetProject
        )->first();

        return $this->project->updateOrCreate(
            ['identifier' => $setProject->getKey()],
            [
                'cache' => $setProject->get('cache'),
                'settings' => $setProject->get('settings'),
                'name' => $setProject->get('name'),
            ]
        );
    }
}
