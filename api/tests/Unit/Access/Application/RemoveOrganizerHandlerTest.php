<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Application;

use App\Access\Application\RemoveOrganizer;
use App\Access\Application\RemoveOrganizerHandler;
use App\Access\Domain\Entity\Account;
use App\Access\Domain\Entity\Organizer;
use App\Access\Domain\Exception\LastOrganizerException;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\InMemory\Repository\InMemoryAccountRepository;
use App\Access\Infrastructure\InMemory\Repository\InMemoryOrganizerRepository;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RemoveOrganizerHandlerTest extends TestCase
{
    private InMemoryAccountRepository $accountRepository;

    private InMemoryOrganizerRepository $organizerRepository;

    private RemoveOrganizerHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = new InMemoryAccountRepository();
        $this->organizerRepository = new InMemoryOrganizerRepository();
        $this->handler = new RemoveOrganizerHandler($this->accountRepository, $this->organizerRepository);
    }

    public function testItRemovesAnOrganizerByEmail(): void
    {
        $account = $this->createOrganizerAccount('anne@bailletced.org');
        $this->createOrganizerAccount('bob@bailletced.org');

        ($this->handler)(new RemoveOrganizer(new Email('anne@bailletced.org')));

        self::assertFalse($this->organizerRepository->isOrganizer($account->getId()->value));
        self::assertSame(1, $this->organizerRepository->countAll());
    }

    public function testItMatchesTheEmailCaseInsensitively(): void
    {
        $account = $this->createOrganizerAccount('case@bailletced.org');
        $this->createOrganizerAccount('other@bailletced.org');

        ($this->handler)(new RemoveOrganizer(new Email('CASE@bailletced.org')));

        self::assertFalse($this->organizerRepository->isOrganizer($account->getId()->value));
    }

    public function testItRefusesToRemoveTheLastOrganizer(): void
    {
        $account = $this->createOrganizerAccount('seul@bailletced.org');

        $this->expectException(LastOrganizerException::class);
        $this->expectExceptionMessage('Il doit toujours rester au moins un organisateur.');

        ($this->handler)(new RemoveOrganizer(new Email('seul@bailletced.org')));
    }

    public function testItRefusesToRemoveANonOrganizerEmail(): void
    {
        $this->createOrganizerAccount('anne@bailletced.org');

        // The email exists as an account but is not an organizer
        $account = new Account(new AccountId(), new Email('priant@bailletced.org'));
        $this->accountRepository->add($account);

        ($this->handler)(new RemoveOrganizer(new Email('priant@bailletced.org')));

        // Nothing changed: the organizer is still there
        self::assertSame(1, $this->organizerRepository->countAll());
    }

    public function testItDoesNothingForAnUnknownEmail(): void
    {
        $this->createOrganizerAccount('anne@bailletced.org');

        ($this->handler)(new RemoveOrganizer(new Email('inconnu@bailletced.org')));

        self::assertSame(1, $this->organizerRepository->countAll());
    }

    private function createOrganizerAccount(string $email): Account
    {
        $account = new Account(new AccountId(), new Email($email));
        $this->accountRepository->add($account);
        $this->organizerRepository->add(new Organizer($account->getId()->value, Uuid::v7()));

        return $account;
    }
}
