<?php

use App\Application\PlatformSettings\Contracts\PlatformSettingsRepository;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

uses(TestCase::class);

it('does not render maintenance messages in the shared layout', function (): void {
    $this->withoutVite();
    expect(view('layouts.app')->render())->not->toContain('maintenanceBannerMessage');
});

it('renders the admin account list in the selected language', function (string $locale, string $heading): void {
    $this->withoutVite();
    app()->setLocale($locale);
    $repository = Mockery::mock(PlatformSettingsRepository::class);
    $this->app->instance(PlatformSettingsRepository::class, $repository);

    $html = view('admin.users.index', ['total' => 0, 'users' => [], 'filters' => [], 'page' => 1, 'lastPage' => 1, 'errors' => new ViewErrorBag])->render();
    expect($html)->toContain($heading);
    if ($locale === 'vi') {
        expect($html)->not->toContain('User management')->toContain('Tạo quản trị viên')->toContain('Không có tài khoản phù hợp');
    }
})->with([['vi', 'Quản lý người dùng'], ['en', 'User management']]);
