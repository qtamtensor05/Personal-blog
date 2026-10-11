<?php

namespace Tests;

use App\Modules\Identity\Auth\SessionStamp;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);
        SessionStamp::bind($user);

        return $this;
    }
}
