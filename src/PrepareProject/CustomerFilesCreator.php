<?php

namespace Go2Flow\Ezport\PrepareProject;

use Go2Flow\Ezport\Models\Project;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class CustomerFilesCreator
{
    private $disk;

    public function __construct(private Project $project)
    {
        $this->disk = new File();
    }

    public function createCustomer() : void
    {
        $this->createFolders();
        $this->createBasicStubs();
    }

    private function createBasicStubs() : void
    {
        $this->createInstructionFromStubs();
        $this->createConfigFromStub();
    }

    private function createConfigFromStub() : void
    {
        $this->makeFile(
            '/config',
            $this->getStub('config')
        );
    }

    private function createInstructionFromStubs() : void
    {
        foreach ($this->instructions() as $instruction => $value) {

            $file = $this->basicPrepend($this->getStub('Instruction'), 'Instructions')
                ->replace('$CLASSNAME$', $instruction)
                ->append($this->instructionsAdd($value))
                ->replace('$NAME$', $this->project->name)->replace('$IDENTIFIER$', $this->project->identifier);

            if ($this->fileExists($instruction)) continue;
            $this->makeFile('/Instructions/' . $instruction, $file->toString());
        }
    }

    private function instructionsAdd(?string $value) : Stringable
    {
        return $value
            ? $this->getStub($value)
            : $this->getStub('SimpleInstructions');
    }

    private function basicPrepend(Stringable $string, string $folder) : Stringable
    {
        foreach ($this->classes() as $class) {
            $string = $string->prepend($class . "\n");
        }

        return $string->prepend("namespace App\Customers\\" . $this->project->identifier . "\\" . $folder .  ";\n\n")
            ->prepend("<?php \n\n");
    }

    private function createFolders() : void
    {
        $this->makeDirectory($this->project->identifier);

        foreach (['Instructions'] as $folder) {
            $this->makeDirectory($this->project->identifier . '/' . $folder);
        }
    }

    private function getStub(?string $stub) : Stringable
    {
        return Str::of(File::get('vendor/go2flow/ezport/stubs/' . $stub . '.stub'));
    }

    private function makeDirectory(string $path) : void
    {
        $this->disk::makeDirectory($this->setPath($path));
    }

    private function makeFile(string $path, $content) : void
    {
        $this->disk::put($this->setPath($this->project->identifier . $path . '.php'), $content);
    }

    private function fileExists(string $path) : bool
    {
        return $this->disk::exists($this->setPath($this->project->identifier . '/' . $path . '.php'));
    }

    private function setPath(string $path) : string
    {
        return 'app/Customers/'  . $path;
    }

    private function instructions() : array
    {
        return [
            'Api' => null,
            'Clean' => null,
            'Import' => null,
            'Jobs' => null,
            'Processors' => null,
            'Project' => 'Project',
            'Schedule' => null,
            'Transform' => null,
            'Upload' => null,
        ];
    }

    private function classes() : array
    {
        return [
            'use Go2Flow\Ezport\Instructions\Setters\Set;',
            'use Go2Flow\Ezport\Instructions\Getters\Get;'
        ];
    }
}
