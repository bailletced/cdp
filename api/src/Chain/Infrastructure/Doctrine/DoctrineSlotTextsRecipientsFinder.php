<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\SlotTextsRecipient;
use App\Chain\Application\Query\SlotTextsRecipientsFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\DBAL\Connection;

/**
 * Doctrine implementation of SlotTextsRecipientsFinder (F24): one SQL query
 * lists the distinct priants of the edition who can be written to, that is
 * every priant with an e-mail address, neither unsubscribed nor erased.
 */
final readonly class DoctrineSlotTextsRecipientsFinder implements SlotTextsRecipientsFinder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[\Override]
    public function findByEdition(EditionId $editionId): array
    {
        /** @var list<array{id: string, display_name: string, email: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT p.id, p.display_name, p.email
             FROM priant p
             JOIN inscription i ON i.priant_id = p.id
             WHERE i.edition_id = :editionId
               AND p.email IS NOT NULL
               AND p.unsubscribed_at IS NULL
               AND p.erased_at IS NULL
             GROUP BY p.id, p.display_name, p.email
             ORDER BY p.id ASC',
            ['editionId' => (string) $editionId],
        );

        return array_map(
            static fn (array $row): SlotTextsRecipient => new SlotTextsRecipient(
                new PriantId($row['id']),
                $row['display_name'],
                new Email($row['email']),
            ),
            $rows,
        );
    }
}
