<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\EventSubscriber;

use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute l'en-tête X-Server-Time sur chaque réponse.
 * L'heure est obtenue via l'horloge injectée (PSR-20 ClockInterface).
 */
final class ServerTimeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['addServerTimeHeader', 254], // Avant ExceptionSubscriber (255)
        ];
    }

    public function addServerTimeHeader(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $dateTime = $this->clock->now();
        
        $response->headers->set('X-Server-Time', $dateTime->format('U'));
    }
}
