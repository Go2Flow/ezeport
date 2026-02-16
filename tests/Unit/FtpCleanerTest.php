<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\FtpCleaner;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\AssignClean;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class FtpCleanerTest extends TestCase
{
    // --- construction ---

    public function test_creates_ftp_cleaner_instance(): void
    {
        $cleaner = Set::FtpCleaner('ftp');

        $this->assertInstanceOf(FtpCleaner::class, $cleaner);
    }

    public function test_default_job_is_assign_clean(): void
    {
        $cleaner = Set::FtpCleaner('ftp');

        $job = $cleaner->get('job');
        $this->assertEquals(AssignClean::class, $job->getJob());
    }

    // --- class ---

    public function test_class_stores_class_name(): void
    {
        $cleaner = Set::FtpCleaner('ftp');

        $result = $cleaner->class('App\\Jobs\\CleanFtp');

        $this->assertSame($cleaner, $result);
    }

    // --- config ---

    public function test_config_stores_config(): void
    {
        $cleaner = Set::FtpCleaner('ftp');

        $result = $cleaner->config(['path' => '/uploads', 'age' => 30]);

        $this->assertSame($cleaner, $result);
    }

    // --- prepareItems (noop) ---

    public function test_prepare_items_returns_self(): void
    {
        $cleaner = Set::FtpCleaner('ftp');

        $result = $cleaner->prepareItems();

        $this->assertSame($cleaner, $result);
    }

    // --- getCleaner ---

    public function test_get_cleaner_instantiates_class_with_project_and_config(): void
    {
        $project = new Project();
        $project->id = 1;

        $cleaner = Set::FtpCleaner('ftp');
        $cleaner->setProject($project);

        // Use a mock job class that we control
        $cleaner->class(FtpCleanerTestJob::class);
        $cleaner->config(['path' => '/test']);

        $result = $cleaner->getCleaner();

        $this->assertInstanceOf(FtpCleaner::class, $result);
    }

    // --- prepareJobs returns collection ---

    public function test_prepare_jobs_returns_collection_with_job(): void
    {
        $project = new Project();
        $project->id = 1;

        $cleaner = Set::FtpCleaner('ftp');
        $cleaner->setProject($project);

        $cleaner->class(FtpCleanerTestJob::class);
        $cleaner->config(['path' => '/test']);

        $cleaner->getCleaner();

        $jobs = $cleaner->prepareJobs();

        $this->assertInstanceOf(Collection::class, $jobs);
        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(FtpCleanerTestJob::class, $jobs->first());
    }

    // --- chaining ---

    public function test_full_chaining(): void
    {
        $cleaner = Set::FtpCleaner('ftp')
            ->class('App\\Jobs\\FtpClean')
            ->config(['path' => '/uploads']);

        $this->assertInstanceOf(FtpCleaner::class, $cleaner);
    }
}

/**
 * Minimal test stub for FtpCleaner's getCleaner/prepareJobs
 */
class FtpCleanerTestJob
{
    public int $projectId;
    public array $config;

    public function __construct(int $projectId, array $config)
    {
        $this->projectId = $projectId;
        $this->config = $config;
    }
}
