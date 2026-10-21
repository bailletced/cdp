<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Symfony\Security;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Symfony\Security\SecurityUser;
use App\Access\Infrastructure\Symfony\Security\UserProvider;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final class UserProviderTest extends TestCase
{
    private UserProvider $provider;

    private AccountRepositoryInterface $accountRepository;

    private OrganizerRepositoryInterface $organizerRepository;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->organizerRepository = $this->createMock(OrganizerRepositoryInterface::class);

        $this->provider = new UserProvider(
            $this->accountRepository,
            $this->organizerRepository,
        );
    }

    public function testLoadUserByIdentifier(): void
    {
        $uuid = Uuid::v7();
        $email = 'test@bailletced.org';

        $account = new Account(
            new AccountId($uuid),
            new Email($email),
            1
        );

        $accountId = new AccountId($uuid);

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($accountId))
            ->willReturn($account);

        $this->organizerRepository
            ->expects($this->once())
            ->method('isOrganizer')
            ->with($uuid)
            ->willReturn(false);

        $user = $this->provider->loadUserByIdentifier($uuid->toRfc4122());

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($uuid->toRfc4122(), $user->getUserIdentifier());
        $this->assertContains('ROLE_PRIANT', $user->getRoles());
        $this->assertNotContains('ROLE_ORGANIZER', $user->getRoles());
    }

    public function testLoadUserByIdentifierWithOrganizer(): void
    {
        $uuid = Uuid::v7();
        $email = 'organizer@bailletced.org';

        $account = new Account(
            new AccountId($uuid),
            new Email($email),
            1
        );

        $accountId = new AccountId($uuid);

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($accountId))
            ->willReturn($account);

        $this->organizerRepository
            ->expects($this->once())
            ->method('isOrganizer')
            ->with($uuid)
            ->willReturn(true);

        $user = $this->provider->loadUserByIdentifier($uuid->toRfc4122());

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertContains('ROLE_PRIANT', $user->getRoles());
        $this->assertContains('ROLE_ORGANIZER', $user->getRoles());
    }

    public function testLoadUserByIdentifierNotFound(): void
    {
        $uuid = Uuid::v7();

        $accountId = new AccountId($uuid);

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($accountId))
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->provider->loadUserByIdentifier($uuid->toRfc4122());
    }

    public function testLoadUserByIdentifierInvalidUuid(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->provider->loadUserByIdentifier('invalid-uuid');
    }

    public function testRefreshUserUpdatesRoles(): void
    {
        $uuid = Uuid::v7();
        $email = 'test@bailletced.org';

        $account = new Account(
            new AccountId($uuid),
            new Email($email),
            1
        );

        $accountId = new AccountId($uuid);

        // Initial load - not organizer
        $this->accountRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with($this->equalTo($accountId))
            ->willReturn($account);

        // First refresh: is organizer
        // Second refresh: is no longer organizer
        $this->organizerRepository
            ->expects($this->exactly(2))
            ->method('isOrganizer')
            ->with($uuid)
            ->willReturnOnConsecutiveCalls(false, true);

        $initialUser = $this->provider->loadUserByIdentifier($uuid->toRfc4122());
        $this->assertNotContains('ROLE_ORGANIZER', $initialUser->getRoles());

        $refreshedUser = $this->provider->refreshUser($initialUser);
        $this->assertContains('ROLE_ORGANIZER', $refreshedUser->getRoles());
    }

    public function testOrganizerRemovedLosesRoleOnRefresh(): void
    {
        // Test critère de fin : un organisateur retiré perd son rôle à la requête suivante
        $uuid = Uuid::v7();
        $email = 'test@bailletced.org';

        $account = new Account(
            new AccountId($uuid),
            new Email($email),
            1
        );

        $accountId = new AccountId($uuid);

        $this->accountRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with($this->equalTo($accountId))
            ->willReturn($account);

        // First refresh: is organizer
        // Second refresh: is no longer organizer
        $this->organizerRepository
            ->expects($this->exactly(2))
            ->method('isOrganizer')
            ->with($uuid)
            ->willReturnOnConsecutiveCalls(true, false);

        $initialUser = $this->provider->loadUserByIdentifier($uuid->toRfc4122());
        $this->assertTrue($initialUser->isOrganizer());
        $this->assertContains('ROLE_ORGANIZER', $initialUser->getRoles());

        // Refresh: organizer was removed
        $refreshedUser = $this->provider->refreshUser($initialUser);
        $this->assertFalse($refreshedUser->isOrganizer());
        $this->assertNotContains('ROLE_ORGANIZER', $refreshedUser->getRoles());
        $this->assertContains('ROLE_PRIANT', $refreshedUser->getRoles());
    }

    public function testRefreshUserNotSupported(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->provider->refreshUser($this->createMock(UserInterface::class));
    }

    public function testSupportsClass(): void
    {
        $this->assertTrue($this->provider->supportsClass(SecurityUser::class));
        $this->assertFalse($this->provider->supportsClass(\stdClass::class));
    }

    public function testLoadUserByUsername(): void
    {
        $uuid = Uuid::v7();
        $email = 'test@bailletced.org';

        $account = new Account(
            new AccountId($uuid),
            new Email($email),
            1
        );

        $accountId = new AccountId($uuid);

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($accountId))
            ->willReturn($account);

        $this->organizerRepository
            ->expects($this->once())
            ->method('isOrganizer')
            ->with($uuid)
            ->willReturn(false);

        $user = $this->provider->loadUserByUsername($uuid->toRfc4122());

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertEquals($email, $user->getEmail());
    }
}
