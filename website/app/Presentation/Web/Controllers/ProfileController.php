<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Media\Exceptions\MediaStorageFailed;
use App\Application\Media\UseCases\UploadMedia\UploadMediaUseCase;
use App\Application\Profile\UseCases\ChangeProfilePasswordService;
use App\Application\Profile\UseCases\UpdateProfileAvatarService;
use App\Application\Profile\UseCases\UpdateProfileNameService;
use App\Application\Profile\UseCases\ViewProfileService;
use App\Domain\Media\Enums\MediaScope;
use App\Presentation\Web\Requests\UpdateProfileRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(Request $request, ViewProfileService $profiles): View
    {
        return view('profile.edit', ['profile' => $profiles->execute((int) $request->user()->id)]);
    }

    public function update(
        UpdateProfileRequest $request,
        UpdateProfileNameService $names,
        UploadMediaUseCase $upload,
        UpdateProfileAvatarService $avatars,
        ChangeProfilePasswordService $passwords,
    ): RedirectResponse {
        $userId = (int) $request->user()->id;
        $data = $request->validated();
        $changesPassword = filled($data['password'] ?? null);

        if ($changesPassword) {
            try {
                $passwords->assertCurrentPassword($userId, (string) $data['current_password']);
            } catch (DomainException $exception) {
                throw ValidationException::withMessages(['current_password' => $exception->getMessage()]);
            }
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            try {
                $asset = $upload->execute(
                    actorId: $userId,
                    scope: MediaScope::User,
                    blogId: null,
                    localPath: $file->getRealPath(),
                    originalName: $file->getClientOriginalName(),
                    mimeType: $file->getMimeType(),
                    sizeBytes: (int) $file->getSize(),
                    altText: 'Profile image',
                );
                $avatars->execute($userId, $asset);
            } catch (MediaStorageFailed) {
                throw ValidationException::withMessages([
                    'avatar' => 'The profile image could not be uploaded. Check the media storage configuration.',
                ]);
            }
        } elseif ((bool) $data['remove_avatar']) {
            $avatars->remove($userId);
        }

        $names->execute($userId, (string) $data['name']);

        if ($changesPassword) {
            $passwords->execute($userId, (string) $data['current_password'], (string) $data['password']);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Profile and password updated. Please sign in again.');
        }

        return back()->with('status', 'Profile updated.');
    }
}
