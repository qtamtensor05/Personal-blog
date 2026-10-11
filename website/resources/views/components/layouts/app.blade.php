<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="Nền tảng tạo blog cá nhân" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Bút Mây' }}</title>
    @vite (['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">Bỏ qua điều hướng</a>
    <header class="site-header">
        <a class="brand" href="/">{{ $publicSettings['name'] }}</a>
        <nav aria-label="Điều hướng chính">
            <a class="site-nav-link" href="/blogs" @if(request()->is('blogs', 'blogs/*')) aria-current="page" @endif>Khám phá</a>
            <a class="site-nav-link" href="/templates" @if(request()->is('/', 'templates', 'templates/*')) aria-current="page" @endif>Mẫu blog</a>
            <a class="site-nav-link" href="/guide" @if(request()->is('guide')) aria-current="page" @endif>Hướng dẫn</a>
            @auth
                <a class="site-nav-link" href="/account" @if(request()->is('account')) aria-current="page" @endif>Tài khoản</a>
                <a class="site-nav-link" href="/workspace" @if(request()->is('workspace')) aria-current="page" @endif>Blog của tôi</a>
                <a class="site-nav-link" href="/blog-admin">Bảng điều khiển</a>
                <form method="post" action="/logout">
                    @csrf
                    <button class="site-nav-link">Đăng xuất</button>
                </form>
            @else
                <a class="site-nav-link site-nav-auth" href="/login" @if(request()->is('login')) aria-current="page" @endif>Đăng nhập</a
                ><a class="button" href="/register">Tạo blog</a>
            @endauth
        </nav>
    </header>
    <main id="main-content">
        @if (session('status'))
            <p class="notice">{{ session('status') }}</p>
        @endif
        @if($errors->any())
        <div class="error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot }}
    </main>
    <footer>© {{ date('Y') }} Bút Mây · Viết điều đáng nhớ.</footer>
</body>
</html>
