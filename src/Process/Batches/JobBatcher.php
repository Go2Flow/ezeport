<?php

namespace Go2Flow\Ezport\Process\Batches;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\Jobs;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Batches\Tools\Batch as BatchTool;
use Go2Flow\Ezport\Process\Batches\Tools\ManageActions;
use Go2Flow\Ezport\Process\Batches\Tools\Prepare;
use Go2Flow\Ezport\Process\Batches\Tools\SmallestQueue;
use Go2Flow\Ezport\Process\Batches\Tools\UploadManager;
use Go2Flow\Ezport\Process\Errors\EzportProcessException;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;

class JobBatcher
{
    const CHUNKSIZE = 100;

    private ManageActions $action;
    private BatchTool $batch;
    private Prepare $prepare;

    public function __construct(
        protected Project $project,
        UploadManager $uploadManager
    ){
        $this->action = new ManageActions($this->project);
        $this->batch = new BatchTool;
        $this->prepare = new Prepare($uploadManager, $this->project);
    }

    public function executeJobsBatch(Collection $jobs, array $lock = []): ?Batch
    {
        return $this->batch->setActions($this->action)
            ->setQueue($this->action->getQueue())
            ->run($jobs, $lock);
    }

    public function get(string $method, string $type): Collection
    {
        return $this->prepareBatch($method, $type);
    }

    public function run(string $method, string $type): Batch
    {
        return $this->executeJobsBatch(
            $this->prepareBatch($method, $type)
        );
    }

    public function startAction(string $key, string $type): self
    {
        $this->action->start($key, $type, SmallestQueue::get());

        return $this;
    }

    private function prepareBatch(string $method, string $type): Collection
    {
        $instruction = $this->getJobInstructions($method, $type);

        return $this->prepare->prepare($method, $instruction->getJobs(), $type);
    }

    private function getJobInstructions(string $method, string $type): Jobs
    {
        $instruction = Find::Instruction($this->project, 'Jobs')
            ->findAll($method)
            ->filter(fn ($instruction) => $instruction->getType() == $type && $instruction->correctEnv())
            ->first();

        if (!$instruction) {
            throw new EzportProcessException(
                'No instruction found for ' . $method . ' and ' . $type . ' and ' . config('app.env')
            );
        }

        return $instruction;
    }
}