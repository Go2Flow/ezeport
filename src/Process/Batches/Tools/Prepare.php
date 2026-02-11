<?php

namespace Go2Flow\Ezport\Process\Batches\Tools;

use Closure;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportProcessException;
use Go2Flow\Ezport\Process\Jobs\ModifyModel;
use Illuminate\Support\Collection;

class Prepare {

    public function __construct(
        private readonly UploadManager $uploadManager,
        private readonly Project $project,
    ){}

    public function prepare(string $method, Collection $jobs, string $type): Collection
    {
        return match($method) {
            'upload' => $this->prepareUpload($jobs, $type),
            'import' => $this->prepareImport($jobs, $type),
            'shopClean', 'ftpClean', 'clean' => $this->prepareClean($jobs, $type),
            'transform' => $this->prepareTransform($jobs, $type),
            default => throw new EzportProcessException('No prepare method found for ' . $method),
        };
    }

    private function prepareClean(Collection $collection, string $type): Collection
    {
        return $this->mapSteps($collection, fn ($content) => $this->clean($content));
    }

    private function prepareImport(Collection $collection, string $type): Collection
    {
        return $this->mapSteps($collection, fn ($content) => $this->import($content));
    }

    private function prepareTransform(Collection $collection, string $type): Collection
    {
        return $this->mapSteps($collection, fn ($content) => $this->transform($content));
    }

    private function prepareUpload(Collection $jobs, string $type): Collection
    {
        return $this->mapSteps($jobs, fn ($content) => $this->upload($content))
            ->push(
                $this->createModifyModelJobs(
                    $this->uploadManager->getAll(),
                    [
                        'action' => ['updated' => false],
                        'method' => 'update'
                    ]
                )
            );
    }

    private function mapSteps(Collection $collection, Closure $callback): Collection
    {
        return $this->cleanCollection(
            $collection->mapWithKeys(
                fn ($step, $index) => [
                    $step->getKey() ?: $index => $callback($step->getContent())
                ]
            )
        );
    }

    private function cleanCollection(Collection $collection)
    {
        return $collection
            ->filter()
            ->map(
                fn ($item) => $item instanceof Collection
                    ? $this->cleanCollection($item)
                    : $item
            );
    }

    private function upload(Collection $batch): Collection
    {
        return $this->uploadManager
            ->batch($batch)
            ->getBatch();
    }

    private function import(array|Collection $jobInstructions): Collection
    {
        $importInstructions = Find::instruction($this->project, 'import');

        return collect($jobInstructions)->map(
            fn ($job) => $importInstructions
                ->byKey($job)
                ->GetJob()
        );
    }

    private function clean(array|Collection $jobInstructions): Collection
    {
        $cleanInstructions = Find::instruction($this->project, 'clean');

        return collect($jobInstructions)->map(
            fn ($job) => $cleanInstructions
                ->byKey($job)
                ->GetJob(['type' => $job])
        );
    }

    private function transform(array|Collection $jobInstructions): Collection
    {
        $transformInstructions = Find::instruction($this->project, 'transform');

        return collect($jobInstructions)->map(
            fn ($job) => $transformInstructions
                ->byKey($job)
                ->GetJob()
        );
    }

    private function createModifyModelJobs(Collection $ids, array $data): Collection
    {
        return $ids->chunk(100)
            ->map(
                fn ($chunk) => new ModifyModel(
                    $this->project->id,
                    $data,
                    $chunk->toArray()
                )
            );
    }
}