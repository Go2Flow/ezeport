<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special;

use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub\ArticleProcessorApiCalls;
use Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub\ArticleProcessorPatch;
use Go2Flow\Ezport\Instructions\Setters\UploadProcessor;
use Go2Flow\Ezport\Logger\LogOutput;
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

        foreach ($items as $item)
        {
            $response = $this->apiCalls->create($item);
            $this->processResponse($response, $item);
        }
    }

    private function patchArticles(Collection $items) {

        foreach ($items as $item)
        {
            if ( ! $product = $this->apiCalls->getProduct($item->shopware('id')))
            {
                (new LogOutput($this->project->id))->api()->log(
                    'could not find Product ' . $item->unique_id . ' in Shopware',
                    'high'
                );

                continue;
            }

            $this->patch->setData($item->toShopArray())
                ->setProduct($product)
                ->options()
                ->categories()
                ->configurationSettings()
                ->children()
                ->unSet()
                ->article();
        }
    }

    private function processResponse($response, $item) {

        if (!$response || $response->status() != '200') {
            $this->logProblem('error writing' . $item->contentType  . ' ' . $item->unique_id);
        } else {
            $item->shopware(['id' => $response->body()->data->id]);
            $item->updateOrCreate(false);
        }
    }

    private function logProblem(string $problem): void
    {
        (new LogOutput($this->project->id))->api()->log(
            $problem,
            'high'
        );
    }
}
