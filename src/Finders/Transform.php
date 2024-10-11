<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Getters\Transformers\Standard;
use Go2Flow\Ezport\Instructions\Setters\Types\Transform as SetTransform;
use Go2Flow\Ezport\Models\Project;

class Transform extends Base{

    protected function getObject(Project $project, string $string) : SetTransform
    {

        foreach ($this->mergeConfigWithStandard($project, 'transform', Standard::class) as $transform) {

            if ($instruction = (new $transform($project))->find($string)) return $instruction;
        }

        throw new \Exception("Transform {$string} not found. Check your config file whether the correct upload is set.");
    }
}
