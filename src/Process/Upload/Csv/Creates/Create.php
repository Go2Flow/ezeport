<?php

namespace Go2Flow\Ezport\Process\Upload\Csv\Creates;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class Create implements FromCollection,  WithHeadings
{

    use Exportable;

    public function __construct(private Collection $collection)
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

    public function headings(): array
    {
        return collect($this->collection[0])->keys()->toArray();
    }
}
