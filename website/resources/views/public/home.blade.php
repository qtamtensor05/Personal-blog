<x-layouts.app title="Bút Mây — Tạo blog của riêng bạn">
    <section class="hero">
        <p class="eyebrow">NỀN TẢNG BLOG ĐA NGƯỜI DÙNG</p>
        <h1>Một góc riêng cho những điều bạn muốn kể.</h1>
        <p>Chọn giao diện, viết nội dung và xuất bản blog cá nhân trong vài phút.</p>
        <div>
            <a class="button" href="/register">Bắt đầu miễn phí</a>
            <a href="/templates">Xem giao diện</a>
        </div>
    </section>
    <section>
        <h2>Blog mới nổi bật</h2>
        <div class="grid">
            @forelse ($blogs as $blog)
                <article class="card">
                    <span>{{ $blog->visibility }}</span>
                    <h3><a href="/b/{{ $blog->slug }}">{{ $blog->name }}</a></h3>
                    <p>{{ $blog->description }}</p>
                </article>
            @empty
                <p>Chưa có blog công khai. Hãy là người đầu tiên.</p>
            @endforelse
        </div>
    </section>
    <section>
        <h2>Giao diện tuyển chọn</h2>
        <div class="grid">
            @forelse ($templates as $template)
                <article class="card">
                    <h3>{{ $template->name }}</h3>
                    <p>{{ $template->description }}</p>
                </article>
            @empty
                <p>Thư viện đang được chuẩn bị.</p>
            @endforelse
        </div></section
></x-layouts.app>
