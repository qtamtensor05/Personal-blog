<?php

namespace App\Filament\Pages;

use App\Modules\Design\DesignDocument;
use App\Modules\Moderation\Actions\ModerateAction;
use App\Modules\Platform\Actions\SavePlatformSettingsAction;
use App\Modules\Platform\Actions\SaveTopicAction;
use App\Modules\Platform\Queries\PlatformWorkspace;
use App\Modules\Templates\Actions\SaveTemplateAction;
use App\Shared\Support\JsonFingerprint;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class Management extends Page
{
    protected string $view = 'filament.platform.management';

    protected static ?string $navigationLabel = 'Quản lý nền tảng';

    protected static ?string $title = 'Platform Admin';

    protected static bool $shouldRegisterNavigation = false;

    #[Url]
    public string $section = 'templates';

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    #[Url]
    public int $page = 1;

    #[Url]
    public ?int $category = null;

    public ?int $editing = null;

    public array $form = [];

    public string $fingerprint = '';

    public string $message = '';

    public string $group = 'branding';

    public function mount(): void
    {
        app(PlatformWorkspace::class)->authorize(auth()->user(), $this->section);
        if ($this->section === 'settings') {
            $this->loadGroup();
        }
    }

    public function updatedSection(): void
    {
        $this->editing = null;
        $this->form = [];
        $this->page = 1;
        $this->q = '';
        $this->status = '';
        $this->resetErrorBag();
        app(PlatformWorkspace::class)->authorize(auth()->user(), $this->section);
        if ($this->section === 'settings') {
            $this->loadGroup();
        }
    }

    public function updatedQ(): void
    {
        $this->page = 1;
    }

    public function updatedStatus(): void
    {
        $this->page = 1;
    }

    public function updatedCategory(): void
    {
        $this->page = 1;
    }

    public function resetFilters(): void
    {
        $this->q = '';
        $this->status = '';
        $this->category = null;
        $this->page = 1;
    }

    public function edit(?int $id = null): void
    {
        app(PlatformWorkspace::class)->authorize(auth()->user(), $this->section);
        $this->editing = $id ?? 0;
        $this->resetErrorBag();
        if ($this->section === 'templates') {
            $row = $id ? app(PlatformWorkspace::class)->template(auth()->user(), $id) : null;
            $base = json_decode($row?->draft_data ?? $row?->template_data ?? '{}', true);
            $this->form = DesignDocument::form($base) + ['name' => $row?->name ?? '', 'slug' => $row?->slug ?? '', 'description' => $row?->description ?? '', 'category_id' => null];
            // Hero description and catalogue description use separate form fields.
            $this->form['catalogue_description'] = $row?->description ?? '';
            $this->form['preview_media_id'] = $row?->preview_media_id;
            $this->form['category_id'] = $id ? app(PlatformWorkspace::class)->templateCategory(auth()->user(), $id) : null;
            $this->form['project'] = $base['project'] ?? [];
            $this->fingerprint = $row ? JsonFingerprint::make((array) $row) : '';
        } else {
            $this->form = ['status' => '', 'reason' => ''];
        }
    }

    public function saveTemplate(string $intent = 'draft'): void
    {
        $input = $this->form;
        $input['description'] = $this->form['catalogue_description'] ?? '';
        $input['hero_description'] = $this->form['description'] ?? '';
        $id = app(SaveTemplateAction::class)->execute(auth()->user(), $this->editing ?: null, $input, $this->fingerprint, $intent);
        $this->edit($id);
        $this->message = $intent === 'publish' ? 'Đã xuất bản mẫu. Blog đang live giữ snapshot cũ.' : 'Đã lưu mẫu.';
        $this->dispatch('editor-saved');
    }

    public function loadGroup(): void
    {
        $data = app(PlatformWorkspace::class)->read(auth()->user(), 'settings');
        abort_unless(isset(SavePlatformSettingsAction::RULES[$this->group]), 422);
        $this->form = array_intersect_key($data['settings'][$this->group] ?? [], SavePlatformSettingsAction::RULES[$this->group]);
    }

    public function updatedGroup(): void
    {
        $this->loadGroup();
    }

    public function saveSettings(): void
    {
        app(SavePlatformSettingsAction::class)->execute(auth()->user(), $this->group, $this->form);
        $this->message = 'Đã lưu nhóm cài đặt, giữ nguyên các nhóm khác.';
    }

    public function moderate(): void
    {
        abort_unless($this->editing, 422);
        app(ModerateAction::class)->execute(auth()->user(), $this->section, $this->editing, $this->form);
        $this->editing = null;
        $this->message = 'Đã cập nhật và ghi nhật ký.';
    }

    public function addTopic(): void
    {
        app(SaveTopicAction::class)->execute(auth()->user(), $this->form);
        $this->form = [];
        $this->message = 'Đã thêm chủ đề.';
    }

    protected function getViewData(): array
    {
        return app(PlatformWorkspace::class)->read(auth()->user(), $this->section, $this->q, $this->status, max(1, $this->page), $this->category) + ['categories' => app(PlatformWorkspace::class)->categories(), 'thumbnailMedia' => $this->section === 'templates' ? app(PlatformWorkspace::class)->templateMedia(auth()->user()) : collect()];
    }
}
