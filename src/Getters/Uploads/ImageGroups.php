<?php

namespace Go2Flow\Ezport\Getters\Uploads;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImageGroups extends BaseInstructions implements InstructionInterface
{
    public function get() : array
    {
        return [
            Set::upload('articleMediaGroups')
                ->items(fn () => Content::type('ImageGroup', $this->project))
                ->chunk(1)
                ->field(
                    Set::uploadField()
                        ->field(
                            fn ($item, $config) => $item->shopware('images')?->map(
                                function ($imageId, $key) use ($config) {
                                    $array = [
                                        'mediaId' => $imageId,
                                        'productId' => $config['id'],
                                    ];

                                    if (Str::startsWith($key, '01_')) {
                                        $array['coverProducts'] = [
                                            ['id' => $config['id']]
                                        ];
                                    }

                                    return $array;
                                }
                                )->filter(
                                    fn ($item) => $item['mediaId'] && $item['productId']
                                )->toArray()
                        ),
                ),

            Set::Upload('mediaGroups')
                ->items(
                    fn (): Builder => Content::type('ImageGroup', $this->project)
                        ->query()
                        ->whereHas(
                            'parents',
                            fn ($q) => $q->whereType('Article')
                                ->where('project_id', $this->project->id)
                        )
                )->field(
                    Set::uploadField()
                        ->field(
                            fn ($item) => $item->properties('images')
                                ->map(
                                    function ($image) use ($item) {
                                        $data = [
                                            'title' => (string) $image['name'],
                                            'name' => (string) $image['name'],
                                            'mediaFolder' => [
                                                'id' => $item->project()->cache('media_folder_ids')['standard']
                                            ]
                                        ];

                                        if ($id = $item->shopware('images')?->get($image['name'])) {
                                            $data['id'] = $id;
                                        }

                                        return $data;
                                    }
                                )
                        )
                ),
            Set::Upload('imageGroups')
                ->chunk(10)
                ->items(fn (): Builder => Content::type('ImageGroup', $this->project)),
        ];
    }

    protected function imageGroupToImages($items)
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

    protected function checkAndReturnCorrectedImageArray(Collection $items, $config, $api): array
    {
        return $this->checkIfImageExistsOnShop(
            $this->getImagesFromShop($items, $api),
            collect($items->toFlatShopArray(['folder_id' => $config['folderId']]))
        );
    }

    private function getImagesFromShop(Collection $items, $api): Collection
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
}
