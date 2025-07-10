<?php

namespace App\Tests\Unit\Core\Invoice\Application\Command\CreateInvoice;

use App\Core\Invoice\Application\Command\CreateInvoice\CreateInvoiceCommand;
use App\Core\Invoice\Application\Command\CreateInvoice\CreateInvoiceHandler;
use App\Core\Invoice\Domain\Exception\InvoiceException;
use App\Core\Invoice\Domain\Exception\InactiveUserException;
use App\Core\Invoice\Domain\Invoice;
use App\Core\Invoice\Domain\Repository\InvoiceRepositoryInterface;
use App\Core\User\Domain\Exception\UserNotFoundException;
use App\Core\User\Domain\Repository\UserRepositoryInterface;
use App\Core\User\Domain\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateInvoiceHandlerTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepository;

    private InvoiceRepositoryInterface|MockObject $invoiceRepository;

    private CreateInvoiceHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new CreateInvoiceHandler(
            $this->invoiceRepository = $this->createMock(
                InvoiceRepositoryInterface::class
            ),
            $this->userRepository = $this->createMock(
                UserRepositoryInterface::class
            )
        );
    }

    public function test_handle_success(): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getIsActive')->willReturn(true);

        $invoice = new Invoice(
            $user, 12500
        );

        $this->userRepository->expects(self::once())
            ->method('getByEmail')
            ->willReturn($user);

        $this->invoiceRepository->expects(self::once())
            ->method('save')
            ->with($invoice);

        $this->invoiceRepository->expects(self::once())
            ->method('flush');

        $this->handler->__invoke((new CreateInvoiceCommand('test@test.pl', 12500)));
    }

    public function test_handle_user_not_exists(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->userRepository->expects(self::once())
            ->method('getByEmail')
            ->willThrowException(new UserNotFoundException());

        $this->handler->__invoke((new CreateInvoiceCommand('test@test.pl', 12500)));
    }

    public function test_handle_invoice_invalid_amount(): void
    {
        $this->expectException(InvoiceException::class);

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getIsActive')->willReturn(true);

        $this->userRepository->expects(self::once())
            ->method('getByEmail')
            ->willReturn($user);

        $this->handler->__invoke((new CreateInvoiceCommand('test@test.pl', -5)));
    }


    public function test_handle_can_create_invoice_for_active_user(): void
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

    public function test_handle_cannot_create_invoice_for_inactive_user(): void
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
