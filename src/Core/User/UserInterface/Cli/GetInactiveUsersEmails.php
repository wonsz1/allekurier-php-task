<?php

namespace App\Core\User\UserInterface\Cli;

use App\Core\User\Application\Query\GetInactiveUsers\GetInactiveUsersEmailsQuery;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Common\Bus\QueryBusInterface;

#[AsCommand(
    name: 'app:user:get-inactive-emails',
    description: 'Pobiera listę e-maili nieaktywnych użytkowników'
)]
class GetInactiveUsersEmails extends Command
{
    public function __construct(private readonly QueryBusInterface $bus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inactiveUsersEmails = $this->bus->dispatch(new GetInactiveUsersEmailsQuery());

        if (empty($inactiveUsersEmails)) {
            $output->writeln('<info>Brak nieaktywnych użytkowników.</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<info>Lista e-maili nieaktywnych użytkowników:</info>');
        foreach ($inactiveUsersEmails as $userEmail) {
            $output->writeln($userEmail);
        }

        return Command::SUCCESS;
    }
}
