<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogAdminController;
use App\Http\Controllers\PublicBlogController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    fn () => view('public.home', [
        'blogs' => DB::table('blogs')
            ->where(['status' => 'PUBLISHED', 'visibility' => 'PUBLIC', 'discoverable' => true])
            ->whereNull('deleted_at')
            ->latest('published_at')
            ->limit(9)
            ->get(),
        'templates' => DB::table('templates')
            ->where('status', 'PUBLISHED')
            ->whereNull('deleted_at')
            ->latest('published_at')
            ->limit(6)
            ->get(),
    ]),
);
Route::get(
    '/blogs',
    fn () => view('public.catalog', [
        'title' => 'Khám phá blog',
        'items' => DB::table('blogs')
            ->where(['status' => 'PUBLISHED', 'visibility' => 'PUBLIC', 'discoverable' => true])
            ->whereNull('deleted_at')
            ->paginate(18),
    ]),
);
Route::get(
    '/templates',
    fn () => view('public.catalog', [
        'title' => 'Thư viện giao diện',
        'items' => DB::table('templates')
            ->where('status', 'PUBLISHED')
            ->whereNull('deleted_at')
            ->paginate(18),
    ]),
);

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'registerForm']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        DB::table('users')
            ->where('id', $request->user()->id)
            ->where('status', 'PENDING_VERIFICATION')
            ->update(['status' => 'ACTIVE', 'updated_at' => now()]);

        return redirect('/blog-admin');
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
    });

Route::get('/b/{blogSlug}', [PublicBlogController::class, 'home']);
Route::get('/b/{blogSlug}/posts/{postSlug}', [PublicBlogController::class, 'post']);
Route::get('/b/{blogSlug}/pages/{pageSlug}', [PublicBlogController::class, 'page']);
