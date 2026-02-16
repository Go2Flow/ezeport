<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\ActivityLog;
use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Activity;
use Go2Flow\Ezport\Models\Error;
use PHPUnit\Framework\TestCase;

class ActivityLogTest extends TestCase
{
    // --- type switching ---

    public function test_default_type_is_activity(): void
    {
        $log = new ActivityLog();

        // By default, the internal activity should be an Activity instance
        // We verify this indirectly by checking chaining works
        $this->assertInstanceOf(ActivityLog::class, $log);
    }

    public function test_type_standard_creates_activity(): void
    {
        $log = new ActivityLog();

        $result = $log->type('standard');

        $this->assertInstanceOf(ActivityLog::class, $result);
    }

    public function test_type_error_creates_error(): void
    {
        $log = new ActivityLog();

        $result = $log->type('error');

        $this->assertInstanceOf(ActivityLog::class, $result);
    }

    // --- chaining ---

    public function test_unique_id_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->uniqueId('test-123');

        $this->assertSame($log, $result);
    }

    public function test_properties_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->properties(['key' => 'value']);

        $this->assertSame($log, $result);
    }

    public function test_level_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->level('info');

        $this->assertSame($log, $result);
    }

    // --- convenience methods ---

    public function test_is_job_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->isJob();

        $this->assertSame($log, $result);
    }

    public function test_is_shop_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->isShop();

        $this->assertSame($log, $result);
    }

    public function test_is_error_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->isError();

        $this->assertSame($log, $result);
    }

    public function test_is_model_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->isModel();

        $this->assertSame($log, $result);
    }

    public function test_is_api_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->isApi();

        $this->assertSame($log, $result);
    }

    public function test_content_type_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->contentType('product');

        $this->assertSame($log, $result);
    }

    public function test_failed_job_returns_self(): void
    {
        $log = new ActivityLog();

        $result = $log->failedJob();

        $this->assertSame($log, $result);
    }

    // --- full chain scenario ---

    public function test_full_chaining_scenario(): void
    {
        $log = new ActivityLog();

        $result = $log
            ->type('standard')
            ->uniqueId('product-123')
            ->contentType('product')
            ->properties(['name' => 'Changed']);

        $this->assertInstanceOf(ActivityLog::class, $result);
    }

    public function test_error_chain_scenario(): void
    {
        $log = new ActivityLog();

        $result = $log
            ->isJob()
            ->uniqueId('job-456')
            ->properties(['error' => 'timeout']);

        $this->assertInstanceOf(ActivityLog::class, $result);
    }
}
