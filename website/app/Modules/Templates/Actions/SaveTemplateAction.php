<?php

namespace App\Modules\Templates\Actions;

use App\Models\User;
use App\Modules\Design\DesignDocument;
use App\Modules\PublicSite\Rendering\SafeSectionRegistry;
use App\Shared\Contracts\AuditRecorder;
use App\Shared\Support\JsonFingerprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveTemplateAction
{
    public function __construct(
        private readonly AuditRecorder $audit,
        private readonly SafeSectionRegistry $sections,
    ) {}

    public function execute(User $actor, ?int $id, array $input, string $fingerprint, string $intent): int
    {
        abort_unless($actor->canAccessApplication() && $actor->hasPlatformPermission('platform.templates.manage'), 403);
        if ($intent !== 'draft') {
            abort_unless($actor->hasPlatformPermission('platform.templates.publish'), 403);
        }
        abort_unless(in_array($intent, ['draft', 'publish', 'hide']), 422);
        $data = Validator::make($input, ['name' => 'required|string|max:160', 'slug' => 'required|alpha_dash|max:160', 'description' => 'nullable|string|max:2000', 'category_id' => 'nullable|integer', 'preview_media_id' => 'nullable|integer'])->validate();

        return DB::transaction(function () use ($actor, $id, $input, $fingerprint, $intent, $data): int {
            $row = $id ? DB::table('templates')->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first() : null;
            abort_if($id && ! $row, 404);
            if ($row && ! hash_equals(JsonFingerprint::make((array) $row), $fingerprint)) {
                throw ValidationException::withMessages(['conflict' => 'Mẫu đã được sửa ở tab khác. Hãy tải lại.']);
            }
            if (DB::table('templates')->where('slug', $data['slug'])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn mẫu đã tồn tại.']);
            }
            $base = json_decode($row?->draft_data ?? $row?->template_data ?? '{"schemaVersion":1,"project":{"pages":[{"id":"home","sections":[{"id":"posts","type":"post-list"}]}]},"sectionRules":{"allowedTypes":["hero","text","post-list"]}}', true);
            $designInput = $input;
            $designInput['description'] = $input['hero_description'] ?? $input['description'] ?? '';
            $draft = DesignDocument::apply($base, $designInput);
            $this->sections->validate($draft);
            if (! empty($data['preview_media_id']) && ! DB::table('media_assets')->where(['id' => $data['preview_media_id'], 'scope' => 'PLATFORM', 'status' => 'READY', 'delivery_type' => 'upload'])->whereNull('deleted_at')->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['preview_media_id' => 'Ảnh xem trước phải là tài nguyên nền tảng công khai, sẵn sàng.']);
            }
            $values = array_intersect_key($data, array_flip(['name', 'slug', 'description', 'preview_media_id'])) + ['updated_by' => $actor->id, 'updated_at' => now(), 'draft_data' => json_encode($draft, JSON_THROW_ON_ERROR)];
            if ($intent === 'publish') {
                $values += ['template_data' => $values['draft_data'], 'status' => 'PUBLISHED', 'version_number' => ($row?->version_number ?? 0) + 1, 'published_by' => $actor->id, 'published_at' => now()];
                $values['draft_data'] = null;
            }
            if ($intent === 'hide') {
                $values['status'] = 'HIDDEN';
            }
            if ($id) {
                DB::table('templates')->where('id', $id)->update($values);
            } else {
                $id = DB::table('templates')->insertGetId($values + ['created_by' => $actor->id, 'created_at' => now()]);
            }
            if (! empty($data['category_id']) && ! DB::table('template_categories')->where('id', $data['category_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['category_id' => 'Chủ đề mẫu không hợp lệ.']);
            }
            DB::table('template_category_assignments')->where('template_id', $id)->delete();
            if (! empty($data['category_id'])) {
                DB::table('template_category_assignments')->insert(['template_id' => $id, 'category_id' => $data['category_id'], 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->audit->record($actor->id, 'template.'.$intent, 'template', $id);

            return $id;
        });
    }
}
