<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\Dto\AdminInscriptionView;
use App\Chain\Application\Query\AdminInscriptionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminInscriptionResource;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Provides AdminInscriptionResource resources for the GET collection
 * operation (F16-T03, US-16.2).
 *
 * The edition filter is required and must be a valid UUID: anything else
 * answers a 422 with one violation on "edition". The slot filter is
 * optional, and an id that is not a valid UUID is ignored — it only narrows
 * the list.
 *
 * @implements ProviderInterface<AdminInscriptionResource>
 */
final readonly class AdminInscriptionProvider implements ProviderInterface
{
    public function __construct(
        private AdminInscriptionViewFinder $finder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator
    {
        $filters = $context['filters'] ?? [];
        \assert(\is_array($filters));

        $edition = $filters['edition'] ?? null;
        if (!\is_string($edition) || '' === $edition) {
            throw self::violation('edition', 'L\'identifiant de l\'édition est requis.');
        }

        try {
            $editionId = new EditionId($edition);
        } catch (\InvalidArgumentException) {
            throw self::violation('edition', 'L\'identifiant de l\'édition n\'est pas valide.');
        }

        $slotId = null;
        $slot = $filters['slot'] ?? null;
        if (\is_string($slot) && '' !== $slot) {
            try {
                $slotId = new SlotId($slot);
            } catch (\InvalidArgumentException) {
                // Not a valid UUID: the filter only narrows the list, ignore it.
            }
        }

        $views = $this->finder->findByEdition($editionId, $slotId);
        $resources = array_map(
            static fn (AdminInscriptionView $view): AdminInscriptionResource => AdminInscriptionResource::fromView($view),
            $views,
        );

        return new ArrayPaginator($resources, 0, \count($resources));
    }

    private static function violation(string $propertyPath, string $message): ValidationException
    {
        return new ValidationException(new ConstraintViolationList([
            new ConstraintViolation($message, null, [], null, $propertyPath, null),
        ]));
    }
}
