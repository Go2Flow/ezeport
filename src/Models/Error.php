<?php

namespace Go2Flow\Ezport\Models;

use App\ContentTypes\Interfaces\LogInterface;
use App\Models\Action;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Error extends Model implements LogInterface
{
    use HasFactory;

    protected $guarded  = [];

    public function action() {

        return $this->belongsTo(Action::class);
    }

    protected $casts = [
        'properties' => AsCollection::class,
    ];
}
