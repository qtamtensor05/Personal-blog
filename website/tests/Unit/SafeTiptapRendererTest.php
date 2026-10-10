<?php

use App\Modules\Content\Rendering\SafeTiptapRenderer;

it('escapes text and rejects script protocols', function () {
    $html = (new SafeTiptapRenderer)->render([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => '<script>alert(1)</script>',
                        'marks' => [
                            ['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    expect($html)
        ->not->toContain('<script>')
        ->not->toContain('javascript:')
        ->toContain('&lt;script&gt;');
});
