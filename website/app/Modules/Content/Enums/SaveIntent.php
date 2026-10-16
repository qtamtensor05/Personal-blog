<?php

namespace App\Modules\Content\Enums;

enum SaveIntent: string
{
    case Draft = 'draft';
    case Publish = 'publish';
    case Schedule = 'schedule';

    /**
     * Resolve an intent coming from the UI; unknown values are a client error.
     */
    public static function fromRequest(string $value): self
    {
        $intent = self::tryFrom($value);

        abort_if($intent === null, 422);

        return $intent;
    }
}
