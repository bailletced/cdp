<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * One slot of « Mon espace » (F14-T01): its Paris day and label, the title
 * of its text, its temporal state (past, current, future) and the actions
 * still possible — `cancel` until the slot starts (RG-EP-1). The pointage
 * and the relay message join them later (F19, F20).
 */
final readonly class MyInscriptionView
{
    public const string ACTION_CANCEL = 'cancel';

    /**
     * @param 'past'|'current'|'future' $state
     * @param list<string>              $actions
     */
    public function __construct(
        public string $inscriptionId,
        public string $slotId,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public string $label,
        public string $dayLabel,
        public ?string $textTitle,
        public ?string $textSource,
        public string $state,
        public array $actions,
    ) {
    }
}
