<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Symfony\Security;

use App\Access\Application\GrantDevicePass;
use App\Access\Application\GrantDevicePassHandler;
use App\Access\Infrastructure\InMemory\Repository\InMemoryDevicePassRepository;
use App\Access\Infrastructure\Symfony\Security\DevicePassCookie;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Uid\Uuid;

final class DevicePassCookieTest extends TestCase
{
    private InMemoryDevicePassRepository $passes;

    private CommandBusInterface $commandBus;

    private \DateTimeImmutable $editionEndsAt;

    protected function setUp(): void
    {
        $this->passes = new InMemoryDevicePassRepository();
        $handler = new GrantDevicePassHandler($this->passes, new MockClock('2026-11-20 10:00:00', 'UTC'));
        $this->commandBus = new class($handler) implements CommandBusInterface {
            public function __construct(private readonly GrantDevicePassHandler $handler)
            {
            }

            #[\Override]
            public function dispatch(CommandInterface $command): mixed
            {
                \assert($command instanceof GrantDevicePass);

                return ($this->handler)($command);
            }
        };
        $this->editionEndsAt = new \DateTimeImmutable('2026-11-29 19:00:00', new \DateTimeZone('UTC'));
    }

    public function testTheResponseCarriesAProtectedPassCookieUntilAWeekAfterTheEdition(): void
    {
        $inscriptionId = Uuid::v7()->toRfc4122();

        $cookie = $this->grantAndRespond(Request::create('/api/inscriptions', 'POST'), [$inscriptionId]);

        self::assertNotNull($cookie);
        self::assertSame('cdp_pass', $cookie->getName());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertSame('/', $cookie->getPath());
        self::assertSame(new \DateTimeImmutable('2026-12-06 19:00:00', new \DateTimeZone('UTC'))->getTimestamp(), $cookie->getExpiresTime());
        // 256 bits of randomness, hex encoded
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $cookie->getValue());
        self::assertSame([$inscriptionId], $this->passes->findByToken((string) $cookie->getValue())?->getInscriptionIds());
    }

    public function testASecondInscriptionFromTheSameDeviceEnrichesItsPass(): void
    {
        $first = Uuid::v7()->toRfc4122();
        $second = Uuid::v7()->toRfc4122();
        $token = (string) $this->grantAndRespond(Request::create('/api/inscriptions', 'POST'), [$first])?->getValue();

        $cookie = $this->grantAndRespond(Request::create('/api/inscriptions', 'POST', cookies: ['cdp_pass' => $token]), [$second]);

        self::assertSame($token, $cookie?->getValue());
        self::assertSame([$first, $second], $this->passes->findByToken($token)?->getInscriptionIds());
    }

    public function testNoGrantNoCookie(): void
    {
        $request = Request::create('/api/inscriptions', 'POST');
        $response = new Response();

        $this->devicePassCookie(new RequestStack([$request]))->onKernelResponse(new ResponseEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertSame([], $response->headers->getCookies());
    }

    /**
     * @param list<string> $inscriptionIds
     */
    private function grantAndRespond(Request $request, array $inscriptionIds): ?Cookie
    {
        $devicePassCookie = $this->devicePassCookie(new RequestStack([$request]));
        $response = new Response();

        $devicePassCookie->grant($inscriptionIds, $this->editionEndsAt);
        $devicePassCookie->onKernelResponse(new ResponseEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        return $response->headers->getCookies()[0] ?? null;
    }

    private function devicePassCookie(RequestStack $requestStack): DevicePassCookie
    {
        return new DevicePassCookie($requestStack, $this->commandBus);
    }

    private function kernel(): HttpKernelInterface
    {
        return $this->createStub(HttpKernelInterface::class);
    }
}
