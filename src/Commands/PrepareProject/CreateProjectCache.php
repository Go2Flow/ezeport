<?php

namespace Go2Flow\Ezport\Commands\PrepareProject;

use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Str;
use Go2Flow\Ezport\Process\Errors\EzportException;
use Illuminate\Support\Facades\Log;

class CreateProjectCache
{
    private Api $api;

    public function __construct(private readonly Project $project)
    {
        $this->api = new Api([$project, 'shopSix']);
    }

    public function prepareCache(): void
    {
        foreach ($this->caches() as $key => $closure) {

            Log::debug('Ezport: creating ' . $key . ' cache');
            $this->project->cache($closure());
        }

        $this->project->save();
    }

    private function caches(): array
    {
        return [
            'tax' => fn () => $this->simpleFind('tax', 'taxRate'),
            'currency' => fn () => $this->simpleFind('currency', 'shortName'),
            'cms' => fn () => $this->simpleFind('cms_page', 'name'),
            'category' => fn () => $this->simpleFind('category', 'name'),
            'sales channel' => fn () => $this->simpleFind('sales_channel', 'name'),
            'media folder' => fn () => $this->simpleFind('media_folder', 'name'),
            'states' => fn () => $this->setStates(),
            'customer group' => fn () => $this->simpleFind('customer_group', 'name'),
            'countries' => fn () => $this->simpleFind('country', 'name'),
            'salutations' => fn () => $this->simpleFind('salutation', 'salutationKey'),
            'languages' => fn () => $this->simpleFind('language', 'name'),
            'payment methods' => fn () => $this->simpleFind('payment_method', 'name'),
        ];
    }

    private function setStates() : array
    {
        return $this->project->settings('machine_states')?->mapWithKeys(
            fn ($values, $name) => [$name . '_ids' => collect($values)->mapWithKeys(
                fn ($value) => [$value => $this->stateFind($name, $value)]
            )]
        )->toArray();
    }

    private function stateFind($key, $value) : string
    {
        return collect($this->apiSearch(
            'stateMachine',
            $this->filter('technicalName', $key . '.state'),
            $this->associations(['states'])
        )[0]->states)
            ->filter(
                fn ($state) => $state->technicalName === $value
            )->first()->id;
    }

    private function simpleFind(string $key, string $field) : ?array
    {
        [$key, $setting, $method] = $this->keys($key);

        return  ($this->project->settings($setting))
            ? [
                $key => $this->project->settings($setting)
                    ?->mapWithKeys(
                        fn ($value, $name) => [
                            $name => $this->getSetting($name, $method, $field, $value)
                        ]
                    )
            ] : null;
    }

    private function getSetting($name, $method, $field, $value) :string {

        $response = $this->apiSearch(
            $method,
            $this->filter($field, $value)
        );

        if (count($response) > 0) return $response[0]->id;


        throw new EzportException('No ' . $name . ' found on shopSix with ' . $field . ' ' . $value. '. Please check your settings.');

    }

    private function apiSearch(string $method, array $filter, $association = []): array
    {
        return $this->api->$method()
            ->filter($filter)
            ->association($association)
            ->search()
            ->body()
            ->data;
    }

    private function filter(string $field, string $value): array
    {
        return [
            'type' => 'contains',
            'field' => $field,
            'value' => $value
        ];
    }

    private function associations(array $associations): array
    {
        return collect($associations)
            ->mapWithKeys(fn ($association) => [$association => []])
            ->toArray();
    }

    private function keys(string $key): array
    {
        $key = Str::of($key);

        return [
            $key->singular()->toString() . '_ids',
            $key->snake()->plural()->toString(),
            $key->camel()->singular()->toString(),
        ];
    }
}
