<?php

use Tests\TestCase;

uses(TestCase::class);

it('renders the settings logo before the template logo', function (): void {
    $html = view('public.partials.blog-logo', [
        'logoUrl' => '/storage/settings-logo.png',
        'content' => ['mediaId' => 9],
        'mediaUrls' => [9 => '/storage/template-logo.png'],
        'imageClass' => 'le-brand-image',
    ])->render();

    expect($html)->toContain('src="/storage/settings-logo.png"')->not->toContain('template-logo.png');
});

it('uses the template logo when no settings logo exists', function (): void {
    $html = view('public.partials.blog-logo', [
        'content' => ['mediaId' => 9],
        'mediaUrls' => [9 => '/storage/template-logo.png'],
        'imageClass' => 'le-brand-image',
    ])->render();

    expect($html)->toContain('src="/storage/template-logo.png"');
});

it('renders the fallback when neither logo is available', function (): void {
    $html = view('public.partials.blog-logo', [
        'logoUrl' => null,
        'content' => [],
        'mediaUrls' => [],
        'imageClass' => 'le-brand-image',
        'showFallback' => true,
    ])->render();

    expect($html)->toContain('<svg', 'le-brand-mark')->not->toContain('<img', 'fa-book-open');
});
