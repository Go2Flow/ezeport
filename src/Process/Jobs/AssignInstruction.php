<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\Assignable;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignInstruction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries;
    public int $timeout;

    public function __construct(public int $project, private array $config)
    {
        $this->tries = $config['tries'] ?? 1;
        $this->timeout = $config['timeout'] ?? 890;
    }

    public function handle(): void
    {
        $instruction = Find::instruction(
            Project::find($this->project),
            $this->config['instructionType']
        )->find($this->config['key']);

        $jobs = $instruction->assignJobs();

        if ($jobs->isNotEmpty()) {
            $this->batch()->add($jobs);
        }
    }

    public function uniqueId(): string
    {
        return $this->project . '-' . ($this->config['instructionType'] ?? '') . '-' . ($this->config['key'] ?? '');
    }

    public function tags(): array
    {
        return [
            'assign',
            ($this->config['instructionType'] ?? 'unknown') . ':' . ($this->config['key'] ?? 'unknown'),
        ];
    }
}
