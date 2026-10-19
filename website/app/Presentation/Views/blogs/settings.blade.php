@extends ('layouts.app')

@section ('title', __('settings.title') . ' - ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-3xl">
        <div class="mb-8">
            <a
                href="{{ route('blogs.show', $blog->id) }}"
                class="text-secondary hover:text-accent-primary text-sm"
            >
                <i class="fa-solid fa-arrow-left"></i>
                {{ $blog->name }}
            </a>
            <h1 class="text-hero mt-2">{{ __('settings.title') }}</h1>
            <p class="text-secondary">{{ __('settings.intro') }}</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <form
            method="POST"
            action="{{ route('blogs.settings.update', $blog->id) }}"
            class="space-y-8"
        >
            @csrf
            @method ('PUT')

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">{{ __('settings.general') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="locale" class="text-primary block text-sm font-medium"
                            >{{ __('settings.language') }}</label
                        >
                        <select name="locale" id="locale" class="input">
                            @foreach (['vi' => 'Tiếng Việt', 'en' => 'English'] as $code => $label)
                                <option
                                    value="{{ $code }}"
                                    @selected (old('locale', $settings->locale) === $code)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="timezone" class="text-primary block text-sm font-medium"
                            >{{ __('settings.timezone') }}</label
                        >
                        <select name="timezone" id="timezone" class="input" required>
                            @foreach ([
                                    'Asia/Ho_Chi_Minh' => __('settings.timezone_vietnam'),
                                    'UTC' => 'UTC',
                                    'Asia/Tokyo' => 'Tokyo (UTC+9)',
                                    'Europe/London' => 'London',
                                    'America/New_York' => 'New York'
                                ]
                                as $zone => $label)
                                <option
                                    value="{{ $zone }}"
                                    @selected (old('timezone', $settings->timezone) === $zone)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->first('timezone'))
                            <p class="text-sm text-red-600">{{ $errors->first('timezone') }}</p>
                        @endif
                    </div>
                    <x-input
                        type="number"
                        name="posts_per_page"
                        :label="__('settings.posts_per_page')"
                        min="1"
                        max="100"
                        :value="$settings->postsPerPage"
                        required
                        :error="$errors->first('posts_per_page')"
                        :hint="__('settings.posts_per_page_hint')"
                    />
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">{{ __('ui.seo') }}</h2>
                <x-input
                    name="seo_title"
                    :label="__('settings.seo_title')"
                    :value="$settings->seoTitle"
                    maxlength="255"
                    :error="$errors->first('seo_title')"
                />
                <x-textarea
                    name="seo_description"
                    :label="__('settings.seo_description')"
                    rows="3"
                    maxlength="500"
                    :error="$errors->first('seo_description')"
                    >{{
                        old(
                            'seo_description',
                            $settings->seoDescription,
                        )
                    }}</x-textarea
                >
            </section>

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">{{ __('settings.images') }}</h2>
                <p class="text-secondary text-sm">{{ __('settings.media_help') }}</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-input
                        type="number"
                        name="logo_media_id"
                        :label="__('ui.logo')"
                        min="1"
                        :value="$settings->logoMediaId"
                    />
                    <x-input
                        type="number"
                        name="favicon_media_id"
                        :label="__('ui.favicon')"
                        min="1"
                        :value="$settings->faviconMediaId"
                    />
                    <x-input
                        type="number"
                        name="og_media_id"
                        :label="__('ui.social_share_image')"
                        min="1"
                        :value="$settings->ogMediaId"
                    />
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">{{ __('settings.social_links') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($platforms as $platform)
                        <x-input
                            type="url"
                            name="social_links[{{ $platform }}]"
                            :label="ucfirst($platform)"
                            placeholder="https://"
                            :value="old('social_links.' . $platform, $settings->socialLinks[$platform] ?? null)"
                            :error="$errors->first('social_links.' . $platform)"
                        />
                    @endforeach
                </div>
            </section>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary">{{ __('settings.save') }}</button>
                <a href="{{ route('blogs.show', $blog->id) }}" class="btn-ghost">{{ __('settings.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
