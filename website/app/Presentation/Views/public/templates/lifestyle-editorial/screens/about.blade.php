<main class="le-inner-page">
    <section class="le-about-page">
        <img
            src="{{ !empty($about['mediaId']) && isset($mediaUrls[$about['mediaId']]) ? $mediaUrls[$about['mediaId']] : $images.'/portrait.svg' }}"
            alt=""
        />
        <div>
            <p class="le-kicker">Câu chuyện phía sau blog</p>
            <h1>{{ $about['title'] ?? 'About me' }}</h1>
            <p>{{
                $about['description'] ??
                    ($profile['description'] ?? $blog->description)
            }}</p>
            <a class="le-page-cta" href="{{ $screenUrl('contact') }}">Kết nối với tôi →</a>
        </div>
    </section>
</main>
