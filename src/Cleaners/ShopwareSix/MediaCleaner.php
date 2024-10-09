<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

use Illuminate\Support\Collection;

class MediaCleaner extends BaseCleaner
{
    protected string $type = 'media';

    public function clean() : void
    {

    }

    protected function process() : void
    {
        $media = $this->difference;

        dump('gathering media');

        $this->difference = $this->media(
            $media
        );

        dump('remove product media');
        $this->removeProductMedia();

        $this->difference = $media;

        dump ('remove media');

        $this->difference->each(function ($id) {
            $this->api->media()->delete($id);
        });
    }

    private function removeProductMedia(): Collection
    {
        return $this->bulkDelete(
            'productMedia',
            $this->prepareAssociationForDeletion(
                'productMedia',
                fn ($option, $subOptions) => $subOptions->map(
                    fn ($subOption) => [
                        'id' => $subOption->id,
                    ]
                )
            )
        );
    }

    private function media(Collection $difference) : Collection
    {
        return $this->getFromShop(
            $difference,
            [
                'associations' => ['productMedia'],
                'include' => ['productMedia' => ['id']],
            ],
        );
    }
}
