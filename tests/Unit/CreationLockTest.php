<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Helpers\CreationLock;
use PHPUnit\Framework\TestCase;

class CreationLockTest extends TestCase
{
    public function test_runs_callback_without_lock_when_there_is_no_unique_id(): void
    {
        $this->assertSame('ran', CreationLock::run(1, 'Article', null, fn () => 'ran'));
        $this->assertSame('ran', CreationLock::run(1, 'Article', '', fn () => 'ran'));
    }

    public function test_key_is_scoped_by_project_type_and_unique_id(): void
    {
        $this->assertSame(CreationLock::key(1, 'CustomProperty', 'A-1'), CreationLock::key(1, 'customproperty', 'A-1'));
        $this->assertNotSame(CreationLock::key(1, 'CustomProperty', 'A-1'), CreationLock::key(2, 'CustomProperty', 'A-1'));
        $this->assertNotSame(CreationLock::key(1, 'CustomProperty', 'A-1'), CreationLock::key(1, 'CustomProperty', 'A-2'));
    }
}
