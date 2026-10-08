<?php

namespace Go2Flow\Ezport\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * App\Models\Connector
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $environment
 * @property string $host
 * @property string $username
 * @property string $password
 * @property ?Collection $properties
 * @property ?bool $encrypted
 * @property int $project_id
 */
class Connector extends Model
{
    use HasFactory;

    protected $casts = [
        'properties' => AsCollection::class,
        'encrypted' => 'boolean',
    ];

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Connection values as consumed by the connector Api classes.
     * The password is always returned in plain text: stored encrypted
     * passwords are decrypted here, so no connector has to handle it.
     */
    public function getValues(): array
    {
        return [
            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->encrypted ? decrypt($this->password) : $this->password,
            'project_id' => $this->project_id,
            'properties' => $this->properties,
        ];
    }
}
