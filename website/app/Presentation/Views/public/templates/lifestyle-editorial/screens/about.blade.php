<main class="le-inner-page">
    <section class="le-about-page">
        <img src="{{ !empty($about['mediaId']) && isset($mediaUrls[$about['mediaId']]) ? $mediaUrls[$about['mediaId']] : $images.'/portrait.svg' }}" alt="">
        <div>
            <p class="le-kicker">{{ __('public.about_story') }}</p>
            <h1>{{ $about['title'] ?? __('public.about_me') }}</h1>
            <p>{{ $about['description'] ?? ($profile['description'] ?? $blog->description) }}</p>
            <a class="le-page-cta" href="{{ $screenUrl('contact') }}">{{ __('public.connect') }} <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
        </div>
    </section>
</main>
