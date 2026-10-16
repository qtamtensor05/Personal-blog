<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Contracts\RegistrationSettingsRepository;

final class EnsureRegistrationEnabledAction
{
    public function __construct(
        private readonly RegistrationSettingsRepository $settings,
    ) {}

    public function execute(): bool
    {
        return $this->settings->isRegistrationEnabled();
    }
}
