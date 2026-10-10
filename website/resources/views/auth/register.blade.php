<x-layouts.app title="Tạo tài khoản">
    <form class="auth-card" method="post" action="/register">
        @csrf
        <h1>Bắt đầu blog của bạn</h1>
        <label>Tên hiển thị<input name="name" value="{{ old('name') }}" required /></label
        ><label>Email<input name="email" type="email" value="{{ old('email') }}" required /></label
        ><label>Mật khẩu<input name="password" type="password" required /></label
        ><label
            >Nhập lại mật khẩu<input name="password_confirmation" type="password" required /></label
        ><button class="button">Tạo tài khoản</button>
    </form></x-layouts.app
>
