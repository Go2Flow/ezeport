<?php

namespace Go2Flow\Ezport\Import\Csv\Imports;

use Closure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class Import implements ToCollection, WithHeadingRow
{
    use Importable;

    private Closure $structure;

    public function __construct(Closure $structure)
    {
        $this->structure = $structure;
    }

    /**
     * @param Collection $collection
     * @return Collection
     */
    public function collection(Collection $collection): Collection
    {
        return ($this->structure)($collection[0]);
    }
}
