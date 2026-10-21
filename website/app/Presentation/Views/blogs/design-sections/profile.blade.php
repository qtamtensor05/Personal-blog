<section class="rounded-lg border p-5" style="border-color: var(--d-secondary)">
    @if (!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))
        @if (isset($blog->ownerUserId))
            <a href="{{ route('profiles.show', $blog->ownerUserId) }}" aria-label="{{ __('profile.view_public', ['name' => $blog->name]) }}" class="mb-3 inline-block rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        @endif
            <img
                src="{{ $mediaUrls[$content['mediaId']] }}"
                alt=""
                class="h-16 w-16 rounded-full object-cover {{ isset($blog->ownerUserId) ? '' : 'mb-3' }}"
            />
        @if (isset($blog->ownerUserId))
            </a>
        @endif
    @endif
    <h2 class="font-semibold">{{ $content['title'] ?? $blog->name }}</h2>
    @if (!empty($content['description']))
        <p class="mt-2 text-sm opacity-75">{{ $content['description'] }}</p>
    @endif
</section>
