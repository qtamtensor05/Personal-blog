@extends ('layouts.app')

@section ('title', __('profile.title'))

@section ('content')
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-hero mb-2">{{ __('profile.your_profile') }}</h1>
                <p class="text-secondary">{{ __('profile.manage_help') }}</p>
                <a href="{{ route('bookmarks.index') }}" class="text-accent-primary mt-2 inline-block font-medium hover:underline">
                    {{ __('post.view_bookmarks') }}
                </a>
            </div>
            <button
                type="submit"
                form="profile-form"
                class="btn-primary shrink-0 rounded-md px-4 py-2"
            >
                {{ __('profile.save') }}
            </button>
        </div>

        @if (session('status'))
            <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
        @endif

        <form
            id="profile-form"
            method="POST"
            action="{{ route('profile.update') }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method ('PUT')

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="card h-fit">
                    <h2 class="text-primary mb-4 text-lg font-semibold">
                        {{ __('profile.information') }}
                    </h2>
                    <div class="mb-6 flex items-center gap-4">
                        <span class="shrink-0">
                            @if ($profile->avatarUrl)
                                <img
                                    src="{{ $profile->avatarUrl }}"
                                    alt=""
                                    class="h-20 w-20 rounded-full object-cover ring-2 ring-[var(--color-border)]"
                                />
                            @else
                                <span
                                    class="bg-accent-primary flex h-20 w-20 items-center justify-center rounded-full text-2xl font-semibold text-white"
                                    aria-hidden="true"
                                >
                                    {{ mb_strtoupper(mb_substr($profile->name, 0, 1)) }}
                                </span>
                            @endif
                        </span>
                        <div>
                            <p class="text-primary font-semibold">{{ $profile->name }}</p>
                            <p class="text-secondary text-sm">{{ $profile->email }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <x-input
                            name="name"
                            :label="__('profile.display_name')"
                            :value="old('name', $profile->name)"
                            required
                            maxlength="160"
                        />
                        @error ('name')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div>
                            <label
                                class="text-primary mb-1 block text-sm font-medium"
                                >{{ __('profile.email') }}</label
                            >
                            <input
                                class="input bg-surface-secondary"
                                value="{{ $profile->email }}"
                                disabled
                            />
                            <p class="text-secondary mt-1 text-xs">{{ __('profile.email_unavailable') }}</p>
                        </div>
                    </div>
                </section>

                <div class="space-y-6">
                    <section class="card">
                        <h2 class="text-primary mb-2 text-lg font-semibold">
                            {{ __('profile.image') }}
                        </h2>
                        <p class="text-secondary mb-4 text-sm">{{
                            __('profile.image_formats', [
                                'size' => config('media.max_upload_kb') / 1024,
                            ])
                        }}</p>
                        <div class="space-y-4">
                            <input
                                type="file"
                                name="avatar"
                                class="input"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                            />
                            @error ('avatar')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @if ($profile->avatarMediaId)
                            <input type="hidden" name="remove_avatar" value="0" />
                            <label class="text-secondary mt-3 flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="remove_avatar"
                                    value="1"
                                    @checked (old('remove_avatar'))
                                />
                                {{ __('profile.remove_image') }}
                            </label>
                        @else
                            <input type="hidden" name="remove_avatar" value="0" />
                        @endif
                    </section>

                    <section class="card">
                        <h2 class="text-primary mb-2 text-lg font-semibold">
                            {{ __('profile.change_password') }}
                        </h2>
                        <p class="text-secondary mb-4 text-sm">{{ __('profile.password_help') }}</p>
                        <div class="space-y-4">
                            <x-input
                                type="password"
                                name="current_password"
                                :label="__('profile.current_password')"
                                autocomplete="current-password"
                            />
                            @error ('current_password')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <x-input
                                type="password"
                                name="password"
                                :label="__('profile.new_password')"
                                minlength="8"
                                autocomplete="new-password"
                            />
                            @error ('password')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <x-input
                                type="password"
                                name="password_confirmation"
                                :label="__('profile.confirm_new_password')"
                                minlength="8"
                                autocomplete="new-password"
                            />
                        </div>
                    </section>
                </div>
            </div>
        </form>
    </div>
@endsection
