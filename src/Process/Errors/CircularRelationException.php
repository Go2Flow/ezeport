<?php

namespace Go2Flow\Ezport\Process\Errors;

use Exception;

class CircularRelationException extends Exception
{
    public function __construct($message = 'Circular relationship detected.')
    {
        parent::__construct($message);
    }
}


