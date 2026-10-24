<?php

use App\Domain\AccessControl\Enums\PlatformPermission;
use App\Presentation\Web\Controllers\Admin\ContentReportController as AdminContentReportController;
use App\Presentation\Web\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Presentation\Web\Controllers\Admin\PlatformSettingsController as AdminPlatformSettingsController;
use App\Presentation\Web\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Presentation\Web\Controllers\Admin\UserController as AdminUserController;
use App\Presentation\Web\Controllers\BlogController;
use App\Presentation\Web\Controllers\BlogDesignController;
use App\Presentation\Web\Controllers\BlogDomainController;
use App\Presentation\Web\Controllers\BlogSettingsController;
use App\Presentation\Web\Controllers\BookmarkedPostsController;
use App\Presentation\Web\Controllers\ContentReportController;
use App\Presentation\Web\Controllers\ForgotPasswordController;
use App\Presentation\Web\Controllers\HomeController;
use App\Presentation\Web\Controllers\InterfaceLocaleController;
use App\Presentation\Web\Controllers\LoginController;
use App\Presentation\Web\Controllers\MediaController;
use App\Presentation\Web\Controllers\NotificationController;
use App\Presentation\Web\Controllers\PageController;
use App\Presentation\Web\Controllers\PostController;
use App\Presentation\Web\Controllers\PostInteractionController;
use App\Presentation\Web\Controllers\ProfileController;
use App\Presentation\Web\Controllers\PublicBlogController;
use App\Presentation\Web\Controllers\PublicPageController;
use App\Presentation\Web\Controllers\PublicPostController;
use App\Presentation\Web\Controllers\PublicPostIndexController;
use App\Presentation\Web\Controllers\RegisterUserController;
use App\Presentation\Web\Controllers\ResendVerificationEmailController;
use App\Presentation\Web\Controllers\ResetPasswordController;
use App\Presentation\Web\Controllers\TaxonomyController;
use App\Presentation\Web\Controllers\TemplateGalleryController;
use App\Presentation\Web\Controllers\TemplatePreviewController;
use App\Presentation\Web\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/interface-locale', InterfaceLocaleController::class)->name('interface-locale.update');

Route::get('/posts', PublicPostIndexController::class)->name('posts.index');

Route::get('/posts/{slug}', function ($slug) {
    // Mock post data - Replace with PostRepository->findBySlug($slug)
    $mockPost = [
        'title' => 'Getting Started with Clean Architecture in Laravel',
        'slug' => $slug,
        'excerpt' => 'Learn how to structure your Laravel application using Clean Architecture principles for better maintainability and testability.',
        'content' => '
            <h2>Introduction</h2>
            <p>Clean Architecture is a software design philosophy that separates the concerns of an application into distinct layers. This separation allows for better testability, maintainability, and flexibility.</p>

            <h3>The Core Principles</h3>
            <p>At the heart of Clean Architecture are several key principles:</p>
            <ul>
                <li><strong>Independence of Frameworks:</strong> The architecture doesn\'t depend on the existence of some library or framework.</li>
                <li><strong>Testability:</strong> Business rules can be tested without the UI, database, or any external element.</li>
                <li><strong>Independence of UI:</strong> The UI can change easily without changing the rest of the system.</li>
                <li><strong>Independence of Database:</strong> You can swap out Oracle or SQL Server for MongoDB, BigTable, or something else.</li>
            </ul>

            <h3>Implementing in Laravel</h3>
            <p>In Laravel, we organize our code into the following layers:</p>
            <ol>
                <li><strong>Domain Layer:</strong> Contains entities, value objects, and domain services.</li>
                <li><strong>Application Layer:</strong> Contains use cases and application services.</li>
                <li><strong>Infrastructure Layer:</strong> Contains implementations of interfaces defined in inner layers.</li>
                <li><strong>Presentation Layer:</strong> Contains controllers, views, and presentation logic.</li>
            </ol>

            <blockquote>
                "The goal of software architecture is to minimize the human resources required to build and maintain the required system." - Robert C. Martin
            </blockquote>

            <h2>Benefits</h2>
            <p>Adopting Clean Architecture in your Laravel projects brings numerous advantages including improved code organization, easier testing, and better separation of concerns.</p>

            <h3>Example Code</h3>
            <pre><code>namespace App\\Domain\\Identity\\Entities;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}</code></pre>

            <h2>Conclusion</h2>
            <p>Clean Architecture is not a silver bullet, but it provides a solid foundation for building maintainable and scalable applications. Start small, refactor incrementally, and focus on the areas that bring the most value to your project.</p>
        ',
        'cover_image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1200&h=600&fit=crop',
        'published_at' => '2026-10-10',
        'author' => 'John Doe',
        'category' => 'Technology',
        'tags' => ['laravel', 'clean-architecture', 'php', 'software-design'],
    ];

    $relatedPosts = [
        [
            'title' => 'Building Scalable REST APIs',
            'slug' => 'building-scalable-rest-apis-laravel',
            'excerpt' => 'Best practices for designing APIs.',
            'cover_image' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=400&h=300&fit=crop',
            'published_at' => '2026-10-05',
            'author' => 'Mike Johnson',
            'category' => 'Technology',
        ],
        [
            'title' => 'Database Optimization Techniques',
            'slug' => 'database-optimization-high-traffic',
            'excerpt' => 'Optimize database performance.',
            'cover_image' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=400&h=300&fit=crop',
            'published_at' => '2026-09-28',
            'author' => 'Emma Davis',
            'category' => 'Technology',
        ],
    ];

    return view('pages.posts.show', compact('mockPost', 'relatedPosts'))->with('post', $mockPost);
})->name('posts.show');

