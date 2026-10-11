<?php

namespace App\Modules\Identity\Auth;

use Illuminate\Auth\EloquentUserProvider;

final class ActiveUserProvider extends EloquentUserProvider
{
    private array $softDeleteColumns = [];

    protected function newModelQuery($model = null)
    {
        $query = parent::newModelQuery($model);
        $model = $query->getModel();

        $key = $model->getConnection()->getDatabaseName().'.'.$model->getTable();
        $hasColumn = $this->softDeleteColumns[$key] ??= $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'deleted_at');

        return $hasColumn
            ? $query->whereNull($model->qualifyColumn('deleted_at'))
            : $query;
    }
}
