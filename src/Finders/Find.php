<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Stringable;

/**
 * @method static Api api(Project $project, string $type, ?string $name = null)
 * @method static Instruction instruction(Project $project, string $type)
 * @method static Config config(Project $project)
 * @method static Upload upload(Project $project, string $type)
 * @method static Import import(Project $project, string $type)
 * @method static Transform transform(Project $project, string $type)
 * @method static Processor processor(Project $project, string $type)
 */
class Find
{
    public static function __callStatic(string|Stringable $name, ?array $arguments = [])
    {
        return new ('Go2Flow\Ezport\Finders\\'.ucfirst($name))($arguments);
    }
}
