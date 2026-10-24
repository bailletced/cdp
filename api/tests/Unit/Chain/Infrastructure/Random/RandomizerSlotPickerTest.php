<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Random;

use App\Chain\Application\Dto\EmptySlotView;
use App\Chain\Infrastructure\Random\RandomizerSlotPicker;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class RandomizerSlotPickerTest extends TestCase
{
    public function testTheSlotRightAfterTheNearOneComesFirst(): void
    {
        $picked = $this->picker()->pick($this->slots(3, 9, 11, 20), 10, []);

        self::assertSame('slot-11', $picked?->id);
    }

    public function testTheSlotRightBeforeComesNextWhenTheFollowingOneIsTaken(): void
    {
        $picked = $this->picker()->pick($this->slots(3, 9, 20), 10, []);

        self::assertSame('slot-9', $picked?->id);
    }

    public function testWithoutAdjacentEmptySlotTheDrawIsRandomAndDeterministicWithAFixedSeed(): void
    {
        $slots = $this->slots(1, 5, 20, 30, 40);

        $first = $this->picker(42)->pick($slots, 10, []);
        $again = $this->picker(42)->pick($slots, 10, []);

        self::assertNotNull($first);
        self::assertSame($first->id, $again?->id);
        self::assertContains($first->id, ['slot-1', 'slot-5', 'slot-20', 'slot-30', 'slot-40']);
    }

    public function testTheRandomDrawSpreadsThePriantsOverTheEmptySlots(): void
    {
        $slots = $this->slots(1, 5, 20, 30, 40);
        $picked = [];

        for ($seed = 0; $seed < 50; ++$seed) {
            $picked[] = $this->picker($seed)->pick($slots, null, [])?->id;
        }

        self::assertCount(5, array_unique($picked));
    }

    public function testAnExcludedSlotIsNotDrawnAgain(): void
    {
        self::assertSame('slot-9', $this->picker()->pick($this->slots(9, 11), 10, ['slot-11'])?->id);

        for ($seed = 0; $seed < 20; ++$seed) {
            self::assertSame('slot-30', $this->picker($seed)->pick($this->slots(20, 30), null, ['slot-20'])?->id);
        }
    }

    public function testTheExclusionsAreDroppedWhenNoOtherSlotIsLeft(): void
    {
        self::assertSame('slot-20', $this->picker()->pick($this->slots(20), null, ['slot-20'])?->id);
    }

    public function testNoEmptySlotMeansNoSuggestion(): void
    {
        self::assertNull($this->picker()->pick([], 10, []));
    }

    private function picker(int $seed = 1): RandomizerSlotPicker
    {
        return new RandomizerSlotPicker(new Randomizer(new Mt19937($seed)));
    }

    /**
     * @return list<EmptySlotView>
     */
    private function slots(int ...$ranks): array
    {
        return array_map(
            static fn (int $rank): EmptySlotView => new EmptySlotView(
                'slot-'.$rank,
                $rank,
                new \DateTimeImmutable('2099-01-01 19:00:00')->modify(\sprintf('+%d minutes', 30 * ($rank - 1))),
                new \DateTimeImmutable('2099-01-01 19:30:00')->modify(\sprintf('+%d minutes', 30 * ($rank - 1))),
                'label',
                null,
                null,
            ),
            array_values($ranks),
        );
    }
}
