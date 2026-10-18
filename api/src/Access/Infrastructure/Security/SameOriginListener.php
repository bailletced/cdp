<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CSRF same-origin protection for unsafe HTTP methods (F02-T10).
 *
 * Browsers always send an Origin header on cross-origin POST/PUT/DELETE/PATCH
 * requests and a Sec-Fetch-Site header on every fetch. A request carrying a
 * foreign Origin or "cross-site" Sec-Fetch-Site is rejected with 403.
 * Non-browser clients (no Origin, no Sec-Fetch-Site) are allowed through.
 */
final class SameOriginListener implements EventSubscriberInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public static function getSubscribedEvents(): array
    {
        // Runs before the firewall (priority 8) so rejected requests never reach the authenticators
        return [KernelEvents::REQUEST => ['onKernelRequest', 16]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (\in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return;
        }

        if ('cross-site' === $request->headers->get('Sec-Fetch-Site')) {
            $event->setResponse($this->forbidden());

            return;
        }

        $origin = $request->headers->get('Origin');
        if (null !== $origin && !$this->isSameOrigin($request, $origin)) {
            $event->setResponse($this->forbidden());
        }
    }

    private function isSameOrigin(Request $request, string $origin): bool
    {
        $parts = parse_url($origin);
        if (false === $parts || !isset($parts['host'])) {
            return false;
        }

        if (0 !== strcasecmp($parts['host'], $request->getHost())) {
            return false;
        }

        // Compare effective ports (scheme default when absent from the Origin)
        $scheme = $parts['scheme'] ?? 'http';
        $originPort = $parts['port'] ?? ('https' === $scheme ? 443 : 80);

        return $originPort === $request->getPort();
    }

    private function forbidden(): Response
    {
        return new Response('Forbidden', Response::HTTP_FORBIDDEN);
    }
}
