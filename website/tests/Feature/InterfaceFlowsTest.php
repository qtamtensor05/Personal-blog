<?php

use App\Filament\Blog\Pages\Studio;
use App\Filament\Pages\Management;
use App\Models\User;
use App\Modules\Blogs\Actions\CreateBlogAction;
use App\Modules\Content\Actions\PublishScheduledContentAction;
use App\Modules\Content\Actions\SaveContentAction;
use App\Modules\Design\Actions\PublishDesignAction;
use App\Modules\Design\Actions\SaveDesignAction;
use App\Modules\Design\DesignDocument;
use App\Modules\Platform\Actions\SavePlatformSettingsAction;
use App\Modules\PublicSite\Rendering\SafeSectionRegistry;
use App\Modules\Templates\Actions\SaveTemplateAction;
use App\Shared\Support\JsonFingerprint;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function interfaceTemplate(User $user): int
{
    return DB::table('templates')->insertGetId(['name' => 'Nhật ký', 'slug' => 'journal', 'status' => 'PUBLISHED', 'created_by' => $user->id, 'updated_by' => $user->id, 'published_by' => $user->id, 'published_at' => now(), 'version_number' => 1, 'template_data' => json_encode(['schemaVersion' => 1, 'project' => ['pages' => [['id' => 'home', 'sections' => [['type' => 'hero', 'content' => 'Xin chào'], ['type' => 'post-list']]]]], 'themeDefaults' => ['accent' => '#147d73', 'layout' => 'journal']]), 'created_at' => now(), 'updated_at' => now()]);
}

function interfaceContent(string $title = 'Bài đầu tiên'): array
{
    return ['title' => $title, 'slug' => 'bai-dau-tien', 'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $title]]]]], 'category_ids' => [], 'tag_ids' => []];
}

it('keeps a validated template through login and creates a private draft snapshot', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $template = interfaceTemplate($owner);
    $this->get('/templates?q=Nhật')->assertOk()->assertSee('Nhật ký');
    $this->get('/templates/'.$template)->assertOk();
    $this->post('/templates/'.$template.'/choose')->assertRedirect('/login')->assertSessionHas('selected_template', $template);
    $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertRedirect('/workspace')->assertSessionHas('selected_template', $template);
    $this->post('/workspace/blogs', ['name' => 'Blog mới', 'slug' => 'blog-moi', 'template_id' => $template])->assertRedirect();
    $blog = DB::table('blogs')->first();
    $design = DB::table('blog_designs')->where('blog_id', $blog->id)->first();
    expect($blog->status)->toBe('DRAFT')->and($design->published_data)->toBeNull()->and($design->draft_template_id)->toBe($template);
    $this->get('/b/blog-moi')->assertNotFound();
});

it('rejects unavailable templates without creating a partial blog', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $id = interfaceTemplate($owner);
    DB::table('templates')->where('id', $id)->update(['status' => 'HIDDEN']);
    $this->actingAs($owner)->post('/workspace/blogs', ['name' => 'Nope', 'template_id' => $id])->assertSessionHasErrors('template_id');
    $this->assertDatabaseCount('blogs', 0);
});

it('isolates drafts, detects stale writes and publishes scheduled edits', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $template = interfaceTemplate($owner);
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Blog', 'slug' => 'blog', 'template_id' => $template]);
    $draft = json_decode(DB::table('blog_designs')->where('blog_id', $blog)->value('draft_data'), true);
    app(PublishDesignAction::class)->execute($owner, $blog, JsonFingerprint::make($draft));
    $action = app(SaveContentAction::class);
    $post = $action->execute($owner, $blog, 'posts', null, interfaceContent('Live title'), null, 'publish');
    $row = DB::table('posts')->where('id', $post)->first();
    $fingerprint = SaveContentAction::fingerprint($row);
    $input = interfaceContent('Secret draft');
    $input['scheduled_at'] = now()->addHour()->toDateTimeString();
    $action->execute($owner, $blog, 'posts', $post, $input, $fingerprint, 'schedule');
    $this->get('/b/blog/posts/bai-dau-tien')->assertOk()->assertSee('Live title')->assertDontSee('Secret draft');
    expect(fn () => $action->execute($owner, $blog, 'posts', $post, $input, $fingerprint))->toThrow(ValidationException::class);
    DB::table('posts')->where('id', $post)->update(['scheduled_at' => now()->subMinute()]);
    expect(app(PublishScheduledContentAction::class)->execute())->toBe(1);
    $this->get('/b/blog/posts/bai-dau-tien')->assertOk()->assertSee('Secret draft');
});

