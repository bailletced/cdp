<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Shared\Application\Port\InscriptionDirectory;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Doctrine implementation of InscriptionDirectory (F10-T04): an inscription
 * is active as long as its row exists.
 */
final readonly class DoctrineInscriptionDirectory implements InscriptionDirectory
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[\Override]
    public function filterActive(array $inscriptionIds): array
    {
        $ids = array_values(array_filter($inscriptionIds, static fn (string $id): bool => Uuid::isValid($id)));

        if ([] === $ids) {
            return [];
        }

        /** @var list<string> $existing */
        $existing = $this->connection->fetchFirstColumn(
            'SELECT id FROM inscription WHERE id IN (:ids)',
            ['ids' => $ids],
            ['ids' => ArrayParameterType::STRING],
        );

        return array_values(array_intersect($ids, $existing));
    }
}