Route::get('/template-preview/{slug}', TemplatePreviewController::class)
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('templates.preview');

// Public blog home (custom domains come later with blog_domains)
Route::get('/b/{slug}', PublicBlogController::class)
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.blog');

Route::get('/b/{slug}/section/{screen}', PublicBlogController::class)
    ->where([
        'slug' => '[a-z0-9]+(?:-[a-z0-9]+)*',
        'screen' => 'posts|about|contact',
    ])
    ->name('public.blog.section');

// Static pages live under /p/ so their slugs never collide with post slugs.
Route::get('/b/{slug}/p/{page}', PublicPageController::class)
    ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'page' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->name('public.page');

Route::get('/b/{slug}/{post}', PublicPostController::class)
    ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'post' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->name('public.post');

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Register
    Route::get('/register', function () {
        return view('pages.auth.register');
    })->name('register');

    Route::post('/register', RegisterUserController::class)->name('register.store');

    Route::get('/login', function () {
        return view('pages.auth.login');
    })->name('login');

    Route::post('/login', LoginController::class)->name('login.store');

    // Password Reset
    Route::get('/password/forgot', function () {
        return view('pages.auth.forgot-password');
    })->name('password.request');

    Route::post('/password/email', ForgotPasswordController::class)
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/password/reset/{token}', function (string $token) {
        return view('pages.auth.reset-password', ['token' => $token]);
    })->name('password.reset');

    Route::post('/password/reset', ResetPasswordController::class)
        ->middleware('throttle:5,1')
        ->name('password.update');
});

