<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Helpers\Getters\Transformers\Basic\Standard;
use Go2Flow\Ezport\Instructions\Setters\Types\Transform as SetTransform;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportFinderException;

class Transform extends Base{

    protected function getObject(Project $project, string $string) : SetTransform
    {

        foreach (Find::config($project)['transformers'] ?? [] as $transform) {

            if ($instruction = (new $transform($project))->find($string)) return $instruction;
        }

        throw new EzportFinderException('Transformer ' . $string . " not found. Have you specified the correct files in the Customer config under 'transformers'?");

    }
}
