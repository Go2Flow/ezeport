<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Import\StandardImports;
use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Models\Project;

class Import extends Base{

    protected function getObject(Project $project, string $string) : ImportInstructionInterface
    {

        foreach ($this->mergeConfigWithStandard($project, 'imports', StandardImports::class) as $import) {

            if ($instruction = (new $import($project))->find($string)) return $instruction;
        }

        throw new \Exception("Import {$string} not found. Check your config file whether the correct import is set.");
    }

}
