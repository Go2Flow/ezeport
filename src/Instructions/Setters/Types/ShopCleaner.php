<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\Cleaners\ShopwareSix\BaseCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\CrossSellingCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\ManufacturerCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\MediaCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\ProductCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\ProductMediaCleaner;
use Go2Flow\Ezport\Cleaners\ShopwareSix\PropertyOptionCleaner;
use Go2Flow\Ezport\Connectors\ShopwareSix\Api;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\AssignClean;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ShopCleaner extends Basic implements JobInterface {

    protected ?string $type;
    protected ?Closure $ids;
    protected string $cacheId;

    public function __construct(string $key, ?Closure $ids = null , private array $config = [])
    {
        parent::__construct($key);
        $this->items = collect();

        if ($ids) $this->ids = $ids;

        $this->type = $this->getCorrectType($config['type'] ?? $key);

        $this->config = array_merge($config, ['key' => $key]);

        $this->job = Set::Job()
            ->class(AssignClean::class);

        $this->cacheId = Str::uuid();
    }

        public function type(string $type) : self
    {
        $this->type = $this->getCorrectType($type);

        return $this;
    }

    public function items(Closure $ids) : self
    {
        $this->ids = $ids;

        return $this;
    }

    public function filter(array $config) : self
    {

        $filter = [
            'type' => 'equals',
            'field' => 'name',
        ];

        foreach ($config as $key => $value) {
            if ($key === 'GroupName') $key = 'value';
            $filter[$key] = $value;
        }

        $this->config['filter'] = $filter;

        return $this;
    }

    /**
     * runs this items closure and sets the items property
     */

    public function prepareItems() : self
    {
        $items = ($this->ids)();

        Cache::put($this->cacheId, $items, 7200);

        return $this;
    }

    public function getCleaner() : BaseCleaner
    {
        return  new ($this->type)(
            new Api(
                $this->project->connectorType('shopSix')->getValues(),
                Find::instruction($this->project, 'Api')->find('shopSix')?->getConfig() ?? collect([])
            ),
            $this->cacheId,
            $this->config
        );
    }

    private function getCorrectType(?string $type) : ?string {

        if (! $type) return null;

        if ($type == 'Media' ) return MediaCleaner::class;
        if ($type == 'ProductMedia' ) return ProductMediaCleaner::class;

        return match (Str::of($type)->singular()->ucFirst()->toString()) {

            'Product' => ProductCleaner::class,
            'Media' => MediaCleaner::class,
            'ProductMedia' => ProductMediaCleaner::class,
            'Manufacturer' => ManufacturerCleaner::class,
            'CrossSelling' => CrossSellingCleaner::class,
            default => PropertyOptionCleaner::class,
        };
    }
}
