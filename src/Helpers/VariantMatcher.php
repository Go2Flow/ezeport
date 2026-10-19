<?php

namespace Go2Flow\Ezport\Helpers;

use Illuminate\Support\Collection;

/**
 * Finds the Shopware variant an uploaded child stands for. A variant is its options (e.g. the
 * size): when one with the child's options exists, the oldest of them is the match, whatever
 * its product number, so a source that numbers its variants differently than before updates
 * them instead of creating a second variant per size. Without options the product number decides.
 */
class VariantMatcher
{
    /** @param  Collection<int, object>  $shopwareChildren  with productNumber, optionIds, createdAt */
    public static function match(Collection $shopwareChildren, array $child): ?object
    {
        $optionIds = collect($child['options'] ?? [])->pluck('id')->filter();

        if ($optionIds->isNotEmpty()) {
            $byOptions = $shopwareChildren
                ->filter(fn ($variant) => $optionIds->diff($variant->optionIds ?? [])->isEmpty())
                ->sortBy(fn ($variant) => $variant->createdAt ?? '')
                ->first();

            if ($byOptions) {
                return $byOptions;
            }
        }

        return $shopwareChildren->first(fn ($variant) => $variant->productNumber == ($child['productNumber'] ?? null));
    }
}
