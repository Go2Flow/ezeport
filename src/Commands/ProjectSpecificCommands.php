<?php

namespace Go2Flow\Ezport\Commands;

use Go2Flow\Ezport\Commands\Prepare\Deleter;
use Go2Flow\Ezport\Commands\PrepareProject\CreateProject;
use Go2Flow\Ezport\Commands\PrepareProject\CreateProjectCache;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Connector;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Batches\JobBatcher;
use Go2Flow\Ezport\Process\Batches\Tools\UploadManager;
use Illuminate\Support\Str;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;


class ProjectSpecificCommands
{
    public function __construct(private readonly Project $project){}

    public function addToUpload(?string $type = null): string
    {
        if ($this->warning($this->project->connectorType('shopSix'))) return 'Operation cancelled';

        $type = $type
            ? Str::singular($type)
            : select(
                'What content type would you like to add?',
                GenericModel::whereProjectId($this->project->id)
                    ->whereUpdated(false)
                    ->pluck('type')
                    ->unique()
                    ->sort()
                    ->merge('All')
            );

        GenericModel::whereProjectId($this->project->id)
            ->when(
                $type !== 'All',
                fn ($builder) => $builder->whereType($type)
            )->chunk(
                100,
                fn ($models) => $models->each->update(['updated' => true])
            );

        return 'Content added to upload';
    }
    public function removeFromUpload(?string $type = null): string
    {
        if ($this->warning($this->project->connectorType('shopSix'))) return 'Operation cancelled';

        $type = $type
            ? Str::singular($type)
            : select(
                'What content type would you like to remove?',
                GenericModel::whereProjectId($this->project->id)
                    ->whereUpdated(true)
                    ->pluck('type')
                    ->unique()
                    ->sort()
                    ->merge('All')
            );

        GenericModel::whereProjectId($this->project->id)
            ->when(
                $type !== 'All',
                fn ($builder) => $builder->whereType($type)
            )
            ->chunk(
                100,
                fn ($models) => $models->each->update(['updated' => false])
            );

        return 'Content removed from upload';
    }

    public function createConnector()
    {
        $newOrUpdate = select(
            label: 'Would you like to create a new connector or update an existing one?',
            options: ['create', 'update']
        );

        $name = select(
            label: 'Please give the name of the connector would you like to ' . $newOrUpdate . '?',
            options: collect(['shopFive', 'shopSix', 'ftp'])->mapWithKeys(fn ($type) => [$type => Str::ucfirst($type)])->toArray(),
        );

        $connectorType = select(
            label: 'What type of connector would you like to ' . $newOrUpdate . '?',
            options: collect(['shopFive', 'shopSix', 'ftp'])->mapWithKeys(fn ($type) => [$type => Str::ucfirst($type)])->toArray(),
        );

        $host = text(
            label: 'Please provide the connector\'s host' . ($newOrUpdate == 'update' ? ' (leave empty to keep current)' : ''),
            required : $newOrUpdate == 'update' ? false : true
        );

        $username = text(
            label: 'Please provide the connector\'s username' . ($newOrUpdate == 'update' ? ' (leave empty to keep current)' : ''),
            required : $newOrUpdate == 'update' ? false : true
        );

        $password = password(
            label: 'Please provide the connector\'s password' . ($newOrUpdate == 'update' ? ' (leave empty to keep current)' : ''),
            required : $newOrUpdate == 'update' ? false : true
        );

        $environment = select(
            label: 'What environment is this connector for?',
            options: ['production', 'staging']
        );

        $connector = (!$newOrUpdate == 'update')
            ? new Connector
            : $this->project->connectors()
                ->whereType($connectorType)
                ->when(
                    $name,
                    fn($item) => $item->where('name', $name)
                )->first();

        $connector->fill([
            'type' => $connectorType ,
            'host' => $host == '' ? $connector->host : $host,
            'username' => $username == '' ? $connector->username : $username,
            'password' => $password == '' ? $connector->password : $password,
            'environment' => $environment,
            'project_id' => $this->project->id,
            'name' => $name ?? null
        ])->save();

        return 'Connector ' . $newOrUpdate . 'd';
    }

    public function runJobs(string $method, ?string $type, string $job): void
    {
        $job = Str::lower($job);

        $type = $type && collect($this->getOptions($job))
            ->contains(Str::lower($type))
                ? Str::lower($type)
                : null;

        if ($method == 'runClean') {

            $method = $type ?? select(
                label: 'What would you like to clean?',
                options: [
                    'runFtpClean' => 'ftp',
                    'runShopClean' => 'shop',
                    'runClean' => 'standard'
                ]
            );

            if ($method == 'runShopClean') {
                $job = 'shopClean';
            }
        }

        $type = $type ?? select(
            label: 'What type of '. $method . ' do you want to run?',
            options: $this->getOptions($job)
        );

        (new JobBatcher($this->project, new UploadManager($this->project)))
            ->startAction(Str::after($method, 'run'), $type)
            ->$method($type);
    }

    public function delete(): string
    {
        if ($this->warning($connector = $this->project->connectorType('shopSix'))) return 'operation cancelled';

        $type = select(
            label: 'What would you like to delete on ' . $connector->host   . '?',
            options: array_merge($options = [
                'product' => 'Articles',
                'category' => 'Categories',
                'propertyGroup' => 'Properties',
                'manufacturer' => 'Manufacturers',
                'media' => 'Media',
                'order' => 'Orders',
            ], ['all' => 'All'])
        );

        $deleter = new Deleter((new Api([$this->project, 'shopSix']))->get());

        if ($type !== 'all') {
            $deleter->remove($type);
            return 'Delete completed';
        }

        $progress = progress(label: 'Deleting', steps: count($options));
        foreach ($options as $option => $label) {
            $deleter->remove($option);

            $progress->advance();
        }

        return 'Everything Deleted';
    }

    public function prepareShop(): void
    {
        (new CreateProjectCache((new CreateProject($this->project))->run()))->prepareCache();
    }

    private function warning(Connector $connector): bool
    {
        if ($connector->environment !== 'production') return false;

        return confirm(
            label: 'This is a production environment! Are you sure?',
            default: false,
            yes: 'Yes, continue',
            no: 'What? No! Stop!!',
        ) ? false : true;
    }

    private function getOptions($job) : array {

        return Find::instruction(Project::first(), "Jobs")
            ->collect()
            ->filter(fn($item) => $item->get("key") == $job)
            ->mapWithKeys(fn($item) => [$item->get("type") => $item->get("type")])
            ->toArray();
    }
}
