<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ArticleProcessorPatch
{
    private array $data;
    private object $product;
    private $unsetters = [
        'children',
        'visibilities',
        'configuratorSettings',
        'cmsPageId',
        'currencyId',
        'taxId',
    ];

    public function __construct(private ArticleProcessorApiCalls $apiCalls) {}

    public function setData(array $data) : self
    {
        $this->data = $data;

        return $this;
    }

    public function setProduct(object $product) : self
    {
        $this->product = $product;

        return $this;
    }

    public function unSet() : self
    {
        foreach ($this->unsetters as $unsetter) {
            unset($this->data[$unsetter]);
        }

        return $this;
    }

    public function options () : self {

        if (($leftovers = $this->getRemovals('properties'))->count() == 0) return $this;

        $this->apiCalls->deleteProperty(
            $this->prepareLeftovers($leftovers, 'optionId')
        );

        return $this;
    }

    public function categories() : self {

        if (($leftovers = $this->getRemovals('categories'))->count() == 0) return $this;

        $this->apiCalls->deleteCategory(
            $this->prepareLeftovers($leftovers, 'categoryId')
        );

        return $this;
    }

    public function configurationSettings() : self
    {
        [$dbIds, $shopwareIds] = $this->getConfigutationIds();

        $options = $this->prepareConfiguratorSettingsOptions($dbIds->diff($shopwareIds), $this->data['id']);
        if ($options->count() > 0) $this->apiCalls->configuratorSettings($options->toArray(), 'bulk');

        $options = $this->prepareConfiguratorSettingsOptions($shopwareIds->diff($dbIds), $this->data['id']);
        if ($options->count() > 0) $this->apiCalls->configuratorSettings($options->toArray(), 'bulkDelete');

        return $this;
    }

    public function children(): self
    {
        if (($children = collect($this->data['children'] ?? []))->count() === 0) return $this;

        $deletes = $this->findChildrenThatShouldBeDeleted(
            collect($this->product->children),
            $children
        );

        if (($deletes)->count() > 0) {
            $this->apiCalls->deleteProducts($deletes->values()->toArray());
        }

        $this->apiCalls->bulkProducts(
            $this->prepareChildrenForUpload($children, $this->product, $this->data['id'])
        );

        return $this;
    }

    public function article() : self {
        $this->apiCalls->patch($this->data);

        return $this;
    }

    private function getConfigutationIds() : array
    {
        return [
            collect($this->data['configuratorSettings'] ?? [])->pluck('optionId'),
            collect($this->product->configuratorSettings)->pluck('optionId')
        ];
    }

    private function prepareConfiguratorSettingsOptions(Collection $ids, string $productId) : Collection
    {
        return $ids->map(
            fn ($id) => [
                'productId' => $productId,
                'optionId' => $id,
            ]
        )->filter(
            fn ($item) => $item['optionId'] != null && $item['productId'] != null
        )->values();
    }

    private function getRemovals(string $type): Collection
    {

        if ($types = $this->product->$type) {
            $leftovers = collect($types)
                ->pluck('id')
                ->diff(
                    collect($this->data[$type] ?? [])->pluck('id')
                );

            if ($leftovers->count() > 0) return $leftovers;
        }

        return collect();
    }

    private function prepareLeftovers(Collection $leftovers, string $key) : array {

        return $leftovers->map(
            fn ($option) => [
                'productId' => $this->data['id'],
                $key => $option
            ]
        )->values()
            ->toArray();
    }

    private function prepareChildrenForUpload(Collection $children, object $product, string $id) : array
    {
        return $children->map(
            function ($child) use ($id, $product) {
                if ($childId = collect($product->children)->filter(fn ($variant) => $variant->productNumber == $child['productNumber'])->first()?->id) {
                    $child['id'] = $childId;
                }

                return array_merge($child, ['parentId' => $id]);
            }
        )->values()
        ->toArray();
    }

    private function findChildrenThatShouldBeDeleted(Collection $productChildren, Collection $children) : Collection
    {
        $serverIds = $productChildren->mapWithKeys(fn ($child) => [$child->id => $child->optionIds]);
        $dbOptions = $children->pluck('options')->flatten();

        return $serverIds->keys()
            ->diff(
                $serverIds->map(
                    fn ($options, $productId) => collect($options)->filter(fn ($option) => $dbOptions->contains($option))->count() > 0
                        ? $productId
                        : null
                )->filter()
            )->map(fn ($item) => ['id' => $item]);
    }
}
