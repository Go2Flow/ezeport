<?php

namespace Go2Flow\Ezport\Process\Upload\Csv\Creates;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Events\BeforeExport;

class Create implements FromCollection,  WithHeadings, WithCustomCsvSettings, WithEvents
{
    use Exportable;

    public function __construct(readonly private Collection $collection, readonly private array $config = []){}

    /**
     * @param Collection $collection
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->collection;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'use_bom' => false,
            'output_encoding' => $this->config['encoding'] ?? 'ISO-8859-1',
        ];
    }

    public function headings(): array
    {
        return $this->config['headings'] ?? collect($this->collection[0])->keys()->toArray();
    }

    public function registerEvents(): array
    {

        return match($this->config['event'] ?? 'standard') {
            'UTF-8 BOM' => [
                BeforeExport::class => function (BeforeExport $event) {
                    // Write UTF-8 BOM to the output stream
                    $event->writer->getDelegate()->setPreCalculateFormulas(false);
                    $event->writer->getDelegate()->getProperties()->setTitle('Users Export');

                    // Manually prepend UTF-8 BOM
                    $event->writer->getDelegate()->setUseBOM(true);
                }
            ],
            default => [],
        };

    }}
