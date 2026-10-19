@extends ('layouts.app')

@section ('title', 'Choose a Template')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h1 class="text-hero mb-4">Choose a Template</h1>
            <p class="text-secondary">Start with a published template and customize it to match your blog's style.</p>
        </div>

        @if (count($templates) === 0)
            <x-empty-state message="No templates available yet">
                Templates will appear here once the admin publishes them.
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
                                    <svg class="h-16 w-16 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
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
                                    Preview Demo →
                                </a>
                            @else
                                <span></span>
                            @endif
                            <button
                                onclick="chooseTemplate({{ $template->id }})"
                                class="btn-primary text-sm"
                            >
                                Choose
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
