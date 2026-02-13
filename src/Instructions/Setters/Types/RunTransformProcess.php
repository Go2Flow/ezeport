<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

class RunTransformProcess extends RunProcess {

    public function __construct(string $key) {

        $this->eventType = 'transform';
        parent::__construct($key);
    }
}
