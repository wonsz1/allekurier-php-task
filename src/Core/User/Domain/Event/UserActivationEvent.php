<?php

namespace App\Core\User\Domain\Event;

use App\Core\User\Domain\User;

class UserActivationEvent extends AbstractUserEvent
{
    public function __construct(
        public readonly User $user,
        public readonly bool $isActive
    ) {
    }
}
