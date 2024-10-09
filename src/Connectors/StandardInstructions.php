<?php

namespace Go2Flow\Ezport\Connectors;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;

class StandardInstructions extends BaseInstructions implements InstructionInterface {


    public function get() : array
    {
        return [
            'Shop' =>[
                'article' => 'product'
            ],
            'Ftp' => [

            ]
        ];

    }
}
