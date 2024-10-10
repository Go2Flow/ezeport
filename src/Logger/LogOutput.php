<?php

namespace Go2Flow\Ezport\Logger;

use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Facades\Log;
use Go2Flow\Ezport\ContentTypes\ActivityLog;

class LogOutput
{
    private ?ActivityLog $log = null;

    public function __construct(private int $projectId, string $errorType = 'isApi')
    {
        if ($action = Project::find($this->projectId)->actions()?->whereActive(true)->first()) {
            $this->log = (new ActivityLog)->$errorType()->action($action);
        }
    }

    public function properties(array $properties) : self{

        $this->log->properties($properties);

        return $this;
    }

    public function log($message, $priority): void
    {
        if ($this->log) {
            $this->log
                ->level($priority)
            ->log(json_encode($message));
        }

        Log::info($message);
    }
    public function api() : self
    {
        return $this->setType('isApi');
    }
    public function shop() : self
    {
        return $this->setType('isShop');
    }
    public function job() : self
    {
        return $this->setType('isJob');
    }
    public function model() : self
    {
        return $this->setType('isModel');
    }

    public function error() : self
    {
        return $this->setType('isError');
    }

    private function setType(string $type) : self {

        if ($this->log) $this->log->$type();

        return $this;
    }
}
