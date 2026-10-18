<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security;

/**
 * The viewer of the current request (F02-T12).
 *
 * - visitor: not authenticated
 * - verified priant: authenticated through a personal link, acts as priantId
 * - organizer: authenticated account with the organizer role (may also be a priant)
 */
final readonly class Viewer
{
    /**
     * @param string[] $coveredInscriptionIds Inscriptions covered by a device pass (F10); empty until then
     */
    public function __construct(
        public ?string $priantId,
        public bool $verified,
        public array $coveredInscriptionIds,
        public bool $isOrganizer,
    ) {
    }

    public static function visitor(): self
    {
        return new self(null, false, [], false);
    }

    public function isVisitor(): bool
    {
        return !$this->verified && !$this->isOrganizer;
    }
}
