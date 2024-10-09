<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Getters\Processors\StandardShopSix;
use Go2Flow\Ezport\Instructions\Setters\UploadProcessor;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Str;

class Processor extends Base{

    protected function getObject(Project $project, string $string) : UploadProcessor
    {
        if ($processor = $this->checkProjectSpecificProcessors($project, $string)) return $processor;

        foreach ($this->mergeConfigWithStandard($project, 'processors', StandardShopSix::class) as $processor) {

            if ($instruction = (new $processor($project))->find($string)) return $instruction;
        }

        throw new \Exception('Processor ' . $string . ' not found');
    }

    private function checkProjectSpecificProcessors(Project $project, $string) : ?UploadProcessor
    {
        $path = 'App\\Customers\\' . Str::ucfirst($project->identifier). '\Instructions\Processors';

        return (class_exists($path))
            ? (new $path($project))->find($string)
            : null;
    }
}
