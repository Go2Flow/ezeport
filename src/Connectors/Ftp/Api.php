<?php

namespace Go2Flow\Ezport\Connectors\Ftp;

use Go2Flow\Ezport\Models\Connector;
use Go2Flow\Ezport\Connectors\ApiInterface;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Api implements ApiInterface
{
    private $baseFolder;
    private $connection;
    private $storage;
    private $files;
    private string $directory;

    private string $identifier;

    public function __construct(array $connector, private Collection $structure, string $drive = 'public')
    {

        $project = Project::find($connector['project_id']);
        $this->identifier = $project->identifier;

        $this->baseFolder = ucfirst($project->identifier) . '/';

        $this->storage = Storage::drive($drive);

        config([
            'filesystems.disks.ftp' => [
                'driver' => 'ftp',
                'host' => $connector['host'],
                'username' => $connector['username'],
                'password' => $connector['password'],
            ],
        ]);

        $this->connection = Storage::drive('ftp');
    }

    /**
     * Get all the files in the specified directory
     */

    public function get()
    {
        return $this->list()->map(
            fn ($name) => $this->getFile($name)
        );
    }

    /**
     * Get all the files in the specified directory and store them in the storage specified in the 2nd parameter of the constructor
     */

    public function getAndStore()
    {
        return $this->list()->map(
            function ($name) {
                $this->storeFile(
                    $name,
                    $file = $this->getFile($name)
                );

                return $file;
            }
        );
    }

    /**
     * take the specified file out of the list stored in memory (not from the ftp server)
     */

    public function removeFromList(array $array) : self
    {
        if (! $this->checkDirectory()) $this->files = false;

        $this->files = $this->checkDirectory()
            ->filter(
                fn ($item) => ! collect($array)->contains(Str::afterLast($item, '/'))
            );

        return $this;
    }

    /**
     * Move a file from one directory to another.
     * If the destination folder does not exist it will be created
     */

    public function moveFile($file, $destination, $newName = null)
    {
        if (!$this->connection->directoryExists($destination)) $this->connection->makeDirectory($destination);

        $this->connection->move(
            $file,
            $destination . '/' . ($newName ?? Str::afterLast($file, '/'))
        );
    }

    /**
     * put the specified file in the storage directory specified in the 2nd paramater of the constructor
     */

    public function post($file) : string|bool
    {
        return $this->connection->put(
            $this->directory . '/' . $file,
            $this->storage->get($this->baseFolder . $this->directory . '/' . $file)
        );
    }

    /**
     * Delete the specified file from the ftp server
     */

    public function delete($file) : bool
    {
        return $this->connection->delete($file);
    }

    /**
     * Go through the list of files and keep only those who are jpg, png or jpeg
     */

    public function imagesOnly(): self
    {

        $this->files = $this->list()->filter(
            fn ($file) => Str::of($file)->lower()->contains(['.jpg', '.png', '.jpeg'])
        );

        return $this;
    }

    /**
     * Upload a file to the ftp server
     */

    public function upload($name, $file) : self
    {
        $this->connection->put(
            $this->directory . '/' . $name,
            $file
        );

        return $this;
    }

    /**
     * Get the last modified date of the specified file
     */

    public function lastModified($file) : int
    {
        return $this->connection->lastModified($file);
    }

    /**
     * Get the list of files in the specified directory
     */

    public function list() : Collection
    {
        return $this->files ?: $this->checkDirectory();
    }

    /**
     * Check if the specified directory exists and if it does, get the list of files in it
     */

    private function checkDirectory()
    {
        return Cache::remember(
            'ftp-' .  $this->identifier . '-' . $this->directory,
            300,
            function () {

                if (!$this->connection->directoryExists($this->directory) || ! $names = $this->connection->allFiles($this->directory)) {

                    return collect();

                }

                return collect($names);
            }
        );
    }

    /**
     * pass in an array or collection of identifiers to have these found in the list and returned sorted by their key.
     */

    public function find(array|Collection $identifiers)
    {
        return collect($identifiers)
            ->flatMap(
                fn ($identifier) => $this->list()->filter(
                    fn ($image) => Str::of($image)
                        ->contains($identifier)
                )->mapWithKeys(
                    fn ($file) => [$identifier => $this->getFile($file)]
                )
            );
    }

    /**
     * pass in an identifier to have the file found in the list and saved locally on a drive specified in the 2nd paramater of the constructor
     */

    public function findAndStore($identifier, $path)
    {
        $files = $this->list()
            ->filter(fn ($file) => Str::of($file)->contains($identifier));

        if ($files->count() == 0) throw new \Exception('File not found');

        $files->map(
                function ($file) use ($path) {
                $this->storeFile(
                    $path. '/' . Str::afterLast($file, '/'),
                    $this->getFile($file)
                );

                return $file;
            }
        );
    }

    /**
     * return the FilesystemAdapter instance
     */

    public function connector() : FilesystemAdapter
    {
        return $this->connection;
    }

    private function getFile($file)
    {
        return $this->connection->get($file);
    }

    private function storeFile($name, $file)
    {
        if (!$this->storage->directoryExists($this->baseFolder)) $this->storage->makeDirectory($this->baseFolder);

        $this->storage->put(
            $this->baseFolder . $name,
            $file
        );
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }

        $this->directory = $this->structure[$method] ?? $method;

        return $this;
    }
}
