@extends ('layouts.app')

@section ('title', 'Platform Settings - Admin Console')

@section ('content')
    @php ($data = $settings->data)
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8">
            <p class="text-accent-primary mb-2 text-sm font-medium">Admin Console</p>
            <h1 class="text-hero mb-2">Platform Settings</h1>
            <p class="text-secondary">Global defaults and visitor-facing platform controls.</p>
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
                <h2 class="text-primary text-lg font-semibold">General</h2>
                <x-input
                    name="site_name"
                    label="Site name"
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
                    Allow new user registration
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="default_locale" class="text-primary block text-sm font-medium"
                            >Default locale</label
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
                        label="Default timezone"
                        :value="old('default_timezone', $data->defaultTimezone)"
                        required
                    />
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">Default limits</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input
                        type="number"
                        name="default_blog_post_limit"
                        label="Posts per blog"
                        min="1"
                        max="10000"
                        :value="old('default_blog_post_limit', $data->defaultBlogPostLimit)"
                        required
                    />
                    <x-input
                        type="number"
                        name="default_media_quota_mb"
                        label="Media quota (MB)"
                        min="1"
                        max="10240"
                        :value="old('default_media_quota_mb', $data->defaultMediaQuotaMb)"
                        required
                    />
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">Maintenance banner</h2>
                <label class="text-primary flex items-center gap-3 text-sm">
                    <input type="hidden" name="maintenance_banner_enabled" value="0" />
                    <input
                        type="checkbox"
                        name="maintenance_banner_enabled"
                        value="1"
                        @checked (old('maintenance_banner_enabled', $data->maintenanceBannerEnabled))
                    />
                    Show maintenance banner
                </label>
                <x-textarea
                    name="maintenance_banner_message"
                    label="Message"
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
                <p class="text-secondary text-xs">Revision {{ $settings->revision }}. Stale saves are rejected.</p>
                <button type="submit" class="btn btn-primary">Save platform settings</button>
            </div>
        </form>
    </div>
@endsection
