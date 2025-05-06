<?php

namespace Go2Flow\Ezport\Process\Upload\Csv\Creates;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Events\BeforeExport;

class Create implements FromCollection,  WithHeadings, WithCustomCsvSettings
{
    use Exportable;

    public function __construct(readonly private Collection $collection, readonly private array $config = [])
    {
    }

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
        $default = [
            'delimiter' => ';',
            'use_bom' => false,
            'output_encoding' => 'ISO-8859-1',
        ];

        return array_merge($default, $this->config['csvSettings'] ?? []);
    }
    public function headings(): array
    {
        return $this->config['headings'] ?? collect($this->collection[0])->keys()->toArray();
    }
}
