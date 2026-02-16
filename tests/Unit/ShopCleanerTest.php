<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Cleaners\ShopwareSix\CrossSellingCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\ManufacturerCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\MediaCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\ProductCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\ProductMediaCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\PropertyOptionCleaner;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\ShopCleaner;
use Go2Flow\Ezport\Process\Jobs\AssignClean;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ShopCleanerTest extends TestCase
{
    // --- construction ---

    public function test_creates_shop_cleaner_instance(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $this->assertInstanceOf(ShopCleaner::class, $cleaner);
    }

    public function test_default_job_is_assign_clean(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $job = $cleaner->get('job');
        $this->assertEquals(AssignClean::class, $job->getJob());
    }

    // --- type mapping (CRITICAL: wrong mapping = wrong data deleted!) ---

    public function test_type_mapping_product(): void
    {
        $cleaner = Set::ShopCleaner('product', null, ['type' => 'product']);

        $this->assertEquals(ProductCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_product_from_key(): void
    {
        // When no 'type' in config, key is used
        $cleaner = Set::ShopCleaner('product');

        $this->assertEquals(ProductCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_products_plural(): void
    {
        $cleaner = Set::ShopCleaner('products');

        $this->assertEquals(ProductCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_media(): void
    {
        $cleaner = Set::ShopCleaner('media', null, ['type' => 'Media']);

        $this->assertEquals(MediaCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_media_from_key(): void
    {
        $cleaner = Set::ShopCleaner('Media');

        $this->assertEquals(MediaCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_product_media(): void
    {
        $cleaner = Set::ShopCleaner('productMedia', null, ['type' => 'ProductMedia']);

        $this->assertEquals(ProductMediaCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_manufacturer(): void
    {
        $cleaner = Set::ShopCleaner('manufacturer', null, ['type' => 'Manufacturer']);

        $this->assertEquals(ManufacturerCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_manufacturers_plural(): void
    {
        $cleaner = Set::ShopCleaner('manufacturers', null, ['type' => 'manufacturers']);

        $this->assertEquals(ManufacturerCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_cross_selling(): void
    {
        $cleaner = Set::ShopCleaner('crossSelling', null, ['type' => 'CrossSelling']);

        $this->assertEquals(CrossSellingCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_cross_sellings_plural(): void
    {
        $cleaner = Set::ShopCleaner('crossSellings', null, ['type' => 'CrossSellings']);

        $this->assertEquals(CrossSellingCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_property_option_default(): void
    {
        // Anything that doesn't match known types falls to PropertyOptionCleaner
        $cleaner = Set::ShopCleaner('color', null, ['type' => 'Color']);

        $this->assertEquals(PropertyOptionCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_property_option_explicit(): void
    {
        $cleaner = Set::ShopCleaner('size', null, ['type' => 'Size']);

        $this->assertEquals(PropertyOptionCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_material_falls_to_property_option(): void
    {
        $cleaner = Set::ShopCleaner('material', null, ['type' => 'material']);

        $this->assertEquals(PropertyOptionCleaner::class, $cleaner->get('type'));
    }

    // --- type method changes mapping ---

    public function test_type_method_updates_type(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $this->assertEquals(ProductCleaner::class, $cleaner->get('type'));

        $cleaner->type('Manufacturer');

        $this->assertEquals(ManufacturerCleaner::class, $cleaner->get('type'));
    }

    public function test_type_method_returns_self(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $result = $cleaner->type('Media');

        $this->assertSame($cleaner, $result);
    }

    // --- items ---

    public function test_items_stores_closure(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $result = $cleaner->items(fn () => collect([1, 2, 3]));

        $this->assertSame($cleaner, $result);
    }

    // --- prepareItems ---

    public function test_prepare_items_executes_closure(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $cleaner->items(fn () => collect(['id-1', 'id-2']));
        $cleaner->prepareItems();

        $items = $cleaner->get('items');
        $this->assertEquals(['id-1', 'id-2'], $items->toArray());
    }

    public function test_prepare_items_returns_self(): void
    {
        $cleaner = Set::ShopCleaner('product');
        $cleaner->items(fn () => collect());

        $result = $cleaner->prepareItems();

        $this->assertSame($cleaner, $result);
    }

    // --- filter ---

    public function test_filter_sets_config_filter(): void
    {
        $cleaner = Set::ShopCleaner('color');

        $cleaner->filter(['value' => 'Red']);

        $config = $this->getPrivateConfig($cleaner);
        $this->assertArrayHasKey('filter', $config);

        $filter = $config['filter'];
        $this->assertEquals('equals', $filter['type']);
        $this->assertEquals('name', $filter['field']);
        $this->assertEquals('Red', $filter['value']);
    }

    public function test_filter_converts_group_name_to_value(): void
    {
        $cleaner = Set::ShopCleaner('color');

        $cleaner->filter(['GroupName' => 'Color Group']);

        $config = $this->getPrivateConfig($cleaner);
        $filter = $config['filter'];

        // GroupName should be converted to 'value' key
        $this->assertEquals('Color Group', $filter['value']);
        $this->assertArrayNotHasKey('GroupName', $filter);
    }

    public function test_filter_returns_self(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $result = $cleaner->filter(['value' => 'test']);

        $this->assertSame($cleaner, $result);
    }

    // --- config key is passed to config ---

    public function test_config_includes_key(): void
    {
        $cleaner = Set::ShopCleaner('product');

        $config = $this->getPrivateConfig($cleaner);

        $this->assertArrayHasKey('key', $config);
        $this->assertEquals('product', $config['key']);
    }

    private function getPrivateConfig(ShopCleaner $cleaner): array
    {
        $ref = new \ReflectionClass($cleaner);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);

        return $prop->getValue($cleaner);
    }

    // --- chaining ---

    public function test_full_chaining(): void
    {
        $cleaner = Set::ShopCleaner('product')
            ->items(fn () => collect([1, 2, 3]))
            ->type('Media')
            ->filter(['value' => 'test']);

        $this->assertInstanceOf(ShopCleaner::class, $cleaner);
        $this->assertEquals(MediaCleaner::class, $cleaner->get('type'));
    }

    // --- type mapping edge cases ---

    public function test_type_mapping_is_case_sensitive_for_media_string_check(): void
    {
        // 'Media' (uppercase M) should match the direct string check
        $cleaner = Set::ShopCleaner('test', null, ['type' => 'Media']);
        $this->assertEquals(MediaCleaner::class, $cleaner->get('type'));
    }

    public function test_type_mapping_product_media_string_check(): void
    {
        // 'ProductMedia' should match the direct string check
        $cleaner = Set::ShopCleaner('test', null, ['type' => 'ProductMedia']);
        $this->assertEquals(ProductMediaCleaner::class, $cleaner->get('type'));
    }
}
