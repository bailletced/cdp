<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Security;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Security\SecurityUser;
use App\Access\Infrastructure\Security\SymfonyCurrentViewer;
use App\Shared\Domain\Security\Viewer;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\Uuid;

/**
 * F02-T12: Viewer VO + CurrentViewer port + SymfonyCurrentViewer (session only).
 * The Viewer is built from the security token: visitor, verified priant, organizer.
 */
final class SymfonyCurrentViewerTest extends TestCase
{
    private TokenStorageInterface $tokenStorage;

    private AccountRepositoryInterface $accountRepository;

    private SymfonyCurrentViewer $currentViewer;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createStub(TokenStorageInterface::class);
        $this->accountRepository = $this->createStub(AccountRepositoryInterface::class);
        $this->currentViewer = new SymfonyCurrentViewer($this->tokenStorage, $this->accountRepository);
    }

    public function testVisitorProducesVisitorViewer(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);

        $viewer = $this->currentViewer->current();

        $this->assertInstanceOf(Viewer::class, $viewer);
        $this->assertNull($viewer->priantId);
        $this->assertFalse($viewer->verified);
        $this->assertSame([], $viewer->coveredInscriptionIds);
        $this->assertFalse($viewer->isOrganizer);
        $this->assertTrue($viewer->isVisitor());
    }

    public function testVerifiedPriantProducesVerifiedViewer(): void
    {
        $uuid = Uuid::v7();
        $priantId = Uuid::v7()->toRfc4122();
        $account = new Account(new AccountId($uuid), new Email('priant@example.com'), 1, $priantId);

        $this->tokenStorage->method('getToken')->willReturn(
            new UsernamePasswordToken(new SecurityUser($uuid, 'priant@example.com', ''), 'main')
        );
        $this->accountRepository->method('findById')->willReturn($account);

        $viewer = $this->currentViewer->current();

        $this->assertSame($priantId, $viewer->priantId);
        $this->assertTrue($viewer->verified);
        $this->assertSame([], $viewer->coveredInscriptionIds);
        $this->assertFalse($viewer->isOrganizer);
        $this->assertFalse($viewer->isVisitor());
    }

    public function testOrganizerProducesOrganizerViewer(): void
    {
        $uuid = Uuid::v7();
        $account = new Account(new AccountId($uuid), new Email('anne@example.com'));

        $this->tokenStorage->method('getToken')->willReturn(
            new UsernamePasswordToken(new SecurityUser($uuid, 'anne@example.com', '', true), 'main')
        );
        $this->accountRepository->method('findById')->willReturn($account);

        $viewer = $this->currentViewer->current();

        $this->assertNull($viewer->priantId);
        $this->assertTrue($viewer->verified);
        $this->assertSame([], $viewer->coveredInscriptionIds);
        $this->assertTrue($viewer->isOrganizer);
        $this->assertFalse($viewer->isVisitor());
    }

    public function testOrganizerWithPriantIdIsAlsoPriant(): void
    {
        $uuid = Uuid::v7();
        $priantId = Uuid::v7()->toRfc4122();
        $account = new Account(new AccountId($uuid), new Email('anne@example.com'), 1, $priantId);

        $this->tokenStorage->method('getToken')->willReturn(
            new UsernamePasswordToken(new SecurityUser($uuid, 'anne@example.com', '', true), 'main')
        );
        $this->accountRepository->method('findById')->willReturn($account);

        $viewer = $this->currentViewer->current();

        $this->assertSame($priantId, $viewer->priantId);
        $this->assertTrue($viewer->verified);
        $this->assertTrue($viewer->isOrganizer);
        $this->assertFalse($viewer->isVisitor());
    }
}
