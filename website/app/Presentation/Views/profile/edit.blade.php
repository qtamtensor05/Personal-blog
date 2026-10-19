@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container-custom section-spacing max-w-4xl">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-hero mb-2">Your profile</h1>
            <p class="text-secondary">Manage your display name, profile image and password.</p>
        </div>
        <button type="submit" form="profile-form" class="btn-primary shrink-0 px-4 py-2 rounded-md">Save changes</button>
    </div>

    @if(session('status'))
        <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <form id="profile-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="card h-fit">
            <h2 class="mb-4 text-lg font-semibold text-primary">Profile information</h2>
            <div class="mb-6 flex items-center gap-4">
                @if($profile->avatarUrl)
                    <img src="{{ $profile->avatarUrl }}" alt="{{ $profile->name }}" class="h-20 w-20 rounded-full object-cover ring-2 ring-[var(--color-border)]">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-accent-primary text-2xl font-semibold text-white">
                        {{ mb_strtoupper(mb_substr($profile->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-primary">{{ $profile->name }}</p>
                    <p class="text-sm text-secondary">{{ $profile->email }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <x-input name="name" label="Display name" :value="old('name', $profile->name)" required maxlength="160" />
                @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div>
                    <label class="mb-1 block text-sm font-medium text-primary">Email</label>
                    <input class="input bg-surface-secondary" value="{{ $profile->email }}" disabled>
                    <p class="mt-1 text-xs text-secondary">Email changes are not enabled yet.</p>
                </div>
            </div>
            </section>

        <div class="space-y-6">
            <section class="card">
                <h2 class="mb-2 text-lg font-semibold text-primary">Profile image</h2>
                <p class="mb-4 text-sm text-secondary">JPG, PNG, WebP or GIF, up to {{ config('media.max_upload_kb') / 1024 }} MB.</p>
                <div class="space-y-4">
                    <input type="file" name="avatar" class="input" accept="image/jpeg,image/png,image/webp,image/gif">
                    @error('avatar') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                @if($profile->avatarMediaId)
                    <input type="hidden" name="remove_avatar" value="0">
                    <label class="mt-3 flex items-center gap-2 text-sm text-secondary">
                        <input type="checkbox" name="remove_avatar" value="1" @checked(old('remove_avatar'))>
                        Remove current image
                    </label>
                @else
                    <input type="hidden" name="remove_avatar" value="0">
                @endif
            </section>

            <section class="card">
                <h2 class="mb-2 text-lg font-semibold text-primary">Change password</h2>
                <p class="mb-4 text-sm text-secondary">Leave these fields blank to keep your password. You will be signed out if it changes.</p>
                <div class="space-y-4">
                    <x-input type="password" name="current_password" label="Current password" autocomplete="current-password" />
                    @error('current_password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <x-input type="password" name="password" label="New password" minlength="8" autocomplete="new-password" />
                    @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <x-input type="password" name="password_confirmation" label="Confirm new password" minlength="8" autocomplete="new-password" />
                </div>
            </section>
        </div>
        </div>
    </form>
</div>
@endsection
