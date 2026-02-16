<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\Clean;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\AssignInstruction;
use Go2Flow\Ezport\Process\Jobs\ProcessInstruction;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CleanInstructionTest extends TestCase
{
    private function makeClean(string $key = 'product'): Clean
    {
        $clean = Set::Clean($key);
        $project = new Project();
        $project->id = 1;
        $clean->setProject($project)
            ->instructionType('clean');

        return $clean;
    }

    // --- construction ---

    public function test_creates_clean_instance(): void
    {
        $clean = Set::Clean('product');

        $this->assertInstanceOf(Clean::class, $clean);
        $this->assertEquals('product', $clean->getKey());
    }

    public function test_clean_key_is_processed(): void
    {
        $this->assertEquals('product', Set::Clean('products')->getKey());
        $this->assertEquals('category', Set::Clean('Categories')->getKey());
        $this->assertEquals('media', Set::Clean('media')->getKey());
    }

    public function test_clean_default_job_is_assign_clean(): void
    {
        $clean = Set::Clean('product');

        $job = $clean->get('job');
        $this->assertEquals(AssignInstruction::class, $job->getJob());
    }

    // --- items ---

    public function test_items_stores_closure(): void
    {
        $clean = $this->makeClean();

        $result = $clean->items(fn () => collect([1, 2, 3]));

        $this->assertSame($clean, $result);
    }

    // --- config ---

    public function test_config_stores_and_merges(): void
    {
        $clean = $this->makeClean();

        $clean->config(['key1' => 'val1']);
        $clean->config(['key2' => 'val2']);

        $config = $clean->get('config');
        $this->assertEquals(['key1' => 'val1', 'key2' => 'val2'], $config);
    }

    // --- chunk ---

    public function test_chunk_default_is_25(): void
    {
        $clean = $this->makeClean();

        $this->assertEquals(25, $clean->get('chunk'));
    }

    public function test_chunk_can_be_changed(): void
    {
        $clean = $this->makeClean();

        $clean->chunk(50);

        $this->assertEquals(50, $clean->get('chunk'));
    }

    // --- prepareItems ---

    public function test_prepare_items_executes_ids_closure(): void
    {
        $clean = $this->makeClean();

        $clean->items(fn () => collect(['id-1', 'id-2', 'id-3']));
        $clean->prepareItems();

        $items = $clean->get('items');
        $this->assertInstanceOf(Collection::class, $items);
        $this->assertEquals(['id-1', 'id-2', 'id-3'], $items->toArray());
    }

    public function test_prepare_items_returns_self(): void
    {
        $clean = $this->makeClean();
        $clean->items(fn () => collect());

        $result = $clean->prepareItems();

        $this->assertSame($clean, $result);
    }

    // --- prepareJobs ---

    public function test_prepare_jobs_creates_process_instruction_jobs(): void
    {
        $clean = $this->makeClean();
        $project = new Project();
        $project->id = 42;
        $clean->setProject($project);

        $clean->items(fn () => collect([1, 2, 3, 4, 5]));
        $clean->chunk(2);

        $jobs = $clean->assignJobs();

        $this->assertInstanceOf(Collection::class, $jobs);
        // 5 items / chunk of 2 = 3 jobs
        $this->assertCount(3, $jobs);

        foreach ($jobs as $job) {
            $this->assertInstanceOf(ProcessInstruction::class, $job);
            $this->assertEquals(42, $job->project);
        }
    }

    public function test_prepare_jobs_chunk_sizes_are_correct(): void
    {
        $clean = $this->makeClean();
        $project = new Project();
        $project->id = 1;
        $clean->setProject($project);

        $clean->items(fn () => collect(range(1, 10)));
        $clean->chunk(3);

        $jobs = $clean->assignJobs();

        // 10 items / chunk of 3 = 4 jobs (3, 3, 3, 1)
        $this->assertCount(4, $jobs);
    }

    public function test_prepare_jobs_single_chunk(): void
    {
        $clean = $this->makeClean();
        $project = new Project();
        $project->id = 1;
        $clean->setProject($project);

        $clean->items(fn () => collect([1, 2]));
        $clean->chunk(25); // default, much larger than items

        $jobs = $clean->assignJobs();

        $this->assertCount(1, $jobs);
    }

    public function test_prepare_jobs_empty_items(): void
    {
        $clean = $this->makeClean();
        $project = new Project();
        $project->id = 1;
        $clean->setProject($project);

        $clean->items(fn () => collect());

        $jobs = $clean->assignJobs();

        // Empty collection chunks to empty collection
        $this->assertCount(0, $jobs);
    }

    // --- getCleaner ---

    public function test_get_cleaner_returns_self(): void
    {
        $clean = $this->makeClean();

        $result = $clean->getCleaner();

        $this->assertSame($clean, $result);
    }

    // --- processBatch ---

    public function test_process_batch_calls_process_closure_with_chunk(): void
    {
        $clean = $this->makeClean();

        $receivedChunk = null;
        $receivedApi = null;

        $clean->process(function ($chunk, $api) use (&$receivedChunk, &$receivedApi) {
            $receivedChunk = $chunk;
            $receivedApi = $api;
        });

        $testChunk = collect(['id-1', 'id-2']);

        // Use a string API which creates a GetProxy (won't be invoked since process closure captures it)
        $clean->api('ShopSix');

        // processBatch will call the process closure, but the api GetProxy requires a project
        // We need to use a GetProxy that returns a mock
        $mockProxy = new \Go2Flow\Ezport\Instructions\Getters\GetProxy(fn ($project) => 'mock-api');
        $clean->api($mockProxy);

        $clean->processBatch($testChunk);

        $this->assertSame($testChunk, $receivedChunk);
    }

    public function test_process_batch_receives_correct_data(): void
    {
        $clean = $this->makeClean();

        $processed = [];

        $clean->process(function ($chunk, $api) use (&$processed) {
            $processed = $chunk->toArray();
        });

        $mockProxy = new \Go2Flow\Ezport\Instructions\Getters\GetProxy(fn ($project) => 'mock-api');
        $clean->api($mockProxy);

        $clean->processBatch(collect(['a', 'b', 'c']));

        $this->assertEquals(['a', 'b', 'c'], $processed);
    }

    // --- api ---

    public function test_api_accepts_string_creates_get_proxy(): void
    {
        $clean = $this->makeClean();

        $result = $clean->api('ShopSix');

        $this->assertSame($clean, $result);
    }

    public function test_api_accepts_get_proxy(): void
    {
        $clean = $this->makeClean();
        $proxy = new \Go2Flow\Ezport\Instructions\Getters\GetProxy(fn ($p) => 'api');

        $result = $clean->api($proxy);

        $this->assertSame($clean, $result);
    }

    // --- full flow ---

    public function test_full_clean_flow_items_to_jobs(): void
    {
        $clean = $this->makeClean('product');
        $project = new Project();
        $project->id = 5;
        $clean->setProject($project);

        // Simulate: fetch 100 IDs from shop that are not in our DB
        $shopIds = collect(range(1, 100));

        $clean->items(fn () => $shopIds);
        $clean->chunk(25);

        $jobs = $clean->assignJobs();

        // 100 items / 25 per chunk = 4 jobs
        $this->assertCount(4, $jobs);

        foreach ($jobs as $job) {
            $this->assertInstanceOf(ProcessInstruction::class, $job);
            $this->assertEquals(5, $job->project);
        }
    }

    // --- chaining ---

    public function test_full_chaining(): void
    {
        $project = new Project();
        $project->id = 1;

        $clean = Set::Clean('product')
            ->setProject($project)
            ->items(fn () => collect([1, 2, 3]))
            ->config(['timeout' => 30])
            ->chunk(10);

        $this->assertInstanceOf(Clean::class, $clean);
        $this->assertEquals('product', $clean->getKey());
        $this->assertEquals(10, $clean->get('chunk'));
        $this->assertEquals(['timeout' => 30], $clean->get('config'));
    }
}
