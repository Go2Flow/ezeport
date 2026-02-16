<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Cleaners\ShopwareSix\BaseCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\ProductMediaCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\PropertyOptionCleaner;
use Go2Flow\Ezport\Connectors\ShopwareSix\Api;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\CleanShop;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class BaseCleanerTest extends TestCase
{
    /**
     * Create a concrete test-only subclass of BaseCleaner that doesn't call the API
     */
    private function makeCleaner(array|Collection $database, array $config = []): TestCleaner
    {
        $api = $this->createMock(Api::class);

        return new TestCleaner($api, $database, $config);
    }

    // --- serverDatabaseDifference (CRITICAL: wrong diff = wrong items deleted) ---

    public function test_server_database_difference_returns_items_on_server_not_in_db(): void
    {
        $cleaner = $this->makeCleaner(['db-1', 'db-2', 'db-3']);

        // Shop has items db-1 through db-5, but DB only has db-1 through db-3
        $shopItems = collect(['db-1', 'db-2', 'db-3', 'db-4', 'db-5']);

        $result = $cleaner->testServerDatabaseDifference($shopItems);

        // Only db-4 and db-5 should be marked for deletion
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('db-4'));
        $this->assertTrue($result->contains('db-5'));
    }

    public function test_server_database_difference_empty_when_all_in_db(): void
    {
        $cleaner = $this->makeCleaner(['id-1', 'id-2', 'id-3']);

        $shopItems = collect(['id-1', 'id-2', 'id-3']);

        $result = $cleaner->testServerDatabaseDifference($shopItems);

        $this->assertCount(0, $result);
    }

    public function test_server_database_difference_all_when_db_empty(): void
    {
        $cleaner = $this->makeCleaner([]);

        $shopItems = collect(['id-1', 'id-2']);

        $result = $cleaner->testServerDatabaseDifference($shopItems);

        $this->assertCount(2, $result);
    }

    public function test_server_database_difference_empty_shop_returns_empty(): void
    {
        $cleaner = $this->makeCleaner(['id-1', 'id-2']);

        $result = $cleaner->testServerDatabaseDifference(collect());

        $this->assertCount(0, $result);
    }

    public function test_server_database_difference_preserves_shop_ids_not_in_db(): void
    {
        $cleaner = $this->makeCleaner(['a', 'c', 'e']);

        $shopItems = collect(['a', 'b', 'c', 'd', 'e', 'f']);

        $result = $cleaner->testServerDatabaseDifference($shopItems);

        $this->assertEquals(['b', 'd', 'f'], $result->values()->toArray());
    }

    // --- mapToJobs ---

    public function test_map_to_jobs_creates_clean_shop_jobs(): void
    {
        $cleaner = $this->makeCleaner([]);

        $project = new Project();
        $project->id = 7;

        $items = collect(range(1, 5));

        $jobs = $cleaner->testMapToJobs($items, $project, 'product');

        $this->assertInstanceOf(Collection::class, $jobs);
        $this->assertGreaterThan(0, $jobs->count());

        foreach ($jobs as $job) {
            $this->assertInstanceOf(CleanShop::class, $job);
            $this->assertEquals(7, $job->project);
        }
    }

    public function test_map_to_jobs_respects_chunk_size(): void
    {
        $cleaner = $this->makeCleaner([]);
        $cleaner->setChunkSize(3);

        $project = new Project();
        $project->id = 1;

        $items = collect(range(1, 10));

        $jobs = $cleaner->testMapToJobs($items, $project, 'product');

        // 10 items / chunk of 3 = 4 jobs (3, 3, 3, 1)
        $this->assertCount(4, $jobs);
    }

    public function test_map_to_jobs_filters_out_empty_chunks(): void
    {
        $cleaner = $this->makeCleaner([]);

        $project = new Project();
        $project->id = 1;

        // Empty collection - no jobs should be created
        $jobs = $cleaner->testMapToJobs(collect(), $project, 'product');

        $this->assertCount(0, $jobs);
    }

    public function test_map_to_jobs_single_item(): void
    {
        $cleaner = $this->makeCleaner([]);

        $project = new Project();
        $project->id = 1;

        $jobs = $cleaner->testMapToJobs(collect([42]), $project, 'product');

        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(CleanShop::class, $jobs->first());
    }

    // --- associationArray ---

    public function test_association_array_creates_empty_arrays(): void
    {
        $cleaner = $this->makeCleaner([]);

        $result = $cleaner->testAssociationArray(['products', 'categories', 'media']);

        $this->assertEquals([
            'products' => [],
            'categories' => [],
            'media' => [],
        ], $result);
    }

    public function test_association_array_single(): void
    {
        $cleaner = $this->makeCleaner([]);

        $result = $cleaner->testAssociationArray(['options']);

        $this->assertEquals(['options' => []], $result);
    }

    public function test_association_array_empty(): void
    {
        $cleaner = $this->makeCleaner([]);

        $result = $cleaner->testAssociationArray([]);

        $this->assertEquals([], $result);
    }
}

// --- ProductMediaCleaner difference logic tests ---

