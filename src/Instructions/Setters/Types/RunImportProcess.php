<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

class RunImportProcess extends RunProcess {

    public function __construct(string $key) {

        $this->eventType = 'import';
        parent::__construct($key);
    }
}
