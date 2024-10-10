<?php

namespace Go2Flow\Ezport\Finders;


use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Stringable;

/**
 * @method static Api Api(Project $project, string $type, ?string $name = null)
 * @method static Instruction Instruction(Project $project, string $type)
 * @method static Config Config(Project $project)
 * @method static Upload Upload(Project $project, string $type)
 * @method static Import Import(Project $project, string $type)
 * @method static Transform Transform(Project $project, string $type)
 */

class Find {

    public static function __callStatic(string|Stringable $name, ?array $arguments = [])
    {
        return new ('Go2Flow\Ezport\Finders\\' . ucfirst($name))( $arguments);
    }
}
