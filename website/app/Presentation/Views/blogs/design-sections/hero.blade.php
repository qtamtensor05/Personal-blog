<header class="py-8">
    <h1 class="text-3xl font-bold mb-2" style="font-family: var(--d-heading-font), sans-serif; color: var(--d-text);">{{ $blog->name }}</h1>
    @if($blog->description)
        <p class="opacity-80">{{ $blog->description }}</p>
    @endif
    <span class="inline-block mt-4 px-4 py-2 rounded-md text-sm text-white" style="background: var(--d-primary);">Read the latest</span>
</header>
