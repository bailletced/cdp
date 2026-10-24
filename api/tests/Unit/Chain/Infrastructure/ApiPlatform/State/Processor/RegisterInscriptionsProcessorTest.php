<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\Query\EmptySlotsFinder;
use App\Chain\Application\RegisterInscriptions;
use App\Chain\Application\RegisterInscriptionsResult;
use App\Chain\Application\SuggestEmptySlot;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Exception\AlreadyRegisteredException;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\ApiPlatform\Payload\RegisterInscriptionsPayload;
use App\Chain\Infrastructure\ApiPlatform\Resource\InscriptionResource;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\RegisterInscriptionsProcessor;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryPriantRepository;
use App\Chain\Infrastructure\Random\RandomizerSlotPicker;
use App\Shared\Domain\Security\Viewer;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\Shared\Application\Command\Fixture\FakeCommandBus;
use App\Tests\Unit\Shared\Application\Command\Fixture\ThrowingCommandBus;
use App\Tests\Unit\Shared\Application\Port\Fixture\DevicePassIssuerSpy;
use App\Tests\Unit\Shared\Application\Port\Fixture\FakeCurrentViewer;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class RegisterInscriptionsProcessorTest extends TestCase
{
    private const string EDITION_ENDS_AT = '2026-11-29 19:00:00';

    private InMemoryPriantRepository $priantRepository;

    private DevicePassIssuerSpy $devicePassIssuer;

    protected function setUp(): void
    {
        $this->priantRepository = new InMemoryPriantRepository();
        $this->devicePassIssuer = new DevicePassIssuerSpy();
    }

    public function testTheDeviceIsGrantedAPassCoveringTheCreatedInscriptions(): void
    {
        $result = self::successfulResult();
        $processor = $this->processor(new FakeCommandBus($result), Viewer::visitor());

        $processor->process(self::validPayload(), new Post(), [], []);

        self::assertSame([[
            'inscriptionIds' => [
                $result->inscriptions[0]->getId()->value->toRfc4122(),
                $result->inscriptions[1]->getId()->value->toRfc4122(),
            ],
            'editionEndsAt' => $result->editionEndsAt,
        ]], $this->devicePassIssuer->granted);
    }

    public function testItDispatchesRegisterInscriptionsAndReturnsTheCreatedInscriptions(): void
    {
        $result = self::successfulResult();
        $commandBus = new FakeCommandBus($result);
        $processor = $this->processor($commandBus, Viewer::visitor());

        $slot1 = new SlotId();
        $slot2 = new SlotId();

        $resource = $processor->process(new RegisterInscriptionsPayload(
            slotIds: [$slot1->value->toRfc4122(), $slot2->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
        ), new Post(), [], []);

        $command = $commandBus->dispatched;
        self::assertInstanceOf(RegisterInscriptions::class, $command);
        self::assertEquals([$slot1, $slot2], $command->slotIds);
        self::assertSame('Famille Martin', $command->displayName->value);
        self::assertSame('famille.martin@bailletced.org', $command->email->value);
        self::assertTrue($command->consent);

        self::assertInstanceOf(InscriptionResource::class, $resource);
        self::assertSame($result->inscriptions[0]->getId()->value->toRfc4122(), $resource->id);
        self::assertSame($result->priantId->value->toRfc4122(), $resource->priantId);
        self::assertSame('Famille Martin', $resource->displayName);
        self::assertCount(2, $resource->inscriptions);
        self::assertSame($result->inscriptions[0]->getSlotId()->value->toRfc4122(), $resource->inscriptions[0]['slotId']);
        self::assertSame($result->inscriptions[0]->getEditionId()->value->toRfc4122(), $resource->inscriptions[0]['editionId']);
        self::assertSame($result->inscriptions[0]->getId()->value->toRfc4122(), $resource->inscriptions[0]['id']);
    }

    public function testAFilledHoneypotReturnsAnEmptySuccessWithoutDispatching(): void
    {
        $commandBus = new FakeCommandBus(self::successfulResult());
        $processor = $this->processor($commandBus, Viewer::visitor());

        $resource = $processor->process(new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
            website: 'http://spam.example',
        ), new Post(), [], []);

        // §6.7: identical-looking success, but nothing is done.
        self::assertNull($commandBus->dispatched);
        self::assertNotNull($resource->id);
        self::assertNull($resource->priantId);
        self::assertSame([], $resource->inscriptions);
        self::assertSame([], $this->devicePassIssuer->granted);
    }

    public function testMissingSlotIdsThrowsViolationOnSlotIds(): void
    {
        $this->assertViolationOn('slotIds', new RegisterInscriptionsPayload(
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testAnInvalidSlotIdThrowsViolationOnSlotIds(): void
    {
        $this->assertViolationOn('slotIds', new RegisterInscriptionsPayload(
            slotIds: ['not-a-uuid'],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testMissingDisplayNameThrowsViolationOnDisplayName(): void
    {
        $this->assertViolationOn('displayName', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testTooLongDisplayNameThrowsViolationOnDisplayName(): void
    {
        $this->assertViolationOn('displayName', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: str_repeat('a', 81),
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testMissingEmailThrowsViolationOnEmail(): void
    {
        $this->assertViolationOn('email', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            consent: true,
        ));
    }

    public function testAnInvalidEmailThrowsViolationOnEmail(): void
    {
        $this->assertViolationOn('email', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'not-an-email',
            consent: true,
        ));
    }

    public function testAVisitorWithoutConsentThrowsViolationOnConsent(): void
    {
        $this->assertViolationOn('consent', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
        ), Viewer::visitor());
    }

    public function testAVisitorWithConsentFalseThrowsViolationOnConsent(): void
    {
        $this->assertViolationOn('consent', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: false,
        ), Viewer::visitor());
    }

    public function testAnAuthenticatedPriantWhoNeverConsentedThrowsViolationOnConsent(): void
    {
        $priant = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $this->priantRepository->add($priant);
        $viewer = new Viewer($priant->getId()->value->toRfc4122(), true, [], false);

        $this->assertViolationOn('consent', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: false,
        ), $viewer);
    }

    public function testAnAuthenticatedPriantWhoAlreadyConsentedDoesNotNeedToConsentAgain(): void
    {
        $priant = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $priant->giveConsent(new \DateTimeImmutable('2026-10-15 08:00:00', new \DateTimeZone('UTC')));
        $this->priantRepository->add($priant);
        $viewer = new Viewer($priant->getId()->value->toRfc4122(), true, [], false);

        $commandBus = new FakeCommandBus(self::successfulResult());
        $processor = $this->processor($commandBus, $viewer);

        // RG-IN-5: in session, the checkbox is hidden once the consent is given.
        $processor->process(new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
        ), new Post(), [], []);

        $command = $commandBus->dispatched;
        self::assertInstanceOf(RegisterInscriptions::class, $command);
        self::assertFalse($command->consent);
    }

    public function testAUniqueViolationOnTheSlotPriantIndexBecomesA409(): void
    {
        // RG-IN-3: a concurrent request (double click) registered the same
        // (slot, priant) first — the unique index rejected our flush.
        $processor = new RegisterInscriptionsProcessor(
            new ThrowingCommandBus(self::uniqueViolationOn('inscription_slot_priant_unique')),
            $this->priantRepository,
            new FakeCurrentViewer(Viewer::visitor()),
            $this->devicePassIssuer,
            self::noSuggestion(),
        );

        $this->expectException(AlreadyRegisteredException::class);
        $this->expectExceptionMessage('Vous êtes déjà inscrit sur ce créneau.');

        $processor->process(self::validPayload(), new Post(), [], []);
    }

    public function testAnotherUniqueViolationBubblesUp(): void
    {
        // The priant email race (RG-IN-2) is not an inscription conflict.
        $processor = new RegisterInscriptionsProcessor(
            new ThrowingCommandBus(self::uniqueViolationOn('priant_email_unique')),
            $this->priantRepository,
            new FakeCurrentViewer(Viewer::visitor()),
            $this->devicePassIssuer,
            self::noSuggestion(),
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $processor->process(self::validPayload(), new Post(), [], []);
    }

    private function processor(FakeCommandBus $commandBus, Viewer $viewer): RegisterInscriptionsProcessor
    {
        return new RegisterInscriptionsProcessor(
            $commandBus,
            $this->priantRepository,
            new FakeCurrentViewer($viewer),
            $this->devicePassIssuer,
            self::noSuggestion(),
        );
    }

    /**
     * The suggestion (F13-T04) is covered by the acceptance tests: here, the
     * chain is complete.
     */
    private static function noSuggestion(): SuggestEmptySlot
    {
        return new SuggestEmptySlot(
            new class implements EmptySlotsFinder {
                public function findByEdition(EditionId $editionId): array
                {
                    return [];
                }
            },
            new InMemoryEditionRepository(),
            new RandomizerSlotPicker(new Randomizer(new Mt19937(1))),
        );
    }

    private static function validPayload(): RegisterInscriptionsPayload
    {
        return new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
        );
    }

    private static function uniqueViolationOn(string $constraint): UniqueConstraintViolationException
    {
        $driverException = new class(\sprintf('duplicate key value violates unique constraint "%s"', $constraint)) extends \Exception implements DriverException {
            public function getSQLState(): ?string
            {
                return '23505';
            }
        };

        return new UniqueConstraintViolationException($driverException, null);
    }

    private function assertViolationOn(string $propertyPath, RegisterInscriptionsPayload $payload, ?Viewer $viewer = null): void
    {
        $processor = $this->processor(new FakeCommandBus(self::successfulResult()), $viewer ?? Viewer::visitor());

        try {
            $processor->process($payload, new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame($propertyPath, $violations[0]->getPropertyPath());
        }
    }

    private static function successfulResult(): RegisterInscriptionsResult
    {
        $priantId = new PriantId();
        $editionId = new EditionId();
        $slot1 = new SlotId();
        $slot2 = new SlotId();

        return new RegisterInscriptionsResult(
            $priantId,
            new DisplayName('Famille Martin'),
            [
                new Inscription($editionId, $slot1, $priantId),
                new Inscription($editionId, $slot2, $priantId),
            ],
            new \DateTimeImmutable(self::EDITION_ENDS_AT, new \DateTimeZone('UTC')),
        );
    }
}
