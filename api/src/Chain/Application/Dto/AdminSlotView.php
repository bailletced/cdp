<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * One slot of the admin edition detail, with the text it carries (F07-T04).
 * textAlreadyUsed flags a text attributed to another slot of the same
 * edition too (« Texte déjà utilisé », US-07.1).
 */
final readonly class AdminSlotView
{
    public function __construct(
        public string $id,
        public int $rank,
        public string $startsAtUtc,
        public string $endsAtUtc,
        public string $label,
        public string $partOfDay,
        public ?string $textId,
        public ?string $textTitle,
        public ?string $textSource,
        public bool $textAlreadyUsed,
    ) {
    }
}
