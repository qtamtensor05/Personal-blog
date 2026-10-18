@extends('layouts.app')

@section('title', 'Edit Blog')

@section('content')
<div class="container-custom section-spacing max-w-2xl">
    <div class="mb-8">
        <h1 class="text-hero mb-2">Edit Blog: {{ $blog->name }}</h1>
        <p class="text-secondary">Update your blog's details and publishing settings.</p>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('blogs.update', $blog->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            @if($errors->has('general'))
                <x-alert type="error" dismissible>{{ $errors->first('general') }}</x-alert>
            @endif

            <x-input
                name="name"
                label="Blog Name"
                :value="old('name', $blog->name)"
                :error="$errors->first('name')"
                required
            />

            <x-input
                name="slug"
                label="Slug"
                hint="Generated from the blog name. Edit it when you need a different URL."
                :value="old('slug', $blog->slug)"
                :error="$errors->first('slug')"
                required
            />

            <x-textarea
                name="description"
                label="Description (optional)"
                rows="3"
                :error="$errors->first('description')">{{ old('description', $blog->description) }}</x-textarea>

            <div class="border-t border-default pt-5">
                <h2 class="text-lg font-semibold text-primary mb-4">Publishing</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-primary mb-2">Status</label>
                        <select name="status" class="input">
                            @foreach(['DRAFT', 'PUBLISHED', 'HIDDEN', 'ARCHIVED'] as $status)
                                <option value="{{ $status }}" {{ old('status', $blog->status->value) === $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-primary mb-2">Visibility</label>
                        <select name="visibility" class="input">
                            @foreach(['PUBLIC', 'UNLISTED', 'PRIVATE'] as $vis)
                                <option value="{{ $vis }}" {{ old('visibility', $blog->visibility->value) === $vis ? 'selected' : '' }}>
                                    {{ $vis }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="discoverable" value="0">
                        <input type="checkbox" name="discoverable" value="1" id="discoverable"
                               {{ old('discoverable', $blog->discoverable) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-default">
                        <label for="discoverable" class="text-sm text-secondary">
                            Allow this blog to be discovered on the platform
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-default">
                <button type="submit" class="btn-primary px-4 py-2">Save Changes</button>
                <a href="{{ route('blogs.show', $blog->id) }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const name = document.querySelector('[name=name]');
    const slug = document.querySelector('[name=slug]');
    if (!name || !slug) return;
    let edited = slug.value !== name.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    name.addEventListener('input', () => { if (!edited) slug.value = slugify(name.value); });
    slug.addEventListener('input', () => { edited = true; });
})();
</script>
@endpush
@endsection
