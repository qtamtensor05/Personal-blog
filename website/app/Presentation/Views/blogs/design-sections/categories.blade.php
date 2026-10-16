<section>
    <h2 class="text-xl font-semibold mb-3" style="font-family: var(--d-heading-font), sans-serif;">Categories</h2>
    <div class="flex flex-wrap gap-2">
        @foreach(['Notes', 'Travel', 'Code'] as $name)
            <span class="px-3 py-1 rounded-full text-sm" style="background: var(--d-accent); color: var(--d-text);">{{ $name }}</span>
        @endforeach
    </div>
</section>
