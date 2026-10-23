<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Entity;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use PHPUnit\Framework\TestCase;

final class InscriptionTest extends TestCase
{
    public function testItIsCreatedWithSelfOriginByDefault(): void
    {
        $editionId = new EditionId();
        $slotId = new SlotId();
        $priantId = new PriantId();

        $inscription = new Inscription($editionId, $slotId, $priantId);

        self::assertInstanceOf(InscriptionId::class, $inscription->getId());
        self::assertTrue($editionId->equals($inscription->getEditionId()));
        self::assertTrue($slotId->equals($inscription->getSlotId()));
        self::assertTrue($priantId->equals($inscription->getPriantId()));
        self::assertSame(Inscription::ORIGIN_SELF, $inscription->getOrigin());
        self::assertSame(1, $inscription->getVersion());
    }

    public function testItAcceptsTheOrganizerOrigin(): void
    {
        $inscription = new Inscription(new EditionId(), new SlotId(), new PriantId(), Inscription::ORIGIN_ORGANIZER);

        self::assertSame(Inscription::ORIGIN_ORGANIZER, $inscription->getOrigin());
    }

    public function testItRejectsAnUnknownOrigin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Inscription(new EditionId(), new SlotId(), new PriantId(), 'unknown');
    }

    public function testItGeneratesDistinctIds(): void
    {
        $inscription1 = new Inscription(new EditionId(), new SlotId(), new PriantId());
        $inscription2 = new Inscription(new EditionId(), new SlotId(), new PriantId());

        self::assertFalse($inscription1->getId()->equals($inscription2->getId()));
    }
}
