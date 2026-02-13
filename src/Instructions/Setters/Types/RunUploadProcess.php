<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

class RunUploadProcess extends RunProcess {

    public function __construct(string $key) {

        $this->eventType = 'upload';
        parent::__construct($key);
    }
}
