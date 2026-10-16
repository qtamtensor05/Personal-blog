@extends('layouts.app')

@section('title', 'Choose a Template')

@section('content')
<div class="container-custom section-spacing">
    <div class="text-center mb-12 max-w-2xl mx-auto">
        <h1 class="text-hero mb-4">Choose a Template</h1>
        <p class="text-secondary">Start with a published template and customize it to match your blog's style.</p>
    </div>

    @if(count($templates) === 0)
        <x-empty-state message="No templates available yet">
            Templates will appear here once the admin publishes them.
        </x-empty-state>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($templates as $template)
                <div class="card group hover:shadow-lg transition-shadow">
                    @if($template->previewMediaId)
                        <div class="aspect-video bg-surface-secondary rounded mb-4 overflow-hidden">
                            {{-- Preview image would go here --}}
                            <div class="w-full h-full flex items-center justify-center text-secondary">
                                <svg class="w-16 h-16 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    @endif

                    <h3 class="text-lg font-semibold text-primary mb-2">{{ $template->name }}</h3>

                    @if($template->description)
                        <p class="text-sm text-secondary mb-4">{{ $template->description }}</p>
                    @endif

                    <div class="flex items-center justify-between pt-4 border-t border-default">
                        @if($template->demoUrl)
                            <a href="{{ $template->demoUrl }}" target="_blank" rel="noopener"
                               class="text-sm text-secondary hover:text-accent-primary transition-colors">
                                Preview Demo →
                            </a>
                        @else
                            <span></span>
                        @endif
                        <button
                            onclick="chooseTemplate({{ $template->id }})"
                            class="btn-primary text-sm">
                            Choose
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
function chooseTemplate(id) {
    // Templates are applied per blog from the Blog Design Editor.
    location.href = '{{ route('blogs.index') }}';
}
</script>
@endpush
@endsection
