<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class CreateEditionHandlerTest extends TestCase
{
    public function testItCreatesAnEditionInDraft(): void
    {
        $repository = new InMemoryEditionRepository();
        $handler = new CreateEditionHandler($repository, new ParisCalendar());

        $result = ($handler)(new CreateEdition(
            new EditionTitle('24 heures de prière'),
            new Presentation('Une chaîne de prière.'),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));

        self::assertNotNull($result->editionId);
        $edition = $repository->findById($result->editionId);
        self::assertNotNull($edition);
        self::assertSame('draft', $edition->getLifecycle());
        self::assertCount(48, $edition->getSlots());
        // 2026-11-28 20:00 CET = 19:00 UTC
        self::assertSame('2026-11-28 19:00:00', $edition->getStartsAt()->format('Y-m-d H:i:s'));
    }
}
