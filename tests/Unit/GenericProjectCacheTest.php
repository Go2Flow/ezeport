<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\EzportServiceProvider;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use Orchestra\Testbench\TestCase;

class GenericProjectCacheTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [EzportServiceProvider::class];
    }

    private function cache(): array
    {
        return (fn () => self::$projectCache)->bindTo(null, Generic::class)();
    }

    private function seedCache(): void
    {
        (fn () => self::$projectCache[7] = new Project)->bindTo(null, Generic::class)();
    }

    public function test_flush_empties_the_project_cache(): void
    {
        $this->seedCache();

        Generic::flushProjectCache();

        $this->assertSame([], $this->cache());
    }

    public function test_cache_is_flushed_before_every_queued_job(): void
    {
        $this->seedCache();

        event(new JobProcessing('redis', $this->createMock(Job::class)));

        $this->assertSame([], $this->cache());
    }
}
