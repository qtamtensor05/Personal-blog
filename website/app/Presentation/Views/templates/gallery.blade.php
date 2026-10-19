@extends ('layouts.app')

@section ('title', __('templates.gallery_title'))

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h1 class="text-hero mb-4">{{ __('templates.gallery_title') }}</h1>
            <p class="text-secondary">{{ __('templates.gallery_intro') }}</p>
        </div>

        @if (count($templates) === 0)
            <x-empty-state :message="__('templates.no_available')">
                {{ __('templates.published_help') }}
            </x-empty-state>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($templates as $template)
                    <div class="card group transition-shadow hover:shadow-lg">
                        @if ($template->publishedData?->layout === 'lifestyle-editorial')
                            <div
                                class="bg-surface-secondary mb-4 aspect-video overflow-hidden rounded"
                            >
                                <img
                                    src="{{ asset('images/templates/lifestyle-editorial/hero.svg') }}"
                                    alt="Lifestyle Editorial"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                        @endif
                        @if ($template->previewMediaId)
                            <div
                                class="bg-surface-secondary mb-4 aspect-video overflow-hidden rounded"
                            >
                                {{-- Preview image would go here --}}
                                <div
                                    class="text-secondary flex h-full w-full items-center justify-center"
                                >
                                    <i class="fa-regular fa-image h-16 w-16 opacity-30" aria-hidden="true"></i>
                                </div>
                            </div>
                        @endif

                        <h3 class="text-primary mb-2 text-lg font-semibold">
                            {{ $template->name }}
                        </h3>

                        @if ($template->description)
                            <p class="text-secondary mb-4 text-sm">{{ $template->description }}</p>
                        @endif

                        <div class="border-default flex items-center justify-between border-t pt-4">
                            @if ($template->demoUrl)
                                <a
                                    href="{{ $template->demoUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-secondary hover:text-accent-primary text-sm transition-colors"
                                >
                                    {{ __('templates.preview_demo') }}
                                </a>
                            @else
                                <span></span>
                            @endif
                            <button
                                onclick="chooseTemplate({{ $template->id }})"
                                class="btn-primary text-sm"
                            >
                                {{ __('templates.choose') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @push ('scripts')
        <script>
            function chooseTemplate(id) {
                // Templates are applied per blog from the Blog Design Editor.
                location.href = '{{ route('blogs.index') }}';
            }
        </script>
    @endpush
@endsection
