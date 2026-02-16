<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\ContentTypes\Helpers\TypeGetter;
use Go2Flow\Ezport\Models\Project;
use PHPUnit\Framework\TestCase;

class ContentHelperTest extends TestCase
{
    public function test_type_returns_type_getter(): void
    {
        $project = new Project();
        $project->id = 1;

        $result = Content::type('product', $project);

        $this->assertInstanceOf(TypeGetter::class, $result);
    }

    public function test_type_returns_new_instance_each_time(): void
    {
        $project = new Project();
        $project->id = 1;

        $a = Content::type('product', $project);
        $b = Content::type('product', $project);

        $this->assertNotSame($a, $b);
    }

    public function test_type_returns_different_instances_for_different_types(): void
    {
        $project = new Project();
        $project->id = 1;

        $product = Content::type('product', $project);
        $category = Content::type('category', $project);

        $this->assertNotSame($product, $category);
    }
}
