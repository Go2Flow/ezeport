<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Jobs\FileImport;
use Go2Flow\Ezport\Process\Jobs\AssignFtpFileImport;
use Go2Flow\Ezport\Process\Jobs\FtpFileImport as FtpFileImportJob;
use Illuminate\Support\Collection;

class FtpFileImport extends Basic implements JobInterface, ImportInstructionInterface
{

    protected ?\closure $prepare = null;
    protected \closure $process;
    protected array $config = [];

    public function __construct(string $key)
    {
        parent::__construct($key);

        $this->jobClass = AssignFtpFileImport::class;
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

    public function prepareJobs(Api $api, array $config) : Collection {

        return ($this->prepare)($api, $this->config)
            ->chunk(25)
            ->map(
                fn ($chunk) => new FtpFileImportJob(
                    $chunk,
                    $this->project->id,
                    $config
                )
            );
    }

    public function run (Collection $chunk, Api $api) : void {

        ($this->process)(
            $chunk,
            $this->config,
            $api
        );
    }
}
