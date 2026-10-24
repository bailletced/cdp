<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Calendar;

use App\Chain\Application\Dto\InscriptionDigestSlot;
use App\Chain\Application\Port\CalendarFile;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Alarm;
use Eluceo\iCal\Domain\ValueObject\Alarm\DisplayAction;
use Eluceo\iCal\Domain\ValueObject\Alarm\RelativeTrigger;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * CalendarFile backed by eluceo/ical 2 (T28, specs-techniques §10.2): one
 * VEVENT per inscription, UID inscription-{uuid}@<domain>, SEQUENCE = the
 * inscription version, DTSTART/DTEND in UTC, an alarm 10 minutes before.
 */
final readonly class EluceoCalendarFile implements CalendarFile
{
    private const string PRODUCT = '-//Communion de prière Notre-Dame du Mont-Carmel//24 heures de prière//FR';

    public function __construct(
        #[Autowire('%env(DEFAULT_URI)%')]
        private string $appUrl,
    ) {
    }

    #[\Override]
    public function forSlots(string $editionTitle, array $slots): string
    {
        $domain = parse_url($this->appUrl, \PHP_URL_HOST) ?? 'localhost';
        $sequences = [];
        $events = [];

        foreach ($slots as $slot) {
            $uid = \sprintf('inscription-%s@%s', $slot->inscriptionId, $domain);
            $sequences[$uid] = $slot->inscriptionVersion;
            $events[] = new Event(new UniqueIdentifier($uid))
                ->setSummary(\sprintf('%s — %s', $editionTitle, $slot->label))
                ->setDescription($this->description($slot))
                ->setOccurrence(new TimeSpan(new DateTime($this->utc($slot->startsAt), true), new DateTime($this->utc($slot->endsAt), true)))
                ->addAlarm(new Alarm(
                    new DisplayAction(\sprintf('%s — %s', $editionTitle, $slot->label)),
                    new RelativeTrigger($this->tenMinutesBefore())->withRelationToStart(),
                ));
        }

        $calendar = new Calendar($events)->setProductIdentifier(self::PRODUCT);

        return (string) new CalendarFactory(new SequencedEventFactory($sequences))->createCalendar($calendar);
    }

    private function description(InscriptionDigestSlot $slot): string
    {
        $text = null === $slot->textTitle
            ? 'Le texte de ce créneau sera bientôt disponible.'
            : \sprintf('Texte : %s%s', $slot->textTitle, null === $slot->textSource ? '' : ' ('.$slot->textSource.')');

        return \sprintf("%s\nVotre espace : %s/my-space", $text, rtrim($this->appUrl, '/'));
    }

    private function tenMinutesBefore(): \DateInterval
    {
        $interval = new \DateInterval('PT10M');
        $interval->invert = 1;

        return $interval;
    }

    private function utc(\DateTimeImmutable $instant): \DateTimeImmutable
    {
        return $instant->setTimezone(new \DateTimeZone('UTC'));
    }
}
