<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

use Closure;
use Illuminate\Support\Collection;

class PropertyOptionCleaner extends BaseCleaner
{
    protected string $type = 'propertyOption';

    public function clean()
    {
        $this->difference = $this->serverDatabaseDifference(
            $this->shopGroup()
        );

        $this->process();
    }

    protected function process()
    {
        $this->difference = collect(
            $this->shopOptions(
                $difference = $this->difference
            )
        );

        $this->difference = $difference;

        $this->removeProductConfiguratorSettings();
        $this->remove();
    }

    protected function getIdsToDelete(): Collection
    {
        return $this->serverDatabaseDifference(
            $this->shopGroup()->map(fn ($option) => $option->id)
        );
    }

    private function removeProductConfiguratorSettings(): Collection
    {
        return $this->bulkDelete(
            'productConfiguratorSetting',
            collect(
                $this->api->propertyGroupOption()
                    ->association(['productConfiguratorSettings' => []])
                    ->filter([
                        'type' => 'equalsAny',
                        'value' => $this->difference,
                        'field' => 'id'
                    ])->search()
                    ->body()->data
            )->flatMap(
                fn ($option) => collect($option->productConfiguratorSettings)
                    ->map(fn ($setting) => ['id' => $setting->id])
            )->filter()
                ->unique()

        );
    }

    private function shopGroup(): Collection
    {
        $response = $this->api->propertyGroup()
            ->association(['options' => []])
            ->include(['options' => ['id']])
            ->filter($this->setFilter())
            ->search()
            ->body();

        return collect(
            (isset($response->data) && count($response->data) > 0)
                ? $response->data[0]->options
                : []
        );
    }

    private function shopOptions(Collection $difference): Collection
    {
        return $this->getFromShop(
            $difference,
            [
                'url' => 'propertyOption',
                'associations' => ['productConfiguratorSettings', 'productProperties', 'productOptions']
            ]
        );
    }

    protected function prepareAssociation(string $type, Closure $closure)
    {
        return $this->difference->flatMap(
            fn ($option) => $closure($option, collect($option->$type))
        );
    }

    protected function typeSpecificActions(): void
    {
        if (isset($this->config['filter']['groupName'])) {

            $this->config['filter']['name'] = $this->config['filter']['groupName'];
            unset($this->config['filter']['groupName']);
        }
    }
}
