<x-layouts.app title="Đăng nhập">
    <form class="auth-card" method="post" action="/login">
        @csrf
        <h1>Chào bạn trở lại</h1>
        <label
            >Email<input
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus /></label
        ><label>Mật khẩu<input name="password" type="password" required /></label
        ><label class="check"><input name="remember" type="checkbox" /> Ghi nhớ đăng nhập</label
        ><button class="button">Đăng nhập</button>
        <p>Chưa có tài khoản? <a href="/register">Đăng ký</a></p>
    </form></x-layouts.app
>
