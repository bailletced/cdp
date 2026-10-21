<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Symfony\Security;

use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Garde d'accès sur /api/admin/* — réservé aux organisateurs (F04-T02).
 *
 * Le 401 visiteur est géré par la règle `access_control` de security.yaml
 * (^/api/admin, IS_AUTHENTICATED_REMEMBERED) et l'entry point du firewall.
 * Ce listener applique la règle métier : un utilisateur authentifié qui
 * n'est pas organisateur reçoit un 403 (y compris via remember-me).
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
        // Priorité 5 : après le firewall (8), avant le ReadListener d'API Platform (4)
        // qui évalue l'attribut `security` des opérations — un priant remember-me
        // y recevrait un 401 (token pas « fully authenticated ») au lieu du 403.
        return [KernelEvents::REQUEST => ['onKernelRequest', 5]];
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

        // A visitor never reaches this listener: the access_control rule
        // (^/api/admin, IS_AUTHENTICATED_REMEMBERED) answers 401 before.
        if (!$user instanceof SecurityUser) {
            return;
        }

        if (!$user->isOrganizer()) {
            throw new AccessDeniedException();
        }
    }
}
