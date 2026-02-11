<?php

namespace Go2Flow\Ezport\Helpers\Traits\External;

use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\ContentTypes\Generic;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasGeneric
{
    public function generic(): Generic
    {
        return $this->genericModel->toContentType();
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
}
