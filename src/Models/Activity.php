<?php

namespace Go2Flow\Ezport\Models;

use Go2Flow\Ezport\ContentTypes\Interfaces\LogInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Activity extends Model implements LogInterface
{
    /**
     * Go2Flow\Ezport\Models\Activity
     * @property int $id
     * @property int $action_id
     * @property string $unique_id
     * @property string $generic_model_type
     * @property int $generic_model_id
     * @property Collection $properties
     */

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'properties' => AsCollection::class,
    ];

    public function action() : BelongsTo
    {
        return $this->belongsTo(Action::class);
    }
}
