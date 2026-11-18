<?php

declare(strict_types=1);

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\DTOs\BlogRecord;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\DTOs\PostRecord;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Media\DTOs\MediaAssetRecord;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\TiptapDocument;
use App\Domain\Media\Enums\MediaScope;
use App\Domain\Media\Enums\MediaStatus;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function writingTestImage(int $blogId = 10, MediaStatus $status = MediaStatus::Ready, string $mime = 'image/png'): MediaAssetRecord
{
    return new MediaAssetRecord(30, MediaScope::Blog, $blogId, 7, 'images/example.png', '/storage/images/example.png', 1, 1, 100, null, $status, 'example.png', $mime);
}

function writingTestSuggestion(): array
{
    return ['title' => 'A useful title', 'slug' => 'a-useful-title', 'excerpt' => 'A short summary.',
        'seo_title' => 'Useful SEO title', 'seo_description' => 'An accurate SEO description.', 'image_alt' => 'A visible subject.',
        'blocks' => [['type' => 'heading', 'text' => 'A useful section'], ['type' => 'paragraph', 'text' => 'A useful suggestion.'], ['type' => 'bulletList', 'items' => ['First detail', 'Second detail']]]];
}

beforeEach(function (): void {
    Cache::flush();
    config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-3.5-flash-lite', 'services.gemini.fallback_model' => 'gemini-3.1-flash-lite', 'media.driver' => 'local', 'media.local_disk' => 'public']);
    Http::preventStrayRequests();
    Storage::fake('public');
    $this->imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII=');
    Storage::disk('public')->put('images/example.png', $this->imageBytes);
    $this->blogs = Mockery::mock(BlogRepository::class);
    $this->blogs->shouldReceive('find')->with(10)->andReturn(new BlogRecord(10, 7, 'My blog', 'my-blog', null, BlogStatus::Draft, BlogVisibility::Public, true, null, null))->byDefault();
    $this->posts = Mockery::mock(PostRepository::class);
    $this->posts->shouldReceive('find')->with(10, 20)->andReturn(new PostRecord(20, 10, 7, 'Post', 'post', null, TiptapDocument::empty(), null, PostStatus::Draft, null, null, null, null, false, null, '2026-11-18'))->byDefault();
    $this->media = Mockery::mock(MediaAssetRepository::class);
    $this->media->shouldReceive('find')->with(30)->andReturn(writingTestImage())->byDefault();
    $this->app->instance(BlogRepository::class, $this->blogs);
    $this->app->instance(PostRepository::class, $this->posts);
    $this->app->instance(MediaAssetRepository::class, $this->media);
    $this->actor = new GenericUser(['id' => 7, 'email' => 'writer@example.com', 'status' => 'ACTIVE']);
    $this->payload = ['media_id' => 30, 'title' => 'My post', 'context' => 'Nearby paragraph', 'instructions' => 'Use a warm tone', 'locale' => 'vi'];
    $this->endpoint = route('blogs.posts.image-writing', [10, 20]);
});

it('sends image bytes and context to Gemini without saving or publishing content', function (): void {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['thought' => true, 'text' => 'private reasoning'], ['text' => json_encode(writingTestSuggestion())]]]]]])]);
    $this->actingAs($this->actor)->withSession(['interface_locale' => 'vi'])
        ->postJson($this->endpoint, $this->payload)->assertOk()
        ->assertJsonPath('data.title', 'A useful title')->assertJsonPath('data.slug', 'a-useful-title')->assertJsonPath('data.content.content.0.type', 'heading')->assertJsonPath('data.content.content.2.type', 'bulletList')->assertHeader('Cache-Control', 'no-store, private');
    Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key')
        && $request['contents'][0]['parts'][0]['inlineData']['data'] === base64_encode($this->imageBytes)
        && str_contains($request['contents'][0]['parts'][1]['text'], 'Nearby paragraph')
        && str_contains($request['systemInstruction']['parts'][0]['text'], 'Vietnamese'));
});

it('rejects guests before calling Gemini', function (): void {
    $this->postJson($this->endpoint, $this->payload)->assertUnauthorized();
    Http::assertNothingSent();
});

it('rejects a different owner or a missing post before calling Gemini', function (string $case): void {
    if ($case === 'owner') {
        $this->actor = new GenericUser(['id' => 8]);
    } else {
        $this->posts->shouldReceive('find')->with(10, 20)->andReturnNull();
    }
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertNotFound();
    Http::assertNothingSent();
})->with(['owner', 'post']);

