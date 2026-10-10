<x-layouts.app title="Xác minh email">
    <section class="auth-card">
        <h1>Xác minh email</h1>
        <p>Chúng tôi đã gửi liên kết xác minh. Bạn cần xác minh trước khi tạo hoặc xuất bản blog.</p>
        <form method="post" action="/email/verification-notification">
            @csrf
            <button class="button">Gửi lại email</button>
        </form>
    </section></x-layouts.app
>
