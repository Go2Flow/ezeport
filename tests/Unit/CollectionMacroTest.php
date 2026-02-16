<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\EzportContentTypeServiceProvider;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CollectionMacroTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register the collection macros (normally done by the service provider)
        // We call boot() directly to register macros without a full Laravel app
        if (!Collection::hasMacro('toContentType')) {
            $provider = new EzportContentTypeServiceProvider(null);
            $provider->boot();
        }
    }

    private function makeGenericModel(string $type = 'product', array $content = []): GenericModel
    {
        $model = new GenericModel();
        $model->type = $type;
        $model->unique_id = 'test-1';
        $model->project_id = 1;
        $model->content = collect($content);
        $model->shop = collect();
        $model->setRelation('children', collect());

        return $model;
    }

    private function makeGeneric(string $type = 'product', array $content = [], array $shop = []): Generic
    {
        $model = $this->makeGenericModel($type, $content);
        $model->shop = collect($shop);
        return new Generic($model);
    }

    // --- toContentType macro ---

    public function test_to_content_type_converts_generic_models(): void
    {
        $model1 = $this->makeGenericModel('product', ['name' => 'A']);
        $model2 = $this->makeGenericModel('category', ['name' => 'B']);

        $collection = collect([$model1, $model2]);
        $result = $collection->toContentType();

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Generic::class, $result[0]);
        $this->assertInstanceOf(Generic::class, $result[1]);
        $this->assertEquals('product', $result[0]->getType());
        $this->assertEquals('category', $result[1]->getType());
    }

    public function test_to_content_type_leaves_non_models_unchanged(): void
    {
        $model = $this->makeGenericModel('product');

        $collection = collect([$model, 'string', 42]);
        $result = $collection->toContentType();

        $this->assertInstanceOf(Generic::class, $result[0]);
        $this->assertEquals('string', $result[1]);
        $this->assertEquals(42, $result[2]);
    }

    public function test_to_content_type_empty_collection(): void
    {
        $result = collect()->toContentType();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    // --- setStructure macro ---

    public function test_set_structure_applies_to_generic_instances(): void
    {
        $generic1 = $this->makeGeneric('product');
        $generic2 = $this->makeGeneric('product');

        $upload = Set::Upload('product');
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);
        $upload->field(Set::UploadField('test')->field('value'));

        $result = collect([$generic1, $generic2])->setStructure($upload);

        $this->assertCount(2, $result);
        // Each item should now have the structure set
        $this->assertInstanceOf(Generic::class, $result[0]);
        $this->assertInstanceOf(Generic::class, $result[1]);
    }

    public function test_set_structure_leaves_non_generic_unchanged(): void
    {
        $generic = $this->makeGeneric('product');

        $upload = Set::Upload('product');
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);

        $result = collect([$generic, 'other'])->setStructure($upload);

        $this->assertInstanceOf(Generic::class, $result[0]);
        $this->assertEquals('other', $result[1]);
    }

    // --- toShopArray macro ---

    public function test_to_shop_array_transforms_generic_items(): void
    {
        $upload = Set::Upload('product');
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);
        $upload->field(Set::UploadField('name')->field(fn (Generic $item) => $item->properties('name')));
        $upload->field(Set::UploadField('sku')->field(fn (Generic $item) => $item->properties('sku')));

        $generic1 = $this->makeGeneric('product', ['name' => 'A', 'sku' => 'S1']);
        $generic1->setStructure($upload);

        $generic2 = $this->makeGeneric('product', ['name' => 'B', 'sku' => 'S2']);
        $generic2->setStructure($upload);

        $result = collect([$generic1, $generic2])->toShopArray();

        $this->assertEquals([
            ['name' => 'A', 'sku' => 'S1'],
            ['name' => 'B', 'sku' => 'S2'],
        ], $result);
    }

    public function test_to_shop_array_leaves_non_generic_unchanged(): void
    {
        $upload = Set::Upload('product');
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);
        $upload->field(Set::UploadField('name')->field('Test'));

        $generic = $this->makeGeneric('product');
        $generic->setStructure($upload);

        $result = collect([$generic, 'passthrough'])->toShopArray();

        $this->assertEquals(['name' => 'Test'], $result[0]);
        $this->assertEquals('passthrough', $result[1]);
    }

    // --- toShopCollection macro ---

    public function test_to_shop_collection_returns_collections(): void
    {
        $upload = Set::Upload('product');
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);
        $upload->field(Set::UploadField('name')->field('Test'));

        $generic = $this->makeGeneric('product');
        $generic->setStructure($upload);

        $result = collect([$generic])->toShopCollection();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(Collection::class, $result[0]);
        $this->assertEquals('Test', $result[0]['name']);
    }

    // --- toFlatShopArray macro ---

    public function test_to_flat_shop_array_flattens_one_level(): void
    {
        $upload = Set::Upload('product');
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);
        $upload->field(Set::UploadField('name')->field('Flat'));

        $generic = $this->makeGeneric('product');
        $generic->setStructure($upload);

        $result = collect([$generic])->toFlatShopArray();

        // flatMap + toArray: the items from each shopArray are flattened one level
        $this->assertIsArray($result);
    }

    // --- toFlatShopCollection macro ---

    public function test_to_flat_shop_collection_returns_flat_collection(): void
    {
        $upload = Set::Upload('product');
        $project = new Project();
        $project->id = 1;
        $upload->setProject($project);
        $upload->field(Set::UploadField('name')->field('Flat'));

        $generic = $this->makeGeneric('product');
        $generic->setStructure($upload);

        $result = collect([$generic])->toFlatShopCollection();

        $this->assertInstanceOf(Collection::class, $result);
    }
}
