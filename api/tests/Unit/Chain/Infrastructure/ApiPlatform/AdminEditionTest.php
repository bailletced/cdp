<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform;

use App\Chain\Infrastructure\ApiPlatform\AdminEdition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AdminEditionTest extends TestCase
{
    public function testItCanBeInstantiated(): void
    {
        $edition = new AdminEdition(
            id: 'abc',
            title: '24 heures',
            presentation: '',
            startsAt: '2026-11-28T20:00:00+01:00',
            opensAt: '2026-11-13T20:00:00+01:00',
            resolvedStartsAtUtc: '2026-11-28T19:00:00+00:00',
            resolvedStartsAtLabel: '20h00–20h30',
            lifecycle: 'draft',
            status: 'draft',
            scheduledAt: null,
            version: 1,
            slots: [],
        );

        self::assertSame('abc', $edition->id);
        self::assertSame('24 heures', $edition->title);
        self::assertSame('draft', $edition->status);
        self::assertSame(1, $edition->version);
    }

    public function testPayloadConstraintsRejectATooShortTitleOnTheTitleProperty(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $violations = $validator->validate(new AdminEdition(title: 'ab'));

        self::assertCount(1, $violations);
        self::assertSame('title', $violations[0]->getPropertyPath());
        self::assertSame('Le titre doit comporter entre 3 et 120 caractères.', $violations[0]->getMessage());
    }

    public function testPayloadConstraintsRejectATooLongPresentationOnThePresentationProperty(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $violations = $validator->validate(new AdminEdition(presentation: str_repeat('a', 2001)));

        self::assertCount(1, $violations);
        self::assertSame('presentation', $violations[0]->getPropertyPath());
        self::assertSame('La présentation ne doit pas dépasser 2000 caractères.', $violations[0]->getMessage());
    }

    public function testPayloadConstraintsAcceptAValidEdition(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $violations = $validator->validate(new AdminEdition(
            title: '24 heures de prière',
            presentation: 'Une chaîne.',
            startsAt: '2026-11-28T20:00:00+01:00',
            opensAt: '2026-11-13T20:00:00+01:00',
        ));

        self::assertCount(0, $violations);
    }
}
