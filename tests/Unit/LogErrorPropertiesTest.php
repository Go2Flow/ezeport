<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Logger\LogError;
use Go2Flow\Ezport\Models\Error;
use Orchestra\Testbench\TestCase;

class LogErrorPropertiesTest extends TestCase
{
    private function error(LogError $log): Error
    {
        return (fn () => $this->error)->call($log);
    }

    public function test_string_properties_survive_the_collection_cast(): void
    {
        $log = (new LogError(1))->properties('ftp timeout');

        $error = $this->error($log);
        $stored = $error->getAttributes()['properties'];
        $error->setRawAttributes(['properties' => $stored]);

        $this->assertSame(['message' => 'ftp timeout'], $error->properties->all());
    }

    public function test_array_properties_are_kept(): void
    {
        $log = (new LogError(1))->properties(['file' => 'a.xml']);

        $this->assertSame(['file' => 'a.xml'], $this->error($log)->properties->all());
    }
}