// Email Verification
Route::get('/email/verify', function () {
    return view('pages.auth.verify-email');
})
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verified', function () {
    return view('pages.auth.email-verified');
})->name('verification.success');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/bookmarks', BookmarkedPostsController::class)->name('bookmarks.index');

    Route::put('/b/{slug}/{post}/like', [PostInteractionController::class, 'like'])
        ->middleware('throttle:post-interactions')
        ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'post' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
        ->name('public.post.like');
    Route::delete('/b/{slug}/{post}/like', [PostInteractionController::class, 'unlike'])
        ->middleware('throttle:post-interactions')
        ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'post' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
        ->name('public.post.unlike');
    Route::put('/b/{slug}/{post}/bookmark', [PostInteractionController::class, 'bookmark'])
        ->middleware('throttle:post-interactions')
        ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'post' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
        ->name('public.post.bookmark');
    Route::delete('/b/{slug}/{post}/bookmark', [PostInteractionController::class, 'unbookmark'])
        ->middleware('throttle:post-interactions')
        ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'post' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
        ->name('public.post.unbookmark');

    Route::get('/notifications', [NotificationController::class, 'index'])->name(
        'notifications.index',
    );
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name(
        'notifications.read-all',
    );
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->whereUuid('notification')
        ->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
        ->whereUuid('notification')
        ->name('notifications.destroy');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');

    // Media (JSON, session + CSRF)
    Route::post('/media', [MediaController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('media.store');

    Route::delete('/media/{media}', [MediaController::class, 'destroy'])
        ->whereNumber('media')
        ->name('media.destroy');

    Route::get('/blogs/{blog}/media', [MediaController::class, 'index'])
        ->whereNumber('blog')
        ->name('blogs.media.index');

    // Report a blog or post (signed-in users; login already requires an active, verified account)
    Route::post('/b/{slug}/report', ContentReportController::class)
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:10,60')
        ->name('public.report');

    // Template Gallery (users choose one for their blog)
    Route::get('/templates', TemplateGalleryController::class)->name('templates.gallery');

    // User's blogs
    Route::resource('blogs', BlogController::class)->except(['index']);
    Route::get('/my-blogs', [BlogController::class, 'index'])->name('blogs.index');

    // Blog Settings
    Route::get('/blogs/{blog}/settings', [BlogSettingsController::class, 'edit'])
        ->whereNumber('blog')
        ->name('blogs.settings.edit');
    Route::put('/blogs/{blog}/settings', [BlogSettingsController::class, 'update'])
        ->whereNumber('blog')
        ->name('blogs.settings.update');

    // Categories & Tags
    Route::prefix('/blogs/{blog}')
        ->whereNumber(['blog', 'category', 'tag'])
        ->group(function () {
            Route::get('/taxonomy', [TaxonomyController::class, 'index'])->name('blogs.taxonomy');
            Route::post('/categories', [TaxonomyController::class, 'storeCategory'])->name(
                'blogs.categories.store',
            );
            Route::put('/categories/{category}', [
                TaxonomyController::class,
                'updateCategory',
            ])->name('blogs.categories.update');
            Route::delete('/categories/{category}', [
                TaxonomyController::class,
                'destroyCategory',
            ])->name('blogs.categories.destroy');
            Route::post('/tags', [TaxonomyController::class, 'storeTag'])->name('blogs.tags.store');
            Route::put('/tags/{tag}', [TaxonomyController::class, 'updateTag'])->name(
                'blogs.tags.update',
            );
            Route::delete('/tags/{tag}', [TaxonomyController::class, 'destroyTag'])->name(
                'blogs.tags.destroy',
            );
        });

    // Posts (Tiptap editor)
    Route::prefix('/blogs/{blog}/posts')
        ->whereNumber(['blog', 'post'])
        ->name('blogs.posts.')
        ->group(function () {
            Route::get('/', [PostController::class, 'index'])->name('index');
            Route::get('/{post}/likers', [PostController::class, 'likers'])->name('likers');
            Route::post('/', [PostController::class, 'store'])->name('store');
            Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
            Route::put('/{post}/draft', [PostController::class, 'saveDraft'])
                ->middleware('throttle:60,1')
                ->name('draft');
            Route::put('/{post}/meta', [PostController::class, 'updateMeta'])->name('meta');
            Route::post('/{post}/publish', [PostController::class, 'publish'])->name('publish');
            Route::patch('/{post}/status', [PostController::class, 'changeStatus'])->name('status');
            Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
            Route::post('/{post}/restore', [PostController::class, 'restore'])->name('restore');
            Route::delete('/{post}/permanent', [PostController::class, 'forceDestroy'])->name(
                'force-destroy',
            );
        });

    // Static pages (Tiptap editor)
    Route::prefix('/blogs/{blog}/pages')
        ->whereNumber(['blog', 'page'])
        ->name('blogs.pages.')
        ->group(function () {
            Route::get('/', [PageController::class, 'index'])->name('index');
            Route::post('/', [PageController::class, 'store'])->name('store');
            Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
            Route::put('/{page}/draft', [PageController::class, 'saveDraft'])
                ->middleware('throttle:60,1')
                ->name('draft');
            Route::put('/{page}/meta', [PageController::class, 'updateMeta'])->name('meta');
            Route::post('/{page}/publish', [PageController::class, 'publish'])->name('publish');
            Route::patch('/{page}/status', [PageController::class, 'changeStatus'])->name('status');
            Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
        });

    // Custom domains
    Route::prefix('/blogs/{blog}/domains')
        ->whereNumber(['blog', 'domain'])
        ->name('blogs.domains.')
        ->group(function () {
            Route::get('/', [BlogDomainController::class, 'index'])->name('index');
            Route::post('/', [BlogDomainController::class, 'store'])->name('store');
            Route::post('/{domain}/verify', [BlogDomainController::class, 'verify'])
                ->middleware('throttle:10,1')
                ->name('verify');
            Route::post('/{domain}/activate', [BlogDomainController::class, 'activate'])->name(
                'activate',
            );
            Route::post('/{domain}/disable', [BlogDomainController::class, 'disable'])->name(
                'disable',
            );
            Route::delete('/{domain}', [BlogDomainController::class, 'destroy'])->name('destroy');
        });

    // Blog Design Editor
    Route::prefix('/blogs/{blog}/design')
        ->whereNumber('blog')
        ->name('blogs.design.')
        ->group(function () {
            Route::get('/', [BlogDesignController::class, 'edit'])->name('edit');
            Route::post('/template', [BlogDesignController::class, 'applyTemplate'])->name(
                'template',
            );
            Route::put('/content', [BlogDesignController::class, 'saveSlotContent'])->name(
                'content',
            );
            Route::post('/publish/{region}', [BlogDesignController::class, 'publishRegion'])
                ->whereIn('region', ['header', 'body', 'sidebar', 'footer'])
                ->name('publish-region');
        });
});

