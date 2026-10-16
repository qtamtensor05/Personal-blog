<?php

namespace App\Modules\Content\Queries;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;

final class ContentWorkspace
{
    public function __construct(
        private readonly OwnerContext $owners,
    ) {}

    public function table(string $kind): string
    {
        abort_unless(in_array($kind, ['posts', 'pages', 'categories', 'tags']), 404);

        return $kind;
    }

    public function rows(User $actor, int $blog, string $kind, string $q = '', string $status = '')
    {
        $this->owners->resolve($actor, $blog);
        $query = DB::table($this->table($kind))->where('blog_id', $blog);
        if (in_array($kind, ['posts', 'pages'])) {
            $query->whereNull('deleted_at');
            if ($status !== '') {
                $query->where('status', $status);
            }
        }
        if ($q !== '') {
            $query->where(in_array($kind, ['posts', 'pages']) ? 'title' : 'name', 'like', '%'.$q.'%');
        }

        return $query->orderByDesc('id')->get();
    }

    public function find(User $actor, int $blog, string $kind, int $id): object
    {
        $this->owners->resolve($actor, $blog);
        $query = DB::table($this->table($kind))->where(['blog_id' => $blog, 'id' => $id]);
        if (in_array($kind, ['posts', 'pages'])) {
            $query->whereNull('deleted_at');
        }

        return $query->firstOrFail();
    }
}
