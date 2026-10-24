<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Application\Dto\InscriptionConfirmation;
use App\Chain\Application\Dto\InscriptionDigestSlot;
use App\Chain\Application\Port\CalendarFile;
use App\Chain\Application\Port\Notifier;
use App\Chain\Application\Query\InscriptionDigestFinder;
use App\Chain\Domain\Event\InscriptionsRegistered;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Application\Event\AsEventListener;
use App\Shared\Application\Port\PersonalLinkIssuer;

/**
 * Sends the confirmation email of an inscription batch (F11-T05, RG-IN-1):
 * one email for the slots taken at once, with their texts, the personal link
 * and the agenda file. A priant without email gets nothing (RG-NO-3).
 *
 * Runs in the worker after the command transaction commits. The account of
 * a new priant is linked by an earlier event (PriantRegistered); if it is
 * not there yet, the link cannot be issued and the event is retried.
 */
#[AsEventListener]
final readonly class SendConfirmationOnInscriptionsRegistered
{
    public function __construct(
        private PriantRepositoryInterface $priantRepository,
        private InscriptionDigestFinder $inscriptionDigestFinder,
        private PersonalLinkIssuer $personalLinkIssuer,
        private CalendarFile $calendarFile,
        private Notifier $notifier,
    ) {
    }

    public function __invoke(InscriptionsRegistered $event): void
    {
        $priant = $this->priantRepository->findById($event->priantId);
        $email = $priant?->getEmail();

        if (null === $priant || null === $email) {
            return;
        }

        $digest = $this->inscriptionDigestFinder->findByPriantAndEdition($event->priantId, $event->editionId);
        $batch = array_map(static fn (SlotId $slotId): string => (string) $slotId, $event->slotIds);
        $slots = array_values(array_filter(
            $digest->slots ?? [],
            static fn (InscriptionDigestSlot $slot): bool => \in_array($slot->slotId, $batch, true),
        ));

        if (null === $digest || [] === $slots) {
            return;
        }

        $this->notifier->confirmInscriptions(new InscriptionConfirmation(
            $email,
            $priant->getDisplayName()->value,
            $digest->editionTitle,
            $slots,
            $this->personalLinkIssuer->issueForEmail($email, $digest->editionEndsAt)->url,
            $this->calendarFile->forSlots($digest->editionTitle, $slots),
        ));
    }
}
