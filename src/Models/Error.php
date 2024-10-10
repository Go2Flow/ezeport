<?php

namespace Go2Flow\Ezport\Models;

use Go2Flow\Ezport\ContentTypes\Interfaces\LogInterface;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Error extends Model implements LogInterface
{
    use HasFactory;

    protected $guarded  = [];

    public function action() : BelongsTo {

        return $this->belongsTo(Action::class);
    }

    protected $casts = [
        'properties' => AsCollection::class,
    ];
}
