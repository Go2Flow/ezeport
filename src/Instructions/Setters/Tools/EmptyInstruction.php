<?php

namespace Go2Flow\Ezport\Instructions\Setters\Tools;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;

class EmptyInstruction extends BaseInstructions implements InstructionInterface {

    public function get() : array
    {
        return [
        ];
    }
}
