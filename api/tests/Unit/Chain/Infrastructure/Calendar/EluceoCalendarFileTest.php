<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Calendar;

use App\Chain\Application\Dto\InscriptionDigestSlot;
use App\Chain\Infrastructure\Calendar\EluceoCalendarFile;
use PHPUnit\Framework\TestCase;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;

final class EluceoCalendarFileTest extends TestCase
{
    private const string MARIE = '0193a1b2-0000-7000-8000-000000000001';

    private const string MARTIN = '0193a1b2-0000-7000-8000-000000000002';

    public function testEachInscriptionBecomesAnEventWithAnAlarmTenMinutesBefore(): void
    {
        $calendar = $this->read([
            $this->slot(self::MARIE, 1, '2026-11-28 19:00', '2026-11-28 19:30', '20h00–20h30', 'Que rien ne te trouble'),
            $this->slot(self::MARTIN, 3, '2026-11-28 19:30', '2026-11-28 20:00', '20h30–21h00', null),
        ]);

        $events = $calendar->select('VEVENT');
        self::assertCount(2, $events);
        [$first, $second] = array_values($events);
        \assert($first instanceof VEvent && $second instanceof VEvent);

        self::assertSame('inscription-'.self::MARIE.'@communion.test', (string) $first->UID);
        self::assertSame('1', (string) $first->SEQUENCE);
        self::assertSame('3', (string) $second->SEQUENCE);
        // Absolute instants: the agenda converts them (Claire, in Montréal)
        self::assertSame('20261128T190000Z', (string) $first->DTSTART);
        self::assertSame('20261128T193000Z', (string) $first->DTEND);
        self::assertSame('24 heures de prière — 20h00–20h30', (string) $first->SUMMARY);
        self::assertStringContainsString('Que rien ne te trouble', (string) $first->DESCRIPTION);
        self::assertStringContainsString('https://communion.test/my-space', (string) $first->DESCRIPTION);
        self::assertStringContainsString('Le texte de ce créneau sera bientôt disponible', (string) $second->DESCRIPTION);

        $alarm = $first->VALARM;
        self::assertNotNull($alarm);
        self::assertSame('-PT10M', (string) $alarm->TRIGGER);
        self::assertSame('DISPLAY', (string) $alarm->ACTION);
    }

    public function testTheFileIsAValidICalendar(): void
    {
        $calendar = $this->read([$this->slot(self::MARIE, 1, '2026-11-28 19:00', '2026-11-28 19:30', '20h00–20h30', 'Psaume 22')]);

        self::assertSame([], array_map(static fn (array $issue): string => $issue['message'], $calendar->validate()));
    }

    public function testTheSecondTwoOClockOfTheDaylightSavingNightKeepsItsAbsoluteInstant(): void
    {
        // 25/10/2026: « 2h00 » happens twice in Paris; the winter one is 01:00 UTC
        $calendar = $this->read([$this->slot(self::MARIE, 1, '2026-10-25 01:00', '2026-10-25 01:30', '2h00–2h30 (heure d’hiver)', null)]);

        $event = $calendar->VEVENT;
        self::assertNotNull($event);
        self::assertSame('20261025T010000Z', (string) $event->DTSTART);
        self::assertSame('20261025T013000Z', (string) $event->DTEND);
        self::assertSame('24 heures de prière — 2h00–2h30 (heure d’hiver)', (string) $event->SUMMARY);
    }

    /**
     * @param list<InscriptionDigestSlot> $slots
     */
    private function read(array $slots): VCalendar
    {
        $ics = new EluceoCalendarFile('https://communion.test')->forSlots('24 heures de prière', $slots);
        self::assertStringEndsWith("\r\n", $ics);

        $calendar = Reader::read($ics);
        \assert($calendar instanceof VCalendar);

        return $calendar;
    }

    private function slot(string $inscriptionId, int $version, string $startsAtUtc, string $endsAtUtc, string $label, ?string $textTitle): InscriptionDigestSlot
    {
        $utc = new \DateTimeZone('UTC');

        return new InscriptionDigestSlot($inscriptionId, $version, new \DateTimeImmutable($startsAtUtc, $utc), new \DateTimeImmutable($endsAtUtc, $utc), $label, $textTitle, null === $textTitle ? null : 'Source', null === $textTitle ? null : '<p>Texte.</p>', null);
    }
}
