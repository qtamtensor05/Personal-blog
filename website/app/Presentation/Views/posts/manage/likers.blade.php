<div data-likers-total="{{ $likers->total }}">
    @if ($likers->likers === [])
        <p class="text-secondary py-6 text-center">{{ __('post.no_likers') }}</p>
    @else
        <ul class="divide-default divide-y" aria-label="{{ __('post.likers') }}">
            @foreach ($likers->likers as $liker)
                <li class="flex items-center gap-3 py-3">
                    <a href="{{ route('profiles.show', $liker->userId) }}" aria-label="{{ __('profile.view_public', ['name' => $liker->name]) }}" class="shrink-0 rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                        @if ($liker->avatarUrl)
                            <img src="{{ $liker->avatarUrl }}" alt="" class="h-11 w-11 rounded-full object-cover" />
                        @else
                            <span class="bg-accent-primary text-surface flex h-11 w-11 items-center justify-center rounded-full font-semibold" aria-hidden="true">{{ mb_strtoupper(mb_substr($liker->name, 0, 1)) }}</span>
                        @endif
                    </a>
                    <span class="text-primary min-w-0 flex-1 truncate font-medium">{{ $liker->name }}</span>
                    <time datetime="{{ \Illuminate\Support\Carbon::parse($liker->likedAt)->toAtomString() }}" title="{{ \Illuminate\Support\Carbon::parse($liker->likedAt)->format('d/m/Y H:i') }}" class="text-secondary ml-auto text-sm tabular-nums">{{ \Illuminate\Support\Carbon::parse($liker->likedAt)->format('H:i') }}</time>
                </li>
            @endforeach
        </ul>
    @endif
</div>

@if ($likers->lastPage() > 1)
    <nav class="border-default mt-4 flex items-center justify-between border-t pt-4 text-sm" aria-label="{{ __('notifications.pagination') }}">
        @if ($likers->page > 1)
            <button type="button" class="text-accent-primary hover:underline" data-likers-page-url="{{ route('blogs.posts.likers', [$blogId, $post->id, 'page' => $likers->page - 1]) }}">{{ __('notifications.previous') }}</button>
        @else
            <span></span>
        @endif
        <span class="text-secondary">{{ $likers->page }} / {{ $likers->lastPage() }}</span>
        @if ($likers->page < $likers->lastPage())
            <button type="button" class="text-accent-primary hover:underline" data-likers-page-url="{{ route('blogs.posts.likers', [$blogId, $post->id, 'page' => $likers->page + 1]) }}">{{ __('notifications.next') }}</button>
        @else
            <span></span>
        @endif
    </nav>
@endif
