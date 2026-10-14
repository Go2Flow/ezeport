<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Helpers\FileNameFilter;
use PHPUnit\Framework\TestCase;

class FileNameFilterTest extends TestCase
{
    private const FILES = [
        'artikel/HisExport_900.xml',
        'artikel/HisExport_900_20261013100852.xml',
        'artikel/HisExport_900_delta_20261014110011.xml',
        'artikel/readme.txt',
    ];

    private function names(array $config): array
    {
        return (new FileNameFilter($config))->filter(self::FILES)->all();
    }

    public function test_without_config_every_file_passes(): void
    {
        $this->assertSame(self::FILES, $this->names([]));
    }

    public function test_not_leaves_out_exact_base_names_of_full_paths(): void
    {
        $this->assertNotContains('artikel/HisExport_900.xml', $this->names(['not' => ['HisExport_900.xml']]));
        $this->assertCount(3, $this->names(['not' => ['HisExport_900.xml']]));
    }

    public function test_contains_keeps_only_matching_names(): void
    {
        $this->assertSame(['artikel/HisExport_900_delta_20261014110011.xml'], $this->names(['contains' => 'delta']));
    }

    public function test_not_contains_and_type_combine(): void
    {
        $this->assertSame(
            ['artikel/HisExport_900.xml', 'artikel/HisExport_900_20261013100852.xml'],
            $this->names(['notContains' => ['delta'], 'type' => '.xml'])
        );
    }

    public function test_folder_names_do_not_count_as_a_match(): void
    {
        $this->assertFalse((new FileNameFilter(['contains' => 'delta']))->accepts('delta/HisExport_900.xml'));
    }
}
