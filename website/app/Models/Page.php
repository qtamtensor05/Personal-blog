<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Page extends Model
{
    protected $guarded = ['id', 'blog_id', 'created_by', 'published_at'];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'draft_data' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
