<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MediaCleaner extends BaseCleaner
{
    protected string $type = 'media';

    public function clean() : void
    {

    }

    protected function process() : void
    {
        $media = $this->difference;

        Log::debug('Ezport: gathering media');

        $this->difference = $this->media(
            $media
        );

        Log::debug('Ezport: remove product media');
        $this->removeProductMedia();

        $this->difference = $media;

        Log::debug('Ezport: remove media');

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