it('prevents cross tenant edit and preview even when IDs are forged', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $other = User::factory()->create(['status' => 'ACTIVE']);
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Private', 'slug' => 'private']);
    $post = app(SaveContentAction::class)->execute($owner, $blog, 'posts', null, interfaceContent(), null);
    $this->actingAs($other)->get("/workspace/blogs/{$blog}/preview/posts/{$post}")->assertNotFound();
    Filament::setCurrentPanel(Filament::getPanel('blog'));
    Livewire::test(Studio::class, ['blogId' => $blog])->assertNotFound();
});

it('renders the owner workspace and Tiptap form using real scoped data', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $template = interfaceTemplate($owner);
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Studio', 'slug' => 'studio', 'template_id' => $template]);
    $this->actingAs($owner);
    Filament::setCurrentPanel(Filament::getPanel('blog'));
    Livewire::test(Studio::class, ['blogId' => $blog])->assertSee('Studio')->set('section', 'posts')->call('edit')->assertSee('Lưu nháp')->set('form.title', 'My post')->set('form.slug', 'my-post')->call('save', 'draft')->assertHasNoErrors();
    $this->assertDatabaseHas('posts', ['blog_id' => $blog, 'title' => 'My post', 'status' => 'DRAFT']);
});

it('does not let source template changes modify a published blog snapshot', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $template = interfaceTemplate($owner);
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Snapshot', 'slug' => 'snapshot', 'template_id' => $template]);
    $draft = json_decode(DB::table('blog_designs')->where('blog_id', $blog)->value('draft_data'), true);
    app(PublishDesignAction::class)->execute($owner, $blog, JsonFingerprint::make($draft));
    $before = DB::table('blog_designs')->where('blog_id', $blog)->value('published_data');
    DB::table('templates')->where('id', $template)->update(['template_data' => json_encode(['schemaVersion' => 1, 'project' => []])]);
    expect(DB::table('blog_designs')->where('blog_id', $blog)->value('published_data'))->toBe($before);
    app(SaveDesignAction::class)->execute($owner, $blog, DesignDocument::form($draft), JsonFingerprint::make($draft));
    expect(DB::table('blog_designs')->where('blog_id', $blog)->value('published_data'))->toBe($before);
});

