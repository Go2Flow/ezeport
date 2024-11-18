<?php

namespace Go2Flow\Ezport\Process\Import\Csv\Imports;

use Closure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TestImport implements ToCollection, WithHeadingRow
{
    use Importable;

//    private Closure $structure;
//
//    public function __construct(Closure $structure, array $config = [])
//    {
//        $this->structure = $structure;
//    }

    public function headingRow(): int
    {
        return 2;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        return $collection[0];

    }
}
