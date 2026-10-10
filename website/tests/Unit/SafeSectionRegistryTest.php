<?php

use App\Modules\PublicSite\Rendering\SafeSectionRegistry;

it('rejects unknown and executable components', function (array $section) {
    (new SafeSectionRegistry)->validate([
        'schemaVersion' => 1,
        'project' => ['pages' => [['sections' => [$section]]]],
    ]);
})
    ->with([
        [['type' => 'unknown']],
        [['type' => 'text', 'content' => '<script>alert(1)</script>']],
    ])
    ->throws(InvalidArgumentException::class);

it('accepts registered safe components', function () {
    (new SafeSectionRegistry)->validate([
        'schemaVersion' => 1,
        'project' => ['pages' => [['sections' => [['type' => 'hero', 'content' => 'Xin chào']]]]],
    ]);
    expect(true)->toBeTrue();
});
