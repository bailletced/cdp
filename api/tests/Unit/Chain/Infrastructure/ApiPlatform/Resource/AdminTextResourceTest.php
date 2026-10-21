<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\Resource;

use App\Chain\Infrastructure\ApiPlatform\Resource\AdminTextResource;
use PHPUnit\Framework\TestCase;

final class AdminTextResourceTest extends TestCase
{
    public function testItCanBeInstantiated(): void
    {
        $text = new AdminTextResource(
            id: '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
            title: 'Oraison de Thérèse',
            source: 'Thérèse de Lisieux',
            body: '<p>Corps du texte.</p>',
            hints: 'Méditer le matin.',
            attributionCount: 2,
        );

        self::assertSame('9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', $text->id);
        self::assertSame('Oraison de Thérèse', $text->title);
        self::assertSame('Thérèse de Lisieux', $text->source);
        self::assertSame('<p>Corps du texte.</p>', $text->body);
        self::assertSame('Méditer le matin.', $text->hints);
        self::assertSame(2, $text->attributionCount);
    }
}
