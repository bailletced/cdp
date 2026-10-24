<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Symfony\Security;

use App\Access\Application\GrantDevicePass;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Port\DevicePassIssuer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Device pass carried by the cdp_pass cookie (F10-T02, specs-techniques §6.4).
 *
 * The token is 256 random bits; only its hash is stored. The cookie is
 * HttpOnly, Secure and SameSite=Lax, and lives as long as the pass. The
 * cookie to set is kept on the request until the response is sent, so the
 * service stays stateless between requests.
 */
final readonly class DevicePassCookie implements DevicePassIssuer, EventSubscriberInterface
{
    public const string NAME = 'cdp_pass';

    private const string PENDING_COOKIE = '_cdp_pass_cookie';

    public function __construct(
        private RequestStack $requestStack,
        private CommandBusInterface $commandBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    #[\Override]
    public function grant(array $inscriptionIds, \DateTimeImmutable $editionEndsAt): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $expiresAt = $editionEndsAt->modify('+7 days');
        $presented = $request->cookies->get(self::NAME);

        $token = $this->commandBus->dispatch(new GrantDevicePass(
            \is_string($presented) && '' !== $presented ? $presented : null,
            bin2hex(random_bytes(32)),
            $inscriptionIds,
            $expiresAt,
        ));
        \assert(\is_string($token));

        $request->attributes->set(self::PENDING_COOKIE, Cookie::create(self::NAME, $token, $expiresAt, '/', null, true, true, false, Cookie::SAMESITE_LAX));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $cookie = $event->getRequest()->attributes->get(self::PENDING_COOKIE);

        if ($cookie instanceof Cookie) {
            $event->getResponse()->headers->setCookie($cookie);
        }
    }
}
