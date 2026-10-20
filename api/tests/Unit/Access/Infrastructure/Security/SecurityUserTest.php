<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Security;

use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SecurityUserTest extends TestCase
{
    public function testGetUserIdentifier(): void
    {
        $uuid = Uuid::v7();
        $user = new SecurityUser($uuid, 'test@bailletced.org', 'password', false);

        $this->assertEquals($uuid->toRfc4122(), $user->getUserIdentifier());
    }

    public function testGetEmail(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);

        $this->assertEquals('test@bailletced.org', $user->getEmail());
    }

    public function testGetRolesNotOrganizer(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_PRIANT', $roles);
        $this->assertNotContains('ROLE_ORGANIZER', $roles);
    }

    public function testGetRolesIsOrganizer(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', true);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_PRIANT', $roles);
        $this->assertContains('ROLE_ORGANIZER', $roles);
    }

    public function testGetPassword(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password123', false);

        $this->assertEquals('password123', $user->getPassword());
    }

    public function testGetSalt(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);

        $this->assertNull($user->getSalt());
    }

    public function testEraseCredentials(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);

        // This method should not throw any errors
        $user->eraseCredentials();

        $this->assertTrue(true); // If we get here, the method worked
    }

    public function testIsOrganizer(): void
    {
        $userNotOrganizer = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);
        $userOrganizer = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', true);

        $this->assertFalse($userNotOrganizer->isOrganizer());
        $this->assertTrue($userOrganizer->isOrganizer());
    }

    public function testGetId(): void
    {
        $uuid = Uuid::v7();
        $user = new SecurityUser($uuid, 'test@bailletced.org', 'password', false);

        $this->assertEquals($uuid, $user->getId());
    }

    public function testIsEqualTo(): void
    {
        $uuid = Uuid::v7();
        $user1 = new SecurityUser($uuid, 'test@bailletced.org', 'password', false);
        $user2 = new SecurityUser($uuid, 'test@bailletced.org', 'password', false);
        $user3 = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);

        $this->assertTrue($user1->isEqualTo($user2));
        $this->assertFalse($user1->isEqualTo($user3));
    }

    public function testIsEqualToNotSecurityUser(): void
    {
        $uuid = Uuid::v7();
        $user = new SecurityUser($uuid, 'test@bailletced.org', 'password', false);
        $otherUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);

        $this->assertFalse($user->isEqualTo($otherUser));
    }

    public function testUpdateIsOrganizerFromFalseToTrue(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);

        $this->assertFalse($user->isOrganizer());
        $this->assertNotContains('ROLE_ORGANIZER', $user->getRoles());

        $user->updateIsOrganizer(true);

        $this->assertTrue($user->isOrganizer());
        $this->assertContains('ROLE_ORGANIZER', $user->getRoles());
    }

    public function testUpdateIsOrganizerFromTrueToFalse(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', true);

        $this->assertTrue($user->isOrganizer());
        $this->assertContains('ROLE_ORGANIZER', $user->getRoles());

        $user->updateIsOrganizer(false);

        $this->assertFalse($user->isOrganizer());
        $this->assertNotContains('ROLE_ORGANIZER', $user->getRoles());
    }

    public function testUpdateIsOrganizerNoChange(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'test@bailletced.org', 'password', false);
        $originalRoles = $user->getRoles();

        $user->updateIsOrganizer(false);

        $this->assertEquals($originalRoles, $user->getRoles());
    }

    public function testCreateFromAccount(): void
    {
        $accountId = new AccountId();
        $email = new Email('test@bailletced.org');

        $user = new SecurityUser(
            $accountId->value,
            $email->value,
            'password',
            false
        );

        $this->assertEquals($accountId->value->toRfc4122(), $user->getUserIdentifier());
        $this->assertEquals($email->value, $user->getEmail());
    }
}