it('rejects foreign, deleted or unsupported images before calling Gemini', function (MediaAssetRecord $image): void {
    $this->media->shouldReceive('find')->with(30)->andReturn($image);
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertUnprocessable();
    Http::assertNothingSent();
})->with([
    fn () => writingTestImage(99),
    fn () => writingTestImage(status: MediaStatus::Deleted),
    fn () => writingTestImage(mime: 'image/gif'),
]);

it('validates request limits and does not accept image URLs from the client', function (): void {
    $this->payload['instructions'] = str_repeat('x', 1001);
    $this->payload['locale'] = 'fr';
    unset($this->payload['media_id']);
    $this->payload['image_url'] = 'http://127.0.0.1/private';
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)
        ->assertUnprocessable()->assertJsonValidationErrors(['instructions', 'locale', 'media_id']);
    Http::assertNothingSent();
});

it('reports missing configuration in the selected interface language', function (string $locale, string $expected): void {
    config(['services.gemini.key' => '']);
    $this->actingAs($this->actor)->withSession(['interface_locale' => $locale])
        ->postJson($this->endpoint, $this->payload)->assertServiceUnavailable()->assertJsonPath('message', $expected);
    Http::assertNothingSent();
})->with([
    ['vi', 'Tính năng viết bằng AI chưa được cấu hình. Vui lòng liên hệ quản trị viên.'],
    ['en', 'AI writing is not configured. Please contact an administrator.'],
]);

it('returns a safe error on rejected, empty or truncated model responses', function (array $response, int $status): void {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response($response, $status)]);
    $result = $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertServiceUnavailable();
    expect($result->getContent())->not->toContain('test-key', 'provider-secret');
})->with([
    [['error' => ['message' => 'provider-secret']], 403],
    [['candidates' => []], 200],
    [['candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => 'unfinished']]]]]], 200],
    [['candidates' => [['finishReason' => 'SAFETY']], 'promptFeedback' => ['blockReason' => 'SAFETY']], 200],
]);

it('handles a Gemini timeout safely', function (): void {
    Http::fake(fn () => throw new ConnectionException('provider-secret'));
    $response = $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertServiceUnavailable();
    expect($response->getContent())->not->toContain('provider-secret');
});

it('does not forward unreadable image bytes', function (): void {
    Storage::disk('public')->put('images/example.png', 'not an image');
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertServiceUnavailable();
    Http::assertNothingSent();
});

it('reads Cloudinary images without redirects and sends their bytes to Gemini', function (): void {
    config(['media.driver' => 'cloudinary']);
    $this->media->shouldReceive('find')->with(30)->andReturn(new MediaAssetRecord(
        30, MediaScope::Blog, 10, 7, 'images/example', 'https://res.cloudinary.com/test/image/upload/example.png',
        1, 1, 100, null, MediaStatus::Ready, 'example.png', 'image/png',
    ));
    Http::fake([
        'res.cloudinary.com/*' => Http::response($this->imageBytes, 200),
        'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(writingTestSuggestion())]]]]]]),
    ]);
    $this->payload['locale'] = 'en';
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertOk()->assertJsonPath('data.title', 'A useful title');
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com')
        && str_contains($request['systemInstruction']['parts'][0]['text'], 'English'));
});

it('rejects untrusted delivery hosts before any outbound request', function (): void {
    config(['media.driver' => 'cloudinary']);
    $this->media->shouldReceive('find')->with(30)->andReturn(new MediaAssetRecord(
        30, MediaScope::Blog, 10, 7, 'images/example', 'https://127.0.0.1/private',
        1, 1, 100, null, MediaStatus::Ready, 'example.png', 'image/png',
    ));
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertServiceUnavailable();
    Http::assertNothingSent();
});

it('rejects oversized images before calling Gemini', function (): void {
    Storage::disk('public')->put('images/example.png', str_repeat('x', 5 * 1024 * 1024 + 1));
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertServiceUnavailable();
    Http::assertNothingSent();
});

it('limits AI requests per user', function (): void {
    config(['services.gemini.key' => '']);
    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertServiceUnavailable();
    }
    $this->postJson($this->endpoint, $this->payload)->assertTooManyRequests();
    Http::assertNothingSent();
});