class ProductMediaCleanerDiffTest extends TestCase
{
    /**
     * Test the custom serverDatabaseDifference logic in ProductMediaCleaner.
     *
     * ProductMediaCleaner compares shop product-media associations against
     * a database map of productId => [mediaId, mediaId, ...].
     * Items in shop but NOT matching any DB media entry should be flagged for deletion.
     */
    public function test_product_media_difference_detects_orphaned_media(): void
    {
        // DB has product "prod-1" with media ["media-a", "media-b"]
        $database = collect([
            'prod-1' => ['media-a', 'media-b'],
        ]);

        // Shop has a product-media entry for media-c (not in DB) -> should be deleted
        $shopItem = (object) [
            'id' => 'pm-1',
            'productId' => 'prod-1',
            'mediaId' => 'media-c',
        ];

        $api = $this->createMock(Api::class);
        $cleaner = new TestableProductMediaCleaner($api, $database, []);

        $result = $cleaner->testServerDatabaseDifference(collect([$shopItem]));

        $this->assertCount(1, $result);
        $this->assertEquals('pm-1', $result->first());
    }

    public function test_product_media_difference_keeps_matching_media(): void
    {
        $database = collect([
            'prod-1' => ['media-a', 'media-b'],
        ]);

        $shopItem = (object) [
            'id' => 'pm-1',
            'productId' => 'prod-1',
            'mediaId' => 'media-a', // exists in DB
        ];

        $api = $this->createMock(Api::class);
        $cleaner = new TestableProductMediaCleaner($api, $database, []);

        $result = $cleaner->testServerDatabaseDifference(collect([$shopItem]));

        $this->assertCount(0, $result);
    }

    public function test_product_media_difference_unknown_product_returns_null(): void
    {
        $database = collect([
            'prod-1' => ['media-a'],
        ]);

        // Product not in DB at all -> null returned, filtered out
        $shopItem = (object) [
            'id' => 'pm-1',
            'productId' => 'prod-unknown',
            'mediaId' => 'media-x',
        ];

        $api = $this->createMock(Api::class);
        $cleaner = new TestableProductMediaCleaner($api, $database, []);

        $result = $cleaner->testServerDatabaseDifference(collect([$shopItem]));

        // Product not in database at all - nothing returned (filtered to null)
        $this->assertCount(0, $result);
    }

    public function test_product_media_difference_mixed_results(): void
    {
        $database = collect([
            'prod-1' => ['media-a', 'media-b'],
            'prod-2' => ['media-c'],
        ]);

        $shopItems = collect([
            (object) ['id' => 'pm-1', 'productId' => 'prod-1', 'mediaId' => 'media-a'], // match
            (object) ['id' => 'pm-2', 'productId' => 'prod-1', 'mediaId' => 'media-x'], // orphan
            (object) ['id' => 'pm-3', 'productId' => 'prod-2', 'mediaId' => 'media-c'], // match
            (object) ['id' => 'pm-4', 'productId' => 'prod-2', 'mediaId' => 'media-d'], // orphan
        ]);

        $api = $this->createMock(Api::class);
        $cleaner = new TestableProductMediaCleaner($api, $database, []);

        $result = $cleaner->testServerDatabaseDifference($shopItems);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('pm-2'));
        $this->assertTrue($result->contains('pm-4'));
    }
}

// --- PropertyOptionCleaner typeSpecificActions test ---

class PropertyOptionCleanerConfigTest extends TestCase
{
    public function test_group_name_is_renamed_to_name_in_filter(): void
    {
        $api = $this->createMock(Api::class);

        // The constructor calls typeSpecificActions() which renames 'groupName' to 'name'
        $cleaner = new TestablePropertyOptionCleaner(
            $api,
            [],
            ['filter' => ['groupName' => 'Color', 'type' => 'equals', 'field' => 'name']]
        );

        $config = $cleaner->getConfig();

        $this->assertArrayNotHasKey('groupName', $config['filter']);
        $this->assertEquals('Color', $config['filter']['name']);
    }

    public function test_filter_without_group_name_unchanged(): void
    {
        $api = $this->createMock(Api::class);

        $cleaner = new TestablePropertyOptionCleaner(
            $api,
            [],
            ['filter' => ['type' => 'equals', 'field' => 'name', 'value' => 'Size']]
        );

        $config = $cleaner->getConfig();

        $this->assertEquals('Size', $config['filter']['value']);
        $this->assertEquals('equals', $config['filter']['type']);
    }
}

/**
 * Test double exposing protected BaseCleaner methods
 */
class TestCleaner extends BaseCleaner
{
    protected string $type = 'product';
    private int $testChunkSize = 25;

    public function setChunkSize(int $size): void
    {
        $this->chunkSize = $size;
    }

    public function testServerDatabaseDifference(Collection $items): Collection
    {
        return $this->serverDatabaseDifference($items);
    }

    public function testMapToJobs(Collection $items, Project $project, string $type): Collection
    {
        return $this->mapToJobs($items, $project, $type);
    }

    public function testAssociationArray(array $associations): array
    {
        return $this->associationArray($associations);
    }

    protected function typeSpecificActions(): void
    {
        // noop for test
    }
}

/**
 * Test double exposing ProductMediaCleaner's serverDatabaseDifference
 */
class TestableProductMediaCleaner extends ProductMediaCleaner
{
    public function testServerDatabaseDifference(Collection $shopItems): Collection
    {
        return $this->serverDatabaseDifference($shopItems);
    }
}

/**
 * Test double exposing PropertyOptionCleaner's protected config
 */
class TestablePropertyOptionCleaner extends PropertyOptionCleaner
{
    public function getConfig(): array
    {
        return $this->config;
    }
}
