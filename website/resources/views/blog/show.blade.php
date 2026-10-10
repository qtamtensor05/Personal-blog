<x-layouts.app :title="$blog->name">
    <section class="page-head">
        <p class="eyebrow">{{ $blog->status }}</p>
        <h1>{{ $blog->name }}</h1>
        <p>{{ $blog->description }}</p>
        <a href="/b/{{ $blog->slug }}">Xem trang công khai →</a>
    </section>
    <section>
        <h2>Bài viết</h2>
        @forelse ($posts as $post)
            <article class="card">
                <span>{{ $post->status }}</span>
                <h3>{{ $post->title }}</h3>
            </article>
        @empty
            <p>Chưa có bài viết.</p>
        @endforelse</section
></x-layouts.app>
