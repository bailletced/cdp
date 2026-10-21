<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

final readonly class AdminEditionView
{
    /**
     * @param AdminSlotView[] $slots
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $presentation,
        public string $startsAtUtc,
        public string $opensAtUtc,
        public string $lifecycle,
        public string $status,
        public ?string $scheduledAtUtc,
        public int $version,
        public array $slots,
    ) {
    }
}
