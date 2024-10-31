<?php

namespace Go2Flow\Ezport\ContentTypes;

use Go2Flow\Ezport\ContentTypes\Interfaces\LogInterface;
use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Activity;
use Go2Flow\Ezport\Models\Error;

class ActivityLog {

    private ?LogInterface $activity;

    public function __construct() {
        $this->activity = new Activity();
    }

    public function type(string $type = 'standard') : self
    {
        $this->activity = match($type) {
            'error' => new Error(),
            default => new Activity(),
        };

        return $this;
    }

    public function genericType(string $string) : self
    {
        return $this->setField($string, 'type');
    }

    public function uniqueId(string $unique_id) : self
    {
        return $this->setField($unique_id, 'unique_id');
    }

    public function action(Action $action) : self
    {
        return $this->setField($action->id, 'action_id');
    }

    public function model(Generic $model) : self
    {
        return $this->setField($model->id, 'generic_model_id');
    }

    public function properties(array $properties) : self
    {
        return $this->setField($properties, 'properties');
    }
    public function level(string $level) : self
    {
        return $this->setField($level, 'level');
    }

    public function log(string $description) : void
    {
        $this->setField($description, 'description');

        $this->activity->save();
    }

    public function isJob() : self
    {
        return $this->type('error')
            ->setErrorType('job');
    }

    public function isShop() : self
    {
        return $this->type('standard')
            ->setActivityType('shop');
    }

    public function isError() : self
    {
        return $this->type('error');
    }

    public function isModel() : self
    {
        return $this->type('generic_model')
            ->setActivityType('generic_model');
    }

    public function isApi() : self
    {
        return $this->type('error')
            ->setErrorType('api');
    }

    public function contentType(string $type) : self
    {
        return $this->setField($type, 'generic_model_type');
    }

    private function setErrorType(string $type) : self
    {
        return $this->setField($type, 'error_type');
    }

    private function setActivityType(string $activityType) : self
    {
        return $this->setField($activityType, 'activity_type');
    }

    private function setField(mixed $value, string $key) : self
    {
        if (! $this->activity) $this->type('standard');

        $this->activity->$key = $value;

        return $this;
    }
}
