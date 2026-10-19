<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers\Admin;

use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Application\Templates\Exceptions\TemplateSlugTaken;
use App\Application\Templates\UseCases\ChangeTemplateStatus\ChangeTemplateStatusUseCase;
use App\Application\Templates\UseCases\CreateTemplate\CreateTemplateUseCase;
use App\Application\Templates\UseCases\DeleteTemplate\DeleteTemplateUseCase;
use App\Application\Templates\UseCases\PublishTemplate\PublishTemplateUseCase;
use App\Application\Templates\UseCases\UpdateTemplateDraft\UpdateTemplateDraftUseCase;
use App\Domain\Templates\Enums\TemplateStatus;
use App\Domain\Templates\TemplateSlotCatalog;
use App\Domain\Templates\TemplateStarters;
use App\Domain\Templates\ValueObjects\TemplateData;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;
use App\Presentation\Web\Requests\Admin\CreateTemplateRequest;
use App\Presentation\Web\Requests\Admin\UpdateTemplateRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;

final class TemplateController extends Controller
{
    public function index(TemplateRepository $templates): View
    {
        return view('admin.templates.index', [
            'templates' => $templates->all(),
            'deletedTemplates' => $templates->allDeleted(),
        ]);
    }

    public function create(): View
    {
        return view('admin.templates.create');
    }

    public function store(
        CreateTemplateRequest $request,
        CreateTemplateUseCase $create,
    ): JsonResponse|RedirectResponse {
        try {
            $id = $create->execute(
                actorId: (int) $request->user()->id,
                name: $request->validated('name'),
                slug: $request->validated('slug') ?: Str::slug($request->validated('name')),
                description: $request->validated('description'),
                previewMediaId: $request->validated('preview_media_id') !== null
                    ? (int) $request->validated('preview_media_id')
                    : null,
                demoUrl: $request->validated('demo_url'),
                draftData: $this->templateData($request),
            );
        } catch (TemplateSlugTaken|InvalidArgumentException $exception) {
            return $request->expectsJson()
                ? $this->error($exception->getMessage(), 422)
                : back()
                    ->withErrors(['general' => $exception->getMessage()])
                    ->withInput();
        }

        return $request->expectsJson()
            ? new JsonResponse(['data' => ['id' => $id]], 201)
            : redirect()
                ->route('admin.templates.edit', $id)
                ->with('status', 'Template draft created.');
    }

    public function edit(
        int $template,
        TemplateRepository $templates,
        MediaAssetRepository $media,
    ): View {
        $record = $templates->find($template);

        if ($record === null) {
            abort(404);
        }

        $mediaUrls = [];
        $sections = $record->draftData?->sections ?? ($record->publishedData?->sections ?? []);
        foreach ($sections as $section) {
            $content = $section->props['content'] ?? null;
            if (! is_array($content)) {
                continue;
            }
            $walk = function (array $node) use (&$walk, &$mediaUrls, $media): void {
                if (($node['type'] ?? null) === 'image' && isset($node['attrs']['mediaId'])) {
                    $id = (int) $node['attrs']['mediaId'];
                    if (($asset = $media->find($id)) !== null) {
                        $mediaUrls[$id] = $asset->secureUrl;
                    }
                }
                foreach ($node['content'] ?? [] as $child) {
                    if (is_array($child)) {
                        $walk($child);
                    }
                }
            };
            $walk($content);
        }

        return view('admin.templates.edit', ['template' => $record, 'mediaUrls' => $mediaUrls]);
    }

    public function update(
        int $template,
        UpdateTemplateRequest $request,
        UpdateTemplateDraftUseCase $update,
        TemplateRepository $templates,
    ): JsonResponse|RedirectResponse {
        try {
            $update->execute(
                actorId: (int) $request->user()->id,
                templateId: $template,
                name: $request->validated('name'),
                slug: $request->validated('slug') ?: Str::slug($request->validated('name')),
                description: $request->validated('description'),
                previewMediaId: $request->validated('preview_media_id') !== null
                    ? (int) $request->validated('preview_media_id')
                    : null,
                demoUrl: $request->validated('demo_url'),
                draftData: $this->templateData(
                    $request,
                    $templates->find($template)?->draftData ??
                        $templates->find($template)?->publishedData,
                ),
            );
        } catch (TemplateNotFound) {
            return $request->expectsJson() ? $this->error('Template not found.', 404) : abort(404);
        } catch (TemplateSlugTaken|InvalidArgumentException $exception) {
            return $request->expectsJson()
                ? $this->error($exception->getMessage(), 422)
                : back()
                    ->withErrors(['general' => $exception->getMessage()])
                    ->withInput();
        }

        return $request->expectsJson()
            ? new JsonResponse(null, 204)
            : redirect()
                ->route('admin.templates.edit', $template)
                ->with('status', 'Template draft saved.');
    }

