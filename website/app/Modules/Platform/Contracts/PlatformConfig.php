<?php

namespace App\Modules\Platform\Contracts;

/**
 * Read access to the platform-wide settings document.
 */
interface PlatformConfig
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;
}