it('lets writers choose an image from their device without existing article images', function (): void {
    app()->setLocale('vi');
    $html = view('posts.manage.image-writing', ['aiAvailable' => true])->render();
    expect($html)->toContain('type="file"', 'data-ai-file', 'Chọn ảnh từ thiết bị', 'AI điền các mục đã chọn')
        ->not->toContain('data-ai-image', 'Ảnh trong bài viết', 'data-ai-suggestion');
});

it('autosaves slug and SEO inside the draft without changing live metadata', function (): void {
    $this->media->shouldReceive('readyAssetsForBlog')->with(10, [])->andReturn([]);
    $this->posts->shouldReceive('saveDraft')->once()->with(10, 20, Mockery::on(fn ($draft) => $draft->metadata?->slug === 'draft-slug' && $draft->metadata?->seoTitle === 'Draft SEO'
        && $draft->metadata?->seoDescription === 'Draft description' && $draft->excerpt === 'Draft summary'), 7);
    $this->actingAs($this->actor)->putJson(route('blogs.posts.draft', [10, 20]), [
        'title' => 'Draft title', 'excerpt' => 'Draft summary', 'content' => TiptapDocument::empty()->toArray(),
        'metadata' => ['slug' => 'draft-slug', 'seo_title' => 'Draft SEO', 'seo_description' => 'Draft description'],
    ])->assertOk();
    Http::assertNothingSent();
});

it('rejects malformed structured AI responses without returning partial fields', function (string $json): void {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $json]]]]]])]);
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertServiceUnavailable()->assertJsonMissingPath('data');
})->with(['not json', '{"title":"Incomplete"}', '{"title":123}']);

it('allocates a PHP budget above the bounded AI timeouts and restores it', function (): void {
    $previous = (int) ini_get('max_execution_time');
    set_time_limit(30);
    try {
        Http::fake(function () {
            expect((int) ini_get('max_execution_time'))->toBe(90);

            return Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(writingTestSuggestion())]]]]]]);
        });
        $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertOk();
        expect((int) ini_get('max_execution_time'))->toBe(30);
    } finally {
        set_time_limit($previous);
    }
});

it('returns a localized timeout message instead of a PHP fatal response', function (string $locale, string $message): void {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
    $this->actingAs($this->actor)->withSession(['interface_locale' => $locale])
        ->postJson($this->endpoint, $this->payload)->assertServiceUnavailable()->assertJsonPath('message', $message)->assertJsonMissingPath('data');
})->with([
    ['vi', 'AI phản hồi quá chậm. Các ô bài viết của bạn chưa bị thay đổi. Vui lòng thử lại.'],
    ['en', 'AI took too long to respond. Your post fields have not been changed. Please try again.'],
]);

it('identifies provider failures without exposing provider messages', function (int $status, string $expected): void {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'provider-secret', 'details' => null]], $status)]);
    $this->actingAs($this->actor)->withSession(['interface_locale' => 'en'])
        ->postJson($this->endpoint, $this->payload)->assertServiceUnavailable()->assertJsonPath('message', $expected);
    Http::assertSentCount($status === 503 ? 2 : 1);
})->with([
    [400, 'Gemini rejected the request. Please ask an administrator to check the model and request configuration.'],
    [401, 'The Gemini API key is invalid or expired. Please ask an administrator to update it.'],
    [403, 'This Gemini API key does not have access. Please check the project, API restrictions and billing settings.'],
    [404, 'The configured Gemini model is unavailable. Please ask an administrator to check GEMINI_MODEL.'],
    [429, 'The Gemini project has reached its rate limit or quota. Please check its usage limits before trying again.'],
    [503, 'Gemini is temporarily overloaded or unavailable. Please wait a moment and try again.'],
]);

it('recovers from one temporary provider failure without repeating the image upload', function (): void {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push(['error' => ['message' => 'temporarily unavailable']], 503)
        ->push(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(writingTestSuggestion())]]]]]])]);
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertOk()->assertJsonPath('data.title', 'A useful title');
    Http::assertSentCount(2);
});

