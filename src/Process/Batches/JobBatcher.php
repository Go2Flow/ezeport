<?php

namespace Go2Flow\Ezport\Process\Batches;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\Jobs;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Batches\Tools\Batch as BatchTool;
use Go2Flow\Ezport\Process\Batches\Tools\ManageActions;
use Go2Flow\Ezport\Process\Batches\Tools\Prepare;
use Go2Flow\Ezport\Process\Batches\Tools\UploadManager;
use Go2Flow\Ezport\Process\Errors\EzportProcessException;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;

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
        $this->prepare = New Prepare($uploadManager, $this->project, $this->action);
    }

    /** will execute the job batches you pass in. You have two choices about what you pass in. Either a collection of jobs
     * or a collection of collections of jobs. If you pass in a collection of jobs, it will execute them all in one batch.
     * if you pass in a collection of collections of jobs, it will finish one colleciton of jobs before moving on to the next
     */

    public function executeJobsBatch(Collection $jobs, array $lock = []): Batch
    {

        return $this->batch->setActions($this->action)
            ->setQueue($this->action->getQueue())
            ->run(
                $jobs,
                $lock
            );
    }

    public function getFtpClean(string $type): Collection
    {
        return $this->getBatch('ftpClean', $type);
    }

    public function getImport(string $type): Collection
    {
        return $this->getBatch('import', $type);
    }

    public function getShopClean(string $type): Collection
    {
        return $this->getBatch('shopClean', $type);
    }

    public function getTransform(string $type): Collection
    {
        return $this->getBatch('transform', $type);
    }

    public function getUpload(string $type): Collection
    {
        return $this->getBatch('upload', $type);
    }

    public function runFtpClean(string $type): Batch
    {
        return $this->runBatch('ftpClean', $type);
    }

    public function runImport(string $type): Batch
    {
        return $this->runBatch('import', $type);
    }

    public function runShopClean(string $type): Batch
    {
        return $this->runBatch('shopClean', $type);
    }

    public function runTransform(string $type): Batch
    {
        return $this->runBatch('transform', $type);
    }

    public function runUpload(string $type): Batch
    {
        return $this->runBatch('upload', $type);
    }

    /** To correctly store information in the activity log an action must be started before a batch is executed */

    public function startAction(string $key, string $type): self
    {
        $this->action->start($key, $type, $this->findSmallestQueue());

        return $this;
    }

    private function correctPrepare(string $method, Jobs $instruction, string $type): Collection
    {
        $jobs = $instruction->getJobs();

        return
            match($method) {
                'upload' => $this->prepare->prepareUpload($jobs, $type),
                'import' => $this->prepare->prepareImport($jobs, $type),
                'shopClean' => $this->prepare->prepareClean($jobs, $type),
                'ftpClean' => $this->prepare->prepareClean($jobs, $type),
                'transform' => $this->prepare->prepareTransform($jobs, $type),
                default => throw new EzportProcessException('No prepare method found for ' . $method),
            };
    }

    private function prepareBatch(string $method, string $type): Collection
    {
        return $this->correctPrepare(
            $method,
            $this->getJobInstructions($method, $type),
            $type
        );
    }

    private function getJobInstructions(string $method, string $type) : Jobs
    {
        $instruction = Find::Instruction($this->project, 'jobs')
            ->findAll($method)
            ->filter(fn ($instruction) => $instruction->getType() == $type && $instruction->correctEnv())
            ->first();

        if (!$instruction) throw new EzportProcessException('No instruction found for ' . $method . ' and ' . $type . ' and ' . config('app.env'));

        return $instruction;
    }

    private function findSmallestQueue(): string
    {
        $queues = collect(config('horizon.defaults'))->map(
            fn ($supervisor) => $supervisor['queue'][0]
        );

        $smallestQueue = null;
        $smallestSize = PHP_INT_MAX;

        foreach ($queues as $queue) {
            $size = Queue::size($queue);
            if ($size < $smallestSize) {
                $smallestQueue = $queue;
                $smallestSize = $size;
            }
        }

        return $smallestQueue;
    }

    private function runBatch(string $method, string $type) : Batch {

        return $this->executeJobsBatch(
            $this->prepareBatch(
                $method,
                $type
            )
        );
    }

    private function getBatch(string $method, string $type) : Collection {

        return $this->prepareBatch(
            $method,
            $type
        );
    }
}
