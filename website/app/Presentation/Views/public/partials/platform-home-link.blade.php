@if (!auth()->check() || (int) auth()->id() !== $blog->ownerUserId)
    <a
        href="{{ route('home') }}"
        class="inline-flex items-center gap-2 text-sm underline-offset-4 hover:underline"
        style="color: var(--d-primary, currentColor)"
    >
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        {{ __('public.back_to_platform_home') }}
    </a>
@endif
