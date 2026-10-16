<?php

namespace App\Modules\Identity\Contracts;

interface RegistrationSettingsRepository
{
    public function isRegistrationEnabled(): bool;
}
