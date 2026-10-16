<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers\Admin;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\DTOs\TemplateRecord;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Application\Templates\Exceptions\TemplateSlugTaken;
use App\Application\Templates\UseCases\ChangeTemplateStatus\ChangeTemplateStatusUseCase;
use App\Application\Templates\UseCases\CreateTemplate\CreateTemplateUseCase;
use App\Application\Templates\UseCases\DeleteTemplate\DeleteTemplateUseCase;
use App\Application\Templates\UseCases\PublishTemplate\PublishTemplateUseCase;
use App\Application\Templates\UseCases\UpdateTemplateDraft\UpdateTemplateDraftUseCase;
use App\Domain\Templates\Enums\TemplateStatus;
use App\Domain\Templates\ValueObjects\TemplateData;
use App\Presentation\Web\Requests\Admin\CreateTemplateRequest;
use App\Presentation\Web\Requests\Admin\UpdateTemplateRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TemplateController extends Controller
{
    public function index(TemplateRepository $templates): View
    {
        return view('admin.templates.index', [
            'templates' => $templates->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.templates.create');
    }

    public function store(CreateTemplateRequest $request, CreateTemplateUseCase $create): JsonResponse
    {
        try {
            $id = $create->execute(
                actorId: (int) $request->user()->id,
                name: $request->validated('name'),
                slug: $request->validated('slug'),
                description: $request->validated('description'),
                previewMediaId: $request->validated('preview_media_id') !== null
                    ? (int) $request->validated('preview_media_id')
                    : null,
                demoUrl: $request->validated('demo_url'),
                draftData: $this->parseTemplateData($request->validated('draft_data')),
            );
        } catch (TemplateSlugTaken $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return new JsonResponse(['data' => ['id' => $id]], 201);
    }

    public function edit(int $template, TemplateRepository $templates): View
    {
        $record = $templates->find($template);

        if ($record === null) {
            abort(404);
        }

        return view('admin.templates.edit', ['template' => $record]);
    }

    public function update(
        int $template,
        UpdateTemplateRequest $request,
        UpdateTemplateDraftUseCase $update,
    ): JsonResponse {
        try {
            $update->execute(
                actorId: (int) $request->user()->id,
                templateId: $template,
                name: $request->validated('name'),
                slug: $request->validated('slug'),
                description: $request->validated('description'),
                previewMediaId: $request->validated('preview_media_id') !== null
                    ? (int) $request->validated('preview_media_id')
                    : null,
                demoUrl: $request->validated('demo_url'),
                draftData: $this->parseTemplateData($request->validated('draft_data')),
            );
        } catch (TemplateNotFound) {
            return $this->error('Template not found.', 404);
        } catch (TemplateSlugTaken $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return new JsonResponse(null, 204);
    }

    public function publish(int $template, PublishTemplateUseCase $publish, Request $request): JsonResponse
    {
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
        }

        return new JsonResponse(null, 204);
    }

    public function destroy(int $template, DeleteTemplateUseCase $delete, Request $request): JsonResponse
    {
        try {
            $delete->execute((int) $request->user()->id, $template);
        } catch (TemplateNotFound) {
            return $this->error('Template not found.', 404);
        }

        return new JsonResponse(null, 204);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseTemplateData(array $data): TemplateData
    {
        return TemplateData::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(TemplateRecord $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'description' => $t->description,
            'status' => $t->status->value,
            'preview_media_id' => $t->previewMediaId,
            'demo_url' => $t->demoUrl,
            'version_number' => $t->versionNumber,
            'published_at' => $t->publishedAt,
        ];
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }
}
