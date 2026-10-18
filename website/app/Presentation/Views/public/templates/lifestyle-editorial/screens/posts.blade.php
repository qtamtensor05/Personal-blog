<main class="le-inner-page">
    <header class="le-inner-header">
        <p class="le-kicker">Khám phá nội dung</p>
        <h1>{{ $latest['archiveTitle'] ?? 'Tất cả bài viết' }}</h1>
        <p>{{ $latest['archiveDescription'] ?? 'Những câu chuyện, trải nghiệm và cảm hứng mới nhất từ '.$blog->name.'.' }}</p>
    </header>
    <div class="le-archive-grid">
        @forelse($page->posts as $post)
            <article>
                <img src="{{ $images.'/'.$fallbackCards[$loop->index % 3] }}" alt="">
                <div><time>{{ $post->publishedAt ? \Illuminate\Support\Carbon::parse($post->publishedAt)->format('d.m.Y') : '' }}</time><h2>{{ $post->title }}</h2><p>{{ $post->excerpt ?? $post->content->plainText(180) }}</p><a class="le-read-more" href="{{ route('public.post', [$blog->slug, $post->slug]) }}">Đọc bài viết →</a></div>
            </article>
        @empty
            @foreach(['Biến ngày bình thường thành nguồn cảm hứng', '5 lý do để bắt đầu ngày mới sớm hơn', 'Học cách sắp xếp lại cuộc sống'] as $title)
                <article><img src="{{ $images.'/'.$fallbackCards[$loop->index] }}" alt=""><div><time>22.03.2026</time><h2>{{ $title }}</h2><p>Nội dung minh họa sẽ được thay bằng bài viết đã xuất bản của blogger.</p><a class="le-read-more" href="{{ $homeUrl }}">Về trang chủ →</a></div></article>
            @endforeach
        @endforelse
    </div>
</main>
