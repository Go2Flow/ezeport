<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Tools\EmptyInstruction;
use Go2Flow\Ezport\Models\Project;
use Exception;
use Go2Flow\Ezport\Process\Errors\EzportFinderException;

class Instruction extends Base {

    protected function getObject(Project $project, string $string) : object
    {
        $instance = $this->getCorrectOrEmptyInstructionObject($project, $string);

        if (! $instance instanceof InstructionInterface) throw new EzportFinderException("The found file is not of the correct type", 1);

        return $instance;
    }

    private function getCorrectOrEmptyInstructionObject(Project $project, string $string) : object
    {
        return (class_exists('App\Customers\\' . ucfirst($project->identifier) . '\Instructions' . '\\' . ucfirst($string)))
            ? new ('App\Customers\\' . ucfirst($project->identifier) . '\Instructions' . '\\' . ucfirst($string))($project)
            : new EmptyInstruction($project);
    }
}
