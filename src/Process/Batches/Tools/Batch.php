<?php

namespace Go2Flow\Ezport\Process\Batches\Tools;

use Closure;
use Illuminate\Bus\Batch as BusBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class Batch {

    private ManageActions $action;
    private string $queue;

    public function setQueue(string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    public function setActions(ManageActions $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function run(Collection $jobBatch, array $lock = []): BusBatch
    {

        if ($jobBatch->first() instanceof Collection) return $this->executeRecursiveBatch($jobBatch, $lock);

        return $this->createBatch(
            $jobBatch,
            $lock,
            function () use ($lock) {
                $this->action->setStep('finished');
                $this->releaseLock($lock);
                $this->action->finishAction();
            }
        );
    }

    private function executeRecursiveBatch(Collection $collection, array $lock): BusBatch
    {
        $key = $collection->keys()->first();
        $items = $collection->shift();

        if (!$items || $items->count() === 0) return $this->executeRecursiveBatch($collection, $lock);

        if ($collection->count() === 0) {
            return $this->createBatch(
                $items,
                $lock,
                function () use ($lock) {
                    $this->action->setStep('finished');
                    $this->releaseLock($lock);
                    $this->action->finishAction();
                }
            );
        }

        return $this->createBatch(
            $items,
            $lock,
            function () use ($collection, $key, $lock) {
                $this->action->setStep($key);
                return $this->executeRecursiveBatch($collection, $lock);
            }
        );
    }

    private function createBatch(Collection $collection, array $lock, ?Closure $then = null): BusBatch
    {
        $batch = Bus::batch($collection->toArray())
            ->catch(
                function () use ($lock) {
                    $this->releaseLock($lock);

                    Bus::batch($this->action->finishAction());
            });

        if ($then) $batch->then($then);

        Log::info(json_encode($batch));

        return $batch->onQueue($this->queue)
            ->dispatch();
    }

    private function releaseLock(array $lock): void
    {
        if (count($lock) > 0) Cache::restoreLock($lock['key'], $lock['owner'])->release();
    }
}
