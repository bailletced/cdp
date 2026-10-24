<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Policy;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Policy\VisibilityPolicy;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Shared\Domain\Security\Viewer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The rights matrix (specs-fonctionnelles §4) replayed profile by profile:
 * Marie registered in the edition, Jean registered in another edition only.
 */
final class VisibilityPolicyTest extends TestCase
{
    private EditionId $edition;

    private PriantId $marie;

    private PriantId $jean;

    private Inscription $mariesInscription;

    private Inscription $jeansInscription;

    private VisibilityPolicy $policy;

    protected function setUp(): void
    {
        $this->edition = new EditionId();
        $this->marie = new PriantId();
        $this->jean = new PriantId();
        $this->mariesInscription = new Inscription($this->edition, new SlotId(), $this->marie);
        $this->jeansInscription = new Inscription(new EditionId(), new SlotId(), $this->jean);

        $inscriptions = new InMemoryInscriptionRepository();
        $inscriptions->add($this->mariesInscription);
        $inscriptions->add($this->jeansInscription);
        $this->policy = new VisibilityPolicy($inscriptions);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function profiles(): iterable
    {
        // RG-PL-4, RG-LV-2, RG-IN-6: names, pointages and intentions are for the
        // inscrits of the edition and the organizers
        yield 'visitor' => ['visitor', false];
        yield 'pass of the device that registered in the edition' => ['pass', true];
        yield 'pass of a device that registered in another edition' => ['pass of another edition', false];
        yield 'verified priant registered in the edition' => ['verified priant', true];
        yield 'verified priant registered elsewhere only' => ['other priant', false];
        yield 'verified account with no priant' => ['account without priant', false];
        yield 'organizer' => ['organizer', true];
    }

    #[DataProvider('profiles')]
    public function testOnlyTheInscritsOfTheEditionAndTheOrganizersSeeTheNames(string $profile, bool $expected): void
    {
        self::assertSame($expected, $this->policy->canSeeParticipantNames($this->viewer($profile), $this->edition));
    }

    #[DataProvider('profiles')]
    public function testOnlyTheInscritsOfTheEditionAndTheOrganizersSeeTheIntentions(string $profile, bool $expected): void
    {
        self::assertSame($expected, $this->policy->canSeeIntentions($this->viewer($profile), $this->edition));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function textReaders(): iterable
    {
        // RG-TX-1: the full text of a slot is for the priants registered on it
        // (past editions included) and the organizers
        yield 'visitor' => ['visitor', false];
        yield 'pass of the device that registered on the slot' => ['pass', true];
        yield 'pass of a device that registered on another slot' => ['pass of another edition', false];
        yield 'verified priant registered on the slot' => ['verified priant', true];
        yield 'verified priant registered elsewhere' => ['other priant', false];
        yield 'organizer' => ['organizer', true];
    }

    #[DataProvider('textReaders')]
    public function testTheTextOfASlotIsReadByItsPriantsAndTheOrganizers(string $profile, bool $expected): void
    {
        self::assertSame($expected, $this->policy->canReadText($this->viewer($profile), $this->mariesInscription->getSlotId()));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function actors(): iterable
    {
        // RG-IN-6: acting on an inscription takes the session of its priant
        yield 'visitor' => ['visitor', false];
        yield 'pass alone: read yes, act no' => ['pass', false];
        yield 'verified priant, owner' => ['verified priant', true];
        yield 'verified priant, someone else' => ['other priant', false];
        yield 'organizer' => ['organizer', false];
    }

    #[DataProvider('actors')]
    public function testOnlyTheVerifiedOwnerActsOnAnInscription(string $profile, bool $expected): void
    {
        self::assertSame($expected, $this->policy->canActOn($this->viewer($profile), $this->mariesInscription));
    }

    public function testAPriantIdIsOnlyTrustedOnceVerified(): void
    {
        $unverified = new Viewer($this->marie->value->toRfc4122(), false, [], false);

        self::assertFalse($this->policy->canSeeParticipantNames($unverified, $this->edition));
        self::assertFalse($this->policy->canReadText($unverified, $this->mariesInscription->getSlotId()));
        self::assertFalse($this->policy->canActOn($unverified, $this->mariesInscription));
    }

    private function viewer(string $profile): Viewer
    {
        return match ($profile) {
            'visitor' => Viewer::visitor(),
            'pass' => new Viewer(null, false, [(string) $this->mariesInscription->getId()], false),
            'pass of another edition' => new Viewer(null, false, [(string) $this->jeansInscription->getId()], false),
            'verified priant' => new Viewer($this->marie->value->toRfc4122(), true, [], false),
            'other priant' => new Viewer($this->jean->value->toRfc4122(), true, [], false),
            'account without priant' => new Viewer(null, true, [], false),
            'organizer' => new Viewer(null, true, [], true),
            default => throw new \LogicException($profile),
        };
    }
}
