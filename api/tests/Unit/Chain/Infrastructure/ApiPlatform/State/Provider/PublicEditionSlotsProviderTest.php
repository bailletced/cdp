<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Get;
use App\Chain\Application\Dto\PlanningParticipantView;
use App\Chain\Application\Dto\PlanningSlotView;
use App\Chain\Application\Dto\PlanningView;
use App\Chain\Application\Query\PlanningViewFinder;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Policy\VisibilityPolicy;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\PublicEditionSlotsProvider;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Shared\Domain\Security\Viewer;
use App\Tests\Unit\Shared\Application\Port\Fixture\FakeCurrentViewer;
use PHPUnit\Framework\TestCase;

/**
 * F10-T08: the provider masks the participants to null unless the
 * VisibilityPolicy lets the viewer see the names, and marks the viewer's
 * own slots.
 */
final class PublicEditionSlotsProviderTest extends TestCase
{
    private EditionId $edition;

    private SlotId $mariesSlot;

    private SlotId $emptySlot;

    private Inscription $mariesInscription;

    private InMemoryInscriptionRepository $inscriptions;

    protected function setUp(): void
    {
        $this->edition = new EditionId();
        $this->mariesSlot = new SlotId();
        $this->emptySlot = new SlotId();
        $this->mariesInscription = new Inscription($this->edition, $this->mariesSlot, new PriantId());
        $this->inscriptions = new InMemoryInscriptionRepository();
        $this->inscriptions->add($this->mariesInscription);
    }

    public function testAVisitorGetsTheParticipantsMaskedToNull(): void
    {
        $slots = $this->provide(Viewer::visitor());

        foreach ($slots as $slot) {
            self::assertArrayHasKey('participants', $slot);
            self::assertNull($slot['participants']);
            self::assertFalse($slot['mine']);
        }
    }

    public function testTheDeviceThatRegisteredSeesTheNamesAndItsSlot(): void
    {
        $slots = $this->provide(new Viewer(null, false, [(string) $this->mariesInscription->getId()], false));

        self::assertSame([['name' => 'Marie Dupont', 'prayed' => false]], $slots[0]['participants']);
        self::assertTrue($slots[0]['mine']);
        self::assertSame([], $slots[1]['participants']);
        self::assertFalse($slots[1]['mine']);
    }

    public function testAnOrganizerSeesTheNamesWithoutAnySlotOfItsOwn(): void
    {
        $slots = $this->provide(new Viewer(null, true, [], true));

        self::assertSame([['name' => 'Marie Dupont', 'prayed' => false]], $slots[0]['participants']);
        self::assertFalse($slots[0]['mine']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function provide(Viewer $viewer): array
    {
        $view = new PlanningView((string) $this->edition, [
            $this->slot($this->mariesSlot, 1, [new PlanningParticipantView((string) $this->mariesInscription->getId(), (string) $this->mariesInscription->getPriantId(), 'Marie Dupont', false)]),
            $this->slot($this->emptySlot, 2, []),
        ]);
        $finder = new class($view) implements PlanningViewFinder {
            public function __construct(private readonly PlanningView $view)
            {
            }

            #[\Override]
            public function findByEdition(EditionId $editionId): PlanningView
            {
                return $this->view;
            }
        };
        $provider = new PublicEditionSlotsProvider($finder, new FakeCurrentViewer($viewer), new VisibilityPolicy($this->inscriptions));

        return array_values($provider->provide(new Get(), ['id' => (string) $this->edition])->slots ?? []);
    }

    /**
     * @param list<PlanningParticipantView> $participants
     */
    private function slot(SlotId $id, int $rank, array $participants): PlanningSlotView
    {
        return new PlanningSlotView((string) $id, $rank, '2026-11-28T19:00:00+00:00', '2026-11-28T19:30:00+00:00', '20h00–20h30', 'evening', 'future', null, null, \count($participants), [] !== $participants, $participants);
    }
}
