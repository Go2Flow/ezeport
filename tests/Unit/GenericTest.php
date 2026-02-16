<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Process\Errors\EzportContentTypeException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class GenericTest extends TestCase
{
    private function makeGenericModel(array $attributes = [], array $relations = []): GenericModel
    {
        $model = new GenericModel();

        foreach ($attributes as $key => $value) {
            $model->$key = $value;
        }

        foreach ($relations as $key => $value) {
            $model->setRelation($key, $value);
        }

        return $model;
    }

    private function makeGeneric(array $attributes = [], array $relations = []): Generic
    {
        return new Generic($this->makeGenericModel($attributes, $relations));
    }

    // --- getType ---

    public function test_get_type_returns_model_type(): void
    {
        $generic = $this->makeGeneric(['type' => 'product']);

        $this->assertEquals('product', $generic->getType());
    }

    public function test_get_type_for_category(): void
    {
        $generic = $this->makeGeneric(['type' => 'category']);

        $this->assertEquals('category', $generic->getType());
    }

    // --- properties ---

    public function test_properties_returns_empty_collection_when_content_is_null(): void
    {
        $generic = $this->makeGeneric();

        $result = $generic->properties();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_properties_returns_all_content_when_null_input(): void
    {
        $generic = $this->makeGeneric([
            'content' => collect(['name' => 'Test', 'sku' => 'ABC']),
        ]);

        $result = $generic->properties();

        $this->assertEquals('Test', $result['name']);
        $this->assertEquals('ABC', $result['sku']);
    }

    public function test_properties_returns_single_value_by_string_key(): void
    {
        $generic = $this->makeGeneric([
            'content' => collect(['name' => 'Test Product']),
        ]);

        $this->assertEquals('Test Product', $generic->properties('name'));
    }

    public function test_properties_sets_data_with_array_input(): void
    {
        $generic = $this->makeGeneric();

        $generic->properties(['name' => 'New', 'sku' => '123']);

        $this->assertEquals('New', $generic->properties('name'));
        $this->assertEquals('123', $generic->properties('sku'));
    }

    public function test_properties_merges_with_existing(): void
    {
        $generic = $this->makeGeneric([
            'content' => collect(['name' => 'Old']),
        ]);

        $generic->properties(['sku' => 'NEW-SKU']);

        $this->assertEquals('Old', $generic->properties('name'));
        $this->assertEquals('NEW-SKU', $generic->properties('sku'));
    }

    // --- relations ---

    public function test_relations_returns_empty_collection_when_empty(): void
    {
        $model = $this->makeGenericModel();
        $model->modelRelations = collect();
        $generic = new Generic($model);

        $result = $generic->relations();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_relations_sets_and_gets_data(): void
    {
        $generic = $this->makeGeneric();

        $child1 = $this->makeGeneric(['type' => 'category', 'unique_id' => 'cat-1']);
        $child2 = $this->makeGeneric(['type' => 'category', 'unique_id' => 'cat-2']);

        $generic->relations(['categories' => collect([$child1, $child2])]);

        $result = $generic->relations('categories');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    // --- shop ---

    public function test_shop_returns_empty_collection_when_null(): void
    {
        $generic = $this->makeGeneric();

        $result = $generic->shop();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_shop_gets_single_value(): void
    {
        $generic = $this->makeGeneric([
            'shop' => collect(['shopware_id' => 'abc123']),
        ]);

        $this->assertEquals('abc123', $generic->shop('shopware_id'));
    }

    public function test_shop_sets_data(): void
    {
        $generic = $this->makeGeneric();

        $generic->shop(['shopware_id' => 'xyz']);

        $this->assertEquals('xyz', $generic->shop('shopware_id'));
    }

    // --- setContentAndRelations ---

    public function test_set_content_and_relations_strips_unique_id(): void
    {
        $generic = $this->makeGeneric();

        $generic->setContentAndRelations([
            'unique_id' => 'should-be-removed',
            'name' => 'Product',
        ]);

        $this->assertEquals('Product', $generic->properties('name'));
        // unique_id should have been forgotten from the data
        $this->assertNull($generic->properties('unique_id'));
    }

    public function test_set_content_and_relations_with_scalar_values(): void
    {
        $generic = $this->makeGeneric();

        $generic->setContentAndRelations([
            'name' => 'Test',
            'price' => '19.99',
            'active' => true,
        ]);

        $this->assertEquals('Test', $generic->properties('name'));
        $this->assertEquals('19.99', $generic->properties('price'));
        $this->assertTrue($generic->properties('active'));
    }

    public function test_set_content_and_relations_returns_self(): void
    {
        $generic = $this->makeGeneric();

        $result = $generic->setContentAndRelations(['name' => 'Test']);

        $this->assertSame($generic, $result);
    }

    // --- exists ---

    public function test_exists_returns_false_for_new_model(): void
    {
        $generic = $this->makeGeneric();

        $this->assertFalse($generic->exists());
    }

    // --- getModel ---

    public function test_get_model_returns_underlying_generic_model(): void
    {
        $model = $this->makeGenericModel(['type' => 'product']);
        $generic = new Generic($model);

        $this->assertSame($model, $generic->getModel());
    }

    // --- __get magic ---

    public function test_magic_get_proxies_to_model(): void
    {
        $generic = $this->makeGeneric([
            'type' => 'product',
            'unique_id' => 'p-123',
        ]);

        $this->assertEquals('product', $generic->type);
        $this->assertEquals('p-123', $generic->unique_id);
    }

    // --- propertiesForget ---

    public function test_properties_forget_removes_key(): void
    {
        $generic = $this->makeGeneric([
            'content' => collect(['name' => 'Test', 'sku' => 'ABC']),
        ]);

        $generic->propertiesForget('name');

        $this->assertNull($generic->properties('name'));
        $this->assertEquals('ABC', $generic->properties('sku'));
    }

    public function test_properties_forget_returns_self(): void
    {
        $generic = $this->makeGeneric([
            'content' => collect(['name' => 'Test']),
        ]);

        $result = $generic->propertiesForget('name');

        $this->assertSame($generic, $result);
    }

    // --- shopForget ---

    public function test_shop_forget_removes_key(): void
    {
        $generic = $this->makeGeneric([
            'shop' => collect(['id' => '123', 'url' => 'example.com']),
        ]);

        $generic->shopForget('id');

        $this->assertNull($generic->shop('id'));
        $this->assertEquals('example.com', $generic->shop('url'));
    }

    // --- processRelations returns self ---

    public function test_process_relations_returns_self(): void
    {
        $model = $this->makeGenericModel(['content' => null]);
        $generic = new Generic($model);

        $result = $generic->processRelations();

        $this->assertSame($generic, $result);
    }

    // --- chaining ---

    public function test_set_content_and_relations_chaining(): void
    {
        $generic = $this->makeGeneric([
            'content' => collect(['existing' => 'data']),
        ]);

        $result = $generic
            ->setContentAndRelations(['name' => 'Chained'])
            ->setContentAndRelations(['price' => '5.00']);

        $this->assertEquals('data', $result->properties('existing'));
        $this->assertEquals('Chained', $result->properties('name'));
        $this->assertEquals('5.00', $result->properties('price'));
    }
}
