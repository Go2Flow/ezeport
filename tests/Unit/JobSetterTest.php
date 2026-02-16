<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\Job;
use PHPUnit\Framework\TestCase;

class JobSetterTest extends TestCase
{
    public function test_job_stores_class(): void
    {
        $job = Set::Job();
        $job->class('App\\Jobs\\TestJob');

        $this->assertEquals('App\\Jobs\\TestJob', $job->getJob());
    }

    public function test_job_stores_config(): void
    {
        $job = Set::Job();
        $job->config(['key' => 'value', 'batch' => 50]);

        $this->assertEquals(['key' => 'value', 'batch' => 50], $job->getConfig());
    }

    public function test_job_default_config_is_empty(): void
    {
        $job = Set::Job();

        $this->assertEquals([], $job->getConfig());
    }

    public function test_job_class_returns_self(): void
    {
        $job = Set::Job();

        $result = $job->class('SomeClass');

        $this->assertSame($job, $result);
    }

    public function test_job_config_returns_self(): void
    {
        $job = Set::Job();

        $result = $job->config([]);

        $this->assertSame($job, $result);
    }
}
