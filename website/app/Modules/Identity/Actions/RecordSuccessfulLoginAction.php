<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Contracts\UserRepository;

final class RecordSuccessfulLoginAction
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function execute(int $userId): void
    {
        $this->users->recordSuccessfulLogin($userId);
    }
}
