<?php

use App\Application\Identity\Exceptions\InvalidPasswordResetToken;
use App\Application\Identity\UseCases\ResetPassword\ResetPasswordUseCase;
use App\Presentation\Web\Support\LocalizedError;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

it('uses the selected interface language for login validation', function (string $locale, string $message): void {
    $this->withSession(['interface_locale' => $locale])
        ->postJson(route('login.store'), [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', $message);
})->with([
    ['vi', 'Vui lòng nhập địa chỉ email.'],
    ['en', 'The email field is required.'],
]);

it('translates rejected login credentials at the presentation boundary', function (string $locale, string $message): void {
    Auth::shouldReceive('guard')->andReturnSelf();
    Auth::shouldReceive('check')->andReturnFalse();
    Auth::shouldReceive('attempt')->once()->andReturnFalse();
    $this->withSession(['interface_locale' => $locale])
        ->postJson(route('login.store'), ['email' => 'reader@example.com', 'password' => 'incorrect'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', $message);
})->with([
    ['vi', 'Email hoặc mật khẩu không đúng.'],
    ['en', 'These credentials do not match our records.'],
]);

it('renders translated shared modal controls', function (string $locale, string $close, string $error): void {
    app()->setLocale($locale);
    $html = view('components.modal', ['id' => 'test-modal', 'title' => null])->render();
    expect($html)->toContain('data-close-label="'.$close.'"')
        ->toContain('data-error-title="'.$error.'"')
        ->toContain('aria-label="'.$close.'"');
})->with([
    ['vi', 'Đóng', 'Đã xảy ra lỗi'],
    ['en', 'Close', 'Something went wrong'],
]);

it('keeps validation limits and business errors meaningful in both languages', function (): void {
    app()->setLocale('vi');
    $errors = Validator::make(['title' => str_repeat('a', 161)], ['title' => 'max:160'])->errors();
    expect($errors->first('title'))->toBe('tiêu đề phải có tối đa 160 ký tự.')
        ->and(__('This media is still in use and cannot be deleted.'))->toBe('Tệp này vẫn đang được sử dụng nên không thể xóa.')
        ->and(__('The file may not exceed :max MB.', ['max' => 10]))->toBe('Tệp không được lớn hơn 10 MB.');
    app()->setLocale('en');
    expect(__('This media is still in use and cannot be deleted.'))->toBe('This media is still in use and cannot be deleted.');
});

it('translates parameterized template errors without losing the invalid value', function (): void {
    app()->setLocale('vi');
    expect(LocalizedError::message('Invalid hex color: #xyz'))
        ->toBe('Mã màu không hợp lệ: #xyz')
        ->and(LocalizedError::message('Section hero cannot be placed in footer.'))
        ->toBe('Không thể đặt phần nội dung hero trong khu vực footer.');
    app()->setLocale('en');
    expect(LocalizedError::message('Invalid hex color: #xyz'))
        ->toBe('Invalid hex color: #xyz');
});

it('localizes password reset failures returned by the use case', function (string $locale, string $message): void {
    $useCase = Mockery::mock(ResetPasswordUseCase::class);
    $useCase->shouldReceive('execute')->once()->andThrow(new InvalidPasswordResetToken);
    $this->app->instance(ResetPasswordUseCase::class, $useCase);

    $this->withSession(['interface_locale' => $locale])
        ->postJson(route('password.update'), [
            'email' => 'reader@example.com',
            'token' => 'expired-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', $message);
})->with([
    ['vi', 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'],
    ['en', 'This password reset link is invalid or has expired.'],
]);
