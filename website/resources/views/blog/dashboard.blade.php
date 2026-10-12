<x-layouts.app title="Blog của tôi">
    <section class="page-head">
        <p class="eyebrow">KHU VỰC QUẢN TRỊ</p>
        <h1>Blog của tôi</h1>
    </section>
    <div class="admin-grid">
        <form class="card" method="post" action="/workspace/blogs">
            @csrf
            <h2>Tạo blog mới</h2>
            @if($errors->any())
                <div role="alert" class="error">
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <label>Mẫu<select name="template_id" required><option value="">Chọn mẫu</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected(old('template_id', session('selected_template')) == $template->id)>{{ $template->name }}</option>@endforeach</select></label>
            <label>Tên blog<input name="name" value="{{ old('name') }}" required /></label>
            <label>Đường dẫn<input name="slug" value="{{ old('slug') }}" placeholder="tu-dong-neu-de-trong" /><small>Chỉ dùng chữ thường, số và dấu gạch ngang. Để trống để tự sinh từ tên.</small></label>
            <label>Giới thiệu<textarea name="description">{{ old('description') }}</textarea></label>
            <button class="button">Tạo blog</button>
        </form>
        <section>
            <h2>Danh sách</h2>
            @forelse ($blogs as $blog)
                <article class="card">
                    <span>{{ $blog->status }}</span>
                    <h3><a href="/blog-admin/studio?blog={{ $blog->id }}">{{ $blog->name }}</a></h3>
                    <p>/b/{{ $blog->slug }}</p>
                </article>
            @empty
                <p>Bạn chưa có blog.</p>
            @endforelse
        </section>
    </div>
    {{ $blogs->links() }}</x-layouts.app>
