<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Application\Dto\PriantProfile;
use App\Shared\Application\Port\PriantProfileReader;

/**
 * Doctrine implementation of PriantProfileReader (F09-T14).
 *
 * Reads the priant aggregate through its repository and exposes only the
 * two fields the "me" endpoint needs: the display name and whether the
 * consent was already given (RG-IN-7). An invalid priant id (corrupt link
 * on an account) yields null rather than an error.
 */
final readonly class DoctrinePriantProfileReader implements PriantProfileReader
{
    public function __construct(
        private PriantRepositoryInterface $priantRepository,
    ) {
    }

    public function findProfileByPriantId(string $priantId): ?PriantProfile
    {
        try {
            $priant = $this->priantRepository->findById(new PriantId($priantId));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (null === $priant) {
            return null;
        }

        return new PriantProfile(
            displayName: $priant->getDisplayName()->value,
            consentGiven: null !== $priant->getConsentAt(),
        );
    }
}
