<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerEventBus implements EventBusInterface
{
    public function __construct(
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    #[\Override]
    public function dispatch(DomainEvent $event): void
    {
        // Retient l'événement jusqu'à la validation de la transaction de la commande en cours.
        $this->eventBus->dispatch(Envelope::wrap($event, [new DispatchAfterCurrentBusStamp()]));
    }
}
