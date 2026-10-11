<?php

namespace App\Modules\Identity\Auth;

use Illuminate\Auth\EloquentUserProvider;

final class ActiveUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->whereNull('deleted_at');
    }
}
