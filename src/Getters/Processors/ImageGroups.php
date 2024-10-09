<?php

namespace Go2Flow\Ezport\Getters\Processors;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Helpers\Processors\GeneralHelpers;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImageGroups extends BaseInstructions implements InstructionInterface {

    use GeneralHelpers;

    public function get() : array
    {

        return [
            Set::UploadProcessor('articleMediaGroups')
                ->process(
                    function (Collection $items, Api $api) {

                        if (count($send = $this->getUniqueImages($this->imageGroupToImages($items), $api)) > 0) {

                            $response = $api->productMedia()
                                ->bulk(
                                    $send->filter()->values()->toArray()
                                )->body();
                        }
                    }
                ),
            Set::UploadProcessor('imageGroup')
                ->process(
                    function (Collection $items, Api $api) {

                        $ftp = Find::Api($this->project, 'ftp');
                        $content = $items->flatMap(
                            function ($item) use ($ftp) {

                                if (count($images = $this->getImagesThatHaveShopwareId($item)) == 0) return collect();

                                $files = $ftp->image()
                                    ->find($images);

                                return $files->map(
                                    fn ($file, $key) => [
                                        'file' => $file,
                                        'id' => $item->shopware('images')[$key],
                                        'path' => $item->properties('images')
                                        ->filter(fn ($image) => $image['name'] === $key)
                                        ->first()['path']
                                    ]
                                );
                            }
                        );

                        if ($content->count() > 0) {

                            foreach ($content as $item) {
                                $api->media()
                                    ->upload(
                                        ['file' => $item['file']],
                                        $item['id'],
                                        $this->checkImageType($item['path'])
                                    );
                            }
                        }
                    }
                ),
            Set::UploadProcessor('mediaGroup')
                ->process(
                    function (Collection $items, Api $api, array $config) {

                        $config['folderId'] = $this->project->cache('media_folder_ids')['standard'] ?? $this->mediaFolders('Shopware Import Media', $api);

                        if (count($array = $this->checkAndReturnCorrectedImageArray($items, $config, $api)) === 0) return;

                        $body = $api->media()
                            ->bulk($array)
                            ->body();

                        $ids = collect($array)->mapWithKeys(
                            fn ($item, $key) => [$item['file'] => $body->data->media[$key] ?? null]
                        )->filter();

                        foreach ($items as $item) {

                            $shopwareIds = collect();

                            foreach ($item->properties('images') as $image) {

                                if (isset($ids[$image['file']])) {
                                    $shopwareIds[$image['file']] = $ids[$image['file']];
                                }
                            }

                            $item->shopware([
                                'images' => $shopwareIds
                            ]);
                            $item->updateOrCreate();
                        }
                    }
                ),
        ];
    }

    private function getImagesThatHaveShopwareId(Generic $item) {

        return $item->properties('images')
            ->pluck('name')
            ->map(
                fn ($image) => isset($item->shopware('images')[$image]) ? $image : null
            )->filter();
    }

    private function imageGroupToImages($items)
    {

        return $items
            ->flatMap(
                fn ($group) => $group->parents()
                    ->filter(fn ($article) => $article->getType() === 'Article')
                    ->flatmap(
                        fn ($article) => $article->shopware('id')
                            ? $group->toShopArray(['id' => $article->shopware('id')])
                            : null
                    )->filter()
            )->filter()
            ->values();
    }

    private function checkAndReturnCorrectedImageArray(Collection $items, $config, $api): array
    {
        return $this->checkIfImageExistsOnShop(
            $this->getImagesFromShop($items, $api),
            collect($items->toFlatShopArray(['folder_id' => $config['folderId']]))
        );
    }

    private function getImagesFromShop(Collection $items, Api $api): Collection
    {

        if (($images = $items->flatMap->shopware('images')->filter())->isEmpty()) return collect();

        $response = $api->media()
            ->filter([
                'type' => 'equalsAny',
                'field' => 'id',
                'value' => $images->flatten()->toArray()
            ])->search()
            ->body();

        return isset($response->total) && $response->total > 0
            ? collect($response->data)->pluck('id')
            : collect();
    }

    private function checkIfImageExistsOnShop(Collection $imageIds, Collection $databaseItems): array
    {
        return $databaseItems->map(
            function ($item) use ($imageIds) {

                if (!isset($item['id']) || $imageIds->contains($item['id'])) return $item;

                unset($item['id']);

                return $item;
            }
        )->toArray();
    }

    private function checkImageType($file): string
    {
        return in_array(Str::of($file)->afterLast('.'), ['png', 'jpg', 'jpeg', 'gif', 'pdf'])
            ? Str::of($file)->afterLast('.')
            : 'jpg';
    }
}
