<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\Api;
use Go2Flow\Ezport\Instructions\Setters\Types\Basic;
use Go2Flow\Ezport\Instructions\Setters\Types\Connector;
use Go2Flow\Ezport\Instructions\Setters\Types\CsvImport;
use Go2Flow\Ezport\Instructions\Setters\Types\Job;
use Go2Flow\Ezport\Instructions\Setters\Types\Step;
use Go2Flow\Ezport\Instructions\Setters\Types\Transform;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadProcessor;
use Go2Flow\Ezport\Process\Errors\EzportSetterException;
use PHPUnit\Framework\TestCase;

class SetFactoryTest extends TestCase
{
    public function test_creates_upload_instance(): void
    {
        $instance = Set::Upload('product');

        $this->assertInstanceOf(Upload::class, $instance);
        $this->assertEquals('product', $instance->getKey());
    }

    public function test_creates_upload_field_instance(): void
    {
        $instance = Set::UploadField('name');

        $this->assertInstanceOf(UploadField::class, $instance);
        $this->assertEquals('name', $instance->getKey());
    }

    public function test_creates_upload_field_without_key(): void
    {
        $instance = Set::UploadField();

        $this->assertInstanceOf(UploadField::class, $instance);
        $this->assertNull($instance->getKey());
    }

    public function test_creates_transform_instance(): void
    {
        $instance = Set::Transform('product');

        $this->assertInstanceOf(Transform::class, $instance);
        $this->assertEquals('product', $instance->getKey());
    }

    public function test_creates_api_instance(): void
    {
        $instance = Set::Api('ShopSix');

        $this->assertInstanceOf(Api::class, $instance);
    }

    public function test_creates_connector_instance(): void
    {
        $instance = Set::Connector('ShopSix');

        $this->assertInstanceOf(Connector::class, $instance);
    }

    public function test_creates_job_instance(): void
    {
        $instance = Set::Job();

        $this->assertInstanceOf(Job::class, $instance);
    }

    public function test_creates_basic_instance(): void
    {
        $instance = Set::Basic('test');

        $this->assertInstanceOf(Basic::class, $instance);
        $this->assertEquals('test', $instance->getKey());
    }

    public function test_creates_step_instance(): void
    {
        $instance = Set::Step('myStep');

        $this->assertInstanceOf(Step::class, $instance);
    }

    public function test_creates_csv_import_instance(): void
    {
        $instance = Set::CsvImport('products');

        $this->assertInstanceOf(CsvImport::class, $instance);
    }

    public function test_creates_upload_processor_instance(): void
    {
        $instance = Set::UploadProcessor('product');

        $this->assertInstanceOf(UploadProcessor::class, $instance);
    }

    public function test_throws_on_unknown_type(): void
    {
        $this->expectException(EzportSetterException::class);

        Set::CompletelyFakeType('key');
    }

    public function test_upload_key_processing(): void
    {
        // Verify that keys are processed through processKey
        $upload = Set::Upload('articles');
        $this->assertEquals('article', $upload->getKey());

        $upload2 = Set::Upload('PropertyGroups');
        $this->assertEquals('propertyGroup', $upload2->getKey());
    }

    public function test_transform_key_processing(): void
    {
        $transform = Set::Transform('Articles');
        $this->assertEquals('article', $transform->getKey());
    }
}
