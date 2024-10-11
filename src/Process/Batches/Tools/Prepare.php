<?php

namespace Go2Flow\Ezport\Process\Batches\Tools;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\ModifyModel;
use Go2Flow\Ezport\Upload\UploadManager;
use Illuminate\Support\Collection;

class Prepare {


    public function __construct(
        private readonly UploadManager $uploadManager,
        private readonly Project $project,
    ){}

    public function prepareClean(Collection $collection, string $type): Collection
    {
        $instructions = Find::instruction($this->project, 'clean');

        return $collection->map(
            fn ($top) => collect($top)->map(
                fn ($item) => $instructions->byKey($item)->getJob(['type' => $item])
            )
        );
    }

    public function prepareImport(Collection $collection, string $type): Collection
    {
        return $this->cleanCollection(
            $collection->map(
                fn ($item) => $this->import($item)
            )->when(
                $type == 'full',
                fn ($collection) => $collection->prepend(
                    $this->createModifyModelJobs(
                        GenericModel::where('project_id', $this->project->id)
                            ->where('touched', true)
                            ->pluck('id'),
                        ['action' => ['touched' => false], 'method' => 'update']
                    )
                )
            )
        );
    }

    public function prepareTransform(Collection $jobs, string $type) : Collection
    {
        return
            $this->cleanCollection(
                $jobs->map(fn ($item) => $this->transform($item))
            );
    }

    public function prepareUpload(Collection $jobs, string $type): Collection
    {
        return
            $this->cleanCollection(
                $jobs->map(fn ($item) => $this->upload($item))
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
            ->values()
            ->map(
                fn ($item) => $item instanceof Collection ? $this->cleanCollection($item) : $item
            );
    }


    private function upload(array $batch): Collection
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
