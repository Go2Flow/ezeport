<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\Transform;
use Go2Flow\Ezport\Process\Errors\EzportSetterException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class TransformTest extends TestCase
{
    // --- construction ---

    public function test_transform_has_correct_key(): void
    {
        $transform = Set::Transform('products');

        $this->assertEquals('product', $transform->getKey());
    }

    // --- prepare/process/relation closures ---

    public function test_process_adds_closure(): void
    {
        $transform = Set::Transform('product');

        $result = $transform->process(fn ($item) => $item);

        $this->assertSame($transform, $result);
    }

    public function test_multiple_processes_can_be_added(): void
    {
        $transform = Set::Transform('product');

        $transform->process(fn ($item) => null);
        $transform->process(fn ($item) => null);
        $transform->process(fn ($item) => null);

        // Processes are stored internally - we verify via the get method
        $processes = $transform->get('processes');
        $this->assertInstanceOf(Collection::class, $processes);
        $this->assertCount(3, $processes);
    }

    public function test_processes_replaces_all(): void
    {
        $transform = Set::Transform('product');

        $transform->process(fn () => 'a');
        $transform->process(fn () => 'b');
        $transform->processes([fn () => 'x']);

        $processes = $transform->get('processes');
        $this->assertCount(1, $processes);
    }

    public function test_relation_adds_closure(): void
    {
        $transform = Set::Transform('product');

        $result = $transform->relation(fn ($item) => collect());

        $this->assertSame($transform, $result);
    }

    public function test_multiple_relations_can_be_added(): void
    {
        $transform = Set::Transform('product');

        $transform->relation(fn () => collect());
        $transform->relation(fn () => collect());

        $relations = $transform->get('relations');
        $this->assertCount(2, $relations);
    }

    public function test_relations_replaces_all(): void
    {
        $transform = Set::Transform('product');

        $transform->relation(fn () => collect());
        $transform->relation(fn () => collect());
        $transform->relations([fn () => collect()]);

        $relations = $transform->get('relations');
        $this->assertCount(1, $relations);
    }

    // --- chunk ---

    public function test_chunk_default_is_50(): void
    {
        $transform = Set::Transform('product');

        $this->assertEquals(50, $transform->get('chunk'));
    }

    public function test_chunk_can_be_set(): void
    {
        $transform = Set::Transform('product');

        $transform->chunk(100);

        $this->assertEquals(100, $transform->get('chunk'));
    }

    // --- dontSave ---

    public function test_should_save_is_true_by_default(): void
    {
        $transform = Set::Transform('product');

        $this->assertTrue($transform->get('shouldSave'));
    }

    public function test_dont_save_sets_false(): void
    {
        $transform = Set::Transform('product');

        $transform->dontSave();

        $this->assertFalse($transform->get('shouldSave'));
    }

    // --- pluck without prepare ---

    public function test_pluck_without_prepare_sets_items_to_null(): void
    {
        $transform = Set::Transform('product');

        $transform->pluck();

        $this->assertNull($transform->get('items'));
    }

    // --- pluck with collection prepare ---

    public function test_pluck_with_collection_prepare_stores_items(): void
    {
        $transform = Set::Transform('product');

        $transform->prepare(fn () => collect([1, 2, 3]));
        $transform->pluck();

        $items = $transform->get('items');
        $this->assertInstanceOf(Collection::class, $items);
        $this->assertEquals([1, 2, 3], $items->toArray());
    }

    // --- pluck with invalid prepare ---

    public function test_pluck_with_invalid_prepare_throws(): void
    {
        $transform = Set::Transform('product');

        $transform->prepare(fn () => 'invalid');

        $this->expectException(EzportSetterException::class);
        $transform->pluck();
    }

    // --- config closure ---

    public function test_config_closure_is_stored(): void
    {
        $transform = Set::Transform('product');

        $transform->config(fn () => collect(['key' => 'value']));

        $this->assertTrue($transform->has('config'));
    }

    // --- chaining ---

    public function test_full_chaining(): void
    {
        $transform = Set::Transform('product')
            ->prepare(fn () => collect([1, 2, 3]))
            ->process(fn ($item) => $item)
            ->relation(fn ($item) => collect())
            ->config(fn () => collect())
            ->chunk(25)
            ->dontSave();

        $this->assertInstanceOf(Transform::class, $transform);
        $this->assertEquals(25, $transform->get('chunk'));
        $this->assertFalse($transform->get('shouldSave'));
    }

    // --- construction with config array ---

    public function test_construction_with_prepare_in_config(): void
    {
        $transform = new Transform('product', [
            'prepare' => fn () => collect([1, 2]),
        ]);

        $transform->pluck();
        $items = $transform->get('items');
        $this->assertEquals([1, 2], $items->toArray());
    }

    public function test_construction_with_non_callable_throws(): void
    {
        $this->expectException(\Exception::class);

        new Transform('product', [
            'prepare' => 'not a closure',
        ]);
    }
}
