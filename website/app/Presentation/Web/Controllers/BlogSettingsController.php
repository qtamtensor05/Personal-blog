<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Contracts\BlogSettingsRepository;
use App\Application\Blogs\DTOs\BlogSettingsRecord;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\UpdateBlogSettings\UpdateBlogSettingsService;
use App\Presentation\Web\Requests\UpdateBlogSettingsRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BlogSettingsController extends Controller
{
    public function edit(
        int $blog,
        Request $request,
        BlogRepository $blogs,
        BlogSettingsRepository $settings,
    ): View {
        $record = $blogs->find($blog);

        if ($record === null || $record->ownerUserId !== (int) $request->user()->id) {
            abort(404);
        }

        return view('blogs.settings', [
            'blog' => $record,
            'settings' => $settings->find($blog) ?? BlogSettingsRecord::defaults($blog),
            'platforms' => UpdateBlogSettingsRequest::SOCIAL_PLATFORMS,
        ]);
    }

    public function update(
        int $blog,
        UpdateBlogSettingsRequest $request,
        UpdateBlogSettingsService $update,
    ): RedirectResponse {
        try {
            $update->execute(
                actorId: (int) $request->user()->id,
                blogId: $blog,
                locale: $request->validated('locale'),
                timezone: $request->validated('timezone'),
                logoMediaId: $request->mediaId('logo_media_id'),
                faviconMediaId: $request->mediaId('favicon_media_id'),
                postsPerPage: (int) $request->validated('posts_per_page'),
                socialLinks: $request->socialLinks(),
                seoTitle: $request->validated('seo_title'),
                seoDescription: $request->validated('seo_description'),
                ogMediaId: $request->mediaId('og_media_id'),
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('blogs.settings.edit', $blog)->with('status', 'Settings saved.');
    }
}
