<?php

namespace Go2Flow\Ezport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * App\Models\Connector
     * @property int $id
     * @property string $host
     * @property string $username
     * @property string $password
     * @property int $project_id
     */

class Connector extends Model
{


    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getValues()
    {
        return [
            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->password,
            'project_id' => $this->project_id,
        ];

    }
}
