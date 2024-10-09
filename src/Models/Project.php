<?php

namespace Go2Flow\Ezport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;

/**
     * Go2Flow\Ezport\Models\Project
     * @property int $id
     * @property string $identifier
     * @property string $name
     * @property Collection $settings
     * @property Collection $cache
     */

class Project extends BaseModel
{

    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'project_secret',
    ];

    protected $casts = [
        'settings' => AsCollection::class,
        'cache' => AsCollection::class,
    ];

    public function actions() : HasMany {

        return $this->hasMany(Action::class);
    }

    public function currentAction() : ?Action
    {
        return $this->actions()
            ->whereActive(true)
            ->latest()
            ->first();
    }

    public function users() : BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function customerStorage(string $path) : string
    {
        return 'public/' . Str::ucfirst($this->identifier) . '/' . $path;
    }

    public function connectors() : HasMany
    {
        return $this->hasMany(Connector::class);
    }

    public function connectorType(string $type) : ?Connector
    {
        return $this->connectors()->whereType($type)->first();
    }

    public function cache($input = null) : mixed
    {
        return $this->getOrSetData($input, 'cache');
    }

    public function settings($input = null) : mixed
    {
        return $this->getOrSetData($input, 'settings');
    }
}
