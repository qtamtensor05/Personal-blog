<x-layouts.app title="Tài khoản">
    <section class="auth-card">
        <h1>Tài khoản của bạn</h1>
        <form method="post" action="/account/profile">
            @csrf
            <h2>Hồ sơ</h2>
            <label>Tên hiển thị<input name="name" value="{{ old('name', $account->name) }}" required maxlength="160" autocomplete="name" /></label>
            <p>Email: {{ $account->email }}</p>
            <button class="button" type="submit">Lưu hồ sơ</button>
        </form>
        <hr />
        <form method="post" action="/account/email">
            @csrf
            <h2>Đổi email</h2>
            <p>Bạn cần xác minh địa chỉ mới. Các phiên cũ sẽ bị đăng xuất; quyền quản lý blog tạm dừng đến khi xác minh xong.</p>
            <label>Email mới<input type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="email" /></label>
            <label>Mật khẩu hiện tại<input type="password" name="current_password" required autocomplete="current-password" /></label>
            <button class="button" type="submit">Đổi email và gửi xác minh</button>
        </form>
        <hr />
        <form method="post" action="/account/password">
            @csrf
            <h2>Đổi mật khẩu</h2>
            <p>Tối thiểu 12 ký tự, có chữ hoa, chữ thường và số. Sau khi đổi, tất cả phiên sẽ bị đăng xuất.</p>
            <label>Mật khẩu hiện tại<input type="password" name="current_password" required autocomplete="current-password" /></label>
            <label>Mật khẩu mới<input type="password" name="password" required minlength="12" autocomplete="new-password" /></label>
            <label>Xác nhận mật khẩu mới<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" /></label>
            <button class="button" type="submit">Đổi mật khẩu</button>
        </form>
    </section>
</x-layouts.app>
