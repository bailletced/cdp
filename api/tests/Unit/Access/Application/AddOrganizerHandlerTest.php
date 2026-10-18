<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Application;

use App\Access\Application\AddOrganizer;
use App\Access\Application\AddOrganizerHandler;
use App\Access\Application\AddOrganizerResult;
use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Repository\InMemoryAccountRepository;
use App\Access\Infrastructure\Repository\InMemoryOrganizerRepository;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class AddOrganizerHandlerTest extends TestCase
{
    private InMemoryAccountRepository $accountRepository;
    private InMemoryOrganizerRepository $organizerRepository;
    private AddOrganizerHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = new InMemoryAccountRepository();
        $this->organizerRepository = new InMemoryOrganizerRepository();
        $this->handler = new AddOrganizerHandler($this->accountRepository, $this->organizerRepository);
    }

    public function testItCreatesAnAccountAndAnOrganizerForAnUnknownEmail(): void
    {
        $outcome = ($this->handler)(new AddOrganizer(new Email('new@example.com')));

        self::assertSame(AddOrganizerResult::AccountCreated, $outcome);

        $account = $this->accountRepository->findByEmail(new Email('new@example.com'));
        self::assertNotNull($account);
        self::assertSame(1, $account->getCredentialsVersion());
        self::assertTrue($this->organizerRepository->isOrganizer($account->getId()->value));
        self::assertSame(1, $this->organizerRepository->countAll());
    }

    public function testItPromotesAnExistingAccountWithoutTouchingIt(): void
    {
        $account = new Account(new AccountId(), new Email('priant@example.com'));
        $this->accountRepository->add($account);

        $outcome = ($this->handler)(new AddOrganizer(new Email('priant@example.com')));

        self::assertSame(AddOrganizerResult::OrganizerCreated, $outcome);
        self::assertTrue($this->organizerRepository->isOrganizer($account->getId()->value));
        self::assertSame(1, $this->organizerRepository->countAll());

        // The existing account is left untouched
        $found = $this->accountRepository->findById($account->getId());
        self::assertNotNull($found);
        self::assertSame(1, $found->getCredentialsVersion());
        self::assertNull($found->getPriantId());
    }

    public function testItMatchesTheEmailCaseInsensitively(): void
    {
        $account = new Account(new AccountId(), new Email('case@example.com'));
        $this->accountRepository->add($account);

        $outcome = ($this->handler)(new AddOrganizer(new Email('CASE@example.com')));

        self::assertSame(AddOrganizerResult::OrganizerCreated, $outcome);
        self::assertSame(1, $this->organizerRepository->countAll());
    }

    public function testItIsIdempotentWhenTheAccountIsAlreadyAnOrganizer(): void
    {
        $account = new Account(new AccountId(), new Email('organizer@example.com'));
        $this->accountRepository->add($account);
        ($this->handler)(new AddOrganizer(new Email('organizer@example.com')));

        $outcome = ($this->handler)(new AddOrganizer(new Email('organizer@example.com')));

        self::assertSame(AddOrganizerResult::AlreadyOrganizer, $outcome);
        self::assertSame(1, $this->organizerRepository->countAll());
    }
}
