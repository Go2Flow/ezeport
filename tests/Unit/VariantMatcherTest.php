<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Helpers\VariantMatcher;
use PHPUnit\Framework\TestCase;

class VariantMatcherTest extends TestCase
{
    private function variant(string $number, array $optionIds, string $createdAt): object
    {
        return (object) ['id' => 'id-'.$number, 'productNumber' => $number, 'optionIds' => $optionIds, 'createdAt' => $createdAt];
    }

    public function test_a_renumbered_child_updates_the_variant_of_its_size(): void
    {
        $variants = collect([$this->variant('785.0255-0', ['s35'], '2026-08-01'), $this->variant('785.0255-4', ['s39'], '2026-08-01')]);

        $match = VariantMatcher::match($variants, ['productNumber' => '785.0255-1', 'options' => [['id' => 's39']]]);

        $this->assertSame('785.0255-4', $match->productNumber);
    }

    public function test_the_oldest_variant_of_a_size_wins_over_a_duplicate(): void
    {
        $variants = collect([$this->variant('785.0255-1', ['s39'], '2026-10-19'), $this->variant('785.0255-4', ['s39'], '2026-08-01')]);

        $match = VariantMatcher::match($variants, ['productNumber' => '785.0255-1', 'options' => [['id' => 's39']]]);

        $this->assertSame('785.0255-4', $match->productNumber);
    }

    public function test_a_new_size_has_no_match(): void
    {
        $variants = collect([$this->variant('785.0255-0', ['s35'], '2026-08-01')]);

        $this->assertNull(VariantMatcher::match($variants, ['productNumber' => '785.0255-3', 'options' => [['id' => 's47']]]));
    }

    public function test_without_options_the_product_number_decides(): void
    {
        $variants = collect([$this->variant('A-1', [], '2026-08-01')]);

        $this->assertSame('A-1', VariantMatcher::match($variants, ['productNumber' => 'A-1'])->productNumber);
        $this->assertNull(VariantMatcher::match($variants, ['productNumber' => 'A-2']));
    }
}
