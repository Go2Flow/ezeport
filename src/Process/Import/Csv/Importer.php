<?php

namespace Go2Flow\Ezport\Process\Import\Csv;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Setters\CsvImport;
use Go2Flow\Ezport\Instructions\Setters\CsvImportStep;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Import\Csv\Imports\Import;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Importer {

    public function __construct(private Project $project, private CsvImport $instructions)
    {
    }

    public function getItems() : Collection
    {
        return $this->prepareItems($this->instructions->get('imports'));
    }

    private function prepareItems($instructions) : Collection
    {
        $items = collect();

        foreach ($instructions as $instruction)
        {
            if (! $instruction instanceof CsvImportStep) $items->push($this->prepareItems($instruction));
            else {

                $items = $instruction->get('create') ?? false
                ? $this->createOrGetType($instruction)
                : $this->attachData(
                    $items,
                    $instruction
                );
            }
        }

        return $items;
    }

    private function createOrGetType($instruction) : Collection {

        return $this->getData($instruction)
            ->map(
                fn ($item) =>
                    (new Generic ([
                        'unique_id' => $item['unique_id'],
                        'project_id' => $this->project->id,
                        'type' => $this->instructions->get('class')
                    ])
                )->setContentAndRelations($item)
        );
    }

    private function getData($instruction) : Collection
    {
        $importer = (new Import($instruction->get('structure')));

        return $importer->collection(
            $importer->toCollection(
                Storage::drive('public')
                    ->path($this->project->identifier . '/' . $this->getPath() . $instruction->get('file'))
            )
        );
    }

    private function getPath() : string
    {
        return $this->instructions->getJobConfig()['path'] . '/';
    }

    private function attachData($items, $instruction) : Collection {

        $data = $this->getData($instruction);

        return $items->count() == 0 ? $data : $items->map(function ($item) use ($data) {

            foreach ($data->filter(fn ($dataItem) => $item->unique_id == $dataItem['id']) as $dataItem){
                $item->properties($dataItem);
            }

            return $item;
        });
    }
}
