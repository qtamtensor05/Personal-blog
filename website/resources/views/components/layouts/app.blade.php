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
    <header class="site-header">
        <a class="brand" href="/">Bút Mây</a>
        <nav>
            <a href="/blogs">Khám phá</a><a href="/templates">Giao diện</a>
            @auth
                <a href="/workspace">Blog của tôi</a
                ><a href="/blog-admin">Bảng điều khiển</a>
                <form method="post" action="/logout">
                    @csrf
                    <button>Đăng xuất</button>
                </form>
            @else
                <a href="/login">Đăng nhập</a
                ><a class="button" href="/register">Tạo blog</a>
            @endauth
        </nav>
    </header>
    <main>
        @if (session('status'))
            <p class="notice">{{ session('status') }}</p>
        @endif
        @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
        @endif
        {{ $slot }}
    </main>
    <footer>© {{ date('Y') }} Bút Mây · Viết điều đáng nhớ.</footer>
</body>
</html>