    public function publish(
        int $template,
        PublishTemplateUseCase $publish,
        Request $request,
    ): JsonResponse {
        $statusValue = $request->input('status', TemplateStatus::Published->value);

        try {
            $status = TemplateStatus::from((string) $statusValue);
        } catch (\ValueError) {
            return $this->error('Invalid status.', 422);
        }

        try {
            $publish->execute((int) $request->user()->id, $template, $status);
        } catch (TemplateNotFound) {
            return $this->error('Template not found.', 404);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return new JsonResponse(null, 204);
    }

    public function changeStatus(
        int $template,
        ChangeTemplateStatusUseCase $change,
        Request $request,
    ): JsonResponse {
        $statusValue = $request->input('status');

        try {
            $status = TemplateStatus::from((string) $statusValue);
        } catch (\ValueError) {
            return $this->error('Invalid status.', 422);
        }

        try {
            $change->execute((int) $request->user()->id, $template, $status);
        } catch (TemplateNotFound) {
            return $this->error('Template not found.', 404);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return new JsonResponse(null, 204);
    }

    public function destroy(
        int $template,
        DeleteTemplateUseCase $delete,
        Request $request,
    ): JsonResponse {
        try {
            $delete->execute((int) $request->user()->id, $template);
        } catch (TemplateNotFound) {
            return $this->error('Template not found.', 404);
        }

        return new JsonResponse(null, 204);
    }

    public function restore(int $template, TemplateRepository $templates): JsonResponse
    {
        if (collect($templates->allDeleted())->firstWhere('id', $template) === null) {
            return $this->error('Template not found.', 404);
        }

        $templates->restore($template);

        return new JsonResponse(null, 204);
    }

    public function forceDestroy(int $template, TemplateRepository $templates): JsonResponse
    {
        $deleted = collect($templates->allDeleted())->firstWhere('id', $template);

        if ($deleted === null) {
            return $this->error('Deleted template not found.', 404);
        }

        $templates->forceDelete($template);

        return new JsonResponse(null, 204);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseTemplateData(array $data): TemplateData
    {
        return TemplateData::fromArray($data);
    }

    private function templateData(Request $request, ?TemplateData $base = null): TemplateData
    {
        $raw = $request->validated('draft_data');
        if (is_array($raw)) {
            return $this->parseTemplateData($raw);
        }

        $layout = $request->validated('layout');
        $layout = is_string($layout) ? $layout : $base?->layout ?? TemplateStarters::blog()->layout;
        $slotProps = $request->validated('slot_props', []);

        if ($layout === 'lifestyle-editorial') {
            $starter =
                $base?->layout === 'lifestyle-editorial'
                    ? $base
                    : TemplateStarters::lifestyleEditorial();
            $sections = array_map(function (TemplateSectionConfig $slot) use (
                $slotProps,
            ): TemplateSectionConfig {
                $submitted = is_array($slotProps[$slot->key] ?? null) ? $slotProps[$slot->key] : [];
                $allowed = array_flip(TemplateSlotCatalog::fields($slot->section));
                $props = array_replace($slot->props, array_intersect_key($submitted, $allowed));

                return new TemplateSectionConfig(
                    key: $slot->key,
                    section: $slot->section,
                    props: $props,
                    region: $slot->region,
                    label: $slot->label,
                    visible: $slot->visible,
                );
            }, $starter->sections);

            return new TemplateData(
                $sections,
                $starter->designTokens,
                $starter->postLayout,
                $layout,
            );
        }

        $starter = $base ?? TemplateStarters::blog();
        $requested = array_values(
            array_unique(array_filter($request->validated('sections', []), 'is_string')),
        );
        $visibility = $request->validated('slot_visibility', []);
        $order = $request->validated('slot_order', []);
        usort(
            $requested,
            fn (string $left, string $right) => ((int) ($order[$left] ?? 99)) <=>
                ((int) ($order[$right] ?? 99)),
        );
        $labels = [
            'header' => 'Header',
            'hero' => 'Banner chính',
            'image_text' => 'Ảnh và nội dung',
            'gallery' => 'Gallery ảnh',
            'recent_posts' => 'Bài viết mới',
            'categories' => 'Chuyên mục',
            'cta' => 'CTA',
            'profile' => 'Giới thiệu blogger',
            'sidebar_posts' => 'Bài viết sidebar',
            'newsletter' => 'Newsletter',
            'footer' => 'Footer',
        ];
        $sections = array_map(
            fn (string $section) => new TemplateSectionConfig(
                key: str_replace('_', '-', $section),
                section: $section,
                region: TemplateSlotCatalog::defaultRegion($section)->value,
                label: $labels[$section] ?? ucfirst($section),
                visible: (bool) ($visibility[$section] ?? false),
            ),
            $requested,
        );

        if ($sections === []) {
            throw new InvalidArgumentException('Select at least one supported component.');
        }

        return new TemplateData($sections, $starter->designTokens, $starter->postLayout, $layout);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }
}
