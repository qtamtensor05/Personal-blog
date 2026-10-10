<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Template extends Model
{
    protected $guarded = ['id', 'created_by', 'published_by', 'published_at', 'version_number'];

    protected function casts(): array
    {
        return ['template_data' => 'array', 'draft_data' => 'array', 'published_at' => 'datetime'];
    }
}
