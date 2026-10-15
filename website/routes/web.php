<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogAdminController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\TemplateController;
use App\Modules\Identity\Actions\VerifyCurrentEmailAction;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;

Route::get('/', [TemplateController::class, 'index']);
Route::get('/blogs', [DiscoveryController::class, 'index']);
Route::get('/templates', [TemplateController::class, 'index']);
Route::get('/templates/{template}', [TemplateController::class, 'show'])->whereNumber('template');
Route::post('/templates/{template}/choose', [TemplateController::class, 'choose'])->whereNumber('template')->middleware('throttle:30,1');
Route::view('/guide', 'public.guide');

Route::middleware('guest')->group(function (): void {
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'send'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn () => view('auth.reset-password'))->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'registerForm']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::middleware(['auth', 'verified', 'account.active'])->group(function (): void {
    Route::get('/account', [AccountController::class, 'show']);
    Route::post('/account/{section}', [AccountController::class, 'update'])->whereIn('section', ['profile', 'email', 'password'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        app(VerifyCurrentEmailAction::class)->execute((int) $request->user()->id, (string) $request->route('hash'));

        return redirect('/workspace');
    })
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Đã gửi lại email xác minh.');
    })
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::prefix('workspace')
    ->middleware(['auth', 'verified', 'account.active'])
    ->group(function (): void {
        Route::get('/', [BlogAdminController::class, 'index']);
        Route::post('/blogs', [BlogAdminController::class, 'store']);
        Route::get('/blogs/{blog}', [BlogAdminController::class, 'show'])->whereNumber('blog');
        Route::get('/blogs/{blog}/preview/{kind}/{id}', [PreviewController::class, 'content'])->whereNumber(['blog', 'id']);
        Route::get('/blogs/{blog}/preview-design', [PreviewController::class, 'design'])->whereNumber('blog');
    });

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemaps/{page}.xml', [SitemapController::class, 'part'])->whereNumber('page');
Route::get('/b/{blogSlug}', [PublicBlogController::class, 'home']);
Route::get('/b/{blogSlug}/posts/{postSlug}', [PublicBlogController::class, 'post']);
Route::get('/b/{blogSlug}/pages/{pageSlug}', [PublicBlogController::class, 'page']);
