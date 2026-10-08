<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Types\CsvImport;
use PHPUnit\Framework\TestCase;

class CsvImportConfigTest extends TestCase
{
    public function test_constructor_config_is_kept(): void
    {
        $import = new CsvImport('articles', ['delimiter' => ';']);

        $this->assertSame(['delimiter' => ';'], (fn () => $this->config)->call($import));
    }
}
