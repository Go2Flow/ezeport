<?php

namespace Go2Flow\Ezport\Process\Import;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;

class StandardImports  extends BaseInstructions implements InstructionInterface {


    public function get() : array
    {
        return [];
    }
}
