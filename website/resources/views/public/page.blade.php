<x-blog-layout :blog="$blog" :design="$design" :pages="$pages" :logo="$logo" :title="$page->seo_title ?: $page->title">
    <article class="prose">
        <a href="/b/{{ $blog->slug }}">← {{ $blog->name }}</a>
        <h1>{{ $page->title }}</h1>
        <div>{!! $content !!}</div>
    </article></x-blog-layout>
