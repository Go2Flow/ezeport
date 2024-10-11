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

    public function run(Collection $jobBatch, Collection $stepNames, array $lock = []): BusBatch
    {
        if ($jobBatch->first() instanceof Collection) return $this->executeRecursiveBatch($jobBatch, $stepNames, $lock);
        $this->action->setStep($stepNames->shift());

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

    private function executeRecursiveBatch(Collection $collection, Collection $stepNames, array $lock): BusBatch
    {
        $items = $collection->shift();
        $stepName = $stepNames->shift();

        if (!$items || $items->count() === 0) return $this->executeRecursiveBatch($collection, $stepNames, $lock);

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
            function () use ($collection, $stepName, $stepNames, $lock) {

                $this->action->setStep($stepName);
                return $this->executeRecursiveBatch($collection, $stepNames, $lock);
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

        return $batch->onQueue($this->queue)
            ->dispatch();
    }

    private function releaseLock(array $lock): void
    {
        if (count($lock) > 0) Cache::restoreLock($lock['key'], $lock['owner'])->release();
    }
}
