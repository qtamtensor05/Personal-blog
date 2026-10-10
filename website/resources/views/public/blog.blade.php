<x-layouts.app :title="$blog->name">
    <section class="hero">
        <p class="eyebrow">BLOG CÁ NHÂN</p>
        <h1>{{ $blog->name }}</h1>
        <p>{{ $blog->description }}</p>
    </section>
    <div class="posts">
        @forelse ($posts as $post)
            <article>
                <p class="eyebrow">{{ $post->published_at }}</p>
                <h2>
                    <a href="/b/{{ $blog->slug }}/posts/{{ $post->slug }}">{{ $post->title }}</a>
                </h2>
                <p>{{ $post->excerpt }}</p>
            </article>
        @empty
            <p>Blog chưa có bài viết công khai.</p>
        @endforelse
    </div>
    {{ $posts->links() }}</x-layouts.app
>
