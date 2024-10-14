<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Connectors\ApiInterface;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportFinderException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Go2Flow\Ezport\Connectors\Ftp\Api as FtpApi;
use Go2Flow\Ezport\Connectors\ShopwareSix\Api as ShopSixApi;
use Go2Flow\Ezport\Connectors\ShopwareFive\Api as ShopFiveApi;
use Go2Flow\Ezport\Connectors\ShopwareSix\StoreLocatorApi as StoreLocatorSixApi;
use Illuminate\Support\Stringable;

class Api extends Base implements ApiInterface
{
    private Stringable $type;
    private ?string $name;

    public function get()
    {
        return $this->object;
    }

    /**
     * @return FtpApi|ShopSixApi|ShopFiveApi|StoreLocatorSixApi
     */

    protected function getObject(Project $project, string $type, ?string $name = null): object
    {
        $this->type = Str::of($type);

        return new ($this->getClass())(
            $project->connectors()
                ->where(
                    'type',
                    $this->matchConnector('type', 'Type of ' . $this->type->toString() . ' not found')
                )->when(
                    $name,
                    fn ($query) => $query->where('name', $name)
                )->first()
                ->getValues(),
            Find::instruction($project, 'Api')->find($type)?->getConfig() ?? collect()
        );
    }

    private function getClass(): string
    {
        if (class_exists($class = $this->getPath())) return $class;

        throw new EzportFinderException('Class ' . $class . ' not found');
    }

    private function getPath(): string
    {
        if ($this->type->lower()->contains('connectors')) return $this->type->toString();

        $this->type = $this->type->camel()->ucfirst();

        return $this->matchConnector('path', 'Path for ' . $this->type->camel()->toString() . ' not found');
    }

    private function matchConnector(string $field, string $message)
    {
        $response = $this->getConnectors()
            ->filter(
                fn ($item) => $this->type->ucfirst()->contains($item['name']) || $this->type->contains($item['path'])
                    ? $item[$field]
                    : null
            )->first();

        if ($response) return $response[$field];

        throw new EzportFinderException($message);
    }

    private function getConnectors(): Collection
    {
        return collect([
            [
                'name' => 'Ftp',
                'type' => 'ftp',
                'path' => FtpApi::class,
            ],
            [
                'name' => 'ShopSix',
                'type' => 'shopSix',
                'path' => ShopSixApi::class,
            ],
            [
                'name' => 'ShopFive',
                'type' => 'shopFive',
                'path' => ShopFiveApi::class,
            ],
            [
                'name' => 'StoreLocatorSix',
                'type' => 'shopSix',
                'path' => StoreLocatorSixApi::class,
            ]
        ]);
    }
}
