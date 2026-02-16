<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Types\Base;
use Go2Flow\Ezport\Process\Errors\EzportSetterException;
use Illuminate\Support\Stringable;
use PHPUnit\Framework\TestCase;

class SetterBaseTest extends TestCase
{
    private function makeBase(): Base
    {
        return new class extends Base {
            public string $testProp = 'hello';

            public function __construct()
            {
                $this->key = null;
                $this->project = null;
                $this->instructionType = null;
            }

            // Expose protected processKey for testing
            public function testProcessKey(string $key): Stringable
            {
                return $this->processKey($key);
            }
        };
    }

    // --- processKey ---

    public function test_process_key_singularizes_standard_keys(): void
    {
        $base = $this->makeBase();

        // "products" -> singular -> "product" -> camel -> "product"
        $this->assertEquals('product', (string) $base->testProcessKey('products'));
    }

    public function test_process_key_handles_already_singular(): void
    {
        $base = $this->makeBase();

        $this->assertEquals('product', (string) $base->testProcessKey('product'));
    }

    public function test_process_key_ucfirsts_then_singularizes_then_camels(): void
    {
        $base = $this->makeBase();

        // "Category" -> ucfirst "Category" -> singular "Category" -> camel "category"
        $this->assertEquals('category', (string) $base->testProcessKey('Category'));
    }

    public function test_process_key_media_is_pluralized_not_singularized(): void
    {
        $base = $this->makeBase();

        // "media" (lowercase check) -> plural -> camel
        $result = (string) $base->testProcessKey('media');
        $this->assertEquals('media', $result);
    }

    public function test_process_key_media_case_insensitive(): void
    {
        $base = $this->makeBase();

        $result = (string) $base->testProcessKey('Media');
        $this->assertEquals('media', $result);
    }

    public function test_process_key_camel_case_conversion(): void
    {
        $base = $this->makeBase();

        // "PropertyGroup" -> ucfirst "PropertyGroup" -> singular "PropertyGroup" -> camel "propertyGroup"
        $this->assertEquals('propertyGroup', (string) $base->testProcessKey('PropertyGroup'));
    }

    public function test_process_key_handles_articles(): void
    {
        $base = $this->makeBase();

        $this->assertEquals('article', (string) $base->testProcessKey('articles'));
        $this->assertEquals('article', (string) $base->testProcessKey('Article'));
    }

    public function test_process_key_handles_cross_sellings(): void
    {
        $base = $this->makeBase();

        $this->assertEquals('crossSelling', (string) $base->testProcessKey('CrossSellings'));
    }

    // --- key / hasKey / getKey ---

    public function test_key_sets_and_get_key_returns(): void
    {
        $base = $this->makeBase();

        $base->key('products');

        $this->assertEquals('product', $base->getKey());
    }

    public function test_has_key_matches_processed_key(): void
    {
        $base = $this->makeBase();

        $base->key('products');

        $this->assertTrue($base->hasKey('product'));
        $this->assertTrue($base->hasKey('products'));
        $this->assertFalse($base->hasKey('category'));
    }

    public function test_get_key_returns_null_when_not_set(): void
    {
        $base = $this->makeBase();

        $this->assertNull($base->getKey());
    }

    // --- has / get ---

    public function test_has_checks_property_existence(): void
    {
        $base = $this->makeBase();

        $this->assertTrue($base->has('testProp'));
        $this->assertTrue($base->has('key'));
        $this->assertFalse($base->has('nonExistent'));
    }

    public function test_get_returns_property_value(): void
    {
        $base = $this->makeBase();

        $this->assertEquals('hello', $base->get('testProp'));
    }

    public function test_get_throws_on_missing_property(): void
    {
        $base = $this->makeBase();

        $this->expectException(EzportSetterException::class);
        $base->get('nonExistent');
    }

    // --- instructionType ---

    public function test_instruction_type_sets_and_returns_self(): void
    {
        $base = $this->makeBase();

        $result = $base->instructionType('upload');

        $this->assertSame($base, $result);
    }

    // --- getThis ---

    public function test_get_this_returns_self(): void
    {
        $base = $this->makeBase();

        $this->assertSame($base, $base->getThis());
    }

    // --- __call magic for get* ---

    public function test_magic_get_method_returns_property(): void
    {
        $base = $this->makeBase();

        $this->assertEquals('hello', $base->getTestProp());
    }

    public function test_magic_get_method_throws_for_missing(): void
    {
        $base = $this->makeBase();

        $this->expectException(EzportSetterException::class);
        $base->getNonExistent();
    }

    public function test_non_get_magic_method_throws_bad_method_call(): void
    {
        $base = $this->makeBase();

        $this->expectException(\BadMethodCallException::class);
        $base->someRandomMethod();
    }
}
