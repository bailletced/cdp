<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application\Dto;

use App\Chain\Application\Dto\AdminSlotView;
use PHPUnit\Framework\TestCase;

final class AdminSlotViewTest extends TestCase
{
    public function testItHoldsSlotData(): void
    {
        $view = new AdminSlotView(
            id: 'slot1',
            rank: 1,
            startsAtUtc: '2026-11-28T19:00:00+00:00',
            endsAtUtc: '2026-11-28T19:30:00+00:00',
            label: '20h00–20h30',
            partOfDay: 'evening',
            textId: null,
            textTitle: null,
            textSource: null,
            textAlreadyUsed: false,
        );

        self::assertSame(1, $view->rank);
        self::assertSame('20h00–20h30', $view->label);
        self::assertSame('evening', $view->partOfDay);
        self::assertNull($view->textId);
    }
}
