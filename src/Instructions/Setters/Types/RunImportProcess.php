<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Contracts\Queue\ShouldQueue;

class RunImportProcess extends RunProcess implements JobInterface {

    public function __construct(string $key) {

        $this->eventType = 'import';
        parent::__construct($key);
    }
}
