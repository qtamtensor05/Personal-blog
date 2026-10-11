<?php

namespace App\Modules\Design\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Design\DesignDocument;
use App\Modules\PublicSite\Rendering\SafeSectionRegistry;
use App\Modules\Templates\Queries\TemplateLibrary;
use App\Shared\Support\JsonFingerprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaveDesignAction
{
    public function execute(User $actor, int $blog, array $form, string $fingerprint, ?int $template = null): void
    {
        DB::transaction(function () use ($actor, $blog, $form, $fingerprint, $template): void {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blog, true);
            $row = DB::table('blog_designs')->where('blog_id', $blog)->lockForUpdate()->first();
            $base = json_decode($row?->draft_data ?? $row?->published_data ?? '{}', true);
            if (! hash_equals(JsonFingerprint::make($base), $fingerprint)) {
                throw ValidationException::withMessages(['conflict' => 'Giao diện đã được sửa ở tab khác. Hãy tải lại.']);
            }
            $templateId = $row?->draft_template_id ?? $row?->template_id;
            if ($template && $template !== (int) $templateId) {
                $source = app(TemplateLibrary::class)->find($template);
                $base = DesignDocument::snapshot($source);
                $templateId = $template;
                $form = DesignDocument::form($base);
            }
            abort_unless($templateId, 422, 'Chọn một mẫu trước khi lưu giao diện.');
            $draft = DesignDocument::apply($base, $form);
            foreach (DesignDocument::sections($draft) as $section) {
                if (! in_array($section['type'] ?? '', data_get($base, 'sectionRules.allowedTypes', ['header', 'hero', 'text', 'post-list', 'footer']), true)) {
                    throw ValidationException::withMessages(['project' => 'Mẫu không cho phép loại section này.']);
                }
            }
            if (array_key_exists('menu_page_keys', $form)) {
                $keys = array_values(array_unique($form['menu_page_keys']));
                if (DB::table('pages')->where('blog_id', $blog)->whereNull('deleted_at')->whereIn('page_key', $keys)->count() !== count($keys)) {
                    throw ValidationException::withMessages(['menu' => 'Trang trong menu không thuộc blog.']);
                }
                $draft['navigation']['pageKeys'] = $keys;
            }
            app(SafeSectionRegistry::class)->validate($draft);
            DB::table('blog_designs')->updateOrInsert(['blog_id' => $blog], ['draft_template_id' => $templateId,
                'draft_data' => json_encode($draft, JSON_THROW_ON_ERROR), 'updated_by' => $actor->id, 'updated_at' => now(), ...($row ? [] : ['created_at' => now()])]);
        });
    }
}
