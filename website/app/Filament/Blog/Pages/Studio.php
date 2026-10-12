<?php

namespace App\Filament\Blog\Pages;

use App\Modules\Blogs\Actions\SaveBlogSettingsAction;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Blogs\Queries\BlogSettingsQuery;
use App\Modules\Content\Actions\ArchiveContentAction;
use App\Modules\Content\Actions\SaveContentAction;
use App\Modules\Content\Actions\SaveTermAction;
use App\Modules\Content\Queries\ContentRelations;
use App\Modules\Content\Queries\ContentWorkspace;
use App\Modules\Design\Actions\PublishDesignAction;
use App\Modules\Design\Actions\SaveDesignAction;
use App\Modules\Design\DesignDocument;
use App\Modules\Design\Queries\DesignWorkspace;
use App\Modules\Media\Actions\DeleteMediaAction;
use App\Modules\Media\Actions\UpdateMediaAction;
use App\Modules\Media\Actions\UploadBlogImageAction;
use App\Modules\Media\Queries\MediaLibrary;
use App\Modules\Templates\Queries\TemplateLibrary;
use App\Shared\Support\JsonFingerprint;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

class Studio extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.blog.studio';

    protected static ?string $navigationLabel = 'Quản lý blog';

    protected static ?string $title = 'Blog Admin';

    #[Url(as: 'blog')]
    public int $blogId = 0;

    #[Url]
    public string $section = 'overview';

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    public ?int $editing = null;

    public array $form = [];

    #[Locked]
    public array $originalSettings = [];

    public string $fingerprint = '';

    public string $message = '';

    public $image;

    public string $alt = '';

    public ?int $templateId = null;

    public function mount(): void
    {
        if (! $this->blogId) {
            $this->blogId = (int) app(OwnerContext::class)->list(auth()->user())->first()?->id;
        }
        if ($this->blogId) {
            $this->loadSection();
        }
    }

    public function updatedBlogId(): void
    {
        $this->loadSection();
    }

    public function updatedSection(): void
    {
        $this->loadSection();
    }

    public function loadSection(): void
    {
        app(OwnerContext::class)->resolve(auth()->user(), $this->blogId);
        $this->editing = null;
        $this->form = [];
        $this->originalSettings = [];
        $this->resetErrorBag();
        $this->message = '';
        if (in_array($this->section, ['design', 'navigation'])) {
            $row = app(DesignWorkspace::class)->find(auth()->user(), $this->blogId);
            $base = json_decode($row?->draft_data ?? $row?->published_data ?? '{}', true);
            $this->form = DesignDocument::form($base);
            $this->form['project'] = $base['project'] ?? [];
            $this->templateId = $row?->draft_template_id ?? $row?->template_id;
            $this->fingerprint = JsonFingerprint::make($base);
        }
        if ($this->section === 'settings') {
            $this->form = app(BlogSettingsQuery::class)->find(auth()->user(), $this->blogId);
            $this->originalSettings = $this->form;
        }
    }

    public function edit(?int $id = null): void
    {
        abort_unless(in_array($this->section, ['posts', 'pages']), 404);
        $this->resetErrorBag();
        $this->editing = $id ?? 0;
        $this->form = ['title' => '', 'slug' => '', 'excerpt' => '', 'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]], 'seo_title' => '', 'seo_description' => '', 'cover_media_id' => null, 'scheduled_at' => null, 'category_ids' => [], 'tag_ids' => []];
        $this->fingerprint = '';
        if ($id) {
            $row = app(ContentWorkspace::class)->find(auth()->user(), $this->blogId, $this->section, $id);
            $this->fingerprint = SaveContentAction::fingerprint($row);
            $data = (array) $row;
            $draft = json_decode($row->draft_data ?? '{}', true);
            foreach (array_keys($this->form) as $key) {
                $this->form[$key] = $draft[$key] ?? $data[$key] ?? $this->form[$key];
            }
            if (is_string($this->form['content_json'])) {
                $this->form['content_json'] = json_decode($this->form['content_json'], true);
            }
            $this->form = array_merge($this->form, app(ContentRelations::class)->find(auth()->user(), $this->blogId, $id));
            foreach (['category_ids', 'tag_ids'] as $key) {
                if (isset($draft[$key])) {
                    $this->form[$key] = $draft[$key];
                }
            }
        }
    }

    public function save(string $intent = 'draft'): void
    {
        abort_unless($this->editing !== null, 422);
        $id = app(SaveContentAction::class)->execute(auth()->user(), $this->blogId, $this->section, $this->editing ?: null, $this->form, $this->fingerprint, $intent);
        $this->edit($id);
        $this->message = match ($intent) {
            'publish' => 'Đã xuất bản.', 'schedule' => 'Đã lên lịch (UTC).', default => 'Đã lưu nháp. Nội dung live không thay đổi.'
        };
    }

    public function saveDesign(bool $publish = false): void
    {
        app(SaveDesignAction::class)->execute(auth()->user(), $this->blogId, $this->form, $this->fingerprint, $this->templateId);
        $this->loadSection();
        if ($publish) {
            app(PublishDesignAction::class)->execute(auth()->user(), $this->blogId, $this->fingerprint);
            $this->loadSection();
        }
        $this->message = $publish ? 'Đã xuất bản giao diện.' : 'Đã lưu giao diện nháp.';
    }

    public function saveSettings(): void
    {
        abort_unless($this->section === 'settings' && $this->originalSettings !== [], 422);
        app(SaveBlogSettingsAction::class)->execute(auth()->user(), $this->blogId, $this->form, $this->originalSettings);
        // Reload the form so it reflects the stored, normalized slug and other values.
        $this->form = app(BlogSettingsQuery::class)->find(auth()->user(), $this->blogId);
        $this->originalSettings = $this->form;
        $this->message = 'Đã lưu cài đặt.';
    }

    public function upload(): void
    {
        $this->validate(['image' => 'required|image|max:10240', 'alt' => 'nullable|string|max:500']);
        try {
            app(UploadBlogImageAction::class)->execute(auth()->user(), $this->blogId, $this->image, $this->alt);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('image', 'Không tải được ảnh. Kiểm tra kết nối dịch vụ rồi thử lại.');

            return;
        }
        $this->reset('image', 'alt');
        $this->message = 'Đã tải ảnh lên thư viện.';
    }

    public function addTerm(): void
    {
        app(SaveTermAction::class)->execute(auth()->user(), $this->blogId, $this->section, $this->form);
        $this->form = [];
        $this->message = 'Đã thêm phân loại.';
    }

    public function archive(int $id): void
    {
        app(ArchiveContentAction::class)->execute(auth()->user(), $this->blogId, $this->section, $id);
        $this->message = 'Đã lưu trữ nội dung. Nội dung không còn xuất hiện công khai.';
    }

    public function editMedia(int $id): void
    {
        $asset = app(MediaLibrary::class)->list(auth()->user(), $this->blogId)->firstWhere('id', $id);
        abort_unless($asset, 404);
        $this->editing = $id;
        $this->form = ['alt_text' => $asset->alt_text];
    }

    public function saveMedia(): void
    {
        app(UpdateMediaAction::class)->execute(auth()->user(), $this->blogId, $this->editing, $this->form);
        $this->editing = null;
        $this->message = 'Đã cập nhật văn bản thay thế.';
    }

    public function deleteMedia(int $id): void
    {
        try {
            app(DeleteMediaAction::class)->execute(auth()->user(), $this->blogId, $id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('media', 'Dịch vụ xóa chưa sẵn sàng. Thử lại sau.');

            return;
        }
        $this->message = 'Đã xóa tài nguyên không còn được sử dụng.';
    }

    protected function getViewData(): array
    {
        $blogs = app(OwnerContext::class)->list(auth()->user());
        if (! $this->blogId) {
            return compact('blogs') + ['blog' => null];
        }
        $blog = app(OwnerContext::class)->resolve(auth()->user(), $this->blogId);
        abort_unless(in_array($this->section, ['overview', 'posts', 'pages', 'categories', 'tags', 'media', 'design', 'navigation', 'settings']), 404);
        $query = app(ContentWorkspace::class);

        return compact('blogs', 'blog') + [
            'rows' => $query->rows(auth()->user(), $this->blogId, in_array($this->section, ['posts', 'pages', 'categories', 'tags']) ? $this->section : 'posts', $this->q, $this->status),
            'categories' => $query->rows(auth()->user(), $this->blogId, 'categories'),
            'pages' => $query->rows(auth()->user(), $this->blogId, 'pages'),
            'tags' => $query->rows(auth()->user(), $this->blogId, 'tags'),
            'media' => app(MediaLibrary::class)->list(auth()->user(), $this->blogId, $this->section === 'media' ? $this->q : ''),
            'templates' => app(TemplateLibrary::class)->query()->get(),
        ];
    }
}
