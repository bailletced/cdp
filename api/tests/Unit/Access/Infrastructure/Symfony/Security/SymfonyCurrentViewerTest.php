<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Symfony\Security;

use App\Access\Application\PresentDevicePass;
use App\Access\Application\PresentDevicePassHandler;
use App\Access\Domain\Entity\Account;
use App\Access\Domain\Entity\DevicePass;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\InMemory\Repository\InMemoryDevicePassRepository;
use App\Access\Infrastructure\Symfony\Security\SecurityUser;
use App\Access\Infrastructure\Symfony\Security\SymfonyCurrentViewer;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\Security\Viewer;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\Shared\Application\Port\Fixture\FakeInscriptionDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\Uuid;

/**
 * F02-T12: Viewer VO + CurrentViewer port + SymfonyCurrentViewer.
 * The Viewer is built from the security token (visitor, verified priant,
 * organizer) and, since F10-T04, from the device pass cookie.
 */
final class SymfonyCurrentViewerTest extends TestCase
{
    private const string NOW = '2026-11-28 21:15:00';

    private TokenStorageInterface $tokenStorage;

    private AccountRepositoryInterface $accountRepository;

    private RequestStack $requestStack;

    private InMemoryDevicePassRepository $passes;

    private SymfonyCurrentViewer $currentViewer;

    private string $active;

    private string $cancelled;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createStub(TokenStorageInterface::class);
        $this->accountRepository = $this->createStub(AccountRepositoryInterface::class);
        $this->requestStack = new RequestStack([Request::create('/api/editions/current')]);
        $this->passes = new InMemoryDevicePassRepository();
        $this->active = Uuid::v7()->toRfc4122();
        $this->cancelled = Uuid::v7()->toRfc4122();

        $presentHandler = new PresentDevicePassHandler($this->passes, new MockClock(self::NOW, 'UTC'));
        $commandBus = new class($presentHandler) implements CommandBusInterface {
            public function __construct(private readonly PresentDevicePassHandler $handler)
            {
            }

            #[\Override]
            public function dispatch(CommandInterface $command): mixed
            {
                \assert($command instanceof PresentDevicePass);

                return ($this->handler)($command);
            }
        };

        $this->currentViewer = new SymfonyCurrentViewer(
            $this->tokenStorage,
            $this->accountRepository,
            $this->requestStack,
            $commandBus,
            new FakeInscriptionDirectory([$this->active]),
        );
    }

    public function testAPassBearerIsCoveredForItsActiveInscriptionsOnly(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->givenADevicePass(expiresAt: '2026-12-06 19:00:00');

        $viewer = $this->currentViewer->current();

        // A cancelled inscription gives nothing anymore (rights recomputed on each request)
        $this->assertSame([$this->active], $viewer->coveredInscriptionIds);
        $this->assertNull($viewer->priantId);
        $this->assertFalse($viewer->verified);
        $this->assertFalse($viewer->isOrganizer);
        $this->assertFalse($viewer->isVisitor(), 'A pass bearer has the read-only « inscrit » view.');
        $this->assertEquals(new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC')), $this->passes->findByToken('device-token')?->getLastSeenAt());
    }

    public function testAnExpiredPassGivesNoRightAnymore(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->givenADevicePass(expiresAt: self::NOW);

        $viewer = $this->currentViewer->current();

        $this->assertSame([], $viewer->coveredInscriptionIds);
        $this->assertTrue($viewer->isVisitor());
    }

    public function testAVerifiedPriantKeepsThePassOfItsDevice(): void
    {
        $uuid = Uuid::v7();
        $priantId = Uuid::v7()->toRfc4122();
        $this->tokenStorage->method('getToken')->willReturn(
            new UsernamePasswordToken(new SecurityUser($uuid, 'priant@bailletced.org', ''), 'main')
        );
        $this->accountRepository->method('findById')->willReturn(new Account(new AccountId($uuid), new Email('priant@bailletced.org'), 1, $priantId));
        $this->givenADevicePass(expiresAt: '2026-12-06 19:00:00');

        $viewer = $this->currentViewer->current();

        $this->assertSame($priantId, $viewer->priantId);
        $this->assertTrue($viewer->verified);
        $this->assertSame([$this->active], $viewer->coveredInscriptionIds);
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
        $account = new Account(new AccountId($uuid), new Email('priant@bailletced.org'), 1, $priantId);

        $this->tokenStorage->method('getToken')->willReturn(
            new UsernamePasswordToken(new SecurityUser($uuid, 'priant@bailletced.org', ''), 'main')
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
        $account = new Account(new AccountId($uuid), new Email('anne@bailletced.org'));

        $this->tokenStorage->method('getToken')->willReturn(
            new UsernamePasswordToken(new SecurityUser($uuid, 'anne@bailletced.org', '', true), 'main')
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
        $account = new Account(new AccountId($uuid), new Email('anne@bailletced.org'), 1, $priantId);

        $this->tokenStorage->method('getToken')->willReturn(
            new UsernamePasswordToken(new SecurityUser($uuid, 'anne@bailletced.org', '', true), 'main')
        );
        $this->accountRepository->method('findById')->willReturn($account);

        $viewer = $this->currentViewer->current();

        $this->assertSame($priantId, $viewer->priantId);
        $this->assertTrue($viewer->verified);
        $this->assertTrue($viewer->isOrganizer);
        $this->assertFalse($viewer->isVisitor());
    }

    private function givenADevicePass(string $expiresAt): void
    {
        $this->passes->add(new DevicePass(
            'device-token',
            [$this->active, $this->cancelled],
            new \DateTimeImmutable($expiresAt, new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-20 10:00:00', new \DateTimeZone('UTC')),
        ));
        $this->requestStack->getCurrentRequest()?->cookies->set('cdp_pass', 'device-token');
    }
}
