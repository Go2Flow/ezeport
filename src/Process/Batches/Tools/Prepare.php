<?php

namespace Go2Flow\Ezport\Process\Batches\Tools;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\ModifyModel;
use Illuminate\Support\Collection;

class Prepare {


    public function __construct(
        private readonly UploadManager $uploadManager,
        private readonly Project $project,
    ){}

    public function prepareClean(Collection $collection, string $type): Collection
    {
        $instructions = Find::instruction($this->project, 'clean');

        return  $this->cleanCollection(
            $collection->mapWithKeys(fn ($step, $index) => [$step->getKey() == '' ? $index : $step->getKey() => $this->clean($step->getContent())])
        );
    }

    public function prepareImport(Collection $collection, string $type): Collection
    {
        return $this->cleanCollection(
            $collection->mapWithKeys(
                fn ($step, $index) => [
                    $step->getKey() == '' ? $index : $step->getKey() => $this->import($step->getcontent())
                ]
            )
        );
    }

    public function prepareTransform(Collection $collection, string $type) : Collection
    {
        return
            $this->cleanCollection(
                $collection->mapWithKeys(fn ($step, $index) => [$step->getKey() == '' ? $index : $step->getKey() => $this->transform($step->getContent())])
            );
    }

    public function prepareUpload(Collection $jobs, string $type): Collection
    {
        return
            $this->cleanCollection(
                $jobs->mapWithKeys(fn ($step, $index) => [$step->getKey() == '' ? $index : $step->getKey() => $this->upload($step->getContent())])
            )->push(
                $this->createModifyModelJobs(
                    $this->uploadManager->getAll(),
                    [
                        'action' => ['updated' => false],
                        'method' => 'update'
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
        $importInstructions = Find::instruction($this->project, 'clean');

        return collect($jobInstructions)->map(
            fn ($job) => $importInstructions
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
