<?php

namespace Go2Flow\Ezport\Logger;

use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Error;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Facades\Log;

class LogError {

    private Error $error;

    public function __construct(private int $projectId)
    {
        $this->error = New Error();
    }

    public function type(string $string) : self {

        $this->error->error_type = $string;

        return $this;
    }

    public function action(Action $action) : self {

        $this->error->action_id = $action->id;

        return $this;
    }

    public function properties(string|array $properties) : self {

        $this->error->properties = $properties;

        return $this;
    }

    public function level(string $level) : self {
        $this->error->level = $level;

        return $this;
    }

    public function log(string $message) : void {

        if ($this->error->action_id == null && $action = Project::find($this->projectId)->actions()?->whereActive(1)->first()) {
            $this->error->action_id = $action->id;
        }

        $this->error->description = $message;
        $this->error->save();

        Log::info($message);
    }
}
