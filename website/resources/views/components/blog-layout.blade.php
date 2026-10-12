@props(['blog','design'=>[],'pages'=>[], 'title'=>null,'logo'=>null,'locale'=>'vi','socialLinks'=>[]])
<!doctype html><html lang="{{ $locale }}"><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width,initial-scale=1" /><title>{{ $title ?? $blog->name }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head><body><a class="skip-link" href="#main-content">Bỏ qua điều hướng</a><header class="site-header"><a class="brand" href="/b/{{ $blog->slug }}">@if($logo)<img width="40" src="{{ $logo }}" alt="" />@endif{{ $blog->name }}</a><nav><a href="/b/{{ $blog->slug }}">Trang chủ</a>@foreach($pages as $item)<a href="/b/{{ $blog->slug }}/pages/{{ $item->slug }}">{{ $item->title }}</a>@endforeach</nav></header><main id="main-content">{{ $slot }}</main><footer>{{ data_get($design,'navigation.footerText','Viết điều đáng nhớ.') }} · <a href="/">Bút Mây</a>@if($socialLinks)
<nav aria-label="{{ $locale === 'en' ? 'Social links' : 'Liên kết mạng xã hội' }}">
@foreach($socialLinks as $network => $url)
<a href="{{ $url }}" rel="noopener noreferrer">{{ ['twitter'=>'Twitter/X','facebook'=>'Facebook','instagram'=>'Instagram','youtube'=>'YouTube','github'=>'GitHub','linkedin'=>'LinkedIn'][$network] }}</a>
@endforeach
</nav>
@endif
</footer></body></html>
