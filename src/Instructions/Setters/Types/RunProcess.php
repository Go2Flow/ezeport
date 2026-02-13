<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\Executable;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\ProcessInstruction;

class RunProcess extends Basic implements JobInterface, Executable {

    protected string $eventType = '';

    public function __construct(string $key) {

        parent::__construct($key);

        $this->job = Set::Job()
            ->class(ProcessInstruction::class)
            ->config([
                'type' => $this->eventType,
                'key' => $this->key
            ]);
    }

    public function execute(array $config): void
    {
        $instruction = Find::instruction(
            $this->project,
            $config['type']
        )->find($config['key']);

        $instruction->get('process')(
            $config['items'] ?? collect([]),
            $instruction->has('api')
                ? $instruction->get('api')($this->project)
                : null,
        );
    }
}
