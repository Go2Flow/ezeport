<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    private function makeGeneric(array $content = [], array $shop = []): Generic
    {
        $model = new GenericModel();
        $model->type = 'product';
        $model->unique_id = 'p-1';
        $model->project_id = 1;
        $model->content = collect($content);
        $model->shop = collect($shop);
        $model->setRelation('children', collect());

        return new Generic($model);
    }

    private function makeUpload(string $key = 'product'): Upload
    {
        $upload = Set::Upload($key);
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);

        return $upload;
    }

    // --- toShopArray ---

    public function test_to_shop_array_with_static_fields(): void
    {
        $upload = $this->makeUpload();

        $upload->field(Set::UploadField('name')->field('Static Name'));
        $upload->field(Set::UploadField('active')->field(true));

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals([
            'name' => 'Static Name',
            'active' => true,
        ], $result);
    }

    public function test_to_shop_array_with_closure_fields(): void
    {
        $upload = $this->makeUpload();

        $upload->field(
            Set::UploadField('productName')
                ->field(fn (Generic $item) => $item->properties('name'))
        );
        $upload->field(
            Set::UploadField('sku')
                ->field(fn (Generic $item) => $item->properties('sku'))
        );

        $generic = $this->makeGeneric(['name' => 'Test Product', 'sku' => 'SKU-123']);
        $result = $upload->toShopArray($generic);

        $this->assertEquals([
            'productName' => 'Test Product',
            'sku' => 'SKU-123',
        ], $result);
    }

    public function test_to_shop_array_filters_null_values_by_default(): void
    {
        $upload = $this->makeUpload();

        $upload->field(Set::UploadField('name')->field('Present'));
        $upload->field(Set::UploadField('absent')->field(fn () => null));

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('absent', $result);
    }

    public function test_to_shop_array_with_config_propagation(): void
    {
        $upload = $this->makeUpload();

        // First field sets a config value
        $upload->field(
            Set::UploadField('price')
                ->field(fn () => ['array' => '19.99', 'config' => ['currency' => 'EUR']])
        );

        // Second field reads from config
        $upload->field(
            Set::UploadField('currency')
                ->field(fn (Generic $item, array $config) => $config['currency'] ?? 'USD')
        );

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals('19.99', $result['price']);
        $this->assertEquals('EUR', $result['currency']);
    }

    public function test_to_shop_array_with_null_key_field_spreads_into_result(): void
    {
        $upload = $this->makeUpload();

        $upload->field(
            Set::UploadField()->field(fn () => ['key1' => 'val1', 'key2' => 'val2'])
        );

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals([
            'key1' => 'val1',
            'key2' => 'val2',
        ], $result);
    }

    public function test_to_shop_array_config_does_not_leak_between_calls(): void
    {
        $upload = $this->makeUpload();

        $upload->field(
            Set::UploadField('price')
                ->field(fn () => ['array' => '10.00', 'config' => ['tax' => '19']])
        );
        $upload->field(
            Set::UploadField('tax')
                ->field(fn (Generic $item, array $config) => $config['tax'] ?? 'none')
        );

        $generic1 = $this->makeGeneric();
        $generic2 = $this->makeGeneric();

        $result1 = $upload->toShopArray($generic1);
        $result2 = $upload->toShopArray($generic2);

        // Both should produce same output since config is reset between calls
        $this->assertEquals($result1, $result2);
        $this->assertEquals('19', $result1['tax']);
    }

    // --- field management ---

    public function test_fields_adds_multiple_fields(): void
    {
        $upload = $this->makeUpload();

        $upload->fields([
            Set::UploadField('a')->field('A'),
            Set::UploadField('b')->field('B'),
            Set::UploadField('c')->field('C'),
        ]);

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals(['a' => 'A', 'b' => 'B', 'c' => 'C'], $result);
    }

    public function test_drop_fields_removes_all(): void
    {
        $upload = $this->makeUpload();

        $upload->field(Set::UploadField('name')->field('Test'));
        $upload->dropFields();

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEmpty($result);
    }

    public function test_drop_field_removes_specific(): void
    {
        $upload = $this->makeUpload();

        $upload->field(Set::UploadField('name')->field('Test'));
        $upload->field(Set::UploadField('sku')->field('SKU'));
        $upload->dropField('name');

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertArrayNotHasKey('name', $result);
        $this->assertEquals('SKU', $result['sku']);
    }

    public function test_duplicate_key_replaces_existing_field(): void
    {
        $upload = $this->makeUpload();

        $upload->field(Set::UploadField('name')->field('First'));
        $upload->field(Set::UploadField('name')->field('Second'));

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals('Second', $result['name']);
    }

    // --- field shorthand (array syntax) ---

    public function test_field_accepts_array_shorthand(): void
    {
        $upload = $this->makeUpload();

        $upload->field(['myField' => 'myValue']);

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals(['myField' => 'myValue'], $result);
    }

    public function test_field_array_shorthand_with_closure(): void
    {
        $upload = $this->makeUpload();

        $upload->field(['productName' => fn (Generic $item) => $item->properties('name')]);

        $generic = $this->makeGeneric(['name' => 'Test']);
        $result = $upload->toShopArray($generic);

        $this->assertEquals(['productName' => 'Test'], $result);
    }

    // --- config ---

    public function test_config_merges_with_existing(): void
    {
        $upload = $this->makeUpload();

        $upload->config(['key1' => 'val1']);
        $upload->config(['key2' => 'val2']);

        $upload->field(
            Set::UploadField('combined')
                ->field(fn (Generic $item, array $config) => $config['key1'] . '-' . $config['key2'])
        );

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals('val1-val2', $result['combined']);
    }

    // --- components ---

    public function test_upload_field_component_is_null_by_default(): void
    {
        // The UploadField closure's third param is its own $component property (not Upload's components)
        $upload = $this->makeUpload();

        $upload->field(
            Set::UploadField('componentTest')
                ->field(fn (Generic $item, array $config, $component) =>
                    $component === null ? 'null' : 'set'
                )
        );

        $result = $upload->toShopArray($this->makeGeneric());

        $this->assertEquals('null', $result['componentTest']);
    }

    // --- chunk ---

    public function test_chunk_returns_self_for_chaining(): void
    {
        $upload = $this->makeUpload();

        $result = $upload->chunk(50);

        $this->assertSame($upload, $result);
    }

    // --- getKey ---

    public function test_upload_key_is_processed(): void
    {
        $upload = Set::Upload('products');
        $this->assertEquals('product', $upload->getKey());

        $upload2 = Set::Upload('Category');
        $this->assertEquals('category', $upload2->getKey());

        $upload3 = Set::Upload('media');
        $this->assertEquals('media', $upload3->getKey());
    }

    // --- complex real-world-like scenarios ---

    public function test_to_shop_array_real_world_product_upload(): void
    {
        $generic = $this->makeGeneric([
            'name' => 'Widget Pro',
            'sku' => 'WP-100',
            'price' => '49.99',
            'description' => 'A professional widget',
            'active' => true,
            'stock' => 150,
        ], [
            'shopware_id' => 'abc-def-123',
        ]);

        $upload = $this->makeUpload();

        $upload->fields([
            Set::UploadField('id')->field(fn (Generic $item) => $item->shop('shopware_id')),
            Set::UploadField('name')->field(fn (Generic $item) => $item->properties('name')),
            Set::UploadField('productNumber')->field(fn (Generic $item) => $item->properties('sku')),
            Set::UploadField('price')->field(fn (Generic $item) => [
                ['net' => (float) $item->properties('price'), 'gross' => (float) $item->properties('price') * 1.19],
            ]),
            Set::UploadField('active')->field(fn (Generic $item) => $item->properties('active')),
            Set::UploadField('stock')->field(fn (Generic $item) => $item->properties('stock')),
            Set::UploadField('description')->field(fn (Generic $item) => $item->properties('description')),
        ]);

        $result = $upload->toShopArray($generic);

        $this->assertEquals('abc-def-123', $result['id']);
        $this->assertEquals('Widget Pro', $result['name']);
        $this->assertEquals('WP-100', $result['productNumber']);
        $this->assertEquals([['net' => 49.99, 'gross' => 49.99 * 1.19]], $result['price']);
        $this->assertTrue($result['active']);
        $this->assertEquals(150, $result['stock']);
        $this->assertEquals('A professional widget', $result['description']);
    }

    public function test_components_are_stored_on_upload(): void
    {
        $categoryUpload = $this->makeUpload('category');
        $categoryUpload->field(Set::UploadField('catName')->field('Electronics'));

        $upload = $this->makeUpload();
        $upload->component($categoryUpload);

        // Verify components are stored and accessible via get
        $components = $upload->get('components');
        $this->assertArrayHasKey('category', $components);
        $this->assertSame($categoryUpload, $components['category']);
    }

    public function test_multiple_components(): void
    {
        $catUpload = $this->makeUpload('category');
        $tagUpload = $this->makeUpload('tag');

        $upload = $this->makeUpload();
        $upload->components([$catUpload, $tagUpload]);

        $components = $upload->get('components');
        $this->assertCount(2, $components);
        $this->assertArrayHasKey('category', $components);
        $this->assertArrayHasKey('tag', $components);
    }
}
