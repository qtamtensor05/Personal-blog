<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Blog extends Model
{
    protected $table = 'blogs';

    protected $guarded = ['id', 'owner_user_id', 'published_at'];

    protected function casts(): array
    {
        return ['discoverable' => 'boolean', 'published_at' => 'datetime'];
    }
}
