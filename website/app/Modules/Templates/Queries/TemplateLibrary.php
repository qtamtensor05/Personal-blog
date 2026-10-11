<?php

namespace App\Modules\Templates\Queries;

use Illuminate\Support\Facades\DB;

final class TemplateLibrary
{
    public function query()
    {
        return DB::table('templates')->select('templates.*')->selectSub(DB::table('media_assets')->select('secure_url')->whereColumn('id', 'templates.preview_media_id')->where(['scope' => 'PLATFORM', 'status' => 'READY', 'delivery_type' => 'upload'])->whereNull('deleted_at')->limit(1), 'preview_url')->where('status', 'PUBLISHED')->whereNull('deleted_at');
    }

    public function find(int $id): object
    {
        return $this->query()->where('id', $id)->firstOrFail();
    }

    public function search(string $search = '', string $category = ''): array
    {
        $query = $this->query();
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%'));
        }
        if ($category !== '') {
            $query->whereIn('id', DB::table('template_category_assignments')->select('template_id')->where('category_id', $category));
        }

        return ['templates' => $query->orderBy('name')->paginate(9)->withQueryString(),
            'categories' => DB::table('template_categories')->where('is_active', true)->orderBy('name')->get()];
    }
}
