<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\XmlImport;
use PHPUnit\Framework\TestCase;

class XmlImportExcludeTest extends TestCase
{
    public function test_exclude_defaults_to_empty(): void
    {
        $this->assertTrue(Set::XmlImport('Articles')->get('exclude')->isEmpty());
    }

    public function test_exclude_is_stored_and_merged(): void
    {
        $import = Set::XmlImport('Articles')
            ->exclude(['name' => ['Farbe']])
            ->exclude(['type' => 'x']);

        $this->assertSame(['name' => ['Farbe'], 'type' => 'x'], $import->get('exclude')->all());
    }

    public function test_exclude_from_component_config_array(): void
    {
        $component = new XmlImport('custom_properties', ['exclude' => ['name' => ['Weite']]]);

        $this->assertSame(['name' => ['Weite']], $component->get('exclude')->all());
    }
}
