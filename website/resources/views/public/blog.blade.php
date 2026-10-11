<x-blog-layout :blog="$blog" :design="$design" :pages="$pages" :logo="$logo">

<form class="filter-bar" method="get"><label>Tìm bài<input type="search" name="q" value="{{ request('q') }}" /></label><label>Danh mục<select name="category"><option value="">Tất cả</option>@foreach($categories as $term)<option value="{{ $term->id }}" @selected(request('category')==$term->id)>{{ $term->name }}</option>@endforeach</select></label><label>Thẻ<select name="tag"><option value="">Tất cả</option>@foreach($tags as $term)<option value="{{ $term->id }}" @selected(request('tag')==$term->id)>{{ $term->name }}</option>@endforeach</select></label><button class="button">Tìm bài</button></form>
@if($design)<x-design-preview :design="$design" :name="$blog->name" :posts="$posts" :base="'/b/'.$blog->slug" />@else<h1>{{ $blog->name }}</h1><p>{{ $blog->description }}</p><div class="posts">@forelse($posts as $post)<article><h2><a href="/b/{{ $blog->slug }}/posts/{{ $post->slug }}">{{ $post->title }}</a></h2><p>{{ $post->excerpt }}</p></article>@empty<p>Chưa có bài viết phù hợp.</p>@endforelse</div>@endif
{{ $posts->links() }}
</x-blog-layout>
