<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Models\BaseModel;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class BaseModelTest extends TestCase
{
    private function makeModel(array $properties = []): BaseModel
    {
        $model = new class extends BaseModel {
            protected $guarded = [];
            public $content;
            public $shop;
            public $modelRelations;
        };

        foreach ($properties as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    // --- getOrSetData: READ mode (null input) ---

    public function test_get_or_set_data_returns_empty_collection_when_property_is_null(): void
    {
        $model = $this->makeModel();

        $result = $model->getOrSetData(null, 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_get_or_set_data_returns_collection_of_existing_data(): void
    {
        $model = $this->makeModel([
            'content' => collect(['name' => 'Test', 'sku' => 'ABC-123']),
        ]);

        $result = $model->getOrSetData(null, 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals('Test', $result['name']);
        $this->assertEquals('ABC-123', $result['sku']);
    }

    // --- getOrSetData: READ mode (string input) ---

    public function test_get_or_set_data_returns_value_for_string_key(): void
    {
        $model = $this->makeModel([
            'content' => collect(['name' => 'Test Product', 'price' => '19.99']),
        ]);

        $this->assertEquals('Test Product', $model->getOrSetData('name', 'content'));
        $this->assertEquals('19.99', $model->getOrSetData('price', 'content'));
    }

    public function test_get_or_set_data_returns_null_for_missing_key(): void
    {
        $model = $this->makeModel([
            'content' => collect(['name' => 'Test']),
        ]);

        $this->assertNull($model->getOrSetData('nonexistent', 'content'));
    }

    public function test_get_or_set_data_returns_collection_for_array_value(): void
    {
        $model = $this->makeModel([
            'content' => collect(['tags' => ['red', 'blue', 'green']]),
        ]);

        $result = $model->getOrSetData('tags', 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(['red', 'blue', 'green'], $result->toArray());
    }

    // --- getOrSetData: singular/plural lookup ---

    public function test_get_or_set_data_singular_key_gets_first_from_plural_collection(): void
    {
        $model = $this->makeModel([
            'content' => collect(['categories' => collect(['Cat A', 'Cat B', 'Cat C'])]),
        ]);

        $result = $model->getOrSetData('category', 'content');

        $this->assertEquals('Cat A', $result);
    }

    public function test_get_or_set_data_singular_key_gets_first_from_plural_array(): void
    {
        $model = $this->makeModel([
            'content' => collect(['categories' => ['Cat A', 'Cat B']]),
        ]);

        $result = $model->getOrSetData('category', 'content');

        $this->assertEquals('Cat A', $result);
    }

    public function test_get_or_set_data_returns_null_when_singular_and_plural_missing(): void
    {
        $model = $this->makeModel([
            'content' => collect(['name' => 'Test']),
        ]);

        $this->assertNull($model->getOrSetData('color', 'content'));
    }

    // --- getOrSetData: WRITE mode (array input) ---

    public function test_get_or_set_data_sets_data_from_array_when_property_is_null(): void
    {
        $model = $this->makeModel();

        $result = $model->getOrSetData(['name' => 'New'], 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals('New', $result['name']);
        $this->assertEquals('New', $model->content['name']);
    }

    public function test_get_or_set_data_merges_array_with_existing_data(): void
    {
        $model = $this->makeModel([
            'content' => collect(['name' => 'Old', 'sku' => 'SKU-1']),
        ]);

        $result = $model->getOrSetData(['name' => 'Updated', 'color' => 'red'], 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals('Updated', $result['name']);
        $this->assertEquals('SKU-1', $result['sku']);
        $this->assertEquals('red', $result['color']);
    }

    // --- getOrSetData: WRITE mode (Collection input) ---

    public function test_get_or_set_data_sets_data_from_collection_when_property_is_null(): void
    {
        $model = $this->makeModel();

        $result = $model->getOrSetData(collect(['name' => 'Via Collection']), 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals('Via Collection', $result['name']);
    }

    public function test_get_or_set_data_merges_collection_with_existing_data(): void
    {
        $model = $this->makeModel([
            'content' => collect(['name' => 'Original', 'sku' => 'S1']),
        ]);

        $result = $model->getOrSetData(collect(['name' => 'Merged', 'new_key' => 'val']), 'content');

        $this->assertEquals('Merged', $result['name']);
        $this->assertEquals('S1', $result['sku']);
        $this->assertEquals('val', $result['new_key']);
    }

    // --- getOrSetData: works across different properties ---

    public function test_get_or_set_data_works_on_shop_property(): void
    {
        $model = $this->makeModel([
            'shop' => collect(['shopware_id' => 'abc123']),
        ]);

        $this->assertEquals('abc123', $model->getOrSetData('shopware_id', 'shop'));

        $model->getOrSetData(['new_shop_field' => 'value'], 'shop');
        $this->assertEquals('value', $model->shop['new_shop_field']);
    }

    public function test_get_or_set_data_works_on_model_relations_property(): void
    {
        $model = $this->makeModel();

        $model->getOrSetData(['categories' => collect(['cat1', 'cat2'])], 'modelRelations');

        $this->assertInstanceOf(Collection::class, $model->modelRelations);
        $this->assertEquals(['cat1', 'cat2'], $model->modelRelations['categories']->toArray());
    }

    // --- Edge cases ---

    public function test_get_or_set_data_with_false_input_returns_collection(): void
    {
        $model = $this->makeModel([
            'content' => collect(['key' => 'val']),
        ]);

        // false is falsy, should trigger read mode
        $result = $model->getOrSetData(false, 'content');

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_get_or_set_data_with_empty_string_is_falsy(): void
    {
        $model = $this->makeModel([
            'content' => collect(['name' => 'Test']),
        ]);

        // empty string is falsy in PHP, so it triggers read-all mode (same as null/false)
        $result = $model->getOrSetData('', 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals('Test', $result['name']);
    }

    public function test_get_or_set_data_preserves_nested_arrays(): void
    {
        $model = $this->makeModel([
            'content' => collect([
                'meta' => ['title' => 'Hello', 'description' => 'World'],
            ]),
        ]);

        $result = $model->getOrSetData('meta', 'content');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals('Hello', $result['title']);
        $this->assertEquals('World', $result['description']);
    }

    public function test_get_or_set_data_successive_merges(): void
    {
        $model = $this->makeModel();

        $model->getOrSetData(['a' => 1], 'content');
        $model->getOrSetData(['b' => 2], 'content');
        $model->getOrSetData(['c' => 3], 'content');

        $this->assertEquals(1, $model->content['a']);
        $this->assertEquals(2, $model->content['b']);
        $this->assertEquals(3, $model->content['c']);
    }
}
