<?php

namespace App\Core\User\Application\Command;

final class CreateUserCommand
{
    public function __construct(
        public readonly string $email
    ) {
    }
}