Route::get('/email/verify/{user}', VerifyEmailController::class)
    ->middleware('signed')
    ->name('identity.email.verify');

// Admin Console
Route::middleware(['auth', 'permission:'.PlatformPermission::AccessAdminConsole->value])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // Users and account restrictions
        Route::middleware(['permission:'.PlatformPermission::ManageUsers->value])
            ->whereNumber(['user', 'role'])
            ->group(function () {
                Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
                Route::post('/users', [AdminUserController::class, 'createAdmin'])->name(
                    'users.store',
                );
                Route::get('/users/{user}', [AdminUserController::class, 'show'])->name(
                    'users.show',
                );
                Route::post('/users/{user}/restrictions', [
                    AdminUserController::class,
                    'restrict',
                ])->name('users.restrict');
                Route::delete('/users/{user}/restrictions', [
                    AdminUserController::class,
                    'lift',
                ])->name('users.lift');

                // Role changes need roles.manage on top of users.manage.
                Route::middleware(['permission:'.PlatformPermission::ManageRoles->value])->group(
                    function () {
                        Route::post('/users/{user}/roles', [
                            AdminUserController::class,
                            'grantRole',
                        ])->name('users.roles.grant');
                        Route::delete('/users/{user}/roles/{role}', [
                            AdminUserController::class,
                            'revokeRole',
                        ])->name('users.roles.revoke');
                        Route::post('/users/{user}/permissions', [
                            AdminUserController::class,
                            'setPermission',
                        ])->name('users.permissions.set');
                    },
                );
            });

        // Content reports
        Route::middleware(['permission:'.PlatformPermission::ModerateContent->value])
            ->whereNumber('report')
            ->group(function () {
                Route::get('/reports', [AdminContentReportController::class, 'index'])->name(
                    'reports.index',
                );
                Route::get('/reports/{report}', [
                    AdminContentReportController::class,
                    'show',
                ])->name('reports.show');
                Route::post('/reports/{report}/review', [
                    AdminContentReportController::class,
                    'review',
                ])->name('reports.review');
                Route::post('/reports/{report}/close', [
                    AdminContentReportController::class,
                    'close',
                ])->name('reports.close');
                Route::post('/reports/{report}/lift-suspension', [
                    AdminContentReportController::class,
                    'liftSuspension',
                ])->name('reports.lift');
            });

        Route::get('/audit-logs', [AdminUserController::class, 'auditLogs'])
            ->middleware(['permission:'.PlatformPermission::ViewAuditLogs->value])
            ->name('audit-logs');

        Route::middleware([
            'permission:'.PlatformPermission::ManagePlatformSettings->value,
        ])->group(function () {
            Route::get('/settings', [AdminPlatformSettingsController::class, 'edit'])->name(
                'settings.edit',
            );
            Route::put('/settings', [AdminPlatformSettingsController::class, 'update'])->name(
                'settings.update',
            );
        });

        // Templates
        Route::middleware(['permission:'.PlatformPermission::ManageTemplates->value])->group(
            function () {
                Route::get('/templates', [AdminTemplateController::class, 'index'])->name(
                    'templates.index',
                );
                Route::get('/templates/create', [AdminTemplateController::class, 'create'])->name(
                    'templates.create',
                );
                Route::post('/templates', [AdminTemplateController::class, 'store'])->name(
                    'templates.store',
                );
                Route::post('/templates/{template}/restore', [
                    AdminTemplateController::class,
                    'restore',
                ])
                    ->whereNumber('template')
                    ->name('templates.restore');
                Route::delete('/templates/{template}/permanent', [
                    AdminTemplateController::class,
                    'forceDestroy',
                ])
                    ->whereNumber('template')
                    ->name('templates.force-destroy');
                Route::get('/templates/{template}/edit', [AdminTemplateController::class, 'edit'])
                    ->whereNumber('template')
                    ->name('templates.edit');
                Route::put('/templates/{template}', [AdminTemplateController::class, 'update'])
                    ->whereNumber('template')
                    ->name('templates.update');
                Route::post('/templates/{template}/publish', [
                    AdminTemplateController::class,
                    'publish',
                ])
                    ->whereNumber('template')
                    ->name('templates.publish');
                Route::patch('/templates/{template}/status', [
                    AdminTemplateController::class,
                    'changeStatus',
                ])
                    ->whereNumber('template')
                    ->name('templates.status');
                Route::delete('/templates/{template}', [AdminTemplateController::class, 'destroy'])
                    ->whereNumber('template')
                    ->name('templates.destroy');
            },
        );
    });

Route::post('/email/verification-notification', ResendVerificationEmailController::class)
    ->middleware('throttle:3,1')
    ->name('identity.email.resend');
