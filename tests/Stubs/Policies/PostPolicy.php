<?php

namespace Scrn\Bakery\Tests\Stubs\Policies;

use Scrn\Bakery\Tests\Stubs\User;
use Scrn\Bakery\Test\Stubs\Post;

class PostPolicy
{
    /**
     * Determine if a post can be created by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }
}