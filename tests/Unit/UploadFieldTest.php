<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Go2Flow\Ezport\Models\GenericModel;
use PHPUnit\Framework\TestCase;

class UploadFieldTest extends TestCase
{
    private function makeGeneric(array $content = []): Generic
    {
        $model = new GenericModel();
        $model->type = 'product';
        $model->content = collect($content);
        $model->setRelation('children', collect());

        return new Generic($model);
    }

    // --- process with static value ---

    public function test_process_static_value_returns_key_value_array(): void
    {
        $field = new UploadField('name');
        $field->field('static_value');

        $result = $field->process($this->makeGeneric(), []);

        $this->assertEquals([
            'key' => 'name',
            'value' => 'static_value',
        ], $result);
    }

    public function test_process_static_integer_value(): void
    {
        $field = new UploadField('quantity');
        $field->field(42);

        $result = $field->process($this->makeGeneric(), []);

        $this->assertEquals([
            'key' => 'quantity',
            'value' => 42,
        ], $result);
    }

    public function test_process_static_boolean_value(): void
    {
        $field = new UploadField('active');
        $field->field(true);

        $result = $field->process($this->makeGeneric(), []);

        $this->assertEquals([
            'key' => 'active',
            'value' => true,
        ], $result);
    }

    // --- process with closure ---

    public function test_process_closure_receives_item_and_config(): void
    {
        $generic = $this->makeGeneric(['name' => 'Test Product']);

        $field = new UploadField('productName');
        $field->field(fn (Generic $item, array $config) => $item->properties('name'));

        $result = $field->process($generic, []);

        $this->assertEquals([
            'key' => 'productName',
            'value' => 'Test Product',
        ], $result);
    }

    public function test_process_closure_uses_config(): void
    {
        $generic = $this->makeGeneric(['price' => '10.00']);

        $field = new UploadField('finalPrice');
        $field->field(fn (Generic $item, array $config) => (float) $item->properties('price') * ($config['multiplier'] ?? 1));

        $result = $field->process($generic, ['multiplier' => 2]);

        $this->assertEquals([
            'key' => 'finalPrice',
            'value' => 20.0,
        ], $result);
    }

    // --- process with null key ---

    public function test_process_with_null_key(): void
    {
        $field = new UploadField();
        $field->field(fn () => ['nested_key' => 'nested_value']);

        $result = $field->process($this->makeGeneric(), []);

        $this->assertEquals([
            'key' => null,
            'value' => ['nested_key' => 'nested_value'],
        ], $result);
    }

    // --- showNull behavior ---

    public function test_process_returns_null_result_when_show_null_false_and_value_is_null(): void
    {
        $field = new UploadField('optional');
        $field->field(fn () => null);
        $field->showNull(false);

        $result = $field->process($this->makeGeneric(), []);

        $this->assertNull($result);
    }

    public function test_process_returns_null_result_when_show_null_false_and_value_is_empty_array(): void
    {
        $field = new UploadField('optional');
        $field->field(fn () => []);
        $field->showNull(false);

        $result = $field->process($this->makeGeneric(), []);

        $this->assertNull($result);
    }

    public function test_process_returns_value_when_show_null_true_and_value_is_null(): void
    {
        $field = new UploadField('optional');
        $field->field(fn () => null);
        $field->showNull(true);

        $result = $field->process($this->makeGeneric(), []);

        $this->assertEquals([
            'key' => 'optional',
            'value' => null,
        ], $result);
    }

    public function test_process_returns_value_when_show_null_true_and_value_is_empty_array(): void
    {
        $field = new UploadField('optional');
        $field->field(fn () => []);
        $field->showNull(true);

        $result = $field->process($this->makeGeneric(), []);

        $this->assertEquals([
            'key' => 'optional',
            'value' => [],
        ], $result);
    }

    // --- field chaining ---

    public function test_field_returns_self_for_chaining(): void
    {
        $field = new UploadField('test');

        $result = $field->field('value');

        $this->assertSame($field, $result);
    }

    public function test_show_null_returns_self_for_chaining(): void
    {
        $field = new UploadField('test');

        $result = $field->showNull(true);

        $this->assertSame($field, $result);
    }

    // --- complex closure scenarios ---

    public function test_process_closure_returning_array(): void
    {
        $generic = $this->makeGeneric([
            'tags' => ['red', 'blue'],
        ]);

        $field = new UploadField('tags');
        $field->field(fn (Generic $item) => $item->properties('tags'));

        $result = $field->process($generic, []);

        $this->assertEquals('tags', $result['key']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['value']);
        $this->assertEquals(['red', 'blue'], $result['value']->toArray());
    }

    public function test_process_closure_returning_config_update(): void
    {
        $field = new UploadField('price');
        $field->field(fn () => [
            'array' => '19.99',
            'config' => ['currency' => 'EUR'],
        ]);

        $result = $field->process($this->makeGeneric(), []);

        // The field returns the raw value - config separation happens in Upload::prepareField
        $this->assertEquals('price', $result['key']);
        $this->assertEquals([
            'array' => '19.99',
            'config' => ['currency' => 'EUR'],
        ], $result['value']);
    }
}
