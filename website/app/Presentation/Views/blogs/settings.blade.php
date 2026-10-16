@extends('layouts.app')

@section('title', 'Settings — '.$blog->name)

@section('content')
<div class="container-custom section-spacing max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('blogs.show', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← {{ $blog->name }}</a>
        <h1 class="text-hero mt-2">Blog Settings</h1>
        <p class="text-secondary">Language, SEO and links shown on your public blog.</p>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('blogs.settings.update', $blog->id) }}" class="space-y-8">
        @csrf
        @method('PUT')

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold text-primary">General</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label for="locale" class="block text-sm font-medium text-primary">Language</label>
                    <select name="locale" id="locale" class="input">
                        @foreach(['vi' => 'Tiếng Việt', 'en' => 'English'] as $code => $label)
                            <option value="{{ $code }}" @selected(old('locale', $settings->locale) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input name="timezone" label="Timezone" :value="$settings->timezone" required
                         :error="$errors->first('timezone')" hint="e.g. Asia/Ho_Chi_Minh" />
                <x-input type="number" name="posts_per_page" label="Posts per page" min="1" max="100"
                         :value="$settings->postsPerPage" required :error="$errors->first('posts_per_page')" />
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold text-primary">SEO</h2>
            <x-input name="seo_title" label="SEO title" :value="$settings->seoTitle" maxlength="255"
                     :error="$errors->first('seo_title')" />
            <x-textarea name="seo_description" label="SEO description" rows="3" maxlength="500"
                        :error="$errors->first('seo_description')">{{ old('seo_description', $settings->seoDescription) }}</x-textarea>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold text-primary">Images</h2>
            <p class="text-sm text-secondary">Enter the media ID of an image uploaded to this blog. A media picker comes with the Media Library.</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <x-input type="number" name="logo_media_id" label="Logo" min="1" :value="$settings->logoMediaId" />
                <x-input type="number" name="favicon_media_id" label="Favicon" min="1" :value="$settings->faviconMediaId" />
                <x-input type="number" name="og_media_id" label="Social share image" min="1" :value="$settings->ogMediaId" />
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold text-primary">Social links</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($platforms as $platform)
                    <x-input type="url" name="social_links[{{ $platform }}]" :label="ucfirst($platform)"
                             placeholder="https://"
                             :value="old('social_links.'.$platform, $settings->socialLinks[$platform] ?? null)"
                             :error="$errors->first('social_links.'.$platform)" />
                @endforeach
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">Save settings</button>
            <a href="{{ route('blogs.show', $blog->id) }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
