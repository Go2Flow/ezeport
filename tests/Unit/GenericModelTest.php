<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Process\Errors\CircularRelationException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class GenericModelTest extends TestCase
{
    private function makeGenericModel(array $attributes = []): GenericModel
    {
        $model = new GenericModel();

        foreach ($attributes as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    // --- setContentAndRelations ---

    public function test_set_content_and_relations_sets_scalar_data_to_content(): void
    {
        $model = $this->makeGenericModel();

        $model->setContentAndRelations([
            'name' => 'Test Product',
            'sku' => 'SKU-001',
            'price' => '29.99',
        ]);

        $this->assertInstanceOf(Collection::class, $model->content);
        $this->assertEquals('Test Product', $model->content['name']);
        $this->assertEquals('SKU-001', $model->content['sku']);
        $this->assertEquals('29.99', $model->content['price']);
    }

    public function test_set_content_and_relations_sets_generic_collections_to_model_relations(): void
    {
        $model = $this->makeGenericModel();

        $generic1 = $this->createMock(Generic::class);
        $generic2 = $this->createMock(Generic::class);

        $model->setContentAndRelations([
            'categories' => collect([$generic1, $generic2]),
        ]);

        $this->assertInstanceOf(Collection::class, $model->modelRelations);
        $this->assertCount(2, $model->modelRelations['categories']);
    }

    public function test_set_content_and_relations_sets_generic_arrays_to_model_relations(): void
    {
        $model = $this->makeGenericModel();

        $generic1 = $this->createMock(Generic::class);

        $model->setContentAndRelations([
            'categories' => [$generic1],
        ]);

        $this->assertInstanceOf(Collection::class, $model->modelRelations);
        $this->assertArrayHasKey('categories', $model->modelRelations->toArray());
    }

    public function test_set_content_and_relations_merges_with_existing_content(): void
    {
        $model = $this->makeGenericModel();
        $model->content = collect(['existing' => 'value']);

        $model->setContentAndRelations([
            'new_field' => 'new_value',
        ]);

        $this->assertEquals('value', $model->content['existing']);
        $this->assertEquals('new_value', $model->content['new_field']);
    }

    public function test_set_content_and_relations_merges_with_existing_relations(): void
    {
        $model = $this->makeGenericModel();

        $generic1 = $this->createMock(Generic::class);
        $generic2 = $this->createMock(Generic::class);

        $model->modelRelations = collect(['tags' => collect([$generic1])]);

        $model->setContentAndRelations([
            'categories' => [$generic2],
        ]);

        $this->assertArrayHasKey('tags', $model->modelRelations->toArray());
        $this->assertArrayHasKey('categories', $model->modelRelations->toArray());
    }

    public function test_set_content_and_relations_mixed_content_and_relations(): void
    {
        $model = $this->makeGenericModel();

        $generic = $this->createMock(Generic::class);

        $model->setContentAndRelations([
            'name' => 'Product',
            'price' => '10.00',
            'categories' => [$generic],
        ]);

        $this->assertEquals('Product', $model->content['name']);
        $this->assertEquals('10.00', $model->content['price']);
        $this->assertArrayHasKey('categories', $model->modelRelations->toArray());
    }

    // --- findOrCreateModel ---

    public function test_find_or_create_model_fills_without_unique_id(): void
    {
        // When unique_id is not set, findOrCreateModel just fills without querying DB
        $model = $this->makeGenericModel();

        $result = $model->findOrCreateModel([
            'type' => 'category',
            'project_id' => 2,
        ]);

        $this->assertNull($result->unique_id);
        $this->assertEquals('category', $result->type);
        $this->assertEquals(2, $result->project_id);
        $this->assertFalse($result->exists);
    }

    // --- assertNoCircularRelation ---

    public function test_assert_no_circular_relation_passes_for_independent_models(): void
    {
        $parent = $this->makeGenericModel();
        $parent->id = 1;

        $child = $this->makeGenericModel();
        $child->id = 2;

        // Set empty children on child to simulate loaded relation
        $child->setRelation('children', collect());

        // Should not throw
        $parent->assertNoCircularRelation($child);
        $this->assertTrue(true); // If we get here, no exception thrown
    }

    public function test_assert_no_circular_relation_throws_when_circular(): void
    {
        $parent = $this->makeGenericModel();
        $parent->id = 1;

        $child = $this->makeGenericModel();
        $child->id = 2;

        $grandchild = $this->makeGenericModel();
        $grandchild->id = 1; // Same as parent -> circular!

        $grandchild->setRelation('children', collect());
        $child->setRelation('children', collect([$grandchild]));

        $this->expectException(CircularRelationException::class);
        $parent->assertNoCircularRelation($child);
    }

    public function test_assert_no_circular_relation_deep_nesting(): void
    {
        $parent = $this->makeGenericModel();
        $parent->id = 1;

        $child = $this->makeGenericModel();
        $child->id = 2;

        $grandchild = $this->makeGenericModel();
        $grandchild->id = 3;

        $greatGrandchild = $this->makeGenericModel();
        $greatGrandchild->id = 1; // circular back to parent

        $greatGrandchild->setRelation('children', collect());
        $grandchild->setRelation('children', collect([$greatGrandchild]));
        $child->setRelation('children', collect([$grandchild]));

        $this->expectException(CircularRelationException::class);
        $parent->assertNoCircularRelation($child);
    }

    public function test_assert_no_circular_relation_passes_for_deep_non_circular(): void
    {
        $parent = $this->makeGenericModel();
        $parent->id = 1;

        $child = $this->makeGenericModel();
        $child->id = 2;

        $grandchild = $this->makeGenericModel();
        $grandchild->id = 3;

        $greatGrandchild = $this->makeGenericModel();
        $greatGrandchild->id = 4;

        $greatGrandchild->setRelation('children', collect());
        $grandchild->setRelation('children', collect([$greatGrandchild]));
        $child->setRelation('children', collect([$grandchild]));

        $parent->assertNoCircularRelation($child);
        $this->assertTrue(true);
    }

    // --- toContentType ---

    public function test_to_content_type_returns_generic_instance(): void
    {
        $model = $this->makeGenericModel([
            'type' => 'product',
            'unique_id' => 'p-1',
            'project_id' => 1,
        ]);
        $model->setRelation('children', collect());

        $result = $model->toContentType();

        $this->assertInstanceOf(Generic::class, $result);
    }

    public function test_to_content_type_with_false_skips_relation_loading(): void
    {
        $model = $this->makeGenericModel([
            'type' => 'product',
            'unique_id' => 'p-1',
        ]);
        $model->setRelation('children', collect());

        // [false] means don't load children's descendants
        $result = $model->toContentType([false]);

        $this->assertInstanceOf(Generic::class, $result);
    }
}
