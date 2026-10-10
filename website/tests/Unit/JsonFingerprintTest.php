<?php

use App\Shared\Support\JsonFingerprint;

it('creates the same fingerprint regardless of object key order', function () {
    expect(JsonFingerprint::make(['b' => 2, 'a' => ['d' => 4, 'c' => 3]]))->toBe(
        JsonFingerprint::make(['a' => ['c' => 3, 'd' => 4], 'b' => 2]),
    );
});
