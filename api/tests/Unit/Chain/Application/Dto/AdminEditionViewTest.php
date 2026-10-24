<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application\Dto;

use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Dto\AdminSlotView;
use PHPUnit\Framework\TestCase;

final class AdminEditionViewTest extends TestCase
{
    public function testItHoldsEditionData(): void
    {
        $slots = [
            new AdminSlotView('slot1', 1, '2026-11-28T19:00:00+00:00', '2026-11-28T19:30:00+00:00', '20h00–20h30', 'evening', null, null, null, false),
        ];

        $view = new AdminEditionView(
            id: 'abc-123',
            title: '24 heures de prière',
            presentation: 'Une chaîne.',
            startsAtUtc: '2026-11-28T19:00:00+00:00',
            opensAtUtc: '2026-11-13T19:00:00+00:00',
            lifecycle: 'draft',
            status: 'draft',
            scheduledAtUtc: null,
            version: 1,
            slots: $slots,
            slotsWithoutText: 48,
            duplicatedTextIds: [],
        );

        self::assertSame('abc-123', $view->id);
        self::assertSame('24 heures de prière', $view->title);
        self::assertSame('draft', $view->status);
        self::assertCount(1, $view->slots);
    }
}
