<?php

namespace Go2Flow\Ezport\Commands;

use Go2Flow\Ezport\PrepareProject\CustomerFilesCreator;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\text;

class MakeCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $name = text(
            label: 'Please provide an name for the customer',
            required: 'A name is required',
        );

        $identifier = text(
            label: 'Please provide an identifier for the customer',
            required: 'An identifier is required',
            validate: fn (string $name) => match (true) {
                ! Str::isAscii($name) => 'The identifier must be ascii characters only',
                Project::whereIdentifier($name)->exists() => 'The identifier must be unique',
                default => null
            }
        );

        $project = Project::create([
            'name' => Str::ucfirst($name),
            'identifier' => $identifier = Str::ucfirst($identifier),
            'settings' => [],
            'cache' => [],
        ]);

        (new CustomerFilesCreator($project))->createCustomer();

        $this->info('Customer created: ' . $name);
    }
}
