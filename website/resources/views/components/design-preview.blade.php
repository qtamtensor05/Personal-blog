@props(['design' => [], 'name' => 'Bút Mây', 'compact' => false, 'posts' => [], 'base' => null])
@php
    $form = \App\Modules\Design\DesignDocument::form($design);
    $sections = \App\Modules\Design\DesignDocument::sections($design);
    $accent = preg_match('/^#[a-f0-9]{6}$/i', $form['accent']) ? $form['accent'] : '#147d73';
@endphp
<div class="design-preview layout-{{ in_array($form['layout'], ['journal','journey','magazine']) ? $form['layout'] : 'journal' }} {{ $compact ? 'compact' : '' }}" style="--design-accent:{{ $accent }}">
    <div class="demo-header"><strong>{{ $name }}</strong><span>Trang chủ · Bài viết</span></div>
    @foreach($sections as $section)
        @switch($section['type'] ?? '')
            @case('hero')
                <section class="demo-hero"><div><h2>{{ is_string($section['content'] ?? null) ? $section['content'] : $name }}</h2><p>{{ $section['description'] ?? '' }}</p>@if($base)<a class="button" href="#bai-viet">Đọc bài viết ↓</a>@endif</div><img src="/images/{{ $form['layout'] === 'journey' ? 'mountains' : 'journal' }}.svg" alt="{{ $form['layout'] === 'journey' ? 'Minh họa núi và hồ' : 'Minh họa góc viết' }}" /></section>@break
            @case('text')<p class="demo-text">{{ $section['content'] ?? '' }}</p>@break
            @case('post-list')
                <section id="bai-viet" class="demo-posts"><h2>Bài viết mới</h2>@forelse($posts as $post)<article><h3>@if($base)<a href="{{ $base }}/posts/{{ $post->slug }}">{{ $post->title }}</a>@else{{ $post->title }}@endif</h3><p>{{ $post->excerpt ?? '' }}</p></article>@empty<p>Chưa có bài viết được xuất bản.</p>@endforelse</section>@break
        @endswitch
    @endforeach
    <div class="demo-footer">{{ $form['footer'] }}</div>
</div>
