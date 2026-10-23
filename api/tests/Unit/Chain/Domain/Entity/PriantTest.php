<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Entity;

use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class PriantTest extends TestCase
{
    public function testItIsCreatedWithAnEmailAndADisplayName(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        self::assertInstanceOf(PriantId::class, $priant->getId());
        self::assertSame('marie@bailletced.org', $priant->getEmail()?->value);
        self::assertSame('Marie Dupont', $priant->getDisplayName()->value);
        self::assertNull($priant->getConsentAt());
        self::assertNull($priant->getUnsubscribedAt());
        self::assertNull($priant->getErasedAt());
    }

    public function testItCanBeCreatedWithoutEmail(): void
    {
        $priant = new Priant(null, new DisplayName('Famille sans e-mail'));

        self::assertNull($priant->getEmail());
        self::assertSame('Famille sans e-mail', $priant->getDisplayName()->value);
    }

    public function testItNormalizesTheEmailToLowercase(): void
    {
        $priant = new Priant(new Email('Marie@BailletCed.ORG'), new DisplayName('Marie'));

        self::assertSame('marie@bailletced.org', $priant->getEmail()?->value);
    }

    public function testItRenames(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $priant->rename(new DisplayName('Marie Martin'));

        self::assertSame('Marie Martin', $priant->getDisplayName()->value);
    }

    public function testItRecordsTheConsent(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $at = new \DateTimeImmutable('2026-11-01 10:00:00');

        $priant->giveConsent($at);

        self::assertSame($at, $priant->getConsentAt());
    }

    public function testItKeepsTheFirstConsentDate(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $first = new \DateTimeImmutable('2026-11-01 10:00:00');
        $later = new \DateTimeImmutable('2026-12-24 18:00:00');

        $priant->giveConsent($first);
        $priant->giveConsent($later);

        self::assertSame($first, $priant->getConsentAt());
    }
}
