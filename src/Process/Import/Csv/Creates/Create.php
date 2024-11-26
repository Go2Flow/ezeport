<?php

namespace Go2Flow\Ezport\Process\Import\Csv\Creates;

use Closure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class Create implements FromCollection
{
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
}
