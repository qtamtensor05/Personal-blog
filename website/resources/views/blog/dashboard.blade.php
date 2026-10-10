<x-layouts.app title="Blog của tôi">
    <section class="page-head">
        <p class="eyebrow">KHU VỰC QUẢN TRỊ</p>
        <h1>Blog của tôi</h1>
    </section>
    <div class="admin-grid">
        <form class="card" method="post" action="/workspace/blogs">
            @csrf
            <h2>Tạo blog mới</h2>
            <label>Tên blog<input name="name" required /></label
            ><label>Đường dẫn<input name="slug" placeholder="tu-dong-neu-de-trong" /></label
            ><label>Giới thiệu<textarea name="description"></textarea></label
            ><button class="button">Tạo blog</button>
        </form>
        <section>
            <h2>Danh sách</h2>
            @forelse ($blogs as $blog)
                <article class="card">
                    <span>{{ $blog->status }}</span>
                    <h3><a href="/workspace/blogs/{{ $blog->id }}">{{ $blog->name }}</a></h3>
                    <p>/b/{{ $blog->slug }}</p>
                </article>
            @empty
                <p>Bạn chưa có blog.</p>
            @endforelse
        </section>
    </div>
    {{ $blogs->links() }}</x-layouts.app
>
