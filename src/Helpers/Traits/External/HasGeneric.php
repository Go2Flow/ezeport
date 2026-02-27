<?php

namespace App\Ezport\Helpers\Traits\External;

use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\ContentTypes\Generic;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasGeneric
{
    public function generic($layers = [true, false]): Generic
    {
        return $this->genericModel->toContentType($layers);
    }

    public function genericModel() : MorphOne
    {
        return $this->morphOne(GenericModel::class, 'morph');
    }

    public function attachGeneric(Generic $generic) : self
    {
        $generic->attachExternal($this);

        return $this;
    }

    public function detachGeneric(Generic $generic) : self
    {
        $generic->detachExternal();

        return $this;
    }
}
