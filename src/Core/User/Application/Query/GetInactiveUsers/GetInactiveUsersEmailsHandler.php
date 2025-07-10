<?php

namespace App\Core\User\Application\Query\GetInactiveUsers;

use App\Core\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetInactiveUsersEmailsHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(GetInactiveUsersEmailsQuery $query): array
    {
        return $this->userRepository->getInactiveUsersEmails();
    }
}
