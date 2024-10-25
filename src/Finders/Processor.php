<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Helpers\Getters\Processors\StandardShopSix;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadProcessor;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportFinderException;
use Illuminate\Support\Str;

class Processor extends Base{

    protected function getObject(Project $project, string $string) : UploadProcessor
    {
        if ($processor = $this->checkProjectSpecificProcessors($project, $string)) return $processor;

        foreach ($this->mergeConfigWithStandard($project, 'processors', StandardShopSix::class) as $processor) {

            if ($instruction = (new $processor($project))->find($string)) return $instruction;
        }

        throw new EzportFinderException('Processor ' . $string . ' not found');
    }

    private function checkProjectSpecificProcessors(Project $project, $string) : ?UploadProcessor
    {

        return (class_exists($this->instructionPath($project->identifier, 'Processors')))
            ? (new ($this->instructionPath($project->identifier, 'Processors'))($project))->find($string)
            : null;
    }
}