it('recognizes invalid API keys reported as a bad request', function (): void {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['details' => [['reason' => 'API_KEY_INVALID']]]], 400)]);
    $this->actingAs($this->actor)->withSession(['interface_locale' => 'en'])->postJson($this->endpoint, $this->payload)
        ->assertServiceUnavailable()->assertJsonPath('message', 'The Gemini API key is invalid or expired. Please ask an administrator to update it.');
    Http::assertSentCount(1);
});

it('requests and returns only the selected metadata without generating an article', function (): void {
    $this->payload['fields'] = ['seo_title', 'seo_description'];
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(['seo_title' => 'SEO', 'seo_description' => 'Description'])]]]]]])]);
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertOk()
        ->assertJsonPath('data.seo_title', 'SEO')->assertJsonMissingPath('data.content')->assertJsonMissingPath('data.title');
    Http::assertSent(fn ($request) => $request['generationConfig']['maxOutputTokens'] === 768
        && $request['generationConfig']['responseJsonSchema']['required'] === ['seo_title', 'seo_description']
        && ! isset($request['generationConfig']['responseJsonSchema']['properties']['blocks']));
});

it('does not accept unknown or empty selected fields', function (array $fields): void {
    $this->payload['fields'] = $fields;
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertUnprocessable();
    Http::assertNothingSent();
})->with([[[]], [['css']], [['title', 'title']]]);

it('uses the temporary analysis copy without downloading or changing original media', function (): void {
    config(['media.driver' => 'cloudinary']);
    $this->payload['fields'] = ['title'];
    $this->payload['analysis_image'] = UploadedFile::fake()->createWithContent('analysis.png', $this->imageBytes);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"title":"Optimized title"}']]]]]])]);
    $this->actingAs($this->actor)->post($this->endpoint, $this->payload, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.title', 'Optimized title');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['contents'][0]['parts'][0]['inlineData']['data'] === base64_encode($this->imageBytes));
    expect(Storage::disk('public')->get('images/example.png'))->toBe($this->imageBytes);
});

it('switches to the configured fallback model for the single transient retry', function (): void {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()->push([], 503)
        ->push(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(writingTestSuggestion())]]]]]])]);
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertOk();
    $requests = Http::recorded();
    expect($requests[0][0]->url())->toContain('gemini-3.5-flash-lite:generateContent')
        ->and($requests[1][0]->url())->toContain('gemini-3.1-flash-lite:generateContent');
});

it('preserves Vietnamese accents in all generated fields and restricts ASCII to the slug', function (): void {
    $this->payload['title'] = 'Bua com gia dinh';
    $data = [
        'title' => 'Bữa cơm gia đình ấm cúng', 'slug' => 'bua-com-gia-dinh',
        'excerpt' => 'Gắn kết gia đình qua những bữa ăn giản dị.',
        'seo_title' => 'Bữa cơm gia đình: hương vị thân quen',
        'seo_description' => 'Khám phá ý nghĩa của bữa cơm gia đình và cách tạo nên những khoảnh khắc ấm áp.',
        'image_alt' => 'Bàn ăn với những món ăn gia đình.',
        'blocks' => [['type' => 'heading', 'text' => 'Hương vị gắn kết'], ['type' => 'paragraph', 'text' => 'Một bữa cơm giản dị mang đến cảm giác gần gũi.']],
    ];
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($data)]]]]]])]);
    $this->actingAs($this->actor)->postJson($this->endpoint, $this->payload)->assertOk()
        ->assertJsonPath('data.title', $data['title'])->assertJsonPath('data.slug', $data['slug'])
        ->assertJsonPath('data.excerpt', $data['excerpt'])->assertJsonPath('data.seo_title', $data['seo_title'])
        ->assertJsonPath('data.seo_description', $data['seo_description'])->assertJsonPath('data.image_alt', $data['image_alt'])
        ->assertJsonPath('data.content.content.0.content.0.text', 'Hương vị gắn kết')
        ->assertJsonPath('data.content.content.1.content.0.text', 'Một bữa cơm giản dị mang đến cảm giác gần gũi.');
    Http::assertSent(fn ($request) => str_contains($request['systemInstruction']['parts'][0]['text'], 'Viết tiếng Việt đầy đủ dấu')
        && str_contains($request['systemInstruction']['parts'][0]['text'], 'ASCII restriction applies ONLY to the slug')
        && str_contains($request['generationConfig']['responseJsonSchema']['properties']['title']['description'], 'Tiếng Việt đầy đủ dấu'));
});
