<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportFinderException;
use Go2Flow\Ezport\Process\Import\StandardImports;

class Import extends Base{

    protected function getObject(Project $project, string $string) : ImportInstructionInterface
    {

        foreach (Find::config($project)['imports'] as $import) {

            if ($instruction = (new $import($project))->find($string)) return $instruction;
        }

        throw new EzportFinderException("Import {$string} not found. Check your config file whether the correct import is set.");
    }

}
