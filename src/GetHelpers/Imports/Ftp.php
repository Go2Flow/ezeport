<?php

namespace Go2Flow\Ezport\GetHelpers\Imports;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Str;

class Ftp extends BaseInstructions implements InstructionInterface {

    public function get () : array{

        return [
            Set::FtpFileImport('createImageGroups')
                ->Job(
                    Set::Job()
                        ->config(['path' => 'images'])
                )->prepare(
                    function ($api, $config) {

                        $files = $api->image()
                            ->imagesOnly()
                            ->list();

                        return $files->map(
                            fn($key) => Str::of($key)
                                ->after("/")
                                ->before("_")
                                ->toString()
                            )->unique()
                            ->mapWithKeys(
                                fn($name) => [
                                $name => $files->filter(
                                    fn ($file) => $this->checkConfig(
                                        $config,
                                        'filter',
                                        fn ($file) => Str::of($file)->after('/')->startsWith($name . '_')
                                    )($file)
                                )
                                ]
                            );
                    }
                )->process(
                    fn ($collection, $config, $api) =>
                        $collection->each(
                            fn ($content, $name) => (
                                (new Generic([
                                    'project_id' => $this->project->id,
                                    'unique_id' => $name,
                                    'type' => 'ImageGroup'
                                ])
                                )->setContentAndRelations([

                                    'images' => $content->map(
                                        fn ($file) => $this->checkConfig(
                                            $config,
                                            'file',
                                            fn ($file) => [
                                                'name' => Str::of($file)->after($name . '_')->beforeLast('.')->toString(),
                                                'path' => $file,
                                                'modified' => $api->image()->lastModified($file)
                                            ]
                                        )($file)
                                    )
                                ])->updateOrCreate(true)
                            )
                        )
                ),
        ];
    }

    private function checkConfig(array $config, string $key, \closure $closure)
    {
        return isset($config[$key]) ? $config[$key] : $closure;
    }
}
