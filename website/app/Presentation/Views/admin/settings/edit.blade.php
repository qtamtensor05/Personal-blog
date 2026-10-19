@extends ('layouts.app')

@section ('title', __('admin.settings_title') . ' - ' . __('admin.console'))

@section ('content')
    @php ($data = $settings->data)
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8">
            <p class="text-accent-primary mb-2 text-sm font-medium">{{ __('admin.console') }}</p>
            <h1 class="text-hero mb-2">{{ __('admin.settings_title') }}</h1>
            <p class="text-secondary">{{ __('admin.settings_help') }}</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
            @csrf
            @method ('PUT')
            <input type="hidden" name="revision" value="{{ $settings->revision }}" />

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">{{ __('admin.general') }}</h2>
                <x-input
                    name="site_name"
                    :label="__('admin.site_name')"
                    maxlength="160"
                    :value="old('site_name', $data->siteName)"
                    required
                />
                <label class="text-primary flex items-center gap-3 text-sm">
                    <input type="hidden" name="registration_enabled" value="0" />
                    <input
                        type="checkbox"
                        name="registration_enabled"
                        value="1"
                        @checked (old('registration_enabled', $data->registrationEnabled))
                    />
                    {{ __('admin.allow_registration') }}
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="default_locale" class="text-primary block text-sm font-medium"
                            >{{ __('admin.default_locale') }}</label
                        >
                        <select name="default_locale" id="default_locale" class="input">
                            @foreach (['vi' => 'Tiếng Việt', 'en' => 'English'] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected (old('default_locale', $data->defaultLocale) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <x-input
                        name="default_timezone"
                        :label="__('admin.default_timezone')"
                        :value="old('default_timezone', $data->defaultTimezone)"
                        required
                    />
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">{{ __('admin.default_limits') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input
                        type="number"
                        name="default_blog_post_limit"
                        :label="__('admin.posts_per_blog')"
                        min="1"
                        max="10000"
                        :value="old('default_blog_post_limit', $data->defaultBlogPostLimit)"
                        required
                    />
                    <x-input
                        type="number"
                        name="default_media_quota_mb"
                        :label="__('admin.media_quota')"
                        min="1"
                        max="10240"
                        :value="old('default_media_quota_mb', $data->defaultMediaQuotaMb)"
                        required
                    />
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">{{ __('admin.maintenance_banner') }}</h2>
                <label class="text-primary flex items-center gap-3 text-sm">
                    <input type="hidden" name="maintenance_banner_enabled" value="0" />
                    <input
                        type="checkbox"
                        name="maintenance_banner_enabled"
                        value="1"
                        @checked (old('maintenance_banner_enabled', $data->maintenanceBannerEnabled))
                    />
                    {{ __('admin.show_maintenance_banner') }}
                </label>
                <x-textarea
                    name="maintenance_banner_message"
                    :label="__('admin.message')"
                    rows="3"
                    maxlength="500"
                    >{{
                        old(
                            'maintenance_banner_message',
                            $data->maintenanceBannerMessage,
                        )
                    }}</x-textarea
                >
            </section>

            <div class="flex items-center justify-between gap-4">
                <p class="text-secondary text-xs">{{ __('admin.revision_stale', ['revision' => $settings->revision]) }}</p>
                <button type="submit" class="btn btn-primary">{{ __('admin.save_settings') }}</button>
            </div>
        </form>
    </div>
@endsection