it('publishes templates with permissions and preserves other settings groups', function () {
    $this->seed(PlatformSeeder::class);
    $admin = User::factory()->create(['status' => 'ACTIVE']);
    DB::table('user_platform_roles')->insert(['user_id' => $admin->id, 'role_id' => DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id'), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
    $id = app(SaveTemplateAction::class)->execute($admin, null, DesignDocument::form([]) + ['name' => 'New template', 'slug' => 'new-template', 'category_id' => null], '', 'publish');
    expect(DB::table('templates')->where('id', $id)->value('status'))->toBe('PUBLISHED');
    $before = json_decode(DB::table('platform_settings')->value('config_json'), true);
    app(SavePlatformSettingsAction::class)->execute($admin, 'branding', ['name' => 'Bút Mây', 'secret' => 'not allowed']);
    $after = json_decode(DB::table('platform_settings')->value('config_json'), true);
    expect($after['limits'])->toBe($before['limits'])->and($after['branding']['name'])->toBe('Bút Mây')->and(isset($after['branding']['secret']))->toBeFalse();
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('platform'));
    Livewire::test(Management::class)->assertSee('New template')->call('edit', $id)->assertSee('Lưu nháp');
});

it('rejects scripts in native editor project frames', function () {
    expect(fn () => app(SafeSectionRegistry::class)->validate(['schemaVersion' => 1, 'project' => ['pages' => [['frames' => [['component' => ['type' => 'wrapper', 'components' => [['type' => 'hero', 'script' => 'alert(1)']]]]]]]]]))->toThrow(InvalidArgumentException::class);
});

it('prevents portal sessions from bypassing the platform login challenge', function () {
    $this->seed(PlatformSeeder::class);
    $admin = User::factory()->create(['status' => 'ACTIVE']);
    DB::table('user_platform_roles')->insert(['user_id' => $admin->id, 'role_id' => DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id'), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($admin)->get('/platform-admin/management')->assertRedirect('/platform-admin/login');
});

it('filters template galleries by category and preserves the category after editing', function () {
    $this->seed(PlatformSeeder::class);
    $admin = User::factory()->create(['status' => 'ACTIVE']);
    DB::table('user_platform_roles')->insert(['user_id' => $admin->id, 'role_id' => DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id'), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
    $category = DB::table('template_categories')->insertGetId(['name' => 'Du lịch', 'slug' => 'travel', 'kind' => 'TOPIC', 'created_at' => now(), 'updated_at' => now()]);
    $id = app(SaveTemplateAction::class)->execute($admin, null, DesignDocument::form([]) + ['name' => 'Travel theme', 'slug' => 'travel-theme', 'category_id' => $category], '', 'publish');
    $this->get('/templates?category='.$category)->assertOk()->assertSee('Travel theme');
    $this->get('/templates?category=999999')->assertOk()->assertDontSee('Travel theme')->assertSee('Không tìm thấy mẫu');
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('platform'));
    Livewire::test(Management::class)->set('category', $category)->assertSee('Travel theme')->call('edit', $id)->assertSet('form.category_id', $category)->set('form.heading', 'A changed draft')->call('saveTemplate')->assertHasNoErrors();
    $this->get('/templates/'.$id)->assertOk()->assertDontSee('A changed draft');
    expect(DB::table('template_category_assignments')->where('template_id', $id)->value('category_id'))->toBe($category);
});

it('does not let a regular owner manage platform templates', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $this->actingAs($owner);
    Filament::setCurrentPanel(Filament::getPanel('platform'));
    Livewire::test(Management::class)->assertForbidden();
    expect(fn () => app(SaveTemplateAction::class)->execute($owner, null, DesignDocument::form([]) + ['name' => 'Forged', 'slug' => 'forged'], '', 'publish'))->toThrow(HttpException::class);
});

it('seeds three actual layouts and refuses demo seed outside development', function () {
    $this->seed(DemoSeeder::class);
    expect(DB::table('templates')->where('status', 'PUBLISHED')->count())->toBe(3)
        ->and(DB::table('posts')->where('status', 'PUBLISHED')->count())->toBe(2);
    $this->get('/templates')->assertOk()->assertSee('Nhật ký')->assertSee('Hành trình')->assertSee('Tạp chí');
    $this->get('/b/chuyen-cua-an')->assertOk()->assertSee('Câu chuyện đầu tiên');
    app()->detectEnvironment(fn () => 'production');
    expect(fn () => app(DemoSeeder::class)->run())->toThrow(HttpException::class);
});

it('cancels scheduled publication when saving a new draft', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Schedule', 'slug' => 'schedule']);
    $input = interfaceContent();
    $input['scheduled_at'] = now()->addHour()->toDateTimeString();
    $id = app(SaveContentAction::class)->execute($owner, $blog, 'posts', null, $input, null, 'schedule');
    $row = DB::table('posts')->where('id', $id)->first();
    app(SaveContentAction::class)->execute($owner, $blog, 'posts', $id, interfaceContent('Saved draft'), SaveContentAction::fingerprint($row), 'draft');
    $this->assertDatabaseHas('posts',['id' => $id, 'status' => 'DRAFT', 'scheduled_at' => null]);
});
