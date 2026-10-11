<x-blog-layout :blog="$blog" :design="$design" :pages="$pages" :logo="$logo" :title="$post->seo_title ?: $post->title">
    <article class="prose">
        <a href="/b/{{ $blog->slug }}">← {{ $blog->name }}</a>
        <h1>{{ $post->title }}</h1>
        <p>{{ $post->excerpt }}</p>
        <div>{!! $content !!}</div>
    </article></x-blog-layout>
