<?php

namespace Go2Flow\Ezport\ContentTypes;

use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Activity;

class ActivityLog {

    private Activity $activity;

    public function __construct() {

        $this->activity = new Activity();
    }

    public function uniqueId(string $unique_id) : self
    {
        $this->activity->unique_id = $unique_id;

        return $this;
    }

    public function action(Action $action) : self
    {
        $this->activity->action_id = $action->id;

        return $this;
    }

    public function model(Generic $model) : self
    {
        $this->activity->generic_model_id = $model->id;

        return $this;
    }

    public function properties(array $properties) : self
    {
        $this->activity->properties = $properties;

        return $this;
    }

    public function log(string $description) : void
    {
        $this->activity->description = $description;

        $this->activity->save();
    }

    public function isJob() : self
    {
        return $this->setActivityType('failed_job');
    }

    public function isShop() : self
    {
        return $this->setActivityType('shop');
    }

    public function isModel() : self
    {
        return $this->setActivityType('generic_model');
    }

    public function isApi() : self
    {
        return $this->setActivityType('api');
    }

    public function type(string $type) : self
    {
        $this->activity->generic_model_type = $type;

        return $this;
    }

    private function setActivityType(string $activityType) : self
    {
        $this->activity->activity_type = $activityType;

        return $this;
    }
}
