<?php

use App\Domain\AccessControl\Enums\PlatformPermission;
use App\Presentation\Web\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Presentation\Web\Controllers\BlogController;
use App\Presentation\Web\Controllers\BlogDesignController;
use App\Presentation\Web\Controllers\ForgotPasswordController;
use App\Presentation\Web\Controllers\LoginController;
use App\Presentation\Web\Controllers\MediaController;
use App\Presentation\Web\Controllers\RegisterUserController;
use App\Presentation\Web\Controllers\ResendVerificationEmailController;
use App\Presentation\Web\Controllers\ResetPasswordController;
use App\Presentation\Web\Controllers\TemplateGalleryController;
use App\Presentation\Web\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Mock featured posts - Replace with PostRepository
    $featuredPosts = [
        [
            'title' => 'Getting Started with Clean Architecture in Laravel',
            'slug' => 'getting-started-clean-architecture-laravel',
            'excerpt' => 'Learn how to structure your Laravel application using Clean Architecture principles for better maintainability and testability.',
            'cover_image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&h=600&fit=crop',
            'published_at' => '2026-10-10',
            'author' => 'John Doe',
            'category' => 'Technology',
            'is_featured' => true,
        ],
        [
            'title' => 'Modern UI Design Principles for Web Applications',
            'slug' => 'modern-ui-design-principles',
            'excerpt' => 'Explore the fundamental principles of modern UI design that create intuitive and beautiful web applications.',
            'cover_image' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&h=600&fit=crop',
            'published_at' => '2026-10-08',
            'author' => 'Jane Smith',
            'category' => 'Design',
            'is_featured' => true,
        ],
        [
            'title' => 'Building Scalable REST APIs with Laravel',
            'slug' => 'building-scalable-rest-apis-laravel',
            'excerpt' => 'Best practices for designing and implementing RESTful APIs that scale with your application needs.',
            'cover_image' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&h=600&fit=crop',
            'published_at' => '2026-10-05',
            'author' => 'Mike Johnson',
            'category' => 'Technology',
            'is_featured' => true,
        ],
    ];

    $recentPosts = [
        [
            'title' => 'The Future of Web Development in 2027',
            'slug' => 'future-web-development-2027',
            'excerpt' => 'Predictions and trends shaping the future of web development, from AI integration to new frameworks.',
            'cover_image' => 'https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=600&h=400&fit=crop',
            'published_at' => '2026-10-03',
            'author' => 'Sarah Williams',
            'category' => 'Technology',
            'is_featured' => false,
        ],
        [
            'title' => 'Effective Team Collaboration in Remote Work',
            'slug' => 'effective-team-collaboration-remote',
            'excerpt' => 'Strategies and tools for maintaining productive collaboration in distributed teams.',
            'cover_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&h=400&fit=crop',
            'published_at' => '2026-10-01',
            'author' => 'David Brown',
            'category' => 'Business',
            'is_featured' => false,
        ],
        [
            'title' => 'Database Optimization Techniques',
            'slug' => 'database-optimization-high-traffic',
            'excerpt' => 'Proven techniques to optimize database performance.',
            'cover_image' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=600&h=400&fit=crop',
            'published_at' => '2026-09-28',
            'author' => 'Emma Davis',
            'category' => 'Technology',
            'is_featured' => false,
        ],
    ];

    return view('pages.welcome', compact('featuredPosts', 'recentPosts'));
});

// Blog Posts Routes (placeholder - needs backend)
Route::get('/posts', function () {
    return view('pages.posts.index');
})->name('posts.index');

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

    return view('pages.posts.show', compact('mockPost', 'relatedPosts'))
        ->with('post', $mockPost);
})->name('posts.show');

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Register
    Route::get('/register', function () {
        return view('pages.auth.register');
    })->name('register');

    Route::post('/register', RegisterUserController::class)
        ->name('register.store');

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
})->middleware('auth')->name('verification.notice');

Route::get('/email/verified', function () {
    return view('pages.auth.email-verified');
})->name('verification.success');

// Authenticated Routes
Route::middleware('auth')->group(function () {
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

    // Template Gallery (users choose one for their blog)
    Route::get('/templates', TemplateGalleryController::class)->name('templates.gallery');

    // User's blogs
    Route::resource('blogs', BlogController::class)->except(['index']);
    Route::get('/my-blogs', [BlogController::class, 'index'])->name('blogs.index');

    // Blog Design Editor
    Route::prefix('/blogs/{blog}/design')
        ->whereNumber('blog')
        ->name('blogs.design.')
        ->group(function () {
            Route::get('/', [BlogDesignController::class, 'edit'])->name('edit');
            Route::post('/template', [BlogDesignController::class, 'applyTemplate'])->name('template');
            Route::put('/', [BlogDesignController::class, 'saveDraft'])->name('draft');
            Route::post('/publish', [BlogDesignController::class, 'publish'])->name('publish');
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
        // Templates
        Route::middleware(['permission:'.PlatformPermission::ManageTemplates->value])->group(function () {
            Route::get('/templates', [AdminTemplateController::class, 'index'])->name('templates.index');
            Route::get('/templates/create', [AdminTemplateController::class, 'create'])->name('templates.create');
            Route::post('/templates', [AdminTemplateController::class, 'store'])->name('templates.store');
            Route::get('/templates/{template}/edit', [AdminTemplateController::class, 'edit'])
                ->whereNumber('template')
                ->name('templates.edit');
            Route::put('/templates/{template}', [AdminTemplateController::class, 'update'])
                ->whereNumber('template')
                ->name('templates.update');
            Route::post('/templates/{template}/publish', [AdminTemplateController::class, 'publish'])
                ->whereNumber('template')
                ->name('templates.publish');
            Route::patch('/templates/{template}/status', [AdminTemplateController::class, 'changeStatus'])
                ->whereNumber('template')
                ->name('templates.status');
            Route::delete('/templates/{template}', [AdminTemplateController::class, 'destroy'])
                ->whereNumber('template')
                ->name('templates.destroy');
        });
    });

Route::post('/email/verification-notification', ResendVerificationEmailController::class)
    ->middleware('throttle:3,1')
    ->name('identity.email.resend');
