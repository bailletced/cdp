<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\Dto\InscriptionConfirmation;
use App\Chain\Application\Dto\InscriptionDigest;
use App\Chain\Application\Dto\InscriptionDigestSlot;
use App\Chain\Application\Dto\PublicEditionView;
use App\Chain\Application\Dto\SlotTextsEmail;
use App\Chain\Application\Dto\SlotTextsRecipient;
use App\Chain\Application\Port\Notifier;
use App\Chain\Application\Port\SlotTextsDeliveries;
use App\Chain\Application\Query\InscriptionDigestFinder;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Application\Query\SlotTextsRecipientsFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Infrastructure\Symfony\Console\SendSlotTextsCommand;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class SendSlotTextsCommandTest extends TestCase
{
    public function testItSendsOneEmailPerPriantWithTheTextsOfAllTheirSlots(): void
    {
        $marie = $this->recipient('0193ca20-0000-7000-8000-000000000001', 'Marie Dupont', 'marie@bailletced.org');
        $jean = $this->recipient('0193ca20-0000-7000-8000-000000000002', 'Jean Martin', 'jean@bailletced.org');
        $editionId = new EditionId('0193ca20-0000-7000-8000-00000000000a');
        $marieDigest = $this->digest('Marie', ['20h00–20h30', '21h00–21h30']);
        $jeanDigest = $this->digest('Jean', ['22h00–22h30']);
        $notifier = $this->fakeNotifier();

        $tester = new CommandTester($this->command(
            $this->fakeRecipientsFinder([$marie, $jean], $editionId),
            $this->fakeDigestFinder([(string) $marie->priantId->value => $marieDigest, (string) $jean->priantId->value => $jeanDigest]),
            $notifier,
            $this->fakePublicEditionFinder(null),
            $this->fakeDeliveries(),
        ));

        self::assertSame(0, $tester->execute(['--edition' => (string) $editionId]));
        self::assertCount(2, $notifier->slotTextsSent);
        [$first, $second] = $notifier->slotTextsSent;
        self::assertSame('marie@bailletced.org', $first->recipient->value);
        self::assertSame('Marie Dupont', $first->displayName);
        self::assertSame(['20h00–20h30', '21h00–21h30'], array_map(static fn (InscriptionDigestSlot $slot): string => $slot->label, $first->digest->slots));
        self::assertSame('jean@bailletced.org', $second->recipient->value);
        self::assertSame('Jean Martin', $second->displayName);
        self::assertSame(['22h00–22h30'], array_map(static fn (InscriptionDigestSlot $slot): string => $slot->label, $second->digest->slots));
        self::assertStringContainsString('2 e-mails envoyés', $tester->getDisplay());
    }

    public function testItIgnoresAPriantWithoutSlotsAndStillSendsTheOthers(): void
    {
        $marie = $this->recipient('0193ca20-0000-7000-8000-000000000001', 'Marie Dupont', 'marie@bailletced.org');
        $priantWithoutSlots = $this->recipient('0193ca20-0000-7000-8000-000000000003', 'Pierre Durand', 'pierre@bailletced.org');
        $editionId = new EditionId('0193ca20-0000-7000-8000-00000000000a');
        $notifier = $this->fakeNotifier();

        $tester = new CommandTester($this->command(
            $this->fakeRecipientsFinder([$marie, $priantWithoutSlots], $editionId),
            $this->fakeDigestFinder([(string) $marie->priantId->value => $this->digest('Marie', ['20h00–20h30'])]),
            $notifier,
            $this->fakePublicEditionFinder(null),
            $this->fakeDeliveries(),
        ));

        self::assertSame(0, $tester->execute(['--edition' => (string) $editionId]));
        self::assertCount(1, $notifier->slotTextsSent);
        self::assertSame('marie@bailletced.org', $notifier->slotTextsSent[0]->recipient->value);
        self::assertStringContainsString('Pierre Durand : aucun créneau, e-mail ignoré', $tester->getDisplay());
        self::assertStringContainsString('1 e-mail envoyé', $tester->getDisplay());
    }

    public function testItSendsTheCurrentEditionWhenNoEditionIsGiven(): void
    {
        $marie = $this->recipient('0193ca20-0000-7000-8000-000000000001', 'Marie Dupont', 'marie@bailletced.org');
        $currentEditionId = new EditionId('0193ca20-0000-7000-8000-00000000000b');
        $notifier = $this->fakeNotifier();

        $tester = new CommandTester($this->command(
            $this->fakeRecipientsFinder([$marie], $currentEditionId),
            $this->fakeDigestFinder([(string) $marie->priantId->value => $this->digest('Marie', ['20h00–20h30'])]),
            $notifier,
            $this->fakePublicEditionFinder($currentEditionId),
            $this->fakeDeliveries(),
        ));

        self::assertSame(0, $tester->execute([]));
        self::assertCount(1, $notifier->slotTextsSent);
    }

    public function testItListsTheRecipientEditionWhenNoneIsOpenAndNoneIsGiven(): void
    {
        $marie = $this->recipient('0193ca20-0000-7000-8000-000000000001', 'Marie Dupont', 'marie@bailletced.org');
        $editionId = new EditionId('0193ca20-0000-7000-8000-00000000000a');
        $notifier = $this->fakeNotifier();

        $tester = new CommandTester($this->command(
            $this->fakeRecipientsFinder([$marie], $editionId),
            $this->fakeDigestFinder([]),
            $notifier,
            $this->fakePublicEditionFinder(null),
            $this->fakeDeliveries(),
        ));

        self::assertSame(1, $tester->execute([]));
        self::assertCount(0, $notifier->slotTextsSent);
        self::assertStringContainsString('Aucune édition ouverte', $tester->getDisplay());
    }

    public function testItRefusesAnInvalidEditionIdentifier(): void
    {
        $marie = $this->recipient('0193ca20-0000-7000-8000-000000000001', 'Marie Dupont', 'marie@bailletced.org');
        $editionId = new EditionId('0193ca20-0000-7000-8000-00000000000a');
        $notifier = $this->fakeNotifier();

        $tester = new CommandTester($this->command(
            $this->fakeRecipientsFinder([$marie], $editionId),
            $this->fakeDigestFinder([]),
            $notifier,
            $this->fakePublicEditionFinder(null),
            $this->fakeDeliveries(),
        ));

        self::assertSame(1, $tester->execute(['--edition' => 'not-an-uuid']));
        self::assertCount(0, $notifier->slotTextsSent);
        self::assertStringContainsString('Identifiant d’édition invalide', $tester->getDisplay());
    }

    public function testASecondRunSendsNothingNewToTheSamePriants(): void
    {
        $marie = $this->recipient('0193ca20-0000-7000-8000-000000000001', 'Marie Dupont', 'marie@bailletced.org');
        $jean = $this->recipient('0193ca20-0000-7000-8000-000000000002', 'Jean Martin', 'jean@bailletced.org');
        $editionId = new EditionId('0193ca20-0000-7000-8000-00000000000a');
        $digests = $this->fakeDigestFinder([
            (string) $marie->priantId->value => $this->digest('Marie', ['20h00–20h30']),
            (string) $jean->priantId->value => $this->digest('Jean', ['21h00–21h30']),
        ]);
        $notifier = $this->fakeNotifier();
        $command = $this->command(
            $this->fakeRecipientsFinder([$marie, $jean], $editionId),
            $digests,
            $notifier,
            $this->fakePublicEditionFinder(null),
            $deliveries = $this->fakeDeliveries(),
        );

        $firstRun = new CommandTester($command);
        self::assertSame(0, $firstRun->execute(['--edition' => (string) $editionId]));
        self::assertCount(2, $notifier->slotTextsSent);

        $secondRun = new CommandTester($command);
        self::assertSame(0, $secondRun->execute(['--edition' => (string) $editionId]));
        self::assertCount(2, $notifier->slotTextsSent);
        self::assertStringContainsString('2 e-mails déjà envoyés', $secondRun->getDisplay());
        self::assertStringContainsString('0 e-mail envoyé', $secondRun->getDisplay());
    }

    private function command(
        SlotTextsRecipientsFinder $recipients,
        InscriptionDigestFinder $digests,
        Notifier $notifier,
        PublicEditionViewFinder $currentEdition,
        SlotTextsDeliveries $deliveries,
    ): SendSlotTextsCommand {
        return new SendSlotTextsCommand($recipients, $digests, $notifier, $currentEdition, $deliveries, $this->fakeClock());
    }

    private function fakeClock(): ClockInterface
    {
        return new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-11 10:00:00 UTC');
            }
        };
    }

    private function recipient(string $priantId, string $displayName, string $email): SlotTextsRecipient
    {
        return new SlotTextsRecipient(new PriantId($priantId), $displayName, new Email($email));
    }

    /**
     * @param list<string> $labels
     */
    private function digest(string $name, array $labels): InscriptionDigest
    {
        return new InscriptionDigest(
            '24 heures de prière',
            new \DateTimeImmutable('2026-11-29 19:00:00 UTC'),
            array_map(
                static fn (string $label): InscriptionDigestSlot => new InscriptionDigestSlot(
                    '0193ca20-0000-7000-8000-0000000000f1',
                    '0193ca20-0000-7000-8000-0000000000f2',
                    1,
                    new \DateTimeImmutable('2026-11-28 19:00:00 UTC'),
                    new \DateTimeImmutable('2026-11-28 19:30:00 UTC'),
                    $label,
                    'samedi 28 novembre',
                    'Que rien ne te trouble',
                    'Thérèse d’Avila',
                    \sprintf('<p>Le texte de %s.</p>', $name),
                ),
                $labels,
            ),
        );
    }

    /**
     * Answers the expected recipients only for the expected edition, and
     * records the edition it was called with.
     */
    private function fakeRecipientsFinder(array $recipients, EditionId $forEdition): SlotTextsRecipientsFinder
    {
        return new class($recipients, $forEdition) implements SlotTextsRecipientsFinder {
            /** @var list<EditionId> */
            public array $calledWith = [];

            /**
             * @param list<SlotTextsRecipient> $recipients
             */
            public function __construct(
                private readonly array $recipients,
                private readonly EditionId $forEdition,
            ) {
            }

            public function findByEdition(EditionId $editionId): array
            {
                $this->calledWith[] = $editionId;

                return $editionId->equals($this->forEdition) ? $this->recipients : [];
            }
        };
    }

    /**
     * @param array<string, InscriptionDigest> $digests Indexed by priant id
     */
    private function fakeDigestFinder(array $digests): InscriptionDigestFinder
    {
        return new class($digests) implements InscriptionDigestFinder {
            /** @param array<string, InscriptionDigest> $digests */
            public function __construct(private readonly array $digests)
            {
            }

            public function findByPriantAndEdition(PriantId $priantId, EditionId $editionId): ?InscriptionDigest
            {
                return $this->digests[(string) $priantId->value] ?? null;
            }
        };
    }

    private function fakeNotifier(): Notifier
    {
        return new class implements Notifier {
            /** @var list<SlotTextsEmail> */
            public array $slotTextsSent = [];

            public function confirmInscriptions(InscriptionConfirmation $confirmation): void
            {
            }

            public function sendSlotTexts(SlotTextsEmail $email): void
            {
                $this->slotTextsSent[] = $email;
            }
        };
    }

    private function fakeDeliveries(): SlotTextsDeliveries
    {
        return new class implements SlotTextsDeliveries {
            /** @var array<string, \DateTimeImmutable> Deliveries, indexed by "priant|edition" */
            public array $delivered = [];

            public function alreadySentTo(PriantId $priantId, EditionId $editionId): bool
            {
                return isset($this->delivered[$this->key($priantId, $editionId)]);
            }

            public function recordDelivery(PriantId $priantId, EditionId $editionId, \DateTimeImmutable $at): void
            {
                $this->delivered[$this->key($priantId, $editionId)] = $at;
            }

            private function key(PriantId $priantId, EditionId $editionId): string
            {
                return \sprintf('%s|%s', $priantId->value, $editionId->value);
            }
        };
    }

    private function fakePublicEditionFinder(?EditionId $current): PublicEditionViewFinder
    {
        return new class($current) implements PublicEditionViewFinder {
            public function __construct(private readonly ?EditionId $current)
            {
            }

            public function current(): ?PublicEditionView
            {
                if (null === $this->current) {
                    return null;
                }

                $id = (string) $this->current;

                return new PublicEditionView(
                    $id,
                    '24 heures de prière',
                    '',
                    '2026-11-28T19:00:00+00:00',
                    '2026-11-21T19:00:00+00:00',
                    '',
                    '',
                    'scheduled',
                );
            }
        };
    }
}
