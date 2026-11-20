<?php

namespace App\Presentation\Web\Support;

final class LocalizedError
{
    public static function message(string $message): string
    {
        if (preg_match('/^Unknown template layout: (.+)$/u', $message, $matches) === 1) {
            return __('Unknown template layout: :value', ['value' => $matches[1]]);
        }

        if (preg_match('/^Invalid hex color: (.+)$/u', $message, $matches) === 1) {
            return __('Invalid hex color: :value', ['value' => $matches[1]]);
        }

        if (preg_match('/^Invalid font name: (.+)$/u', $message, $matches) === 1) {
            return __('Invalid font name: :value', ['value' => $matches[1]]);
        }

        if (preg_match('/^Invalid post layout variant: (.+)$/u', $message, $matches) === 1) {
            return __('Invalid post layout variant: :value', ['value' => $matches[1]]);
        }

        if (preg_match('/^Invalid post content width: (.+)$/u', $message, $matches) === 1) {
            return __('Invalid post content width: :value', ['value' => $matches[1]]);
        }

        if (preg_match('/^Unknown section type: (.+)$/u', $message, $matches) === 1) {
            return __('Unknown section type: :value', ['value' => $matches[1]]);
        }

        if (preg_match('/^Section (.+) cannot be placed in (.+)\.$/u', $message, $matches) === 1) {
            return __('Section :section cannot be placed in :region.', ['section' => $matches[1], 'region' => $matches[2]]);
        }

        return __($message);
    }
}
