<?php

namespace Go2Flow\Ezport\Models;

use App\Models\Error;
use App\Models\Project;
use Go2Flow\Ezport\Models\Activity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
     * Go2Flow\Ezport\Models\Action
     * @property int $id
     * @property int $project_id
     * @property string $name
     * @property string $type
     * @property bool $active
     * @property string $queue
     * @property Project $project
     */

class Action extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'action_id', 'id');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(Error::class, 'action_id', 'id');
    }
}
