<?php

namespace Scrn\Bakery\Tests\Stubs\Policies;

use Scrn\Bakery\Tests\Stubs\User;

class UserPolicy
{
    /**
     * Determine if a user can be created by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }
}