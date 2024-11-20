<?php

namespace Go2Flow\Ezport\Process\Import\Csv\Imports;

use Closure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class Import implements ToCollection, WithHeadingRow
{
    use Importable;



    public function __construct(private array $config = [])
    {
    }

    public function headingRow(): int
    {
        return $this->config['headingRowNum'] ?? 1;
    }

    /**
     * @param Collection $collection
     * @return Collection
     */
    public function collection(Collection $collection): Collection
    {
        return $collection[0];
    }
}
