<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;

class RunProcess extends Basic implements JobInterface {

    protected string $eventType;

    public function __construct(string $key) {

        parent::__construct($key);

        $this->job = Set::Job()
            ->class(RunProcess::class)
            ->config([
                'type' => $this->eventType,
                'key' => $this->key
            ]);

    }
}
