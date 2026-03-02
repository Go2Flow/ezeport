<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\Assignable;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\Executable;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Process\Jobs\AssignInstruction;
use Go2Flow\Ezport\Process\Jobs\ProcessInstruction;
use Illuminate\Support\Collection;

class FtpFileImport extends Basic implements JobInterface, ImportInstructionInterface, Assignable, Executable
{

    protected ?\closure $prepare = null;
    protected ?\closure $process;
    protected array $config = [];

    public function __construct(string $key)
    {
        parent::__construct($key);

        $this->jobClass = AssignInstruction::class;
    }

    public function config(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function prepare(\closure $closure) : self {

        $this->prepare = $closure;

        return $this;
    }
    public function process(\closure $closure) : self {

        $this->process = $closure;

        return $this;
    }

    public function assignJobs(): Collection
    {
        $api = Find::api($this->project, 'ftp');

        return ($this->prepare)($api, $this->config)
            ->chunk(25)
            ->map(
                fn ($chunk) => new ProcessInstruction(
                    $this->project->id,
                    array_merge(
                        [
                            'chunk' => $chunk,
                            'instructionType' => $this->instructionType,
                            'key' => $this->key,
                            'tries' => 5,
                        ],
                        $this->jobConfig,
                    )
                )
            );
    }

    public function execute(array $config): void
    {
        ($this->process)(
            collect($config['chunk']),
            $this->config,
            Find::api($this->project, 'ftp')
        );
    }
}
