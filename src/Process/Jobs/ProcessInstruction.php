<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\Executable;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInstruction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries;
    public int $timeout;

    public function __construct(public int $project, private array $config)
    {
        $this->tries = $config['tries'] ?? 1;
        $this->timeout = $config['timeout'] ?? 120;
    }

    public function handle(): void
    {
        Find::instruction(
            Project::find($this->project),
            $this->config['instructionType']
        )->find($this->config['key'])
            ->execute($this->config);
    }

    public function tags(): array
    {
        return [
            'process',
            ($this->config['instructionType'] ?? 'unknown') . ':' . ($this->config['key'] ?? 'unknown'),
        ];
    }
}