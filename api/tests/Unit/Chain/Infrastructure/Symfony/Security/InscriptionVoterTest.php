<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Symfony\Security;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Policy\VisibilityPolicy;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Chain\Infrastructure\Symfony\Security\InscriptionVoter;
use App\Shared\Domain\Security\Viewer;
use App\Tests\Unit\Shared\Application\Port\Fixture\FakeCurrentViewer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class InscriptionVoterTest extends TestCase
{
    private PriantId $owner;

    private Inscription $inscription;

    protected function setUp(): void
    {
        $this->owner = new PriantId();
        $this->inscription = new Inscription(new EditionId(), new SlotId(), $this->owner);
    }

    public function testTheVerifiedOwnerIsGranted(): void
    {
        $viewer = new Viewer($this->owner->value->toRfc4122(), true, [], false);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($viewer, 'INSCRIPTION_ACT', $this->inscription));
    }

    public function testAPassAloneIsDenied(): void
    {
        $viewer = new Viewer(null, false, [(string) $this->inscription->getId()], false);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($viewer, 'INSCRIPTION_ACT', $this->inscription));
    }

    public function testAnotherPriantIsDenied(): void
    {
        $viewer = new Viewer(new PriantId()->value->toRfc4122(), true, [], false);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($viewer, 'INSCRIPTION_ACT', $this->inscription));
    }

    public function testItAbstainsOnOtherAttributesAndSubjects(): void
    {
        $viewer = new Viewer($this->owner->value->toRfc4122(), true, [], false);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($viewer, 'EDITION_EDIT', $this->inscription));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($viewer, 'INSCRIPTION_ACT', new \stdClass()));
    }

    private function vote(Viewer $viewer, string $attribute, mixed $subject): int
    {
        $voter = new InscriptionVoter(new FakeCurrentViewer($viewer), new VisibilityPolicy(new InMemoryInscriptionRepository()));

        return $voter->vote(new NullToken(), $subject, [$attribute]);
    }
}
