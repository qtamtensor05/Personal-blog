<?php

use App\Application\Content\UseCases\ManagePostInteractions\PostInteractionsUseCase;
use App\Presentation\Web\Controllers\PostInteractionController;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('returns JSON without redirecting async post interactions', function (string $action, string $method, bool $active): void {
    $request = Request::create('/interaction', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(fn () => new GenericUser(['id' => 7]));
    $useCase = Mockery::mock(PostInteractionsUseCase::class);
    $useCase->shouldReceive($method)->once()->with(7, 'blog', 'post', $active);
    $response = (new PostInteractionController)->$action('blog', 'post', $request, $useCase);
    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->has('Location'))->toBeFalse()
        ->and($response->getData(true))->toBe(['active' => $active]);
})->with([
    ['like', 'setLike', true],
    ['unlike', 'setLike', false],
    ['bookmark', 'setBookmark', true],
    ['unbookmark', 'setBookmark', false],
]);
