<?php

namespace Go2Flow\Ezport\Models;

use Go2Flow\Ezport\ContentTypes\Interfaces\LogInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

class Action extends Model implements LogInterface
{
    use HasFactory;

    protected $guarded = [];

    public function project() : BelongsTo
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

    public function finish() : self
    {
        $this->update([
            'finished_at' => now(),
            'active' => false,
        ]);

        return $this;
    }
}
