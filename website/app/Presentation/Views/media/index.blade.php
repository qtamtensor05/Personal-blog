@extends ('layouts.app')

@section ('title', __('template.media_library') . ' - ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-6xl" data-media-library>
        <div class="mb-8">
            <a href="{{ route('blogs.show', $blog->id) }}" class="text-secondary hover:text-accent-primary text-sm">
                <i class="fa-solid fa-arrow-left-long"></i> {{ $blog->name }}
            </a>
            <h1 class="text-hero mt-2">{{ __('template.media_library') }}</h1>
            <p class="text-secondary mt-2">{{ __('template.media_archive_help') }}</p>
        </div>

        <x-alert type="error" class="mb-6 hidden" data-media-error></x-alert>

        <section>
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-primary text-xl font-semibold">{{ __('template.stored_media') }}</h2>
                <span class="text-secondary text-sm">
                    {{ trans_choice('template.media_file_count', count($assets), ['count' => count($assets)]) }}
                </span>
            </div>

            @if (count($assets) === 0)
                <x-empty-state :message="__('template.no_media')">
                    {{ __('template.no_media_help') }}
                </x-empty-state>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-media-grid>
                    @foreach ($assets as $asset)
                        <article class="card overflow-hidden p-0" data-media-item="{{ $asset->id }}">
                            <div class="bg-surface-secondary aspect-video">
                                @if ($asset->resourceType === 'video')
                                    <video src="{{ $asset->secureUrl }}" controls preload="metadata" class="h-full w-full object-cover"></video>
                                @else
                                    <img src="{{ $asset->secureUrl }}" alt="{{ $asset->altText ?? '' }}" loading="lazy" class="h-full w-full object-cover" />
                                @endif
                            </div>
                            <div class="flex items-start justify-between gap-3 p-4">
                                <div class="min-w-0">
                                    <p class="text-primary truncate font-medium">{{ $asset->altText ?: $asset->originalName }}</p>
                                    <p class="text-secondary mt-1 text-xs">
                                        #{{ $asset->id }} &middot;
                                        {{ $asset->resourceType === 'video' ? __('template.video') : __('template.image') }}
                                        &middot; {{ number_format(($asset->bytes ?? 0) / 1024, 1) }} KB
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-sm text-red-500 hover:underline"
                                    data-media-delete="{{ route('media.destroy', $asset->id) }}"
                                >
                                    {{ __('template.delete_media') }}
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection

@push ('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-media-library]');
            if (!root) return;

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const error = root.querySelector('[data-media-error]');
            const showError = (message) => {
                error.textContent = message;
                error.classList.remove('hidden');
            };

            root.querySelectorAll('[data-media-delete]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const accepted = await window.requestModalConfirmation(
                        @js(__('template.delete_media_title')),
                        @js(__('template.delete_media_confirmation')),
                    );
                    if (!accepted) return;

                    error.classList.add('hidden');
                    button.disabled = true;

                    try {
                        const response = await fetch(button.dataset.mediaDelete, {
                            method: 'DELETE',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        });
                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(result.message ?? @js(__('template.delete_media_error')));

                        window.location.reload();
                    } catch (exception) {
                        showError(exception.message);
                        button.disabled = false;
                    }
                });
            });
        })();
    </script>
@endpush
