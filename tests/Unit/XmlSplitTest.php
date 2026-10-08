<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportImportException;
use Go2Flow\Ezport\Process\Import\Xml\Split;
use PHPUnit\Framework\TestCase;

class XmlSplitTest extends TestCase
{
    public function test_unreadable_file_throws_an_import_exception(): void
    {
        $this->expectException(EzportImportException::class);
        $this->expectExceptionMessage('Could not open file /does/not/exist.xml');

        (new Split('/does/not/exist.xml', new Project))->batch('article');
    }
}
