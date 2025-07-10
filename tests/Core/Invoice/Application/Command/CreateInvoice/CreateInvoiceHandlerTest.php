<?php

namespace Tests\Core\Invoice\Application\Command\CreateInvoice;

use App\Core\Invoice\Application\Command\CreateInvoice\CreateInvoiceCommand;
use App\Core\Invoice\Application\Command\CreateInvoice\CreateInvoiceHandler;
use App\Core\Invoice\Domain\Exception\InactiveUserException;
use App\Core\Invoice\Domain\Invoice;
use App\Core\Invoice\Domain\Repository\InvoiceRepositoryInterface;
use App\Core\User\Domain\Repository\UserRepositoryInterface;
use App\Core\User\Domain\User;
use PHPUnit\Framework\TestCase;

class CreateInvoiceHandlerTest extends TestCase
{
    public function testCanCreateInvoiceForActiveUser(): void
    {
        // Arrange
        $email = 'test@example.com';
        $amount = 100;
        $activeUser = new User($email);
        $activeUser->setIsActive(true);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($email)
            ->willReturn($activeUser);

        $invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $invoice) use ($activeUser, $amount) {
                return $invoice->getUser() === $activeUser && $invoice->getAmount() === $amount;
            }));

        $invoiceRepository->expects($this->once())
            ->method('flush');

        $handler = new CreateInvoiceHandler($invoiceRepository, $userRepository);
        $command = new CreateInvoiceCommand($email, $amount);

        // Act
        $handler->__invoke($command);

        // Assert
        $this->addToAssertionCount(2); // Asserts for save and flush expectations
    }

    public function testCannotCreateInvoiceForInactiveUser(): void
    {
        // Arrange
        $email = 'inactive@example.com';
        $amount = 100;
        $inactiveUser = new User($email);
        $inactiveUser->setIsActive(false);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($email)
            ->willReturn($inactiveUser);

        $invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $invoiceRepository->expects($this->never())
            ->method('save');

        $invoiceRepository->expects($this->never())
            ->method('flush');

        $handler = new CreateInvoiceHandler($invoiceRepository, $userRepository);
        $command = new CreateInvoiceCommand($email, $amount);

        // Act & Assert
        $this->expectException(InactiveUserException::class);

        $handler->__invoke($command);
    }
}
