<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers\Admin;

use App\Application\PlatformSettings\DTOs\PlatformSettingsData;
use App\Application\PlatformSettings\UseCases\UpdatePlatformSettings\UpdatePlatformSettingsService;
use App\Application\PlatformSettings\UseCases\ViewPlatformSettings\ViewPlatformSettingsService;
use App\Presentation\Web\Requests\Admin\UpdatePlatformSettingsRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PlatformSettingsController extends Controller
{
    public function edit(ViewPlatformSettingsService $view): View
    {
        return view('admin.settings.edit', ['settings' => $view->execute()]);
    }

    public function update(
        UpdatePlatformSettingsRequest $request,
        UpdatePlatformSettingsService $update,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $update->execute(
                actorId: (int) $request->user()->id,
                data: new PlatformSettingsData(
                    siteName: trim($data['site_name']),
                    registrationEnabled: (bool) $data['registration_enabled'],
                    defaultLocale: $data['default_locale'],
                    defaultTimezone: $data['default_timezone'],
                    defaultBlogPostLimit: (int) $data['default_blog_post_limit'],
                    defaultMediaQuotaMb: (int) $data['default_media_quota_mb'],
                    maintenanceBannerEnabled: (bool) $data['maintenance_banner_enabled'],
                    maintenanceBannerMessage: trim(
                        (string) ($data['maintenance_banner_message'] ?? ''),
                    ),
                ),
                expectedRevision: (int) $data['revision'],
            );
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Platform settings updated.');
    }
}
