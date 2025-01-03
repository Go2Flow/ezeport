<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special;

use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub\ArticleProcessorApiCalls;
use Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub\ArticleProcessorPatch;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadProcessor;
use Go2Flow\Ezport\Logger\LogError;
use Illuminate\Support\Collection;

class ArticleProcessor extends UploadProcessor {

    private ArticleProcessorApiCalls $apiCalls;
    private ArticleProcessorPatch $patch;

    public function __construct(string $key = null) {
        parent::__construct($key);

        $this->process = function (Collection $items, Api $api) {
            $this->apiCalls = new ArticleProcessorApiCalls($api);
            $this->patch = new ArticleProcessorPatch($this->apiCalls);
            $this->articleProcess($items);
        };
    }

    private function articleProcess(Collection $items)
    {
        $create = collect();
        $patch = collect();

        $items->each(
            fn ($item) => $item->shopware('id')
                ? $patch->push($item)
                : $create->push($item)
        );

        if ($create->count() > 0) $this->createArticles($create);
        if ($patch->count() > 0) $this->patchArticles($patch);
    }

    private function createArticles(Collection $items) {

        $response = $this->apiCalls->bulkProducts($items->toShopArray());

        $products = $response->body()?->data->product;

        if (!$products) {
            $string = 'uploading of Products to shopware failed. Product ids: ' . $items->map(fn ($item) => $item->unique_id)->implode(', ');

            $this->logProblem($string, 'high');
            return;
        }

        $index = 0;

        foreach ($items as $item) {

            $item->shopware(['id' => $products[$index]]);

            $ids = collect();
            foreach ($item->toShopArray()['children'] ?? [] as $child) {

                $index++;
                $ids->push($products[$index]);
            }

            if ($ids->isNotEmpty()) $item->shopware(['children' => $ids]);

            $item->updateOrCreate(false);

            $index++;
        }
    }

    private function patchArticles(Collection $items) : void {

        $products = $this->apiCalls->getProducts($items);

        if ($products->count() !== $items->count()) {

            $ids = $products->pluck('id');

            [$existing, $missing] = $products->partition(
                fn ($item) => $ids->contains($item->id)
            );

            foreach ($missing as $item) {
                $this->logProblem(
                    'could not find Product ' . $item->unique_id . 'with id'. $item->shopware('id') . ' in Shopware',
                    'high'
                );
            }

            $items = $existing->map(fn ($exist) => $items->filter(fn ($item) => $item->shopware('id') == $exist->id)->first());
        }

        $this->patch
            ->setItems($items)
            ->setProducts($products)
            ->options()
            ->categories()
            ->configurationSettings()
            ->children()
            ->unSet()
            ->articles();
    }

    private function logProblem(string $problem, $level = 'high'): void
    {
        (new LogError($this->project->id))
            ->type('api')
            ->level('high')
            ->log(json_encode($problem));
    }
}
