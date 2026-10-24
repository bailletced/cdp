<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Calendar;

use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Presentation\Component;
use Eluceo\iCal\Presentation\Component\Property;
use Eluceo\iCal\Presentation\Component\Property\Value\IntegerValue;
use Eluceo\iCal\Presentation\Factory\EventFactory;

/**
 * eluceo/ical 2 has no SEQUENCE: this factory adds it to each VEVENT, so a
 * re-imported event replaces the previous one (US-11.2).
 */
final class SequencedEventFactory extends EventFactory
{
    /**
     * @param array<string, int> $sequences Keyed by UID
     */
    public function __construct(
        private readonly array $sequences,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function createComponent(Event $event): Component
    {
        $component = parent::createComponent($event);
        $sequence = $this->sequences[(string) $event->getUniqueIdentifier()] ?? null;

        return null === $sequence ? $component : $component->withProperty(new Property('SEQUENCE', new IntegerValue($sequence)));
    }
}
