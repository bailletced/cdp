<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Garde d'accès sur /api/admin/* — réservé aux organisateurs (F04-T02).
 *
 * Équivalent fonctionnel de `access_control` sur ^/api/admin, mais avec les
 * bons codes HTTP quel que soit le type de token (session ou remember-me) :
 * visiteur (pas de SecurityUser) → 401, priant (non organisateur) → 403.
 *
 * S'exécute après le firewall (priorité 8) pour que le token soit disponible.
 */
final class AdminAccessListener implements EventSubscriberInterface
{
    private const ADMIN_PATH_PREFIX = '/api/admin';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité 0 : après le firewall (8), avant le contrôleur.
        return [KernelEvents::REQUEST => ['onKernelRequest', 0]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!str_starts_with($event->getRequest()->getPathInfo(), self::ADMIN_PATH_PREFIX)) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof SecurityUser) {
            throw new UnauthenticatedException();
        }

        if (!$user->isOrganizer()) {
            throw new AccessDeniedException();
        }
    }
}
