<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

final readonly class AdminSlotView
{
    public function __construct(
        public int $rank,
        public string $startsAtUtc,
        public string $endsAtUtc,
        public string $label,
        public string $partOfDay,
        public ?string $textId,
    ) {
    }
}
