<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Post extends Model
{
    protected $guarded = ['id', 'blog_id', 'author_user_id', 'published_at'];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'draft_data' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }
}
