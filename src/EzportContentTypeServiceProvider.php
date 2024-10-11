<?php

namespace Go2Flow\Ezport;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Models\GenericModel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;

class EzportContentTypeServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot(): void
    {

        Collection::macro(
            'toContentType',
            fn () => $this->map(
                fn ($item) => $item instanceof GenericModel
                    ? $item->toContentType()
                    : $item
            )
        );

        Collection::macro(
            'toShopArray',
            fn ($config = []) => $this->map(
                fn ($item) => $item instanceof Generic
                    ? $item->toShopArray($config)
                    : $item
            )->toArray()
        );
        Collection::macro(
            'toShopCollection',
            fn ($config = []) => $this->map(
                fn ($item) => $item instanceof Generic
                    ? $item->toShopCollection($config)
                    : $item
            )
        );
        Collection::macro(
            'toFlatShopCollection',
            fn ($config = []) => $this->flatMap(
                fn ($item) => $item instanceof Generic
                    ? $item->toShopCollection($config)
                    : $item
            )
        );

        Collection::macro(
            'toFlatShopArray',
            fn ($config = []) => $this->flatMap(
                fn ($item) => $item instanceof Generic
                    ? $item->toShopArray($config)
                    : $item
            )->toArray()
        );

        Collection::macro(
            'setStructure',
            fn (Upload $instruction) => $this->map(
                fn ($item) => $item instanceof Generic
                    ? $item->setStructure($instruction)
                    : $item
            )
        );
    }
}
